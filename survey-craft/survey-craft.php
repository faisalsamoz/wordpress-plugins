<?php
/*
 * Plugin Name: SurveyCraft
 * Description: SurveyCraft
 * Version: 1.0
 * Author: Codeavour
 */

// Ensure WordPress has loaded before proceeding
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Hook into the plugin activation action
register_activation_hook(__FILE__, 'your_plugin_check_dependencies');

function your_plugin_check_dependencies() {

    // Include the WordPress functions to check active plugins
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');

    // Check if WooCommerce is active
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            'This plugin requires WooCommerce to be active. Please activate WooCommerce and try again.',
            'Plugin Activation Error',
            ['back_link' => true]
        );
    }

    // Check if Advanced Custom Fields is active
    if (!is_plugin_active('advanced-custom-fields-pro-master/acf.php')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            'This plugin requires Advanced Custom Fields (ACF) PRO to be active. Please activate ACF PRO and try again.',
            'Plugin Activation Error',
            ['back_link' => true]
        );
    }
}

// Hook to display admin notice if either plugin is inactive after activation
add_action('admin_notices', 'your_plugin_admin_notice');

function your_plugin_admin_notice() {
    if (!current_user_can('activate_plugins')) {
        return;
    }

    include_once(ABSPATH . 'wp-admin/includes/plugin.php');

    if (!is_plugin_active('woocommerce/woocommerce.php') || !is_plugin_active('advanced-custom-fields-pro-master/acf.php')) {
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>Survey craft:</strong> This plugin requires both <strong>WooCommerce</strong> and <strong>Advanced Custom Fields (ACF)</strong> to be active. Please activate them to use this plugin.</p>';
        echo '</div>';
    }
}



//Register CPT for survey
//require plugin_dir_path( __FILE__ ) . 'includes/cpt-survey.php';

define('TEXTDOMAIN', 'default');

//generate ACF Field group and manage all fields
require_once plugin_dir_path(__FILE__) . 'includes/manage-survey-fields.php';

//add scripts and styles
require_once plugin_dir_path(__FILE__). 'includes/enqueue-scripts.php';

//add short code
require_once plugin_dir_path(__FILE__). 'includes/short-code.php';

//woocommerce changes
require_once plugin_dir_path(__FILE__). 'includes/svc-woocommerce-changes.php';

//survey questionnaire  page
require_once plugin_dir_path(__FILE__). 'includes/svc-survey-questionnaire.php';
register_activation_hook(__FILE__, 'svc_create_questionnaire_page');

//generate word doc
require_once plugin_dir_path( __FILE__ ) . 'lib/vendor/autoload.php';
require_once plugin_dir_path(__FILE__). 'includes/svc-generate-word-doc.php';
