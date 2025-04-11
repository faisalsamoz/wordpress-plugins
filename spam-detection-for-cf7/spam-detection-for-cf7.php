<?php
/**
 * Plugin Name: Spam Filter for CF7
 * Description: Blocks spam submissions in Contact Form 7 based on customizable keywords and logs spam submissions.
 * Version: 1.3.0
 * Author: codeavour.
 * Text Domain: spam-filter-cf7
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Add submenu under Contact
add_action('admin_menu', 'spam_filter_cf7_add_admin_menu');
function spam_filter_cf7_add_admin_menu() {
    add_submenu_page('wpcf7', 'Spam Filter CF7', 'Spam Filter CF7', 'manage_options', 'spam-filter-cf7', 'spam_filter_cf7_options_page');
    add_submenu_page('wpcf7', 'Spam Logs', 'Spam Logs', 'manage_options', 'spam-filter-cf7-logs', 'spam_filter_cf7_logs_page');
}

// Register settings
add_action('admin_init', 'spam_filter_cf7_settings_init');
function spam_filter_cf7_settings_init() {
    register_setting('spam_filter_cf7', 'spam_filter_cf7_keywords');
    register_setting('spam_filter_cf7', 'spam_filter_cf7_redirect_url');
    
    add_settings_section(
        'spam_filter_cf7_section',
        __('Spam Filter Settings', 'spam-filter-cf7'),
        '__return_false',
        'spam_filter_cf7'
    );
    
    add_settings_field(
        'spam_filter_cf7_keywords',
        __('Blocked Keywords (comma-separated)', 'spam-filter-cf7'),
        'spam_filter_cf7_keywords_render',
        'spam_filter_cf7',
        'spam_filter_cf7_section'
    );
    
    add_settings_field(
        'spam_filter_cf7_redirect_url',
        __('Redirect URL', 'spam-filter-cf7'),
        'spam_filter_cf7_redirect_url_render',
        'spam_filter_cf7',
        'spam_filter_cf7_section'
    );
}

// Render input fields
function spam_filter_cf7_keywords_render() {
    $keywords = get_option('spam_filter_cf7_keywords', '');
    echo "<textarea name='spam_filter_cf7_keywords' rows='5' cols='50'>$keywords</textarea>";
}

function spam_filter_cf7_redirect_url_render() {
    $redirect_url = get_option('spam_filter_cf7_redirect_url', 'https://bragdeal.com/notice');
    echo "<input type='text' name='spam_filter_cf7_redirect_url' value='$redirect_url' size='50'>";
}

// Display settings page
function spam_filter_cf7_options_page() {
    echo "<form action='options.php' method='post'>";
    settings_fields('spam_filter_cf7');
    do_settings_sections('spam_filter_cf7');
    submit_button();
    echo "</form>";
}

//validate form
add_filter('wpcf7_validate', 'spam_filter_cf7_check', 10, 2);
function spam_filter_cf7_check($result, $form) {
    $submission = WPCF7_Submission::get_instance();

    if (!$submission) {
        return $result;
    }
    $posted_data = $submission->get_posted_data();
    $spam_keywords = array_map('trim', explode(',', get_option('spam_filter_cf7_keywords', '')));

    foreach ($posted_data as $field_value) {
        if (is_array($field_value)) {
            $field_value = implode(' ', $field_value);
        }
        foreach ($spam_keywords as $keyword) {
            if (!empty($keyword) && stripos($field_value, $keyword) !== false) {
                global $spam_detected, $spam_posted_data, $form_title;
                $spam_detected = true;
                $spam_posted_data = $posted_data;
                $contact_form = $submission->get_contact_form();
                $form_title = $contact_form ? $contact_form->title() : 'Unknown';
                $result->invalidate('', 'Spam detected.');
                return $result;
            }
        }
    }

    return $result;
}

//add log
add_filter('wpcf7_ajax_json_echo', function ($response, $result) {
    global $spam_detected, $spam_posted_data, $form_title;

    if (!empty($spam_detected)) {
        spam_filter_cf7_log_spam($form_title, $_SERVER['REMOTE_ADDR'], $spam_posted_data);
        $response['redirect_url'] = get_option('spam_filter_cf7_redirect_url', 'https://bragdeal.com/notice');
    }

    return $response;
}, 10, 2);

// Spam log function
function spam_filter_cf7_log_spam($form_title, $ip_address, $submitted_data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_spam_logs';

    $wpdb->insert(
        $table_name,
        array(
            'form_title'     => $form_title,
            'ip_address'     => $ip_address,
            'submitted_data' => maybe_serialize($submitted_data),
            'timestamp'      => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%s')
    );
}

// Enqueue JavaScript for redirection
add_action('wp_enqueue_scripts', 'spam_filter_cf7_enqueue_scripts');
function spam_filter_cf7_enqueue_scripts() {
    wp_enqueue_script('spam-filter-cf7-redirect', plugin_dir_url(__FILE__) . 'spam-filter-cf7-redirect.js', array('jquery'), '1.0', true);
}


// Create table for spam logs on plugin activation
register_activation_hook(__FILE__, 'spam_filter_cf7_create_table');
function spam_filter_cf7_create_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'cf7_spam_logs';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        form_title text NOT NULL,
        ip_address varchar(100) NOT NULL,
        submitted_data longtext NOT NULL,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Display Spam Logs page
function spam_filter_cf7_logs_page() {
    global $wpdb;
    $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $logs_per_page = 20;
    $offset = ($paged - 1) * $logs_per_page;
    $table_name = $wpdb->prefix . 'cf7_spam_logs';
    $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT $logs_per_page OFFSET $offset");

    echo "<div class='wrap'><h1>Spam Logs</h1>";

    if (!empty($logs)) {
        echo "<table class='wp-list-table widefat fixed striped'>";
        echo "<thead><tr><th>ID</th><th>Form Title</th><th>IP Address</th><th>Submitted Data</th><th>Timestamp</th></tr></thead><tbody>";

        foreach ($logs as $log) {
            echo "<tr>
                    <td>{$log->id}</td>
                    <td>{$log->form_title}</td>
                    <td>{$log->ip_address}</td>
                    <td>{$log->submitted_data}</td>
                    <td>{$log->timestamp}</td>
                  </tr>";
        }

        echo "</tbody></table>";
        $total_pages = ceil($total_logs / $logs_per_page);
        if ($total_pages > 1) {
            echo "<div class='pagination'>";
            if ($paged > 1) {
                echo "<a href='" . add_query_arg('paged', $paged - 1) . "'>&laquo; Previous</a> ";
            }

            if ($paged < $total_pages) {
                echo "<a href='" . add_query_arg('paged', $paged + 1) . "'>Next &raquo;</a>";
            }

            echo "</div>";
        }

    } else {
        echo "<p>No spam submissions logged yet.</p>";
    }

    echo "</div>";
}
?>