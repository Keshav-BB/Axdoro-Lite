<?php
/**
 * AXDORO Child Theme functions and definitions
 *
 * @package AXDORO_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue parent and child stylesheets + Google Fonts (Poppins & Inter)
 */
add_action( 'wp_enqueue_scripts', function() {
    // Google Fonts: Poppins (600, 700) and Inter (400, 500, 600)
    wp_enqueue_style(
        'axdoro-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap',
        array(),
        null
    );

    // Parent theme style
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

    // Child theme style
    wp_enqueue_style(
        'axdoro-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( 'parent-style' ),
        '1.0.0'
    );
}, 20 );

/**
 * Hook "Need Help with Size?" WhatsApp CTA below single product variable selects
 */
add_action( 'woocommerce_single_product_summary', function() {
    global $product;
    if ( ! $product ) return;

    $settings = get_option( 'axdoro_settings', array() );
    $phone    = ! empty( $settings['whatsapp_number'] ) ? preg_replace( '/[^0-9]/', '', $settings['whatsapp_number'] ) : '918807304713';
    $title    = $product->get_name();
    $sku      = $product->get_sku();

    $msg = sprintf(
        "Hello AXDORO! I need help choosing the right size for: %s (SKU: %s). Could you share measurements?",
        $title,
        $sku ? $sku : 'N/A'
    );

    $url = 'https://wa.me/' . $phone . '?text=' . rawurlencode( $msg );

    ?>
    <div class="axdoro-size-help-wrapper">
        <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" class="axdoro-size-whatsapp-cta">
            <span>💬 Need help with size? WhatsApp AXDORO Fit Expert →</span>
        </a>
    </div>
    <?php
}, 35 );

/**
 * Sticky Mobile "Add to Bag" Bottom Action on Single Product Pages
 */
add_action( 'wp_footer', function() {
    if ( ! is_product() ) return;
    global $product;
    if ( ! $product ) return;

    ?>
    <div class="axdoro-sticky-mobile-cta" style="display: none;">
        <div class="axdoro-sticky-price">
            <?php echo wp_kses_post( $product->get_price_html() ); ?>
        </div>
        <button type="button" class="axdoro-sticky-btn" onclick="document.querySelector('.single_add_to_cart_button')?.click() || window.scrollTo({top: 400, behavior: 'smooth'});">
            🛍️ Add to Bag
        </button>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.innerWidth <= 767) {
            var stickyBar = document.querySelector('.axdoro-sticky-mobile-cta');
            if (stickyBar) {
                window.addEventListener('scroll', function() {
                    if (window.scrollY > 350) {
                        stickyBar.style.display = 'flex';
                    } else {
                        stickyBar.style.display = 'none';
                    }
                });
            }
        }
    });
    </script>
    <?php
} );

/**
 * Set Shop Archive Catalog image aspect ratio to 4:5 (Standard Fashion Editorial)
 */
add_filter( 'woocommerce_get_image_size_thumbnail', function( $size ) {
    return array(
        'width'  => 400,
        'height' => 500,
        'crop'   => 1,
    );
} );

/**
 * Shop Archive pagination: 16 products per page to keep performance fast and stable
 */
add_filter( 'loop_shop_per_page', function( $cols ) {
    return 16;
}, 20 );

/**
 * Validate that billing phone is mandatory at checkout
 */
add_filter( 'woocommerce_billing_fields', function( $fields ) {
    if ( isset( $fields['billing_phone'] ) ) {
        $fields['billing_phone']['required'] = true;
        $fields['billing_phone']['label']    = __( 'WhatsApp / Mobile Number', 'axdoro-child' );
        $fields['billing_phone']['placeholder'] = __( '10-digit mobile number for order updates', 'axdoro-child' );
    }
    return $fields;
} );
