<?php
//add scripts and styles
function svc_wp_scripts()
{
    wp_enqueue_style('svc_style', plugin_dir_url(__FILE__) . '../assets/css/style.css', array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/style.css'));
    // Enqueue jQuery UI for modal dialog
    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_style('jquery-ui-dialog');

    // Custom script to handle the modal dialog and form submission
    wp_enqueue_script('custom-recipient-modal', plugin_dir_url(__FILE__) . '../assets/js/recipient-modal.js', array('jquery'), '1.0', true);

    // Custom script to handle the questionnaire submission
    wp_enqueue_script('custom-user-save-questionnaire', plugin_dir_url(__FILE__) . '../assets/js/save-user-questionnaire.js', array('jquery'), '1.0', true);

    // Load SweetAlert2 CSS and JS
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11', true);
    wp_enqueue_style('sweetalert2-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');

    wp_localize_script('custom-recipient-modal', 'myPluginAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
}

add_action('wp_enqueue_scripts', 'svc_wp_scripts');

function my_admin_scripts() {
    wp_enqueue_script('svc_admin_script', plugin_dir_url(__FILE__). '../assets/js/survey-craft.js', 'jquery');
    wp_localize_script('svc_admin_script', 'myPluginAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
}
add_action( 'admin_enqueue_scripts', 'my_admin_scripts' );