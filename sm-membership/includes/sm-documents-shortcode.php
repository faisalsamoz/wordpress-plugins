<?php
defined( 'ABSPATH' ) or exit;

//custom downloads shortcode
function pmprodlm_show_user_downloads_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to access downloads.</p>';
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $membership_levels = pmpro_getMembershipLevelsForUser($user_id);

    if (empty($membership_levels)) {
        return '<p>You do not have an active membership to access downloads.</p>';
    }

    // Get all downloads
    $downloads = get_posts([
        'post_type' => 'dlm_download',
        'posts_per_page' => -1
    ]);

    $output = '';

    foreach ($downloads as $download) {
        if (pmpro_has_membership_access($download->ID)) {
            $output .= do_shortcode("[download id='{$download->ID}' template='pmpro_button']");
        }
    }

    if (empty($output)) {
        return '<p>No downloads available for your membership level.</p>';
    }

    return $output;
}
add_shortcode('pmpro_user_downloads', 'pmprodlm_show_user_downloads_shortcode');

