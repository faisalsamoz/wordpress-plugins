<?php
if (!defined('ABSPATH')) {
    exit;
}

// Add Meta Box to Product Editor
function wru_add_retailer_meta_box() {
    add_meta_box(
        'wru_retailers_meta_box',
        'Retailer Links',
        'wru_render_retailer_meta_box',
        'product',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'wru_add_retailer_meta_box');

// Render the Meta Box
function wru_render_retailer_meta_box($post) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wru_retailers';

    $us_saved = get_post_meta($post->ID, '_wru_us_retailers', true);
    $intl_saved = get_post_meta($post->ID, '_wru_intl_retailers', true);

    $us_saved = is_array($us_saved) ? $us_saved : [];
    $intl_saved = is_array($intl_saved) ? $intl_saved : [];

    $_wru_us_retailers = $wpdb->get_results("SELECT * FROM $table_name WHERE type = 'us'");
    $_wru_intl_retailers = $wpdb->get_results("SELECT * FROM $table_name WHERE type = 'international'");
    ?>

    <div>
        <!-- US Retailers -->
        <h3>US Retailers:</h3>
        <div id="us-retailers-container">
            <?php if(count($us_saved)) { ?>
            <?php foreach ($us_saved as $id => $url) : ?>
                <div class="retailer-row" style="margin-bottom: 5px">
                    <select name="_wru_us_retailers[]" class="retailer-select">
                        <option value="">Select a Retailer</option>
                        <?php foreach ($_wru_us_retailers as $retailer) : ?>
                            <option value="<?php echo esc_attr($retailer->id); ?>"
                                <?php selected($id, $retailer->id); ?>>
                                <?php echo esc_html($retailer->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="url" name="_wru_us_retailers_urls[]"
                           value="<?php echo esc_url($url); ?>"
                           placeholder="Enter Retailer URL">
                    <button type="button" class="remove-retailer dashicons-before dashicons-no-alt"></button>
                </div>
            <?php endforeach; ?>
            <?php } else { ?>
                <div class="retailer-row" style="margin-bottom: 5px">
                    <select name="_wru_us_retailers[]" class="retailer-select">
                        <option value="">Select a Retailer</option>
                        <?php foreach ($_wru_us_retailers as $retailer) : ?>
                            <option value="<?php echo esc_attr($retailer->id); ?>">
                                <?php echo esc_html($retailer->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="url" name="_wru_us_retailers_urls[]"
                           value=""
                           placeholder="Enter Retailer URL">
                    <button type="button" class="remove-retailer dashicons-before dashicons-no-alt"></button>
                </div>
            <?php } ?>
        </div>
        <button type="button" class="add-retailer button" style="margin: 10px 0px" data-type="us">+ Add Retailer</button>

        <!-- International Retailers -->
        <h3>International Retailers:</h3>
        <div id="intl-retailers-container">
            <?php if(count($intl_saved)) {?>
            <?php foreach ($intl_saved as $id => $url) : ?>
                <div class="retailer-row" style="margin-bottom: 5px">
                    <select name="_wru_intl_retailers[]" class="retailer-select">
                        <option value="">Select a Retailer</option>
                        <?php foreach ($_wru_intl_retailers as $retailer) : ?>
                            <option value="<?php echo esc_attr($retailer->id); ?>"
                                <?php selected($id, $retailer->id); ?>>
                                <?php echo esc_html($retailer->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="url" name="_wru_intl_retailers_urls[]"
                           value="<?php echo esc_url($url); ?>"
                           placeholder="Enter Retailer URL">
                    <button type="button" class="remove-retailer dashicons-before dashicons-no-alt"></button>
                </div>
            <?php endforeach; ?>
            <?php } else { ?>
                <div class="retailer-row" style="margin-bottom: 5px">
                    <select name="_wru_intl_retailers[]" class="retailer-select">
                        <option value="">Select a Retailer</option>
                        <?php foreach ($_wru_intl_retailers as $retailer) : ?>
                            <option value="<?php echo esc_attr($retailer->id); ?>"
                            >
                                <?php echo esc_html($retailer->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="url" name="_wru_intl_retailers_urls[]"
                           value=""
                           placeholder="Enter Retailer URL">
                    <button type="button" class="remove-retailer dashicons-before dashicons-no-alt"></button>
                </div>
            <?php } ?>
        </div>
        <button type="button" class="add-retailer button" style="margin: 10px 0px" data-type="intl">+ Add Retailer</button>
    </div>

    <?php
}

// Save Meta Box Data
function wru_save_retailer_meta_box($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $us_data = [];
    $intl_data = [];

    if (isset($_POST['_wru_us_retailers']) && is_array($_POST['_wru_us_retailers'])) {
        foreach ($_POST['_wru_us_retailers'] as $index => $id) {
            if (!empty($id) && isset($_POST['_wru_us_retailers_urls'][$index])) {
                $us_data[$id] = esc_url_raw($_POST['_wru_us_retailers_urls'][$index]);
            }
        }
    }

    if (isset($_POST['_wru_intl_retailers']) && is_array($_POST['_wru_intl_retailers'])) {
        foreach ($_POST['_wru_intl_retailers'] as $index => $id) {
            if (!empty($id) && isset($_POST['_wru_intl_retailers_urls'][$index])) {
                $intl_data[$id] = esc_url_raw($_POST['_wru_intl_retailers_urls'][$index]);
            }
        }
    }

    update_post_meta($post_id, '_wru_us_retailers', $us_data);
    update_post_meta($post_id, '_wru_intl_retailers', $intl_data);
}
add_action('save_post_product', 'wru_save_retailer_meta_box');
?>
