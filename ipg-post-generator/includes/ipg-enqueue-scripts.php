<?php

function ipg_enqueue_admin_scripts($hook) {
    if ($hook === 'edit.php') {
        wp_enqueue_media(); // Ensures the media library script is loaded
        wp_enqueue_script('ipg_admin_script', plugin_dir_url(__FILE__) . '../assets/js/admin-script.js', ['jquery'], null, true);
        wp_enqueue_style('ipg_admin_style', plugin_dir_url(__FILE__) . '../assets/css/style.css',  array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/style.css'));
        
           // Load SweetAlert2 CSS and JS
        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11', true);
        wp_enqueue_style('sweetalert2-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');

        
        wp_localize_script('ipg_admin_script', 'myPluginAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'ipg_logs_url' => admin_url('edit.php?page=ipg-post-generator'),
        ));
    }
}
add_action('admin_enqueue_scripts', 'ipg_enqueue_admin_scripts');