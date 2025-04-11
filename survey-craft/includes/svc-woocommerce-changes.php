<?php
// Change Product labels in admin
add_action('init', 'change_product_labels');
function change_product_labels() {
    global $wp_post_types;

    // Get the WooCommerce product post type
    if (isset($wp_post_types['product'])) {
        $labels = &$wp_post_types['product']->labels;

        // Update labels
        $labels->name = __('Surveys', TEXTDOMAIN);
        $labels->singular_name = __('Survey', TEXTDOMAIN);
        $labels->add_new = __('Add Survey', TEXTDOMAIN);
        $labels->add_new_item = __('Add New Survey', TEXTDOMAIN);
        $labels->edit_item = __('Edit Survey', TEXTDOMAIN);
        $labels->new_item = __('New Survey', TEXTDOMAIN);
        $labels->view_item = __('View Survey', TEXTDOMAIN);
        $labels->search_items = __('Search Surveys', TEXTDOMAIN);
        $labels->not_found = __('No Surveys found', TEXTDOMAIN);
        $labels->not_found_in_trash = __('No Surveys found in Trash', TEXTDOMAIN);
        $labels->all_items = __('All Surveys', TEXTDOMAIN);
        $labels->menu_name = __('Surveys', TEXTDOMAIN);
        $labels->name_admin_bar = __('Survey', TEXTDOMAIN);
    }
}

// Change WooCommerce Settings Tab Label
add_filter('woocommerce_settings_tabs_array', 'change_products_tab_label', 50);
function change_products_tab_label($tabs) {
    if (isset($tabs['products'])) {
        $tabs['products'] = __('Surveys', TEXTDOMAIN);
    }
    return $tabs;
}

// Change Settings Page Title for Products Tab
add_filter('woocommerce_get_sections_products', 'change_products_section_label');
function change_products_section_label($sections) {
    foreach ($sections as $key => $value) {
        $sections[$key] = str_replace('Products', __('Surveys', TEXTDOMAIN), $value);
    }
    return $sections;
}

// Change Settings Headings for Products Page
add_filter('woocommerce_get_settings_products', 'change_settings_headings', 10, 2);
function change_settings_headings($settings, $current_section) {
    foreach ($settings as &$setting) {
        if (!empty($setting['title']) && $setting['title'] === 'Products') {
            $setting['title'] = __('Surveys', TEXTDOMAIN);
        }
        if (!empty($setting['desc']) && strpos($setting['desc'], 'Products') !== false) {
            $setting['desc'] = str_replace('Products', 'Surveys', $setting['desc']);
        }
    }
    return $settings;
}

// Remove SKU, Tags, and Stock columns from the product listing table
function custom_remove_product_columns($columns) {
    unset($columns['sku']);           // Remove SKU column
    unset($columns['product_tag']);   // Remove Tags column
    unset($columns['is_in_stock']);   // Remove Stock column

    return $columns;
}
add_filter('manage_edit-product_columns', 'custom_remove_product_columns');

// Remove "Product type" "Product stock status" dropdown filter
add_filter( 'woocommerce_products_admin_list_table_filters', 'remove_products_admin_list_table_filters', 10, 1 );
function remove_products_admin_list_table_filters( $filters ){
    // Remove "Product type" dropdown filter
    if( isset($filters['product_type']))
        unset($filters['product_type']);

    // Remove "Product stock status" dropdown filter
    if( isset($filters['stock_status']))
        unset($filters['stock_status']);

    return $filters;
}

function remove_metaboxes() {
//    remove_meta_box( 'postexcerpt' , 'product' , 'normal' );
    remove_meta_box( 'commentsdiv' , 'product' , 'normal' );
    remove_meta_box( 'tagsdiv-product_tag' , 'product' , 'normal' );
}
add_action( 'add_meta_boxes' , 'remove_metaboxes', 50 );

add_filter('woocommerce_product_data_tabs', 'remove_inventory_product_data_tab', 99);

