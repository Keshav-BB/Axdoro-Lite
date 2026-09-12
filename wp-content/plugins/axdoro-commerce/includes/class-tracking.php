<?php
/**
 * AXDORO Shiprocket & Order Tracking Shortcode
 *
 * Implements [axdoro_track_order] for customer-facing milestone tracking.
 *
 * @package AXDORO_Commerce
 */

defined( 'ABSPATH' ) || exit;

class AXDORO_Tracking {

    public static function init() {
        add_shortcode( 'axdoro_track_order', array( __CLASS__, 'render_tracking_shortcode' ) );
        add_action( 'wp_ajax_axdoro_ajax_track_order', array( __CLASS__, 'handle_ajax_tracking' ) );
        add_action( 'wp_ajax_nopriv_axdoro_ajax_track_order', array( __CLASS__, 'handle_ajax_tracking' ) );
    }

    public static function render_tracking_shortcode() {
        ob_start();
        ?>
        <div class="axdoro-tracking-wrapper">
            <div class="axdoro-tracking-search-box">
                <h2><?php esc_html_e( 'Track Your AXDORO Order', 'axdoro-commerce' ); ?></h2>
                <p><?php esc_html_e( 'Enter your Order ID and the Email or Phone number provided during checkout.', 'axdoro-commerce' ); ?></p>

                <form id="axdoro-track-form" class="axdoro-track-form">
                    <div class="axdoro-track-field-group">
                        <div class="axdoro-track-field">
                            <label for="track_order_id"><?php esc_html_e( 'Order ID / Number', 'axdoro-commerce' ); ?> *</label>
                            <input type="text" id="track_order_id" name="order_id" placeholder="e.g. 1024" required />
                        </div>
                        <div class="axdoro-track-field">
                            <label for="track_billing_contact"><?php esc_html_e( 'Billing Email or Phone', 'axdoro-commerce' ); ?> *</label>
                            <input type="text" id="track_billing_contact" name="contact" placeholder="Email or 10-digit mobile" required />
                        </div>
                    </div>
                    <button type="submit" id="axdoro-track-submit-btn" class="axdoro-btn axdoro-btn-primary">
                        <?php esc_html_e( 'Track Order Status →', 'axdoro-commerce' ); ?>
                    </button>
                    <div id="axdoro-track-error" class="axdoro-status-msg axdoro-error" style="display: none; margin-top: 12px;"></div>
                </form>
            </div>

            <div id="axdoro-tracking-results" class="axdoro-tracking-results" style="display: none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler to fetch order tracking milestones
     */
    public static function handle_ajax_tracking() {
        check_ajax_referer( 'axdoro_tracking_nonce', 'nonce' );

        $raw_order_id = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
        $contact      = isset( $_POST['contact'] ) ? sanitize_text_field( wp_unslash( $_POST['contact'] ) ) : '';

        // Extract numeric ID if prefixed like #1024 or AXD-1024
        preg_match( '/\d+/', $raw_order_id, $matches );
        $order_id = ! empty( $matches[0] ) ? absint( $matches[0] ) : 0;

        if ( ! $order_id || empty( $contact ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter both Order ID and Email/Phone.', 'axdoro-commerce' ) ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => __( 'No order found with this ID. Please double-check your Order Number.', 'axdoro-commerce' ) ) );
        }

        // Privacy verification: check billing email or billing phone
        $billing_email = strtolower( trim( $order->get_billing_email() ) );
        $billing_phone = preg_replace( '/[^0-9]/', '', $order->get_billing_phone() );
        $input_contact = trim( $contact );
        $input_phone   = preg_replace( '/[^0-9]/', '', $input_contact );

        $verified = false;
        if ( is_email( $input_contact ) && strtolower( $input_contact ) === $billing_email ) {
            $verified = true;
        } elseif ( ! empty( $input_phone ) && ( $input_phone === $billing_phone || substr( $billing_phone, -10 ) === substr( $input_phone, -10 ) ) ) {
            $verified = true;
        }

        if ( ! $verified ) {
            wp_send_json_error( array( 'message' => __( 'The email or phone number does not match this order record.', 'axdoro-commerce' ) ) );
        }

        $status       = $order->get_status();
        $date_created = $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '';
        $total        = $order->get_formatted_order_total();
        $item_count   = $order->get_item_count();
        $awb          = $order->get_meta( '_axdoro_awb_number' );
        $courier      = $order->get_meta( '_axdoro_courier_partner' );
        $tracking_url = $order->get_meta( '_axdoro_tracking_url' );

        // Map status to milestone step (0 to 5)
        $milestones = array(
            'awaiting-payment' => array( 'step' => 0, 'label' => __( 'Awaiting Payment', 'axdoro-commerce' ) ),
            'pending'          => array( 'step' => 0, 'label' => __( 'Awaiting Payment', 'axdoro-commerce' ) ),
            'payment-verified' => array( 'step' => 1, 'label' => __( 'Payment Verified', 'axdoro-commerce' ) ),
            'on-hold'          => array( 'step' => 1, 'label' => __( 'Order Confirmed', 'axdoro-commerce' ) ),
            'processing'       => array( 'step' => 2, 'label' => __( 'Processing & QC', 'axdoro-commerce' ) ),
            'packed'           => array( 'step' => 3, 'label' => __( 'Packed at Warehouse', 'axdoro-commerce' ) ),
            'shipped'          => array( 'step' => 4, 'label' => __( 'Shipped / In Transit', 'axdoro-commerce' ) ),
            'out-for-delivery' => array( 'step' => 4, 'label' => __( 'Out for Delivery', 'axdoro-commerce' ) ),
            'completed'        => array( 'step' => 5, 'label' => __( 'Delivered', 'axdoro-commerce' ) ),
            'cancelled'        => array( 'step' => -1, 'label' => __( 'Cancelled', 'axdoro-commerce' ) ),
            'failed'           => array( 'step' => -1, 'label' => __( 'Payment Failed', 'axdoro-commerce' ) ),
        );

        $current_milestone = isset( $milestones[ $status ] ) ? $milestones[ $status ] : array( 'step' => 1, 'label' => ucfirst( $status ) );

        ob_start();
        ?>
        <div class="axdoro-order-summary-card">
            <div class="axdoro-summary-header">
                <div>
                    <span class="axdoro-badge axdoro-badge-gold"><?php echo esc_html( $current_milestone['label'] ); ?></span>
                    <h3>Order #<?php echo esc_html( $order->get_order_number() ); ?></h3>
                </div>
                <div class="axdoro-summary-meta">
                    <div>Placed on: <strong><?php echo esc_html( $date_created ); ?></strong></div>
                    <div>Total: <strong><?php echo wp_kses_post( $total ); ?> (<?php echo esc_html( $item_count ); ?> items)</strong></div>
                </div>
            </div>

            <!-- Milestone Progress Bar -->
            <?php if ( $current_milestone['step'] >= 0 ) : ?>
                <div class="axdoro-timeline-container">
                    <div class="axdoro-timeline-step <?php echo $current_milestone['step'] >= 0 ? 'completed' : ''; ?>">
                        <div class="axdoro-step-dot">1</div>
                        <div class="axdoro-step-label"><?php esc_html_e( 'Order Created', 'axdoro-commerce' ); ?></div>
                    </div>
                    <div class="axdoro-timeline-step <?php echo $current_milestone['step'] >= 1 ? 'completed' : ''; ?>">
                        <div class="axdoro-step-dot">2</div>
                        <div class="axdoro-step-label"><?php esc_html_e( 'Payment Verified', 'axdoro-commerce' ); ?></div>
                    </div>
                    <div class="axdoro-timeline-step <?php echo $current_milestone['step'] >= 3 ? 'completed' : ''; ?>">
                        <div class="axdoro-step-dot">3</div>
                        <div class="axdoro-step-label"><?php esc_html_e( 'Packed', 'axdoro-commerce' ); ?></div>
                    </div>
                    <div class="axdoro-timeline-step <?php echo $current_milestone['step'] >= 4 ? 'completed' : ''; ?>">
                        <div class="axdoro-step-dot">4</div>
                        <div class="axdoro-step-label"><?php esc_html_e( 'Shipped', 'axdoro-commerce' ); ?></div>
                    </div>
                    <div class="axdoro-timeline-step <?php echo $current_milestone['step'] >= 5 ? 'completed' : ''; ?>">
                        <div class="axdoro-step-dot">5</div>
                        <div class="axdoro-step-label"><?php esc_html_e( 'Delivered', 'axdoro-commerce' ); ?></div>
                    </div>
                </div>
            <?php else : ?>
                <div class="axdoro-status-msg axdoro-error" style="margin: 16px 0;">
                    <?php
                    /* translators: %s: order status */
                    printf( esc_html__( 'Notice: This order is currently marked as %s.', 'axdoro-commerce' ), esc_html( $current_milestone['label'] ) );
                    ?>
                </div>
            <?php endif; ?>

            <!-- Shipment Details if Shipped -->
            <?php if ( ! empty( $awb ) ) : ?>
                <div class="axdoro-awb-card">
                    <h4>📦 <?php esc_html_e( 'Live Courier Tracking', 'axdoro-commerce' ); ?></h4>
                    <div class="axdoro-awb-meta">
                        <div>Courier: <strong><?php echo esc_html( $courier ? $courier : 'Shiprocket Partner' ); ?></strong></div>
                        <div>AWB Number: <strong style="font-family: monospace;"><?php echo esc_html( $awb ); ?></strong></div>
                    </div>
                    <?php if ( ! empty( $tracking_url ) ) : ?>
                        <a href="<?php echo esc_url( $tracking_url ); ?>" target="_blank" rel="noopener noreferrer" class="axdoro-btn axdoro-btn-secondary" style="margin-top: 10px; display: inline-block;">
                            <?php esc_html_e( 'Open Courier Tracking Portal →', 'axdoro-commerce' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success( array( 'html' => $html ) );
    }
}

AXDORO_Tracking::init();
