<?php
/**
 * AXDORO Order Metadata and Admin Metaboxes
 *
 * @package AXDORO_Commerce
 */

defined( 'ABSPATH' ) || exit;

class AXDORO_Order_Meta {

    public static function init() {
        // Support both HPOS and legacy CPT order screens
        add_action( 'add_meta_boxes', array( __CLASS__, 'register_metabox' ) );
        add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'save_metabox_data' ), 10, 2 );
        add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'display_tracking_on_order_received' ), 10, 1 );
    }

    public static function register_metabox() {
        $screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) &&
                  wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
                  ? wc_get_page_screen_id( 'shop-order' )
                  : 'shop_order';

        add_meta_box(
            'axdoro_order_controls',
            __( 'AXDORO Payment & Fulfillment Details', 'axdoro-commerce' ),
            array( __CLASS__, 'render_metabox' ),
            $screen,
            'side',
            'high'
        );
    }

    public static function render_metabox( $post_or_order_object ) {
        $order = ( $post_or_order_object instanceof WC_Order ) ? $post_or_order_object : wc_get_order( $post_or_order_object->ID );
        if ( ! $order ) {
            return;
        }

        wp_nonce_field( 'axdoro_order_meta_nonce_action', 'axdoro_order_meta_nonce' );

        $payment_mode   = $order->get_meta( '_axdoro_payment_mode' );
        $transaction_id = $order->get_meta( '_axdoro_transaction_id' );
        $customer_utr   = $order->get_meta( '_axdoro_customer_utr' );
        $verified_by    = $order->get_meta( '_axdoro_payment_verified_by' );
        $verified_at    = $order->get_meta( '_axdoro_payment_verified_at' );
        $awb_number     = $order->get_meta( '_axdoro_awb_number' );
        $courier        = $order->get_meta( '_axdoro_courier_partner' );
        $tracking_url   = $order->get_meta( '_axdoro_tracking_url' );

        ?>
        <div class="axdoro-admin-meta-box" style="font-size: 13px; line-height: 1.5;">
            <p>
                <strong><?php esc_html_e( 'Payment Gateway / Channel:', 'axdoro-commerce' ); ?></strong><br>
                <select name="axdoro_payment_mode" style="width: 100%; margin-top: 4px;">
                    <option value="whatsapp_upi" <?php selected( $payment_mode, 'whatsapp_upi' ); ?>><?php esc_html_e( 'WhatsApp Direct UPI', 'axdoro-commerce' ); ?></option>
                    <option value="razorpay" <?php selected( $payment_mode, 'razorpay' ); ?>><?php esc_html_e( 'Razorpay Payment Link', 'axdoro-commerce' ); ?></option>
                    <option value="cashfree" <?php selected( $payment_mode, 'cashfree' ); ?>><?php esc_html_e( 'Cashfree Payment Link', 'axdoro-commerce' ); ?></option>
                    <option value="other" <?php selected( $payment_mode, 'other' ); ?>><?php esc_html_e( 'Other / Direct Bank Transfer', 'axdoro-commerce' ); ?></option>
                </select>
            </p>

            <p>
                <strong><?php esc_html_e( 'Gateway Payment ID:', 'axdoro-commerce' ); ?></strong><br>
                <input type="text" name="axdoro_transaction_id" value="<?php echo esc_attr( $transaction_id ); ?>" style="width: 100%;" placeholder="e.g. pay_Nabc12345XYZ" />
            </p>

            <p>
                <strong><?php esc_html_e( 'Customer Submitted UTR / Ref:', 'axdoro-commerce' ); ?></strong><br>
                <input type="text" name="axdoro_customer_utr" value="<?php echo esc_attr( $customer_utr ); ?>" style="width: 100%; font-family: monospace; background: #fffde7;" placeholder="12-digit UPI UTR" />
            </p>

            <?php if ( ! empty( $verified_by ) ) : ?>
                <div style="background: #e6f4ea; border-left: 4px solid #137333; padding: 6px 10px; margin-bottom: 12px; font-size: 11px;">
                    <?php
                    /* translators: 1: admin name, 2: formatted date/time */
                    printf( esc_html__( 'Verified by %1$s on %2$s', 'axdoro-commerce' ), esc_html( $verified_by ), esc_html( $verified_at ) );
                    ?>
                </div>
            <?php else : ?>
                <p>
                    <label>
                        <input type="checkbox" name="axdoro_mark_verified" value="1" />
                        <strong><?php esc_html_e( 'Mark Payment Verified by Admin', 'axdoro-commerce' ); ?></strong>
                    </label>
                </p>
            <?php endif; ?>

            <hr style="border: 0; border-top: 1px solid #ddd; margin: 12px 0;">

            <p>
                <strong><?php esc_html_e( 'Shiprocket / Courier Partner:', 'axdoro-commerce' ); ?></strong><br>
                <input type="text" name="axdoro_courier_partner" value="<?php echo esc_attr( $courier ); ?>" style="width: 100%;" placeholder="e.g. Delhivery Surface / BlueDart" />
            </p>

            <p>
                <strong><?php esc_html_e( 'AWB / Tracking Number:', 'axdoro-commerce' ); ?></strong><br>
                <input type="text" name="axdoro_awb_number" value="<?php echo esc_attr( $awb_number ); ?>" style="width: 100%; font-family: monospace;" placeholder="e.g. 142387129031" />
            </p>

            <p>
                <strong><?php esc_html_e( 'Direct Courier Tracking URL:', 'axdoro-commerce' ); ?></strong><br>
                <input type="url" name="axdoro_tracking_url" value="<?php echo esc_attr( $tracking_url ); ?>" style="width: 100%;" placeholder="https://shiprocket.co/tracking/..." />
            </p>
        </div>
        <?php
    }

    public static function save_metabox_data( $order_id, $post_or_order = null ) {
        if ( ! isset( $_POST['axdoro_order_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['axdoro_order_meta_nonce'] ), 'axdoro_order_meta_nonce_action' ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        if ( isset( $_POST['axdoro_payment_mode'] ) ) {
            $order->update_meta_data( '_axdoro_payment_mode', sanitize_text_field( wp_unslash( $_POST['axdoro_payment_mode'] ) ) );
        }

        if ( isset( $_POST['axdoro_transaction_id'] ) ) {
            $order->update_meta_data( '_axdoro_transaction_id', sanitize_text_field( wp_unslash( $_POST['axdoro_transaction_id'] ) ) );
        }

        if ( isset( $_POST['axdoro_customer_utr'] ) ) {
            $order->update_meta_data( '_axdoro_customer_utr', sanitize_text_field( wp_unslash( $_POST['axdoro_customer_utr'] ) ) );
        }

        if ( isset( $_POST['axdoro_courier_partner'] ) ) {
            $order->update_meta_data( '_axdoro_courier_partner', sanitize_text_field( wp_unslash( $_POST['axdoro_courier_partner'] ) ) );
        }

        if ( isset( $_POST['axdoro_awb_number'] ) ) {
            $order->update_meta_data( '_axdoro_awb_number', sanitize_text_field( wp_unslash( $_POST['axdoro_awb_number'] ) ) );
        }

        if ( isset( $_POST['axdoro_tracking_url'] ) ) {
            $order->update_meta_data( '_axdoro_tracking_url', esc_url_raw( wp_unslash( $_POST['axdoro_tracking_url'] ) ) );
        }

        // Handle manual admin payment verification
        if ( ! empty( $_POST['axdoro_mark_verified'] ) && empty( $order->get_meta( '_axdoro_payment_verified_by' ) ) ) {
            $current_user = wp_get_current_user();
            $user_display = $current_user ? $current_user->display_name : 'Admin';
            $now          = current_time( 'mysql' );

            $order->update_meta_data( '_axdoro_payment_verified_by', $user_display );
            $order->update_meta_data( '_axdoro_payment_verified_at', $now );

            $order->update_status( 'payment-verified', sprintf( __( 'Payment manually verified by %s. Status changed to Payment Verified.', 'axdoro-commerce' ), $user_display ) );
        }

        $order->save();
    }

    /**
     * Display AWB and Courier on Customer Thankyou / View Order page
     */
    public static function display_tracking_on_order_received( $order ) {
        if ( ! $order ) {
            return;
        }

        $awb     = $order->get_meta( '_axdoro_awb_number' );
        $courier = $order->get_meta( '_axdoro_courier_partner' );
        $url     = $order->get_meta( '_axdoro_tracking_url' );

        if ( empty( $awb ) ) {
            return;
        }
        ?>
        <div class="axdoro-order-tracking-card" style="margin-top: 24px; padding: 18px 22px; background: #fdfaf6; border: 1px solid #e9e2d5; border-radius: 8px;">
            <h3 style="margin-top: 0; margin-bottom: 8px; font-size: 16px; color: #111111;">
                🚚 <?php esc_html_e( 'Shipment & Tracking Details', 'axdoro-commerce' ); ?>
            </h3>
            <p style="margin: 4px 0; color: #555;">
                <strong><?php esc_html_e( 'Courier Partner:', 'axdoro-commerce' ); ?></strong> <?php echo esc_html( $courier ? $courier : 'Standard Surface' ); ?>
            </p>
            <p style="margin: 4px 0; color: #555;">
                <strong><?php esc_html_e( 'AWB Tracking Number:', 'axdoro-commerce' ); ?></strong>
                <span style="font-family: monospace; font-weight: bold;"><?php echo esc_html( $awb ); ?></span>
            </p>
            <?php if ( ! empty( $url ) ) : ?>
                <p style="margin: 12px 0 0 0;">
                    <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" class="button" style="background: #111111; color: #ffffff; border-radius: 4px; padding: 8px 16px; text-decoration: none; display: inline-block;">
                        <?php esc_html_e( 'Track on Courier Portal →', 'axdoro-commerce' ); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
}

AXDORO_Order_Meta::init();
