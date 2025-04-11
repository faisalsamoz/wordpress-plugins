<?php
function custom_pmpro_add_group_description_field() {
    if (!is_admin()) {
        return; // Only run in admin panel
    }

    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'memberships_page_pmpro-membershiplevels' || empty($_GET['edit_group'])) {
        return;
    }

    // Load TinyMCE editor scripts
    wp_enqueue_editor();

    ?>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            var formTable = document.querySelector(".form-table tbody");
            if (!formTable) return;

            var newRow = document.createElement("tr");
            newRow.innerHTML = `
                <th scope="row" valign="top">
                    <label for="pmpro_group_description"><?php esc_html_e('Description', 'pmpro'); ?></label>
                </th>
                <td>
                    <div id="pmpro_group_description_container">
                        <textarea id="pmpro_group_description" name="pmpro_group_description"></textarea>
                    </div>
                </td>
            `;
            formTable.appendChild(newRow);

            var groupId = document.querySelector("input[name='saveid']");
            if (groupId && groupId.value) {
                fetch('<?php echo admin_url("admin-ajax.php?action=custom_pmpro_get_group_description&group_id="); ?>' + groupId.value)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            tinymce.get("pmpro_group_description").setContent(data.data);
                        }
                    });
            }

            wp.editor.initialize('pmpro_group_description', {
                tinymce: {
                    theme: "modern",
                    skin: "lightgray",
                    height: 250,
                    menubar: true,
                    plugins: "link image media lists fullscreen wpeditimage wpdialogs",
                    toolbar1: "formatselect | bold italic underline | bullist numlist blockquote | alignleft aligncenter alignright | link unlink image media | wp_more",
                    toolbar2: "strikethrough | forecolor backcolor | removeformat | pastetext | charmap | outdent indent | undo redo | wp_help",
                    setup: function(editor) {
                        editor.on('change', function() {
                            editor.save();
                        });
                    }
                },
                quicktags: {
                    buttons: "strong,em,link,block,del,ins,img,ul,ol,li,code,close"
                },
                mediaButtons: true
            });
        });
    </script>
    <?php
}
add_action('admin_footer', 'custom_pmpro_add_group_description_field');


// Fetch Group Description via AJAX
function custom_pmpro_get_group_description() {
    if (!isset($_GET['group_id']) || !current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access');
    }

    $group_id = intval($_GET['group_id']);
    $description = custom_pmpro_get_group_meta($group_id, 'description');

    wp_send_json_success(wp_kses_post($description)); // Preserve HTML formatting
}
add_action('wp_ajax_custom_pmpro_get_group_description', 'custom_pmpro_get_group_description');


// Save Group Description
// Hook into the WordPress admin initialization to save group description
function custom_pmpro_save_group_description() {
    if (!isset($_POST['pmpro_group_description']) || !isset($_POST['saveid']) || !current_user_can('manage_options')) {
        return;
    }

    $group_id = intval($_POST['saveid']);
    $description = wp_kses_post($_POST['pmpro_group_description']); // Sanitize input

    // Save using the custom function
    custom_pmpro_update_group_meta($group_id, 'description', $description);
}
add_action('admin_init', 'custom_pmpro_save_group_description');


// Fetch Meta Data
function custom_pmpro_get_group_meta($group_id, $meta_key) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pmpro_membership_groups_meta';

    return $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM $table_name WHERE group_id = %d AND meta_key = %s",
        $group_id,
        $meta_key
    ));
}

// Update or Insert Meta Data
function custom_pmpro_update_group_meta($group_id, $meta_key, $meta_value) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pmpro_membership_groups_meta';

    $exists = custom_pmpro_get_group_meta($group_id, $meta_key);

    if ($exists !== null) {
        $wpdb->update(
            $table_name,
            ['meta_value' => $meta_value],
            ['group_id' => $group_id, 'meta_key' => $meta_key],
            ['%s'],
            ['%d', '%s']
        );
    } else {
        $wpdb->insert(
            $table_name,
            ['group_id' => $group_id, 'meta_key' => $meta_key, 'meta_value' => $meta_value],
            ['%d', '%s', '%s']
        );
    }
}
