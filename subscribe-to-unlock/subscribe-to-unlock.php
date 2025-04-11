<?php
/**
 * Plugin Name: Subscribe to Unlock
 * Description: Restricts access to the "Rewards" page to users subscribed to the newsletter. Integrated with Kit.com for subscription verification.
 * Version: 1.0
 * Author: codeavour
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


//  Enqueue Scripts & Styles
function stu_enqueue_scripts() {
    wp_enqueue_script('sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], null, true);

    wp_enqueue_script('su-popup-script', plugin_dir_url(__FILE__) . 'popup.js', ['jquery'], null, true);
    wp_localize_script('su-popup-script', 'stu_ajax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'rewardsUrl' => get_permalink(get_page_by_path('rewards')), // Rewards page URL
        'reward_tag' => get_option('stu_reward_tag_id', ''),
        'stu_reward_popup_image' => get_option('stu_reward_popup_image', ''),
        'stu_reward_popup_title' => get_option('stu_reward_popup_title', 'Sign Up for Our Newsletter'),
        'stu_reward_popup_description' => get_option('stu_reward_popup_description', "<strong>Sign up</strong> to get email news from Stuart Atkinson​ and receive a free, exclusive box set of all her published and unpublished short stories."),
        'stu_default_popup_title' => 'Sign Up for Our Newsletter',
        'stu_default_popup_description' => "<strong>Sign up</strong> to get email news from Stuart Atkinson​ and receive a free, exclusive box set of all her published and unpublished short stories.",
    ]);
    wp_enqueue_style('su-popup-style', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('wp_enqueue_scripts', 'stu_enqueue_scripts');

// Show Popup Only on Rewards Page if No Access
function stu_add_popup_html() {
    ?>
    <div id="su-popup" style="display: none;">
        <div class="su-popup-content">
            <span id="su-close">&times;</span>

            <div class="su-popup-grid">
                <div class="su-popup-image">
                    <img id="su-product-image" style="display: none"  src="" alt="Product Image">
                </div>
                <div class="su-popup-form">
                    <h5 id="su-popup-title">Sign Up for Our Newsletter</h5>
                    <p id="su-popup-description"><strong>Sign up</strong> to get email news from Stuart Atkinson​ and receive a free, exclusive box set of all her published and unpublished short stories.</p>
                    <div class="email_area">
                        <input type="hidden" id="su-tag-id" name="su_tag_id">
                        <input type="email" id="su-email" placeholder="Enter your email" required>
                        <button id="su-submit">Submit</button>
                    </div>
                    <p class="su-popup-error" style="color: #F13B3A !important"></p>
                    <p class="su-popup-message" style="color: white"></p>
                    <p class="su-popup-success" style="color: green !important"></p>
                </div>
            </div>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'stu_add_popup_html');

//  Handle AJAX Email Submission
function stu_handle_email_submission() {
    if (!isset($_POST['email']) || !is_email($_POST['email'])) {
        wp_send_json_error(['message' => 'Invalid email address']);
    }

    $email = sanitize_email($_POST['email']);
    $tag = $_POST['su_tag_id'];

    //  Step 1: Subscribe to ConvertKit first
    $subscribe_result = stu_subscribe_to_convertkit($email, $tag);
    if ($subscribe_result !== true) {
        wp_send_json_error(['message' => 'Failed to subscribe']);
    }
    wp_send_json_success(['message' => 'Thank you for subscribing! Please check your email.']);
}

//  Function to subscribe user to ConvertKit FIRST before anything else
function stu_subscribe_to_convertkit($email, $tag = '') {
    $api_key = get_option('stu_api_key', '');
    $form_id = get_option('stu_form_id', '');
    $api_url = "https://api.convertkit.com/v3/forms/$form_id/subscribe";

    $data = [
        'api_key' => $api_key,
        'email'   => $email,
    ];
    if($tag != '' && $tag != null) {
        $data['tags'] = [$tag];
    }

    $data = json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
    ]);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_status == 200 || $http_status == 201) {
        return true;
    } else {
        return "Error failed to subscribe";
    }
}

// Hook AJAX requests
add_action('wp_ajax_stu_submit_email', 'stu_handle_email_submission');
add_action('wp_ajax_nopriv_stu_submit_email', 'stu_handle_email_submission');



// Set a cookie that expires in 24 hours (86400 seconds)
function set_popup_cookie() {
    setcookie('stu_popup_closed', '1', time() + 86400, "/", $_SERVER['HTTP_HOST'], is_ssl(), false);
    wp_die();
}

// Register AJAX action
add_action('wp_ajax_set_popup_cookie', 'set_popup_cookie');
add_action('wp_ajax_nopriv_set_popup_cookie', 'set_popup_cookie');


//add product popup shortcode
function stu_product_popup_shortcode($atts) {
    $atts = shortcode_atts([
        'product-id' => '',
        'tag-id' => ''
    ], $atts, 'stu_product_popup');

    if (empty($atts['product-id'])) {
        return '<p>No product specified.</p>';
    }

    $product_id = intval($atts['product-id']);
    $tag_id = sanitize_text_field($atts['tag-id']);

    // Get product image
    $product = wc_get_product($product_id);
    $image_url = $product ? wp_get_attachment_url($product->get_image_id()) : '';
    $product_title = $product ? $product->get_title() : 'Sign Up for Our Newsletter';

    
    ob_start();
    ?>
    <button class="stu-popup-trigger" data-product-id="<?php echo esc_attr($product_id); ?>" data-tag-id="<?php echo esc_attr($tag_id); ?>" data-image="<?php echo esc_url($image_url); ?>" data-title="<?php echo esc_html($product_title); ?>">
        Read More
    </button>
    <?php
    return ob_get_clean();
}
add_shortcode('stu_product_popup', 'stu_product_popup_shortcode');


//require admin page
require_once (plugin_dir_path(__FILE__).'admin.php');


function custom_password_form() {
    global $post;

    $label = 'pwbox-' . ( empty( $post->ID ) ? rand() : $post->ID );

    // Custom message
    $custom_message = '<p>This page is only for users who have subscribed to our newsletter. Please subscribe to access the shop. <a href="#" class="su-rewards-page-popup">Subscribe Now!</a></p>';

    // Error message logic
    $error_text = 'The password you have entered is invalid.';
    $attempted = isset($_SESSION['pass_attempt']) ? $_SESSION['pass_attempt'] : false;
    $error_message = '';

    if (
        isset($_COOKIE['wp-postpass_' . COOKIEHASH]) &&
        $attempted !== $_COOKIE['wp-postpass_' . COOKIEHASH]
    ) {
        $_SESSION['pass_attempt'] = $_COOKIE['wp-postpass_' . COOKIEHASH];
        $error_message = '<p class="wppass-error-text" style="color:red; font-weight:bold;">' . esc_html($error_text) . '</p>';
    }

    return '
      <div class="banner-area">
        <img class="bannerimg" src="https://covethemes.com/stuartatkinson/wp-content/uploads/2025/02/young-girl-in-a-bookstore-1.jpg" alt="Banner Image">
        <h6 class="stu-popup-trigger-reward">Shop</h6>
      </div>
      <div class="su-reward-restricted-form">
        <form action="' . esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ) . '" method="post">
            ' . $custom_message . '
            <label for="' . $label . '">Password:</label>
            <input name="post_password" id="' . $label . '" type="password" size="20" />
            ' . $error_message . '
            <input type="submit" name="Submit" value="' . esc_attr__( 'Submit' ) . '" />
        </form>
      </div>';
}
add_filter( 'the_password_form', 'custom_password_form' );

