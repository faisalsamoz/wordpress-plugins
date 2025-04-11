<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
die;
}

global $wpdb;

$table_name = $wpdb->prefix . 'wru_retailers';

// Drop the table
$sql = "DROP TABLE IF EXISTS $table_name;";
$wpdb->query($sql);
//clear meta
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_wru\_%'");
?>