function remove_inventory_product_data_tab($tabs) {
    // Unset the inventory tab
    if (isset($tabs['inventory'])) {
        unset($tabs['inventory']);
    }
    // Remove Linked Products tab
    if (isset($tabs['linked_product'])) {
        unset($tabs['linked_product']);
    }
    // Remove Attributes tab
    if (isset($tabs['attribute'])) {
        unset($tabs['attribute']);
    }
    // Remove Advance tab
    if (isset($tabs['advanced'])) {
        unset($tabs['advanced']);
    }
    // Remove get_more_options tab
    if (isset($tabs['marketplace-suggestions'])) {
        unset($tabs['marketplace-suggestions']);
    }
    return $tabs;
}

// hide product data choose product type select
add_action('admin_head', 'hide_product_data_type_dropdown');

function hide_product_data_type_dropdown() {
    echo '<style>
        .postbox-header .type_box label:first-child {
            display: none !important;
        }
    </style>';
}

//add admin order status and survey name
add_filter('manage_woocommerce_page_wc-orders_columns', 'add_custom_order_columns', 20);
function add_custom_order_columns($columns) {
    // Add new columns after existing ones
    $new_columns = [];
    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;

        if ($key === 'order_number') {
            $new_columns['survey'] = __('Survey', TEXTDOMAIN);
            $new_columns['admin_survey_status'] = __('Survey Status', TEXTDOMAIN);
        }
    }
    $new_columns['action'] = __('Action', TEXTDOMAIN);
    unset($new_columns['order_status']);
    return $new_columns;
}

add_action('manage_woocommerce_page_wc-orders_custom_column', 'populate_custom_order_columns', 20, 2);
function populate_custom_order_columns($column, $order) {
    if ($column === 'survey') {
        $products = [];
        foreach ($order->get_items() as $item) {
            $products[] = $item->get_name();
        }
        echo implode(', ', $products);
    }

    if ($column === 'admin_survey_status') {
        $survey_status = $order->get_meta('_admin_survey_status', true);
        echo $survey_status ? esc_html($survey_status) : '';
    }
    if ($column === 'action') {
        $survey_status = $order->get_meta('_admin_survey_status', true);
        if ($survey_status == 'Survey completed') {
            echo '<div style="display: flex; justify-content: space-between; gap: 5px; flex-wrap: wrap">';
            echo '<button type="button" class="download-word-doc button" data-order-id="' . $order->get_id() . '">Download Word Doc</button>';
            echo '<button type="button" class="upload-survey-pdf button" data-order-id="' . $order->get_id() . '">Upload PDF</button>';
            echo '</div>';
        } elseif ($survey_status == 'Transaction closed') {
            $pdf_file = $order->get_meta('_uploaded_survey_pdf_url', true);
            echo '<a href="'.$pdf_file.'" class="button" download="">Download PDF</a>';
        } else {
            echo '----';
        }
    }
}

// AJAX handler for file upload
add_action('wp_ajax_upload_survey_pdf', 'handle_upload_survey_pdf');
function handle_upload_survey_pdf() {
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('You do not have permission to upload files.');
        exit;
    }

    if (empty($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error('File upload failed.');
        exit;
    }

    // Ensure the file is a PDF
    if ($_FILES['pdf_file']['type'] !== 'application/pdf') {
        wp_send_json_error('Please upload a valid PDF file.');
        exit;
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error('Invalid order ID.');
        exit;
    }

    // Handle file upload
    $upload_dir = wp_upload_dir();
    $target_dir = $upload_dir['path'] . '/';
    $file_name = sanitize_file_name($_FILES['pdf_file']['name']);
    $file_path = $target_dir . $file_name;

    if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $file_path)) {
        $file_url = $upload_dir['url'] . '/' . $file_name;
        $order = wc_get_order($order_id);
        $order->update_meta_data('_uploaded_survey_pdf_url', $file_url);
        $order->update_meta_data('_admin_survey_status', 'Transaction closed');
        $order->save();

        $subject = 'Your survey #' . $order_id . ' results are ready for download';
        $message = sprintf(
            "Dear %s %s, <br><br>Your purchased survey #%s results are ready!<br>To download your survey results, please click on the link below:<br><br>
                    <a href='%s' style='color: #0073aa; text-decoration: none;'>Download Survey Results</a><br><br>
                    If you have any questions or require further assistance, please don't hesitate to reach out.<br><br>
                   ",
            esc_html($order->get_billing_first_name()),
            esc_html($order->get_billing_last_name()),
            esc_html($order_id),
            esc_url($file_url)
        );

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($order->get_billing_email(), $subject, $message, $headers);

        wp_send_json_success(['message' => 'File uploaded successfully.']);
    } else {
        wp_send_json_error('Error saving the file.');
    }
}



