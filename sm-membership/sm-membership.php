<?php
/*
Plugin Name: Paid Memberships Pro - Custom Add on
Description: Custom Add on for Paid Memberships Pro.
Version: 1.0
Author: codeavour
*/

defined( 'ABSPATH' ) or exit;

//add group meta table
function custom_pmpro_create_groups_meta_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pmpro_membership_groups_meta';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        group_id BIGINT(20) UNSIGNED NOT NULL,
        meta_key VARCHAR(255) NOT NULL,
        meta_value LONGTEXT NOT NULL,
        UNIQUE KEY group_meta (group_id, meta_key)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'custom_pmpro_create_groups_meta_table');


//enqueue scripts
function enqueue_sm_script() {
    wp_enqueue_script('sm-pmpro-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), null, true);
    wp_enqueue_style('sm-pmpro-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), filemtime(plugin_dir_path(__FILE__) . 'assets/style.css'));

    wp_localize_script('sm-pmpro-script', 'sm_pmpro_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_sm_script');


//documents download shortcode
require_once plugin_dir_path(__FILE__) .'includes/sm-documents-shortcode.php';

//checkout page changes
require_once plugin_dir_path(__FILE__) .'includes/sm-checkout.php';

//account page changes
require_once plugin_dir_path(__FILE__) .'includes/sm-account-page.php';

//admin level changes
require_once plugin_dir_path(__FILE__) .'admin/su-group-description.php';

//front end subscription box
require_once plugin_dir_path(__FILE__) .'includes/su-front-end-plan.php';


// Add JSON support
add_filter('upload_mimes', 'add_custom_mime_types');
function add_custom_mime_types($mimes) {
    $mimes['json'] = 'application/json';
    return $mimes;
}
