<?php
defined( 'ABSPATH' ) or exit;

function custom_pmpro_login_or_account_link() {
    if (is_user_logged_in()) {
        $url = esc_url(pmpro_url('account'));
        $text = 'My Account';
    } else {
        $url = esc_url(pmpro_url('login'));
        $text = 'Login';
    }

    return '<a href="' . $url . '" class="jkit-button-wrapper">' . esc_html($text) . '</a>';
}
add_shortcode('pmpro_login_or_account', 'custom_pmpro_login_or_account_link');