//quantity check
add_filter('woocommerce_add_to_cart_validation', 'restrict_to_one_product_and_quantity', 10, 3);
add_action('woocommerce_before_calculate_totals', 'enforce_single_product_cart', 10);

function restrict_to_one_product_and_quantity($passed, $product_id, $quantity) {
    // Check if there is already a product in the cart
    if (WC()->cart->get_cart_contents_count() > 0) {
        wc_add_notice(__('You can only purchase one product at a time.', 'woocommerce'), 'error');
        return false; // Prevent adding another product
    }

    // Check if the quantity exceeds 1
    if ($quantity > 1) {
        wc_add_notice(__('You can only add one quantity of a product.', 'woocommerce'), 'error');
        return false; // Prevent adding more than one quantity
    }

    return $passed;
}

function enforce_single_product_cart($cart) {
    // Prevent execution in the admin area or during AJAX calls that shouldn't modify the cart
    if (is_admin() || !did_action('woocommerce_before_calculate_totals')) {
        return;
    }

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        // Ensure the cart only contains one product and quantity is 1
        if (count(WC()->cart->get_cart()) > 1 || $cart_item['quantity'] > 1) {
            WC()->cart->empty_cart(); // Clear the cart if multiple items or quantities exist
            wc_add_notice(__('Your cart has been reset because only one product with one quantity is allowed.', 'woocommerce'), 'error');
            break;
        }
    }
}

// Hide quantity input field on the product page
add_filter('woocommerce_is_sold_individually', 'hide_quantity_selector', 10, 2);

function hide_quantity_selector($sold_individually, $product) {
    // Apply only to specific products or all products
    return true; // Forces "sold individually" behavior
}

//default survey status pending
add_action('woocommerce_new_order', 'add_survey_status_to_order_meta', 10, 2);

function add_survey_status_to_order_meta($order_id, $order) {
    $product_items = $order->get_items();
    $survey_type = 'single';
    foreach ($product_items as $item) {
        $product_id = $item->get_product_id();
        $product_survey_type = get_field('survey_type', $product_id);
        $product_predefined_message = get_field('predefined_message', $product_id);
        if ($product_survey_type) {
            $survey_type = $product_survey_type;
            break;
        }
    }
    $order->update_meta_data('_survey_status', 'Pending');
    $order->update_meta_data('_admin_survey_status', 'Survey in progress');
    $order->update_meta_data('_survey_type', $survey_type);
    $order->update_meta_data('_survey_predefined_message', $product_predefined_message);
    $order->save_meta_data();
    $order->save();
}



// Modify the 'Orders' link text
add_filter('woocommerce_account_menu_items', 'customize_account_menu_items');

function customize_account_menu_items($items) {
    $items['orders'] = __('Surveys', TEXTDOMAIN); // Modify the 'Orders' link text
    return $items;
}

add_filter('the_title', 'modify_orders_page_title', 10, 2);

function modify_orders_page_title($title, $id) {
    // Check if this is the Orders page and modify the title
    if (is_account_page() && $title === 'Orders') {
        return __('Surveys', TEXTDOMAIN); // Change the title to 'Surveys'
    }
    return $title;
}



