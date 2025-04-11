<?php
function myplugin_add_menu_page() {
    add_menu_page(
        'Connect Now Settings',
        'Connect Now',
        'manage_options',
        'cnow-settings',
        'cnow_settings_page',
        'dashicons-admin-generic',
        25
    );
}
add_action('admin_menu', 'myplugin_add_menu_page');

function cnow_settings_page() {
    ?>
    <div class="wrap">
        <h1>Connect Now Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cnow_options_group');
            do_settings_sections('cnow-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function cnow_register_settings() {
    register_setting('cnow_options_group', 'cnow_email');

    add_settings_section(
        'cnow_main_section',
        'Main Settings',
        '',
        'cnow-settings'
    );

    add_settings_field(
        'cnow_email_field',
        'Email',
        'cnow_email_field',
        'cnow-settings',
        'cnow_main_section'
    );
}
add_action('admin_init', 'cnow_register_settings');

function cnow_email_field() {
    echo '<input type="email" name="cnow_email" value="' . esc_attr($email) . '" />';
}
?>
