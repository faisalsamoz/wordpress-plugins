<?php
/*
* Plugin Name: Image Post Generator
* Description: Image Post Generator using Joy Caption. Generate image captions from images with the <a href="https://huggingface.co/spaces/fancyfeast/joy-caption-alpha-two-vqa-test-one" target="_blank">joy-caption-alpha-two-vqa-test-one</a> model.
* Version: 1.0
* Author: Codeavour
*/

// Create Media Log Table
function create_media_log_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'ipg_posts_log';
    $charset_collate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        media_id BIGINT(20) UNSIGNED NOT NULL,
        image_url TEXT NOT NULL,
        post_id BIGINT(20) UNSIGNED DEFAULT NULL,
        status TEXT DEFAULT NULL,
        message TEXT DEFAULT NULL,
        tags TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";


    dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_media_log_table');

// Enqueue Scripts
if (file_exists(plugin_dir_path(__FILE__) . 'includes/ipg-enqueue-scripts.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/ipg-enqueue-scripts.php';
} else {
    error_log('Error: ipg-enqueue-scripts.php file is missing.');
}

// Add Admin Page
function ipg_add_admin_menu() {
    add_submenu_page(
        'edit.php', // This makes it appear under the "Posts" menu
        'Image Post Generator', // Page title
        'Image Post Generator', // Menu title
        'manage_options', // Capability needed to view this menu
        'ipg-post-generator', // Menu slug
        'ipg_admin_page', // Function to call for the page content
        20 // Position, adjust this if needed to change its order
    );
}
add_action('admin_menu', 'ipg_add_admin_menu');

// Include Admin Page
if (file_exists(plugin_dir_path(__FILE__) . 'includes/ipg-admin-page.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/ipg-admin-page.php';
} else {
    error_log('Error: ipg-admin-page.php file is missing.');
}