add_filter('woocommerce_my_account_my_orders_columns', 'add_survey_status_column');
function add_survey_status_column($columns) {
    $new_columns = [];
    foreach ($columns as $key => $column) {
        if ($key === 'order-status') {
            $new_columns['survey_status'] = __('Survey Status', TEXTDOMAIN);
        } else {
            $new_columns[$key] = $column;
        }
    }
    unset($new_columns['order-status']);
    return $new_columns;
}

add_action('woocommerce_my_account_my_orders_column_survey_status', 'populate_survey_status_column');
function populate_survey_status_column($order) {
    $survey_status = $order->get_meta('_survey_status'); // Retrieve survey status from order meta.
    echo $survey_status ? esc_html($survey_status) : __('Pending', TEXTDOMAIN);
}



// Add a "Add Recipient" button to each order in the My Orders section
add_action('woocommerce_my_account_my_orders_actions', 'add_recipient_button_to_order', 10, 2);

function add_recipient_button_to_order($view, $order) {
    $survey_recipient = $order->get_meta('_recipient_data');
    $survey_status = $order->get_meta('_survey_status');
    $survey_type = $order->get_meta('_survey_type');
    $survey_purchaser_will_fill_the_survey = $order->get_meta('_survey_purchaser_will_fill_the_survey');
    $survey_predefined_message = $order->get_meta('_survey_predefined_message');
    $survey_pdf_file = $order->get_meta('_uploaded_survey_pdf_url');

    $recipient_count = is_array($survey_recipient) ? count($survey_recipient) : 0;
    echo '<div class="recipient-details">';
          if($recipient_count) {
              if($survey_status == 'Gifted') {
                  echo '<a href="javascript:void(0)" class="button add-recipient-button" data-order-id="' . esc_attr($order->get_id()) . '" data-survey-type="' . esc_attr($survey_type) . '" data-survey-message="' . esc_attr($survey_predefined_message) . '">';
                  echo 'Modify';
                  echo '</a>';
              }
              if($survey_type == 'duo' && $survey_status != 'Gifted' && $survey_purchaser_will_fill_the_survey == 1) {
                  foreach ($order->get_items() as $item_id => $item) {
                      $product = $item->get_product();
                      break;
                  }
                  if($survey_type == 'duo' && $product) {
                      $survey_permalink = get_option('svc_questionnaire_permalink'); // Retrieve the saved permalink
                      $product_id = $product->get_id(); // Replace with the actual method to get product ID
                      $order_id = $order->get_id();
                      echo '<a href="' . esc_url(add_query_arg(array('product_id' => $product_id, 'order_id' => $order_id), $survey_permalink)) . '">'.($survey_status == 'Submitted' ? 'View Survey': 'Fill this survey').'</a>';
                  }
              }
              if($survey_pdf_file) {
                  echo '<a href="' . esc_url($survey_pdf_file) . '" download="">Download PDF</a>';
              }
              if($survey_type == 'duo' && $survey_status == 'Gifted' && $survey_purchaser_will_fill_the_survey == 1) {
                  echo "<p><strong>Note:</strong> You can fill the survey after the 2nd recipient accepts it.</p>";
              }
              echo '<strong>Recipient Details:</strong><br>';
              foreach ($survey_recipient as $recipient) {
                  echo '<p>';
                  echo '<strong>Name:</strong> ' . esc_html($recipient['name']) . '<br>';
                  echo '<strong>Email:</strong> ' . esc_html($recipient['email']). '<br>';
                  if(in_array($survey_status, ['Accepted', 'Gifted', 'In Progress'])) {
                      echo '<a href="javascript:void(0)" class="button send-survey-reminder" data-order-id="' . esc_attr($order->get_id()) . '" data-recipient-name="' . esc_attr($recipient['name']) . '" data-recipient-email="' . esc_attr($recipient['email']) . '">';
                      echo 'Send Reminder';
                      echo '</a>';
                  }
                  if($survey_type == 'duo') {
                      echo '<strong>Status:</strong> ' . esc_html($recipient['status'] ?? ''). '<br>';
                  }
                  if($survey_status == 'In Progress' || $survey_status == 'Submitted') {
                      echo '<strong>Completed:</strong> ' . esc_html($recipient['completed'] ?? 0) . '%<br>';
                  }
                  echo '</p>';
              }
          } else {
              if($survey_status == 'Pending') {
                  echo '<a href="javascript:void(0)" class="button add-recipient-button" data-order-id="' . esc_attr($order->get_id()) . '" data-survey-type="' . esc_attr($survey_type) . '" data-survey-message="' . esc_attr($survey_predefined_message) . '">';
                  echo 'Modify';
                  echo '</a>';
              }
              foreach ($order->get_items() as $item_id => $item) {
                  $product = $item->get_product();
                  break;
              }
              if($survey_type == 'single' && $product) {
                  $survey_permalink = get_option('svc_questionnaire_permalink'); // Retrieve the saved permalink
                  $product_id = $product->get_id(); // Replace with the actual method to get product ID
                  $order_id = $order->get_id();
                  echo '<a href="' . esc_url(add_query_arg(array('product_id' => $product_id, 'order_id' => $order_id), $survey_permalink)) . '">'.($survey_status == 'Submitted' ? 'View Survey': 'Fill this survey').'</a>';
              }
              if($survey_pdf_file) {
                  echo '<a href="' . esc_url($survey_pdf_file) . '" download="">Download PDF</a>';
              }
          }
    echo '</div>';
}



