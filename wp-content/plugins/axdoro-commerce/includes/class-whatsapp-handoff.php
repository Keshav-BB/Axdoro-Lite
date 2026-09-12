<?php
/**
 * AXDORO WhatsApp Payment Handoff and UTR Fallback Flow
 *
 * @package AXDORO_Commerce
 */

defined( 'ABSPATH' ) || exit;

class AXDORO_WhatsApp_Handoff {

    public static function init() {
        // Set initial status to awaiting-payment upon order creation
        add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'set_order_initial_status' ), 10, 3 );

        // Render WhatsApp handoff and UTR submission on Thank You page
        add_action( 'woocommerce_thankyou', array( __CLASS__, 'render_whatsapp_payment_screen' ), 5 );

        // Handle frontend AJAX UTR submission
        add_action( 'wp_ajax_axdoro_submit_utr', array( __CLASS__, 'handle_utr_submission' ) );
        add_action( 'wp_ajax_nopriv_axdoro_submit_utr', array( __CLASS__, 'handle_utr_submission' ) );
    }

    /**
     * Set initial status to awaiting-payment
     */
    public static function set_order_initial_status( $order_id, $posted_data, $order ) {
        if ( ! $order ) {
            return;
        }

        // Set status to awaiting-payment
        if ( $order->has_status( array( 'pending', 'on-hold' ) ) ) {
            $order->update_status( 'awaiting-payment', __( 'Order placed on website. Customer routed to WhatsApp/UPI payment handoff.', 'axdoro-commerce' ) );
        }
    }

    /**
     * Build WhatsApp URL with formatted order details & UPI intent link
     */
    public static function get_whatsapp_handoff_data( $order ) {
        $settings     = get_option( 'axdoro_settings', array() );
        $phone        = ! empty( $settings['whatsapp_number'] ) ? preg_replace( '/[^0-9]/', '', $settings['whatsapp_number'] ) : '918807304713';
        $vpa          = ! empty( $settings['merchant_vpa'] ) ? $settings['merchant_vpa'] : 'axdoro@upi';
        $merchant_name= ! empty( $settings['merchant_name'] ) ? $settings['merchant_name'] : 'AXDORO Clothing';

        $order_id     = $order->get_id();
        $order_number = $order->get_order_number();
        $total        = number_format( (float) $order->get_total(), 2, '.', '' );
        $currency     = $order->get_currency();
        $customer_name= $order->get_formatted_billing_full_name();

        // Summarize items
        $items_summary = array();
        foreach ( $order->get_items() as $item ) {
            $items_summary[] = sprintf( '• %dx %s', $item->get_quantity(), $item->get_name() );
        }
        $items_text = implode( "\n", $items_summary );

        // Generate standard NPCI UPI Intent Link
        $upi_intent = sprintf(
            'upi://pay?pa=%s&pn=%s&am=%s&cu=INR&tn=Order-%s',
            rawurlencode( $vpa ),
            rawurlencode( $merchant_name ),
            rawurlencode( $total ),
            rawurlencode( $order_number )
        );

        // Prefilled WhatsApp message
        $message = "Hello AXDORO!\n\n";
        $message .= "I have placed an order on the AXDORO website.\n";
        $message .= "🛒 Order ID: #" . $order_number . "\n";
        $message .= "👤 Name: " . $customer_name . "\n";
        $message .= "💰 Total Amount: ₹" . $total . "\n\n";
        $message .= "📦 Items Ordered:\n" . $items_text . "\n\n";
        $message .= "💳 UPI Payment Link:\n" . $upi_intent . "\n\n";
        $message .= "Kindly confirm once my payment is received.";

        $whatsapp_url = 'https://wa.me/' . $phone . '?text=' . rawurlencode( $message );

        return array(
            'whatsapp_url' => $whatsapp_url,
            'upi_intent'   => $upi_intent,
            'order_number' => $order_number,
            'total'        => $total,
            'vpa'          => $vpa,
            'phone'        => $phone
        );
    }

    /**
     * Render the payment handoff box on the WooCommerce thankyou page
     */
    public static function render_whatsapp_payment_screen( $order_id ) {
        if ( ! $order_id ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // If order is already verified, display confirmation badge
        if ( $order->has_status( array( 'payment-verified', 'processing', 'completed' ) ) ) {
            ?>
            <div class="axdoro-payment-success-card">
                <div class="axdoro-payment-icon">✅</div>
                <h3><?php esc_html_e( 'Payment Successfully Verified!', 'axdoro-commerce' ); ?></h3>
                <p><?php esc_html_e( 'Your order is confirmed and is now being prepared for fulfillment.', 'axdoro-commerce' ); ?></p>
            </div>
            <?php
            return;
        }

        $data = self::get_whatsapp_handoff_data( $order );
        $existing_utr = $order->get_meta( '_axdoro_customer_utr' );
        ?>
        <div class="axdoro-whatsapp-handoff-container">
            <div class="axdoro-handoff-header">
                <span class="axdoro-badge"><?php esc_html_e( 'Step 2 of 2: Payment', 'axdoro-commerce' ); ?></span>
                <h2><?php esc_html_e( 'Complete Your Payment via WhatsApp / UPI', 'axdoro-commerce' ); ?></h2>
                <p>
                    <?php
                    /* translators: 1: order number, 2: total amount */
                    printf( esc_html__( 'Order #%1$s is created. Pay ₹%2$s via UPI to confirm your order.', 'axdoro-commerce' ), esc_html( $data['order_number'] ), esc_html( $data['total'] ) );
                    ?>
                </p>
            </div>

            <div class="axdoro-action-grid">
                <!-- Option A: Direct WhatsApp Button -->
                <div class="axdoro-action-card axdoro-action-whatsapp">
                    <div class="axdoro-card-icon">💬</div>
                    <h3><?php esc_html_e( 'Pay via WhatsApp', 'axdoro-commerce' ); ?></h3>
                    <p><?php esc_html_e( 'Opens WhatsApp with pre-filled Order ID, amount, and instant payment link.', 'axdoro-commerce' ); ?></p>
                    <a href="<?php echo esc_url( $data['whatsapp_url'] ); ?>" target="_blank" rel="noopener noreferrer" class="axdoro-btn axdoro-btn-whatsapp">
                        <span>📱 <?php esc_html_e( 'Open WhatsApp to Pay', 'axdoro-commerce' ); ?></span>
                    </a>
                </div>

                <!-- Option B: Direct UPI Intent (Mobile) -->
                <div class="axdoro-action-card axdoro-action-upi">
                    <div class="axdoro-card-icon">⚡</div>
                    <h3><?php esc_html_e( 'Direct UPI App', 'axdoro-commerce' ); ?></h3>
                    <p><?php esc_html_e( 'Pay directly using GPay, PhonePe, Paytm, or BHIM on your device.', 'axdoro-commerce' ); ?></p>
                    <a href="<?php echo esc_url( $data['upi_intent'] ); ?>" class="axdoro-btn axdoro-btn-upi">
                        <span>📲 <?php esc_html_e( 'Pay via UPI App (₹', 'axdoro-commerce' ); echo esc_html( $data['total'] ); ?>)</span>
                    </a>
                    <div class="axdoro-vpa-copy">
                        <small><?php esc_html_e( 'UPI ID / VPA: ', 'axdoro-commerce' ); ?><strong><?php echo esc_html( $data['vpa'] ); ?></strong></small>
                    </div>
                </div>
            </div>

            <!-- Manual UTR Submission Fallback -->
            <div class="axdoro-utr-fallback-card">
                <h3><?php esc_html_e( 'Already Paid? Submit 12-Digit UTR for Instant Verification', 'axdoro-commerce' ); ?></h3>
                <p><?php esc_html_e( 'If you transferred via UPI, enter your 12-digit Bank Reference Number / UTR below so our admin can verify your payment immediately.', 'axdoro-commerce' ); ?></p>

                <?php if ( ! empty( $existing_utr ) ) : ?>
                    <div class="axdoro-utr-submitted-notice">
                        <?php
                        /* translators: %s: submitted UTR number */
                        printf( esc_html__( '✓ UTR Reference %s submitted. Our team is verifying it.', 'axdoro-commerce' ), '<code>' . esc_html( $existing_utr ) . '</code>' );
                        ?>
                    </div>
                <?php else : ?>
                    <form id="axdoro-utr-form" class="axdoro-utr-form">
                        <input type="hidden" name="order_id" value="<?php echo esc_attr( $order_id ); ?>" />
                        <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'axdoro_utr_nonce_' . $order_id ) ); ?>" />
                        <div class="axdoro-form-row">
                            <input type="text" name="utr_number" id="axdoro-utr-input" placeholder="<?php esc_attr_e( 'Enter 12-digit UPI UTR / Reference No.', 'axdoro-commerce' ); ?>" maxlength="24" required />
                            <button type="submit" id="axdoro-utr-btn" class="axdoro-btn axdoro-btn-secondary">
                                <?php esc_html_e( 'Submit UTR', 'axdoro-commerce' ); ?>
                            </button>
                        </div>
                        <div id="axdoro-utr-status" class="axdoro-status-msg" style="display: none;"></div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var form = document.getElementById('axdoro-utr-form');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var btn = document.getElementById('axdoro-utr-btn');
                var status = document.getElementById('axdoro-utr-status');
                var utrInput = document.getElementById('axdoro-utr-input');
                var utr = utrInput.value.trim();

                if (utr.length < 6) {
                    status.style.display = 'block';
                    status.className = 'axdoro-status-msg axdoro-error';
                    status.innerText = 'Please enter a valid UTR / Reference number.';
                    return;
                }

                btn.disabled = true;
                btn.innerText = 'Submitting...';

                var formData = new FormData(form);
                formData.append('action', 'axdoro_submit_utr');

                fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    status.style.display = 'block';
                    if (data.success) {
                        status.className = 'axdoro-status-msg axdoro-success';
                        status.innerText = data.data.message || 'UTR received! Admin will verify shortly.';
                        form.style.display = 'none';
                    } else {
                        status.className = 'axdoro-status-msg axdoro-error';
                        status.innerText = data.data.message || 'Failed to submit UTR. Please contact support.';
                        btn.disabled = false;
                        btn.innerText = 'Submit UTR';
                    }
                })
                .catch(function() {
                    status.style.display = 'block';
                    status.className = 'axdoro-status-msg axdoro-error';
                    status.innerText = 'Network error. Please WhatsApp us your UTR directly.';
                    btn.disabled = false;
                    btn.innerText = 'Submit UTR';
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler for customer UTR submission
     */
    public static function handle_utr_submission() {
        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $nonce    = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
        $utr      = isset( $_POST['utr_number'] ) ? sanitize_text_field( wp_unslash( $_POST['utr_number'] ) ) : '';

        if ( ! $order_id || ! wp_verify_nonce( $nonce, 'axdoro_utr_nonce_' . $order_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Security verification failed. Please refresh.', 'axdoro-commerce' ) ) );
        }

        if ( empty( $utr ) || strlen( $utr ) < 6 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid UTR reference number.', 'axdoro-commerce' ) ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => __( 'Order not found.', 'axdoro-commerce' ) ) );
        }

        $order->update_meta_data( '_axdoro_customer_utr', $utr );
        $order->update_meta_data( '_axdoro_utr_submitted_at', current_time( 'mysql' ) );

        /* translators: %s: submitted UTR number */
        $order->add_order_note( sprintf( __( 'Customer submitted payment UTR: %s via order confirmation screen. Awaiting admin verification.', 'axdoro-commerce' ), $utr ) );
        $order->save();

        AXDORO_Logger::info( sprintf( 'Customer submitted UTR %s for Order #%d', $utr, $order_id ) );

        wp_send_json_success( array( 'message' => __( 'Thank you! UTR submitted. Our team will verify and dispatch your order.', 'axdoro-commerce' ) ) );
    }
}

AXDORO_WhatsApp_Handoff::init();
