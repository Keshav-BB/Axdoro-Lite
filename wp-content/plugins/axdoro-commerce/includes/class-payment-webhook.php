<?php
/**
 * AXDORO Secure Payment Webhook Receiver
 *
 * Handles automated payment notifications from Razorpay / Cashfree.
 *
 * @package AXDORO_Commerce
 */

defined( 'ABSPATH' ) || exit;

class AXDORO_Payment_Webhook {

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_webhook_routes' ) );
    }

    public static function register_webhook_routes() {
        register_rest_route( 'axdoro/v1', '/payment-webhook', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'process_webhook' ),
            'permission_callback' => '__return_true', // Authentication is handled via HMAC signature
        ) );
    }

    /**
     * Process incoming webhook payload
     */
    public static function process_webhook( WP_REST_Request $request ) {
        $raw_body = $request->get_body();
        $headers  = $request->get_headers();
        $settings = get_option( 'axdoro_settings', array() );
        $secret   = ! empty( $settings['webhook_secret'] ) ? trim( $settings['webhook_secret'] ) : '';

        AXDORO_Logger::info( 'Payment webhook received.' );

        if ( empty( $secret ) ) {
            AXDORO_Logger::error( 'Webhook rejected: Webhook secret not configured in AXDORO settings.' );
            return new WP_REST_Response( array( 'error' => 'Webhook secret not configured' ), 500 );
        }

        // 1. Verify HMAC Signature
        $signature_valid = self::verify_signature( $raw_body, $headers, $secret );
        if ( ! $signature_valid ) {
            AXDORO_Logger::error( 'Webhook rejected: Invalid signature.' );
            return new WP_REST_Response( array( 'error' => 'Invalid webhook signature' ), 401 );
        }

        // 2. Parse JSON body
        $data = json_decode( $raw_body, true );
        if ( ! is_array( $data ) ) {
            AXDORO_Logger::error( 'Webhook rejected: Malformed JSON body.' );
            return new WP_REST_Response( array( 'error' => 'Invalid JSON' ), 400 );
        }

        // 3. Extract Payment Details based on Gateway Payload format
        $extracted = self::extract_payload_details( $data );
        if ( empty( $extracted['order_id'] ) || empty( $extracted['payment_id'] ) ) {
            AXDORO_Logger::error( 'Webhook rejected: Missing order_id or payment_id in payload.' );
            return new WP_REST_Response( array( 'error' => 'Incomplete payment details' ), 400 );
        }

        $order_id   = absint( $extracted['order_id'] );
        $payment_id = sanitize_text_field( $extracted['payment_id'] );
        $amount_paid= (float) $extracted['amount'];
        $gateway    = sanitize_text_field( $extracted['gateway'] );

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            AXDORO_Logger::error( sprintf( 'Webhook rejected: Order #%d not found in WooCommerce.', $order_id ) );
            return new WP_REST_Response( array( 'error' => 'Order not found' ), 404 );
        }

        // 3b. Verify event payment status (Ensure payment was actually captured/paid)
        if ( ! $extracted['is_paid'] ) {
            $order->add_order_note( sprintf( 'Payment webhook received from %s, but event status indicates payment was not captured/paid. Payment ID: %s', strtoupper( $gateway ), $payment_id ) );
            return new WP_REST_Response( array( 'success' => true, 'message' => 'Event acknowledged without status transition' ), 200 );
        }

        // 4. Idempotency Check: Prevent duplicate processing
        $existing_tx_id = $order->get_meta( '_axdoro_transaction_id' );
        if ( $existing_tx_id === $payment_id && $order->has_status( array( 'payment-verified', 'processing', 'completed' ) ) ) {
            AXDORO_Logger::info( sprintf( 'Idempotency: Order #%d already processed with Payment ID %s.', $order_id, $payment_id ) );
            return new WP_REST_Response( array( 'success' => true, 'message' => 'Order already processed' ), 200 );
        }

        // 5. Verify Amount
        $order_total = (float) $order->get_total();
        if ( abs( $order_total - $amount_paid ) > 0.05 ) {
            $msg = sprintf(
                'Webhook payment amount mismatch for Order #%d! Expected ₹%0.2f, received ₹%0.2f. Payment ID: %s',
                $order_id,
                $order_total,
                $amount_paid,
                $payment_id
            );
            $order->add_order_note( '⚠️ ' . $msg );
            AXDORO_Logger::error( $msg );
            return new WP_REST_Response( array( 'error' => 'Payment amount mismatch' ), 400 );
        }

        // 6. Update WooCommerce Order
        $order->update_meta_data( '_axdoro_transaction_id', $payment_id );
        $order->update_meta_data( '_axdoro_payment_mode', $gateway );
        $order->update_meta_data( '_axdoro_verified_amount', $amount_paid );
        $order->update_meta_data( '_axdoro_webhook_verified_at', current_time( 'mysql' ) );

        $note = sprintf(
            /* translators: 1: gateway name, 2: payment ID, 3: verified amount */
            __( '✓ Payment verified via webhook. Gateway: %1$s. Payment ID: %2$s. Verified Amount: ₹%3$0.2f.', 'axdoro-commerce' ),
            strtoupper( $gateway ),
            $payment_id,
            $amount_paid
        );
        $order->add_order_note( $note );

        // Transition to payment-verified or processing
        $order->update_status( 'payment-verified', __( 'Payment auto-verified by payment gateway webhook.', 'axdoro-commerce' ) );
        $order->save();

        AXDORO_Logger::info( sprintf( 'Order #%d successfully verified via webhook. Payment ID: %s.', $order_id, $payment_id ) );

        return new WP_REST_Response( array(
            'success'   => true,
            'order_id'  => $order_id,
            'status'    => 'payment_verified',
            'timestamp' => current_time( 'mysql' )
        ), 200 );
    }

    /**
     * Verify HMAC-SHA256 signature across supported headers
     */
    private static function verify_signature( $payload, $headers, $secret ) {
        $expected_signature = hash_hmac( 'sha256', $payload, $secret );

        // 1. Check Razorpay signature header
        if ( isset( $headers['x_razorpay_signature'][0] ) ) {
            return hash_equals( $expected_signature, trim( $headers['x_razorpay_signature'][0] ) );
        }

        // 2. Check Cashfree signature header with timestamp verification
        if ( isset( $headers['x_webhook_signature'][0] ) ) {
            $received_sig = trim( $headers['x_webhook_signature'][0] );
            $timestamp    = isset( $headers['x_webhook_timestamp'][0] ) ? trim( $headers['x_webhook_timestamp'][0] ) : '';

            if ( ! empty( $timestamp ) ) {
                $cf_expected_ts = base64_encode( hash_hmac( 'sha256', $timestamp . $payload, $secret, true ) );
                if ( hash_equals( $cf_expected_ts, $received_sig ) ) {
                    return true;
                }
            }

            // Fallback Cashfree HMAC variations
            $cf_expected = base64_encode( hash_hmac( 'sha256', $payload, $secret, true ) );
            return hash_equals( $cf_expected, $received_sig ) || hash_equals( $expected_signature, $received_sig );
        }

        // 3. Check custom AXDORO signature header
        if ( isset( $headers['x_axdoro_signature'][0] ) ) {
            return hash_equals( $expected_signature, trim( $headers['x_axdoro_signature'][0] ) );
        }

        return false;
    }

    /**
     * Normalize gateway payload into consistent keys
     */
    private static function extract_payload_details( $data ) {
        $extracted = array(
            'order_id'   => null,
            'payment_id' => null,
            'amount'     => 0.0,
            'gateway'    => 'generic',
            'is_paid'    => false
        );

        // Case A: Razorpay Webhook format
        if ( isset( $data['event'] ) && isset( $data['payload']['payment']['entity'] ) ) {
            $event = sanitize_text_field( $data['event'] );
            $p     = $data['payload']['payment']['entity'];

            $extracted['gateway']    = 'razorpay';
            $extracted['payment_id'] = $p['id'];
            // Razorpay amounts are in paise (e.g. 99900 = ₹999.00)
            $extracted['amount']     = (float) ( $p['amount'] / 100 );
            $extracted['is_paid']    = in_array( $event, array( 'order.paid', 'payment.captured' ), true );

            // Order ID can be in notes or description
            if ( ! empty( $p['notes']['woocommerce_order_id'] ) ) {
                $extracted['order_id'] = $p['notes']['woocommerce_order_id'];
            } elseif ( ! empty( $p['notes']['order_id'] ) ) {
                $extracted['order_id'] = $p['notes']['order_id'];
            } elseif ( ! empty( $p['description'] ) && preg_match( '/(?:Order|#)\s*([0-9]+)/i', $p['description'], $matches ) ) {
                $extracted['order_id'] = $matches[1];
            }
        }
        // Case B: Cashfree Webhook format
        elseif ( isset( $data['data']['order']['order_id'] ) && isset( $data['data']['payment'] ) ) {
            $event_type = $data['type'] ?? $data['event'] ?? '';
            $extracted['gateway']    = 'cashfree';
            $extracted['payment_id'] = $data['data']['payment']['payment_id'] ?? $data['data']['payment']['cf_payment_id'] ?? '';
            $extracted['amount']     = (float) ( $data['data']['payment']['payment_amount'] ?? $data['data']['order']['order_amount'] ?? 0 );
            $extracted['is_paid']    = empty( $event_type ) || in_array( $event_type, array( 'PAYMENT_SUCCESS_WEBHOOK', 'ORDER_PAID' ), true );
            
            $cf_order_id = $data['data']['order']['order_id'];
            if ( preg_match( '/(?:AXD-)?([0-9]+)/i', $cf_order_id, $matches ) ) {
                $extracted['order_id'] = $matches[1];
            } else {
                $extracted['order_id'] = $cf_order_id;
            }
        }
        // Case C: Standard AXDORO Generic Webhook format
        elseif ( isset( $data['order_id'] ) && isset( $data['payment_id'] ) ) {
            $extracted['gateway']    = $data['gateway'] ?? 'payment_link';
            $extracted['order_id']   = $data['order_id'];
            $extracted['payment_id'] = $data['payment_id'];
            $extracted['amount']     = (float) ( $data['amount'] ?? 0 );
            $extracted['is_paid']    = true;
        }

        return $extracted;
    }
}

AXDORO_Payment_Webhook::init();
