<?php
// Shortcode to display retailers
function wru_display_retailers_shortcode($atts) {
    global $wpdb;

    $post_id = get_the_ID();
    if (!$post_id) return '';
    global $product;


    $us_retailers = get_post_meta($post_id, '_wru_us_retailers', true);
    $intl_retailers = get_post_meta($post_id, '_wru_intl_retailers', true);

    $us_retailers = is_array($us_retailers) ? $us_retailers : [];
    $intl_retailers = is_array($intl_retailers) ? $intl_retailers : [];


    $table_name = $wpdb->prefix . 'wru_retailers';
    $us_list = $wpdb->get_results("SELECT * FROM $table_name WHERE type = 'us'");
    $intl_list = $wpdb->get_results("SELECT * FROM $table_name WHERE type = 'international'");

    ob_start();
    ?>
    <div class="wru-retailers" id="wru-retailers-list">
        <?php if (!empty($us_retailers) && $product && !$product->get_price()) : ?>
            <h3>US Retailers:</h3>
            <ul class="wru-front-end-retailers">
                <?php foreach ($us_list as $retailer) :
                    if (!empty($us_retailers[$retailer->id])) :
                        ?>
                        <li>
                            <a class="wru-front-end-logo-link" href="<?php echo esc_url($us_retailers[$retailer->id]); ?>" target="_blank">
                                <img src="<?php echo esc_url(wp_get_attachment_url($retailer->logo)); ?>" alt="<?php echo esc_attr($retailer->name); ?>" class="wru-front-end-logo">
                            </a>
                        </li>
                    <?php endif;
                endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (!empty($intl_retailers) && $product && !$product->get_price()) : ?>
            <h3>International Retailers:</h3>
            <ul class="wru-front-end-retailers">
                <?php foreach ($intl_list as $retailer) :
                    if (!empty($intl_retailers[$retailer->id])) :
                        ?>
                    <li>
                        <a class="wru-front-end-logo-link" href="<?php echo esc_url($intl_retailers[$retailer->id]); ?>" target="_blank">
                            <img src="<?php echo esc_url(wp_get_attachment_url($retailer->logo)); ?>" alt="<?php echo esc_attr($retailer->name); ?>" class="wru-front-end-logo">
                        </a>
                    </li>
                    <?php endif;
                endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('wru_retailers', 'wru_display_retailers_shortcode');


//function wru_auto_display_retailers() {
//    echo do_shortcode('[wru_retailers]');
//}
//add_action('woocommerce_after_single_product_summary', 'wru_auto_display_retailers', 20);

add_shortcode('wru_retailers_order_now_btn', 'wru_display_retailers_order_now');
function wru_display_retailers_order_now($atts) {
    global $wpdb;
    
    $post_id = get_the_ID();
    if (!$post_id) return '';
    global $product;

    $atts = shortcode_atts(
        array(
            'label' => 'Order Now',
            'href'  => '#wru-retailers-list',
        ),
        $atts,
        'wru_retailers_order_now_btn'
    );


    $us_retailers = get_post_meta($post_id, '_wru_us_retailers', true);
    $intl_retailers = get_post_meta($post_id, '_wru_intl_retailers', true);

    $us_retailers = is_array($us_retailers) ? $us_retailers : [];
    $intl_retailers = is_array($intl_retailers) ? $intl_retailers : [];

    if ((!empty($us_retailers) && $product && !$product->get_price()) || 
        (!empty($intl_retailers) && $product && !$product->get_price())) {
        return '<a href="' . esc_url($atts['href']) . '" class="call_to_action">' . esc_html($atts['label']) . '</a>';
    }
}

?>