// AJAX handler for saving recipient data
add_action('wp_ajax_save_recipient', 'save_recipient_data');

function save_recipient_data() {
    if (isset($_POST['order_id']) && isset($_POST['recipients'])) {
        $order_id = sanitize_text_field($_POST['order_id']);
        $recipients = json_decode(stripslashes($_POST['recipients']), true); // Decode JSON array
        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(array('message' => 'Order not found.'));
            return;
        }

        $survey_type = $order->get_meta('_survey_type');
        $purchaser_will_fill_the_survey = sanitize_text_field($_POST['purchaser_will_fill_the_survey']);

        $max_recipients = $survey_type === 'duo' ? ($purchaser_will_fill_the_survey == 1 ? 1 : 2): 1;

        if (count($recipients) > $max_recipients) {
            wp_send_json_error(array('message' => 'Too many recipients for this survey type.'));
            return;
        }

        foreach ($recipients as $recipient) {
            $name = sanitize_text_field($recipient['name']);
            $email = sanitize_email($recipient['email']);

            if (empty($name) || empty($email)) {
                wp_send_json_error(array('message' => 'All recipient fields are required.'));
                return;
            }
        }

        $recipients = array_map(function($recipient) {
            return array_merge($recipient, ['accepted' => false, 'status' => 'Pending', 'completed' => 0]);
        }, $recipients);
        $survey_message = sanitize_text_field($_POST['message']);

        $order->update_meta_data('_recipient_data', $recipients);
        $order->update_meta_data('_survey_status', 'Gifted');
        $order->update_meta_data('_survey_purchaser_will_fill_the_survey', $purchaser_will_fill_the_survey);
        $order->save_meta_data();
        $order->save();

        // Send emails to all recipients
        foreach ($recipients as $index => $recipient) {
            $survey_acceptance_link = add_query_arg(array(
                'order_id' => $order_id,
                'action' => 'accept_survey',
                'recipient_index' => $index,
            ), wc_get_page_permalink('myaccount'));

            $subject = 'You Have Received a Gift - Accept Your Survey';
            $message = sprintf(
                'Hello %s, <br><br>You have been added as a recipient of a survey gift! <br>
                        %s<br>
                        Please click the link below to accept your survey:<br><a href="%s">Accept Survey</a><br><br>',
                esc_html($recipient['name']),
                esc_html($survey_message),
                esc_url($survey_acceptance_link)
            );

            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($recipient['email'], $subject, $message, $headers);
        }

        wp_send_json_success(array('message' => 'Recipients added and emails sent successfully!'));
    } else {
        wp_send_json_error(array('message' => 'Failed to add recipients. Please try again.'));
    }
}

