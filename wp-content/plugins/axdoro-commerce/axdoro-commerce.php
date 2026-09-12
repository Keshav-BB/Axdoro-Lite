<?php
/**
 * Plugin Name: AXDORO Commerce Core
 * Plugin URI:  https://axdoro.com/
 * Description: Production-ready commerce extensions for AXDORO Clothing: WhatsApp payment handoff, payment webhook verification, manual UTR fallback, Shiprocket tracking sync, and lean AI/FAQ chatbot.
 * Version:     1.0.0
 * Author:      PeoplePoint Consultants
 * Author URI:  https://peoplepoint.in/
 * Text Domain: axdoro-commerce
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 * WC tested up to:      9.2
 *
 * License: Proprietary / Commercial
 */

defined( 'ABSPATH' ) || exit;

// Define plugin constants
define( 'AXDORO_COMMERCE_VERSION', '1.0.0' );
define( 'AXDORO_COMMERCE_FILE', __FILE__ );
define( 'AXDORO_COMMERCE_PATH', plugin_dir_path( __FILE__ ) );
define( 'AXDORO_COMMERCE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declare HPOS (High-Performance Order Storage) compatibility
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

/**
 * Core Plugin Singleton Class
 */
final class AXDORO_Commerce {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes() {
        require_once AXDORO_COMMERCE_PATH . 'includes/class-axdoro-logger.php';
        require_once AXDORO_COMMERCE_PATH . 'includes/class-axdoro-activator.php';
        require_once AXDORO_COMMERCE_PATH . 'includes/class-order-meta.php';
        require_once AXDORO_COMMERCE_PATH . 'includes/class-whatsapp-handoff.php';
        require_once AXDORO_COMMERCE_PATH . 'includes/class-payment-webhook.php';
        require_once AXDORO_COMMERCE_PATH . 'includes/class-tracking.php';
        require_once AXDORO_COMMERCE_PATH . 'includes/class-admin-settings.php';
        require_once AXDORO_COMMERCE_PATH . 'includes/class-ai-faq-chatbot.php';
    }

    private function init_hooks() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'axdoro-commerce-frontend',
            AXDORO_COMMERCE_URL . 'assets/css/axdoro-commerce.css',
            array(),
            AXDORO_COMMERCE_VERSION
        );

        wp_enqueue_script(
            'axdoro-tracking-script',
            AXDORO_COMMERCE_URL . 'assets/js/axdoro-tracking.js',
            array( 'jquery' ),
            AXDORO_COMMERCE_VERSION,
            true
        );

        wp_localize_script( 'axdoro-tracking-script', 'axdoroTracking', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'axdoro_tracking_nonce' )
        ) );
    }

    public function enqueue_admin_assets( $hook ) {
        if ( false !== strpos( $hook, 'axdoro' ) || false !== strpos( $hook, 'shop_order' ) ) {
            wp_enqueue_style(
                'axdoro-commerce-admin',
                AXDORO_COMMERCE_URL . 'assets/css/axdoro-commerce.css',
                array(),
                AXDORO_COMMERCE_VERSION
            );
        }
    }
}

/**
 * Plugin activation and deactivation
 */
require_once AXDORO_COMMERCE_PATH . 'includes/class-axdoro-activator.php';
register_activation_hook( __FILE__, array( 'AXDORO_Activator', 'activate' ) );

/**
 * Bootstrap on plugins_loaded
 */
add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php esc_html_e( 'AXDORO Commerce Core requires WooCommerce to be installed and active.', 'axdoro-commerce' ); ?></p>
            </div>
            <?php
        } );
        return;
    }

    AXDORO_Commerce::instance();
} );
