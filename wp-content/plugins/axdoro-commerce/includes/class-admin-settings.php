<?php
/**
 * AXDORO Admin Settings Page
 *
 * Configures WhatsApp, Payment Gateways, Webhook Secrets, and Shiprocket options.
 *
 * @package AXDORO_Commerce
 */

defined( 'ABSPATH' ) || exit;

class AXDORO_Admin_Settings {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ), 50 );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

    public static function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'AXDORO Settings', 'axdoro-commerce' ),
            __( 'AXDORO Settings', 'axdoro-commerce' ),
            'manage_woocommerce',
            'axdoro-settings',
            array( __CLASS__, 'render_settings_page' )
        );
    }

    public static function register_settings() {
        register_setting( 'axdoro_settings_group', 'axdoro_settings', array(
            'sanitize_callback' => array( __CLASS__, 'sanitize_settings' )
        ) );
    }

    public static function sanitize_settings( $input ) {
        $sanitized = array();
        $sanitized['whatsapp_number'] = ! empty( $input['whatsapp_number'] ) ? preg_replace( '/[^0-9]/', '', $input['whatsapp_number'] ) : '';
        $sanitized['merchant_vpa']    = ! empty( $input['merchant_vpa'] ) ? sanitize_text_field( $input['merchant_vpa'] ) : 'axdoro@upi';
        $sanitized['merchant_name']   = ! empty( $input['merchant_name'] ) ? sanitize_text_field( $input['merchant_name'] ) : 'AXDORO Clothing';
        $sanitized['payment_mode']    = ! empty( $input['payment_mode'] ) ? sanitize_text_field( $input['payment_mode'] ) : 'whatsapp_upi';
        $sanitized['webhook_secret']  = ! empty( $input['webhook_secret'] ) ? sanitize_text_field( $input['webhook_secret'] ) : '';
        $sanitized['razorpay_key_id'] = ! empty( $input['razorpay_key_id'] ) ? sanitize_text_field( $input['razorpay_key_id'] ) : '';
        $sanitized['shiprocket_mode'] = ! empty( $input['shiprocket_mode'] ) ? sanitize_text_field( $input['shiprocket_mode'] ) : 'manual';
        $sanitized['enable_chatbot']  = ! empty( $input['enable_chatbot'] ) ? 'yes' : 'no';
        $sanitized['chatbot_name']    = ! empty( $input['chatbot_name'] ) ? sanitize_text_field( $input['chatbot_name'] ) : 'AXDORO Style & Support';
        $sanitized['debug_mode']      = ! empty( $input['debug_mode'] ) ? 'yes' : 'no';
        return $sanitized;
    }

    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $settings    = get_option( 'axdoro_settings', array() );
        $webhook_url = rest_url( 'axdoro/v1/payment-webhook' );
        ?>
        <div class="wrap axdoro-admin-wrap" style="max-width: 960px;">
            <h1 style="display: flex; align-items: center; gap: 10px;">
                <span style="background: #111111; color: #ffffff; padding: 4px 10px; border-radius: 4px; font-size: 16px; font-weight: bold;">AXDORO</span>
                <?php esc_html_e( 'Commerce Core Settings (₹10,000 MVP)', 'axdoro-commerce' ); ?>
            </h1>

            <?php if ( isset( $_GET['settings-updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e( 'AXDORO settings updated successfully.', 'axdoro-commerce' ); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php" style="margin-top: 20px;">
                <?php
                settings_fields( 'axdoro_settings_group' );
                ?>

                <!-- Section 1: WhatsApp Configuration -->
                <div class="postbox" style="padding: 16px 20px; border-radius: 6px;">
                    <h2 style="margin-top: 0; padding-bottom: 8px; border-bottom: 1px solid #eee;">
                        💬 <?php esc_html_e( 'WhatsApp Business & Payment Handoff', 'axdoro-commerce' ); ?>
                    </h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'WhatsApp Business Number', 'axdoro-commerce' ); ?></th>
                            <td>
                                <input type="text" name="axdoro_settings[whatsapp_number]" value="<?php echo esc_attr( $settings['whatsapp_number'] ?? '918807304713' ); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e( 'Include country code without + (e.g. 918807304713 for India).', 'axdoro-commerce' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Merchant UPI ID (VPA)', 'axdoro-commerce' ); ?></th>
                            <td>
                                <input type="text" name="axdoro_settings[merchant_vpa]" value="<?php echo esc_attr( $settings['merchant_vpa'] ?? 'axdoro@upi' ); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e( 'Direct UPI ID used for upi://pay links and fallback QR payments.', 'axdoro-commerce' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Merchant Display Name', 'axdoro-commerce' ); ?></th>
                            <td>
                                <input type="text" name="axdoro_settings[merchant_name]" value="<?php echo esc_attr( $settings['merchant_name'] ?? 'AXDORO Clothing' ); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e( 'Shown inside UPI apps like GPay and PhonePe.', 'axdoro-commerce' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Section 2: Payment Provider & Automated Webhooks -->
                <div class="postbox" style="padding: 16px 20px; border-radius: 6px;">
                    <h2 style="margin-top: 0; padding-bottom: 8px; border-bottom: 1px solid #eee;">
                        💳 <?php esc_html_e( 'Payment Gateway & Webhook Verification', 'axdoro-commerce' ); ?>
                    </h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Active Payment Channel', 'axdoro-commerce' ); ?></th>
                            <td>
                                <select name="axdoro_settings[payment_mode]" class="regular-text">
                                    <option value="whatsapp_upi" <?php selected( $settings['payment_mode'] ?? '', 'whatsapp_upi' ); ?>><?php esc_html_e( 'WhatsApp Direct UPI + Manual UTR Verification (Zero Cost)', 'axdoro-commerce' ); ?></option>
                                    <option value="razorpay" <?php selected( $settings['payment_mode'] ?? '', 'razorpay' ); ?>><?php esc_html_e( 'Razorpay Payment Links + Automated Webhook', 'axdoro-commerce' ); ?></option>
                                    <option value="cashfree" <?php selected( $settings['payment_mode'] ?? '', 'cashfree' ); ?>><?php esc_html_e( 'Cashfree Payment Links + Automated Webhook', 'axdoro-commerce' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Webhook Endpoint URL', 'axdoro-commerce' ); ?></th>
                            <td>
                                <input type="text" readonly value="<?php echo esc_url( $webhook_url ); ?>" class="large-text code" onclick="this.select();" />
                                <p class="description"><?php esc_html_e( 'Copy and paste this URL into your Razorpay or Cashfree dashboard webhooks settings.', 'axdoro-commerce' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Webhook Secret (HMAC)', 'axdoro-commerce' ); ?></th>
                            <td>
                                <input type="text" name="axdoro_settings[webhook_secret]" value="<?php echo esc_attr( $settings['webhook_secret'] ?? '' ); ?>" class="large-text code" />
                                <p class="description"><?php esc_html_e( 'Secret key shared with payment gateway to cryptographically verify payment signatures.', 'axdoro-commerce' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Razorpay Key ID (Optional)', 'axdoro-commerce' ); ?></th>
                            <td>
                                <input type="text" name="axdoro_settings[razorpay_key_id]" value="<?php echo esc_attr( $settings['razorpay_key_id'] ?? '' ); ?>" class="regular-text" placeholder="rzp_live_..." />
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Section 3: Shiprocket & Fulfilment -->
                <div class="postbox" style="padding: 16px 20px; border-radius: 6px;">
                    <h2 style="margin-top: 0; padding-bottom: 8px; border-bottom: 1px solid #eee;">
                        🚚 <?php esc_html_e( 'Shiprocket & Fulfillment Workflow', 'axdoro-commerce' ); ?>
                    </h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Fulfillment Integration Mode', 'axdoro-commerce' ); ?></th>
                            <td>
                                <select name="axdoro_settings[shiprocket_mode]" class="regular-text">
                                    <option value="manual" <?php selected( $settings['shiprocket_mode'] ?? '', 'manual' ); ?>><?php esc_html_e( 'Manual AWB & Status Updates (Recommended for ₹10k MVP)', 'axdoro-commerce' ); ?></option>
                                    <option value="native_plugin" <?php selected( $settings['shiprocket_mode'] ?? '', 'native_plugin' ); ?>><?php esc_html_e( 'Official Free Shiprocket WooCommerce Plugin', 'axdoro-commerce' ); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e( 'AWB tracking displays automatically on [axdoro_track_order] page once entered.', 'axdoro-commerce' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Order Tracking Page Shortcode', 'axdoro-commerce' ); ?></th>
                            <td>
                                <code>[axdoro_track_order]</code>
                                <p class="description"><?php esc_html_e( 'Place this shortcode inside the "Track Order" WordPress page.', 'axdoro-commerce' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Section 4: Lean AI / FAQ Chatbot -->
                <div class="postbox" style="padding: 16px 20px; border-radius: 6px;">
                    <h2 style="margin-top: 0; padding-bottom: 8px; border-bottom: 1px solid #eee;">
                        🤖 <?php esc_html_e( 'Lean AI / FAQ Support Chatbot', 'axdoro-commerce' ); ?>
                    </h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enable Floating Chatbot', 'axdoro-commerce' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="axdoro_settings[enable_chatbot]" value="yes" <?php checked( $settings['enable_chatbot'] ?? 'yes', 'yes' ); ?> />
                                    <?php esc_html_e( 'Show lean AI assistant widget on the bottom right of the store', 'axdoro-commerce' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Assistant Name', 'axdoro-commerce' ); ?></th>
                            <td>
                                <input type="text" name="axdoro_settings[chatbot_name]" value="<?php echo esc_attr( $settings['chatbot_name'] ?? 'AXDORO Style & Support' ); ?>" class="regular-text" />
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button( __( 'Save AXDORO Settings', 'axdoro-commerce' ) ); ?>
            </form>
        </div>
        <?php
    }
}

AXDORO_Admin_Settings::init();
