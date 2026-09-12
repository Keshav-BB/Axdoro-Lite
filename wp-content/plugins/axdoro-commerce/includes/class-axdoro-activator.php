<?php
/**
 * AXDORO Activator and Custom Order Statuses
 *
 * @package AXDORO_Commerce
 */

defined( 'ABSPATH' ) || exit;

class AXDORO_Activator {

    public static function activate() {
        // Set default settings if not existing
        if ( ! get_option( 'axdoro_settings' ) ) {
            update_option( 'axdoro_settings', array(
                'whatsapp_number'        => '918807304713',
                'merchant_vpa'           => 'axdoro@upi',
                'merchant_name'          => 'AXDORO Clothing',
                'payment_mode'           => 'whatsapp_upi', // 'razorpay', 'cashfree', 'whatsapp_upi'
                'webhook_secret'         => wp_generate_password( 32, false ),
                'enable_chatbot'         => 'yes',
                'chatbot_name'           => 'AXDORO Style & Support',
                'shiprocket_mode'        => 'manual',
                'debug_mode'             => 'yes'
            ) );
        }

        self::register_order_statuses();
        flush_rewrite_rules();
    }

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_order_statuses' ) );
        add_filter( 'wc_order_statuses', array( __CLASS__, 'add_custom_statuses_to_order_statuses' ) );
        add_filter( 'woocommerce_reports_order_statuses', array( __CLASS__, 'add_custom_statuses_to_reports' ) );
        add_action( 'admin_head', array( __CLASS__, 'add_status_colors_to_admin' ) );
    }

    /**
     * Register Custom WooCommerce Order Statuses
     */
    public static function register_order_statuses() {
        $statuses = array(
            'wc-awaiting-payment' => array(
                'label'                     => _x( 'Awaiting Payment', 'Order status', 'axdoro-commerce' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                /* translators: %s: count */
                'label_count'               => _n_noop( 'Awaiting Payment <span class="count">(%s)</span>', 'Awaiting Payment <span class="count">(%s)</span>', 'axdoro-commerce' ),
            ),
            'wc-payment-verified' => array(
                'label'                     => _x( 'Payment Verified', 'Order status', 'axdoro-commerce' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                /* translators: %s: count */
                'label_count'               => _n_noop( 'Payment Verified <span class="count">(%s)</span>', 'Payment Verified <span class="count">(%s)</span>', 'axdoro-commerce' ),
            ),
            'wc-packed' => array(
                'label'                     => _x( 'Packed', 'Order status', 'axdoro-commerce' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                /* translators: %s: count */
                'label_count'               => _n_noop( 'Packed <span class="count">(%s)</span>', 'Packed <span class="count">(%s)</span>', 'axdoro-commerce' ),
            ),
            'wc-shipped' => array(
                'label'                     => _x( 'Shipped', 'Order status', 'axdoro-commerce' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                /* translators: %s: count */
                'label_count'               => _n_noop( 'Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>', 'axdoro-commerce' ),
            ),
            'wc-out-for-delivery' => array(
                'label'                     => _x( 'Out for Delivery', 'Order status', 'axdoro-commerce' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                /* translators: %s: count */
                'label_count'               => _n_noop( 'Out for Delivery <span class="count">(%s)</span>', 'Out for Delivery <span class="count">(%s)</span>', 'axdoro-commerce' ),
            ),
        );

        foreach ( $statuses as $status_key => $status_args ) {
            register_post_status( $status_key, $status_args );
        }
    }

    /**
     * Add Custom Statuses to WooCommerce dropdowns
     */
    public static function add_custom_statuses_to_order_statuses( $order_statuses ) {
        $new_statuses = array();

        foreach ( $order_statuses as $key => $status ) {
            $new_statuses[ $key ] = $status;

            if ( 'wc-pending' === $key ) {
                $new_statuses['wc-awaiting-payment'] = _x( 'Awaiting Payment', 'Order status', 'axdoro-commerce' );
                $new_statuses['wc-payment-verified'] = _x( 'Payment Verified', 'Order status', 'axdoro-commerce' );
            }

            if ( 'wc-processing' === $key ) {
                $new_statuses['wc-packed']           = _x( 'Packed', 'Order status', 'axdoro-commerce' );
                $new_statuses['wc-shipped']          = _x( 'Shipped', 'Order status', 'axdoro-commerce' );
                $new_statuses['wc-out-for-delivery'] = _x( 'Out for Delivery', 'Order status', 'axdoro-commerce' );
            }
        }

        return $new_statuses;
    }

    /**
     * Include Custom Statuses in Sales Reports
     */
    public static function add_custom_statuses_to_reports( $statuses ) {
        $statuses[] = 'payment-verified';
        $statuses[] = 'packed';
        $statuses[] = 'shipped';
        $statuses[] = 'out-for-delivery';
        return $statuses;
    }

    /**
     * Custom Badge Colors in WP Admin Orders Table
     */
    public static function add_status_colors_to_admin() {
        ?>
        <style>
            .order-status.status-awaiting-payment { background: #fdf2e9; color: #b95000; border: 1px solid #f8cbad; }
            .order-status.status-payment-verified { background: #e6f4ea; color: #137333; border: 1px solid #ceead6; }
            .order-status.status-packed           { background: #e8f0fe; color: #1a73e8; border: 1px solid #d2e3fc; }
            .order-status.status-shipped          { background: #f3e8fd; color: #8430ce; border: 1px solid #e9d2fd; }
            .order-status.status-out-for-delivery { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
        </style>
        <?php
    }
}

AXDORO_Activator::init();