// AJAX handler for sending survey reminder
add_action('wp_ajax_send_survey_reminder', 'send_survey_reminder');

function send_survey_reminder() {
    if (isset($_POST['order_id']) && isset($_POST['name']) && isset($_POST['email']) && isset($_POST['message'])) {
        $order_id = sanitize_text_field($_POST['order_id']);
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_text_field($_POST['email']);
        $message= sanitize_text_field($_POST['message']);

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => 'Order not found.'));
            return;
        }
        if (empty($name) || empty($email) || empty($message)) {
           wp_send_json_error(array('message' => 'All fields are required.'));
           return;
        }

        $recipients = $order->get_meta('_recipient_data') ?? [];
        
        if(!svc_check_survey_user_email($recipients, $email)) {
            wp_send_json_error(array('message' => 'Recipient not found.'));
        }

        // Send email
        $subject = 'You have an update regarding survey #'.$order_id;
        $message = sprintf(
                'Hello %s, <br> %s',
                esc_html($name),
                esc_html($message),
        );

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($email, $subject, $message, $headers);

        wp_send_json_success(array('message' => 'Message sent successfully!'));
    } else {
        wp_send_json_error(array('message' => 'Failed to send reminder. Please try again.'));
    }
}


function svc_check_survey_user_email($survey_recipients, $email)
{
    $email_found = false;
    if (is_array($survey_recipients)) {
        foreach ($survey_recipients as $recipient) {
            if (isset($recipient['email']) && $recipient['email'] === $email) {
                $email_found = true;
                break;
            }
        }
    }
    return $email_found;
}

add_action('template_redirect', 'handle_survey_acceptance');

function handle_survey_acceptance() {
    if (is_user_logged_in() && isset($_GET['action']) && $_GET['action'] === 'accept_survey' && isset($_GET['order_id']) && isset($_GET['recipient_index'])) {

        $order_id = intval($_GET['order_id']);
        $recipient_index = $_GET['recipient_index'] ??  null;
        $order = wc_get_order($order_id);

        if (!$order) {
            wc_add_notice('Order not found.', 'error');
            return;
        }

        $current_user = wp_get_current_user();
        $recipients = $order->get_meta('_recipient_data');

        if ($recipient_index === null || !isset($recipients[$recipient_index])) {
            wc_add_notice('Recipient not found.', 'error');
            return;
        }

        // Check if the logged-in user's email matches the recipient's email
        if (!svc_check_survey_user_email([$recipients[$recipient_index]], $current_user->user_email)) {
            wc_add_notice('You are not authorized to accept this survey.', 'error');
            wp_redirect(wc_get_page_permalink('myaccount'));
            exit;
        }

        if($recipients[$recipient_index]['accepted']) {
            wc_add_notice('Survey already accepted.', 'error');
            return;
        }

        // Mark recipient as accepted
        $recipients[$recipient_index]['accepted'] = true;
        $recipients[$recipient_index]['status'] = 'Accepted';
        $order->update_meta_data('_recipient_data', $recipients);

        $accepted_user_ids = $order->get_meta('_recipients_user_id', true);
        if (!is_array($accepted_user_ids)) {
            $accepted_user_ids = [];
        }

        $current_user_id = get_current_user_id();
        if (!in_array($current_user_id, $accepted_user_ids)) {
            $accepted_user_ids[] = $current_user_id;
        }

        $order->update_meta_data('_recipients_user_id', $accepted_user_ids);
        $order->save_meta_data();

        // Check if all recipients accepted
        $all_accepted = array_reduce($recipients, function ($carry, $recipient) {
            return $carry && $recipient['accepted'];
        }, true);

        if ($all_accepted) {
            // Update survey status to Accepted
            $order->update_meta_data('_survey_status', 'Accepted');
            $order->save_meta_data();
        }

        wc_add_notice('Thank you! Your survey has been accepted.', 'success');
        wp_redirect(wc_get_page_permalink('myaccount'));
        exit;
    }
}

