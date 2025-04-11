<?php
/**
 * Plugin Name: WRU WooCommerce Retailers
 * Description: Add US and International retailers for each product.
 * Version: 1.0.0
 * Author: codeavour
 */

if (!defined('ABSPATH')) {
    exit;
}

// Create new table
function wru_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wru_retailers';
    $charset_collate = $wpdb->get_charset_collate();

    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

    if (!$table_exists) {
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            type ENUM('us', 'international') NOT NULL,
            logo VARCHAR(255) NOT NULL
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'wru_create_table');


//Add Admin Menu
function wru_add_retailers_submenu() {
    add_submenu_page(
        'edit.php?post_type=product',
        'Retailer Settings',
        'Retailers',
        'manage_options',
        'wru-retailers',
        'wru_render_admin_page'
    );
}
add_action('admin_menu', 'wru_add_retailers_submenu');


//Admin Page
function wru_render_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wru_retailers';
    $retailers = $wpdb->get_results("SELECT * FROM $table_name");
    ?>
    <div class="wrap">
        <h1>Retailers</h1>
        <button id="add-retailer-btn" class="button button-primary" style="margin-bottom: 10px;">Add New Retailer</button>

        <div id="retailer-form" style="display: none; margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9;">
            <h2 style="margin-bottom: 10px;">Add New Retailer</h2>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                <label for="retailer-name"><strong>Retailer Name:</strong></label>
                <input type="text" id="retailer-name" required style="width: 100%; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">

                <label for="retailer-type"><strong>Retailer Type:</strong></label>
                <select id="retailer-type" style="max-width: 100%; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="us">US</option>
                    <option value="international">International</option>
                </select>

                <input type="hidden" id="retailer-logo" />

                <label><strong>Retailer Logo:</strong></label>
                <button id="upload-logo-btn" class="button">Upload Logo</button>

                <div id="logo-preview-container" style="display: none; text-align: center; margin-top: 10px;">
                    <p><strong>Selected Logo:</strong></p>
                    <img id="logo-preview" style="width: 100px; height: auto; border: 1px solid #ddd; padding: 5px; border-radius: 4px;">
                </div>

                <button id="save-retailer-btn" class="button button-primary">Save Retailer</button>
            </div>
        </div>

        <table class="widefat">
            <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Type</th>
                <th>Logo</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php if(count($retailers)) { ?>
                <?php $count = 1; foreach ($retailers as $retailer) : ?>
                    <tr data-id="<?php echo esc_attr($retailer->id); ?>">
                        <td><?php echo $count++; ?></td>
                        <td><?php echo esc_html($retailer->name); ?></td>
                        <td style="text-transform: uppercase;"><?php echo esc_html(ucfirst($retailer->type)); ?></td>
                        <td><img src="<?php echo esc_url(wp_get_attachment_url($retailer->logo)); ?>" width="50" height="50" style="object-fit:contain" /></td>
                        <td>
                            <button class="delete-retailer-btn button button-danger" data-id="<?php echo esc_attr($retailer->id); ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach;
            } else {
                ?>
                <tr>
                    <td colspan="5" style="text-align:center">No record found</td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <p><strong>Please use this shortcode [wru_retailers] on product detail to display retailers of product.</strong></p>
    </div>
    <?php
}

function wru_enqueue_admin_scripts($hook) {
//    if ($hook !== 'product_page_wru-retailers') {
//        return;
//    }
    wp_enqueue_media();
    wp_enqueue_script('wru-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin.js', array('jquery'), null, true);
    wp_localize_script('wru-admin-script', 'ajaxurl', admin_url('admin-ajax.php'));
    // Load SweetAlert2 CSS and JS
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11', true);
    wp_enqueue_style('sweetalert2-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');
    wp_enqueue_style(
        'wru-admin-styles',
        plugin_dir_url(__FILE__) . 'assets/css/admin.css'
    );
}
add_action('admin_enqueue_scripts', 'wru_enqueue_admin_scripts');

function wru_enqueue_scripts($hook) {

    wp_enqueue_style(
        'wru-styles',
        plugin_dir_url(__FILE__) . 'assets/css/style.css'
    );
}
add_action('wp_enqueue_scripts', 'wru_enqueue_scripts');

//Save Retailer
function wru_add_retailer() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wru_retailers';

    $name = sanitize_text_field($_POST['name']);
    $type = sanitize_text_field($_POST['type']);
    $logo = sanitize_text_field($_POST['logo']);

    if ($wpdb->insert($table_name, ['name' => $name, 'type' => $type, 'logo' => $logo])) {
        wp_send_json_success(['message' => 'Retailer added successfully']);
    } else {
        wp_send_json_error();
    }
}
add_action('wp_ajax_wru_add_retailer', 'wru_add_retailer');

//Delete Retailer
function wru_delete_retailer() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wru_retailers';
    $id = intval($_POST['id']);

    if ($wpdb->delete($table_name, ['id' => $id])) {
        wp_send_json_success(['message' => 'Retailer deleted successfully']);
    } else {
        wp_send_json_error();
    }
}
add_action('wp_ajax_wru_delete_retailer', 'wru_delete_retailer');


include_once plugin_dir_path(__FILE__) . 'includes/wru_product-meta.php';
include_once plugin_dir_path(__FILE__) . 'includes/wru_short-codes.php';
?>