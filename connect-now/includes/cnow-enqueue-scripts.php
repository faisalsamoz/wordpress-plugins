<?php
function cnow_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('custom-jquery-ui', 'https://code.jquery.com/ui/1.13.2/jquery-ui.min.js', array('jquery'), '1.13.2', true);

    //Load pignose Calendar
    wp_enqueue_script('pignose', 'https://cdn.jsdelivr.net/npm/pg-calendar/dist/js/pignose.calendar.full.min.js', array(), '11', true);
    wp_enqueue_style('pignose-css', 'https://cdn.jsdelivr.net/npm/pg-calendar/dist/css/pignose.calendar.min.css');

    // Load SweetAlert2 CSS and JS
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11', true);
    wp_enqueue_style('sweetalert2-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');

    //Load Fontawesom
    wp_enqueue_style('fontawesom', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');

    //Load cnow Scripts
    wp_enqueue_script('cnow_script', plugin_dir_url(__FILE__) . '../assets/js/script.js', ['jquery'], null, true);
    wp_enqueue_style('cnow_style', plugin_dir_url(__FILE__) . '../assets/css/style.css',  array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/style.css'));

    wp_localize_script('cnow_script', 'myPluginAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
    ));
}
add_action('wp_enqueue_scripts', 'cnow_enqueue_scripts');