add_filter('woocommerce_before_account_orders', 'my_account_orders_query_filter');
function my_account_orders_query_filter($has_orders) {
    $current_user_id = get_current_user_id();

    $args = array(
        'meta_query' => array(
            array(
                'key'     => '_recipients_user_id',
                'value'   => sprintf('i:%d;', $current_user_id),
                'compare' => 'LIKE',
            )
        )
    );
    $orders = wc_get_orders($args);

    if (!empty($orders)) {
        echo '<p>Surveys received as gift:</p>';
        echo '<table class="shop_table shop_table_responsive my_account_orders">
            <thead>
                <tr>
                    <th>Surey ID</th>
                    <th>Survey</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($orders as $order) {
            $survey_type = $order->get_meta('_survey_type', true);
            foreach ($order->get_items() as $item_id => $item) {
                $product = $item->get_product();
                break;
            }
            if ($product) {
               $product_name = $product->get_name(); // Product name
               $product_link = get_permalink($product->get_id()); // Product permalink
               $survey_permalink = get_option('svc_questionnaire_permalink'); // Retrieve the saved permalink
               $product_id = $product->get_id(); // Replace with the actual method to get product ID
               $order_id = $order->get_id();
               echo '<tr>
                        <td>' . $order->get_order_number() . '</td>
                        <td><a href="' . esc_url($product_link) . '">' . esc_html($product_name) . '</a></td>
                        <td>';
               if($survey_type == 'duo') {
                   $recipients = $order->get_meta('_recipient_data');
                   $current_user = wp_get_current_user();
                   $recipients_to_show = [];
                   foreach ($recipients as $index => $recipient) {
                       if($recipient['email'] != $current_user->user_email) {
                           $recipients_to_show[] = $recipient;
                       }
                   }
                   $survey_purchaser_will_fill_the_survey = $order->get_meta('_survey_purchaser_will_fill_the_survey');
                   if($survey_purchaser_will_fill_the_survey) {
                       $recipients_to_show[] = [
                           'name'      => $order->get_billing_first_name().' '.$order->get_billing_last_name(),
                           'email'     => $order->get_billing_email(),
                           'status'    => $order->get_meta('_purchaser_survey_status', true),
                           'completed' => $order->get_meta('_purchaser_survey_completed', true) ?? 0,
                       ];
                   }
                   echo '<a href="' . esc_url(add_query_arg(array('product_id' => $product_id, 'order_id' => $order_id), $survey_permalink)) . '">Fill this survey</a>';
                   echo '<br><strong>Other Recipient:</strong><br>';
                   echo '<div class="recipient-details">';
                   foreach ($recipients_to_show as $recipient) {
                       echo '<p style="margin: 0px">';
                       echo '<strong>Name:</strong> ' . esc_html($recipient['name']) . '<br>';
                       echo '<strong>Email:</strong> ' . esc_html($recipient['email']). '<br>';
                       echo '<strong>Completed:</strong> ' . esc_html($recipient['completed'] ?? 0) . '%<br>';
                       echo '</p>';
                   }
                   echo '</div>';

               } else {
                   echo '<a href="' . esc_url(add_query_arg(array('product_id' => $product_id, 'order_id' => $order_id), $survey_permalink)) . '">Fill this survey</a>';
               }
               echo '</td>
               </tr>';
            }
        }

        echo '</tbody>
        </table>';
    }

    if ($has_orders) {
        echo '<p>Purchased Surveys:</p>';
    }
}

//front end orders page text changes
add_filter('gettext', 'custom_no_orders_text', 10, 3);
function custom_no_orders_text($translated_text, $text, $domain) {
    if ($text === 'No order has been made yet.' && $domain === 'woocommerce') {
        $translated_text = 'You haven’t purchased any survey yet.';
    }
    if ($text === 'Browse products' && $domain === 'woocommerce') {
        $translated_text = 'Explore surveys now!';
    }
    if ($text === 'Orders' && $domain === 'woocommerce') {
        $translated_text = 'Surveys';
    }
    return $translated_text;
}
