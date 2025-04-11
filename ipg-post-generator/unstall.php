<?php
// If this file is accessed directly, abort.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

global $wpdb;

$table_name = $wpdb->prefix . 'ipg_posts_log';

// Drop the table
$sql = "DROP TABLE IF EXISTS $table_name;";
$wpdb->query($sql);
