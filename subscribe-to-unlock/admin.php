<?php

if (!defined('ABSPATH')) exit;

/**
 * Create an admin menu page for API Key & Form ID
 */
function custom_admin_settings_page() {
    add_menu_page(
        'Subscribe To Unlock',
        'Subscribe To Unlock',
        'manage_options',
        'stu-settings',
        'stu_settings_page_html',
        'dashicons-admin-generic',
        80
    );
}
add_action('admin_menu', 'custom_admin_settings_page');

/**
 * Register settings to store API Key & Form ID
 */
function stu_register_settings() {
    register_setting('stu_settings_group', 'stu_api_key');
    register_setting('stu_settings_group', 'stu_form_id');
    register_setting('stu_settings_group', 'stu_reward_tag_id');
    register_setting('stu_settings_group', 'stu_reward_popup_title');
    register_setting('stu_settings_group', 'stu_reward_popup_description');
    register_setting('stu_settings_group', 'stu_reward_popup_image');
}
add_action('admin_init', 'stu_register_settings');


function stu_settings_page_html() {
    // Check user permissions
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>API & Form Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('stu_settings_group');
            do_settings_sections('stu-settings');
            ?>

            <table class="form-table">
                <tr>
                    <th><label for="stu_api_key">API Key</label></th>
                    <td>
                        <input type="text" id="stu_api_key" name="stu_api_key"
                               value="<?php echo esc_attr(get_option('stu_api_key', '')); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="stu_form_id">Form ID</label></th>
                    <td>
                        <input type="text" id="stu_form_id" name="stu_form_id"
                               value="<?php echo esc_attr(get_option('stu_form_id', '')); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="stu_reward_tag_id">Reward Tag ID</label></th>
                    <td>
                        <input type="text" id="stu_reward_tag_id" name="stu_reward_tag_id"
                               value="<?php echo esc_attr(get_option('stu_reward_tag_id', '')); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="stu_reward_popup_title">Reward Popup Title</label></th>
                    <td>
                        <input type="text" id="stu_reward_popup_title" name="stu_reward_popup_title"
                               value="<?php echo esc_attr(get_option('stu_reward_popup_title', '')); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="stu_reward_popup_description">Reward Popup Description</label></th>
                    <td>
                        <textarea rows="4" id="stu_reward_popup_description" name="stu_reward_popup_description" class="regular-text"><?php echo esc_attr(get_option('stu_reward_popup_description', '')); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="stu_reward_popup_image">Reward Popup Image</label></th>
                    <td>
                        <input type="text" id="stu_reward_popup_image" name="stu_reward_popup_image"
                               value="<?php echo esc_attr(get_option('stu_reward_popup_image', '')); ?>"
                               class="regular-text">
                        <button type="button" class="button stu-upload-image">Choose Image</button>
                        <br>
                        <img id="stu_reward_popup_image_preview" src="<?php echo esc_attr(get_option('stu_reward_popup_image', '')); ?>"
                             style="max-width: 200px; display: <?php echo get_option('stu_reward_popup_image') ? 'block' : 'none'; ?>; margin-top: 10px;">
                    </td>
                </tr>

                <tr>
                    <td></td>
                    <td>
                        <div style="width: 200px">
                            <?php submit_button(); ?>
                        </div>
                    </td>
                </tr>
            </table>

        </form>

        <p><strong>Available Shortcodes</strong></p>
        <p><strong>1.</strong> `[stu_product_popup product-id="123" tag-id="456"]` – Displays a product popup for the specified `product-id` and `tag-id`.</p>
    </div>
    <?php
}

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_media();
});

add_action('admin_footer', function () {
    ?>
    <script>
        jQuery(document).ready(function ($) {
            $('.stu-upload-image').click(function (e) {
                e.preventDefault();
                var imageField = $('#stu_reward_popup_image');
                var imagePreview = $('#stu_reward_popup_image_preview');

                var mediaUploader = wp.media({
                    title: 'Select Image',
                    button: { text: 'Use this Image' },
                    multiple: false
                }).on('select', function () {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    imageField.val(attachment.url);
                    imagePreview.attr('src', attachment.url).show();
                }).open();
            });
        });
    </script>
    <?php
});

