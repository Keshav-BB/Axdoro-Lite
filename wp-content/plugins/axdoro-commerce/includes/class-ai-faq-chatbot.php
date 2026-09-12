<?php
/**
 * AXDORO Lean AI / FAQ Support Chatbot
 *
 * Implements a lightweight, zero-recurring-cost client-side assistant with instant FAQ matching
 * and seamless WhatsApp human handoff.
 *
 * @package AXDORO_Commerce
 */

defined( 'ABSPATH' ) || exit;

class AXDORO_AI_FAQ_Chatbot {

    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_chatbot_assets' ) );
        add_action( 'wp_footer', array( __CLASS__, 'render_chatbot_html' ) );
    }

    public static function enqueue_chatbot_assets() {
        $settings = get_option( 'axdoro_settings', array() );
        if ( isset( $settings['enable_chatbot'] ) && 'no' === $settings['enable_chatbot'] ) {
            return;
        }

        wp_enqueue_style(
            'axdoro-chatbot-style',
            AXDORO_COMMERCE_URL . 'assets/css/axdoro-chatbot.css',
            array(),
            AXDORO_COMMERCE_VERSION
        );

        wp_enqueue_script(
            'axdoro-chatbot-script',
            AXDORO_COMMERCE_URL . 'assets/js/axdoro-chatbot.js',
            array(),
            AXDORO_COMMERCE_VERSION,
            true
        );

        $phone = ! empty( $settings['whatsapp_number'] ) ? preg_replace( '/[^0-9]/', '', $settings['whatsapp_number'] ) : '918807304713';
        $name  = ! empty( $settings['chatbot_name'] ) ? $settings['chatbot_name'] : 'AXDORO Style & Support';

        wp_localize_script( 'axdoro-chatbot-script', 'axdoroBotData', array(
            'botName'      => $name,
            'whatsappUrl'  => 'https://wa.me/' . $phone . '?text=' . rawurlencode( "Hello AXDORO Support! I need human assistance with my shopping inquiry." ),
            'trackPageUrl' => site_url( '/track-order/' ),
            'shopPageUrl'  => wc_get_page_permalink( 'shop' ),
            'bulkPageUrl'  => site_url( '/custom-bulk-orders/' ),
        ) );
    }

    public static function render_chatbot_html() {
        $settings = get_option( 'axdoro_settings', array() );
        if ( isset( $settings['enable_chatbot'] ) && 'no' === $settings['enable_chatbot'] ) {
            return;
        }

        $bot_name = ! empty( $settings['chatbot_name'] ) ? $settings['chatbot_name'] : 'AXDORO Style & Support';
        ?>
        <div id="axdoro-bot-container" class="axdoro-bot-container">
            <!-- Floating Launch Button -->
            <button id="axdoro-bot-toggle" class="axdoro-bot-launcher" aria-label="<?php esc_attr_e( 'Open Style & Support Chat', 'axdoro-commerce' ); ?>">
                <span class="axdoro-bot-icon-open">💬</span>
                <span class="axdoro-bot-icon-close">✕</span>
                <span class="axdoro-bot-badge"><?php esc_html_e( 'AI Help', 'axdoro-commerce' ); ?></span>
            </button>

            <!-- Chatbot Window Panel -->
            <div id="axdoro-bot-window" class="axdoro-bot-window" style="display: none;">
                <div class="axdoro-bot-header">
                    <div class="axdoro-bot-profile">
                        <div class="axdoro-bot-avatar">⚡</div>
                        <div>
                            <h4><?php echo esc_html( $bot_name ); ?></h4>
                            <span class="axdoro-bot-status">● <?php esc_html_e( 'Online • Instant Help', 'axdoro-commerce' ); ?></span>
                        </div>
                    </div>
                    <button id="axdoro-bot-close-btn" class="axdoro-bot-close" aria-label="Close">✕</button>
                </div>

                <div id="axdoro-bot-messages" class="axdoro-bot-messages">
                    <div class="axdoro-msg axdoro-msg-bot">
                        <p><?php esc_html_e( 'Hey there! Welcome to AXDORO. How can I help you today?', 'axdoro-commerce' ); ?></p>
                    </div>
                    <!-- Quick Prompt Chips -->
                    <div class="axdoro-bot-chips">
                        <button type="button" class="axdoro-chip" data-query="size guide"><?php esc_html_e( '📏 Size Guide', 'axdoro-commerce' ); ?></button>
                        <button type="button" class="axdoro-chip" data-query="fabric gsm"><?php esc_html_e( '👕 240 GSM Fabric', 'axdoro-commerce' ); ?></button>
                        <button type="button" class="axdoro-chip" data-query="shipping delivery"><?php esc_html_e( '🚚 Shipping & Delivery', 'axdoro-commerce' ); ?></button>
                        <button type="button" class="axdoro-chip" data-query="track order"><?php esc_html_e( '📍 Track Order', 'axdoro-commerce' ); ?></button>
                        <button type="button" class="axdoro-chip" data-query="returns exchange"><?php esc_html_e( '🔄 Returns & Exchange', 'axdoro-commerce' ); ?></button>
                        <button type="button" class="axdoro-chip" data-query="bulk orders"><?php esc_html_e( '📦 Bulk / Custom Merch', 'axdoro-commerce' ); ?></button>
                    </div>
                </div>

                <!-- Input Footer -->
                <div class="axdoro-bot-footer">
                    <form id="axdoro-bot-form" class="axdoro-bot-form">
                        <input type="text" id="axdoro-bot-input" placeholder="<?php esc_attr_e( 'Ask about sizing, fabric, order...', 'axdoro-commerce' ); ?>" autocomplete="off" />
                        <button type="submit" id="axdoro-bot-send" aria-label="Send">➤</button>
                    </form>
                    <div class="axdoro-bot-human-handoff">
                        <a id="axdoro-human-link" href="#" target="_blank" rel="noopener noreferrer">
                            💬 <?php esc_html_e( 'Need a human? Chat on WhatsApp →', 'axdoro-commerce' ); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

AXDORO_AI_FAQ_Chatbot::init();
