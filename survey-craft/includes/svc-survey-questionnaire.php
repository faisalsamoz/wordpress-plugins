<?php
function svc_create_questionnaire_page() {
    $page_title = 'Survey Questionnaire';
    $page = get_page_by_title($page_title);

    if (!$page) {
        // Create the page
        $page_id  = wp_insert_post(array(
            'post_title'    => $page_title,
            'post_content'  => '[svc_questionnaire]', // Add the shortcode
            'post_status'   => 'publish',
            'post_type'     => 'page',
        ));
        if($page_id) {
            $permalink = get_permalink($page_id);
            update_option('svc_questionnaire_permalink', $permalink);
            update_option('svc_questionnaire_page_id', $page_id);
        }
    }
}

function svc_render_questionnaire($atts) {
    ob_start();
    if(!isset($_GET['product_id']) || !isset($_GET['order_id'])) {
        wc_add_notice('Something went wrong!.', 'error');
    } else {
        // Fetch data from ACF fields and query parameters
        $product_id      = $_GET['product_id'];
        $order_id        = $_GET['order_id'];
        $current_user_id = get_current_user_id();

        // Fetch order meta data
        $order = wc_get_order($order_id);
        if (!$order) {
            wc_add_notice('Something went wrong!.', 'error');
        } else {
            $survey_found = false;
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product->get_id() == $product_id) {
                    $survey_found = true;
                    break;
                }
            }
            if (!$survey_found) {
                wc_add_notice('Survey not found!.', 'error');
            }

            $survey_status = $order->get_meta('_survey_status');
            $survey_type = $order->get_meta('_survey_type');
            $recipients_user_id = $order->get_meta('_recipients_user_id');

            $can_fill_survey = false;
            $is_survey_submitted = false;
            $filled_by_user = null;
            $message = "";


            if ($survey_type === 'single') {
                // Single type checks
                if($recipients_user_id) {
                    if (in_array($current_user_id, $recipients_user_id) && in_array($survey_status, ['Accepted', 'In Progress', 'Submitted'])) {
                        $can_fill_survey = true;
                        $filled_by_user = $current_user_id;
                    } else {
                        $can_fill_survey = false;
                    }
                } elseif($order->get_user_id() == $current_user_id) {
                    $can_fill_survey = true;
                    $filled_by_user = $current_user_id;
                } else {
                    $can_fill_survey = false;
                }
                if($survey_status == 'Submitted') {
                    $is_survey_submitted = true;
                }
            } elseif ($survey_type === 'duo') {
                // Duo type checks
                if(!$recipients_user_id)  {
                    $can_fill_survey = false;
                } else {
                    $survey_purchaser_will_fill_the_survey = $order->get_meta('_survey_purchaser_will_fill_the_survey');
                    if($survey_purchaser_will_fill_the_survey && $order->get_user_id() == $current_user_id) {
                        $can_fill_survey = true;
                        $filled_by_user = $current_user_id;
                        if($order->get_meta('_purchaser_survey_status', true) && $order->get_meta('_purchaser_survey_status', true) == 'Submitted') {
                            $is_survey_submitted = true;
                        }
                    } elseif (in_array($current_user_id, $recipients_user_id) && in_array($survey_status, ['Accepted', 'In Progress', 'Submitted'])) {
                        $can_fill_survey = true;
                        $filled_by_user = $current_user_id;
                        $recipients = $order->get_meta('_recipient_data');
                        $current_user = wp_get_current_user();
                        foreach ($recipients as $index => $recipient) {
                            if($recipient['email'] == $current_user->user_email) {
                                if($recipients[$index]['status'] ==  'Submitted') {
                                    $is_survey_submitted = true;
                                    break;
                                }
                            }
                        }
                    } else {
                        $can_fill_survey = false;
                    }
                }
            }

            // Display the form if the user is allowed to fill it
            if ($can_fill_survey) {
                if (have_rows('questionnaire', $product_id)) {
                    $saved_data = $order->get_meta( 'questionnaire_data');
                    if(isset($saved_data[$filled_by_user])) {
                        $saved_data = $saved_data[$filled_by_user];
                    }

                    echo '<form id="questionnaire-form" method="post" action="" enctype="multipart/form-data">';
                    echo '<input type="hidden" name="order_id" value="' . esc_attr($order_id) . '">';
                    echo '<input type="hidden" name="product_id" value="' . esc_attr($product_id) . '">';
                    echo '<input type="hidden" name="questionnaire_nonce" value="' . wp_create_nonce('save_questionnaire') . '">';

                    while (have_rows('questionnaire', $product_id)) {
                        the_row();

                        $question_title = trim(get_sub_field('question_title')); // Remove spaces from both ends
                        $sanitized_title = str_replace(' ', '_', strtolower(esc_attr($question_title))); // Replace internal spaces with underscores
                        $sanitized_title = preg_replace('/[^a-z0-9]+$/i', '', $sanitized_title); // Remove any special character at the end

                        echo '<div style="margin-bottom: 10px">';
                        if ($question_title) {
                            echo '<label>' . esc_html($question_title) . '</label><br>';
                        }

                        $question_type = get_sub_field('question_type');
                        $field_name = 'question_' . str_replace(' ', '_', strtolower(trim(esc_attr($question_title))));
                        $field_name = preg_replace('/[^a-z0-9]+$/i', '', $field_name); // Remove any special character at the end

                        $default_value = $saved_data[$field_name] ?? '';
                        switch ($question_type) {
                            case 'text':
                                echo '<input type="text" name="question_' .$sanitized_title. '" value="' . esc_attr($default_value) . '" ' . ($is_survey_submitted ? 'disabled' : '') . ' /> <br>';
                                break;
                            case 'file':
                                if ($default_value) {
                                    echo '<a href="' . esc_url($default_value) . '" target="_blank">View Uploaded File</a><br>';
                                    echo '<input type="hidden" name="question_'.$sanitized_title.'_existing"  value="' . esc_attr($default_value) . '">';
                                }
                                echo '<input type="file" accept="image/*" name="question_'.$sanitized_title.'" ' . ($is_survey_submitted ? 'disabled' : '') . ' /> <br>';
                                echo '<small>only image files allowed</small>';
                                break;
                            case 'textarea':
                                echo '<textarea name="question_'. $sanitized_title .'" ' . ($is_survey_submitted ? 'disabled' : '') . '>' . esc_attr($default_value) . '</textarea> <br>';
                                break;
                            case 'checkbox':
                                if (have_rows('question_options')) {
                                    while (have_rows('question_options')) {
                                        the_row();
                                        $option_value = get_sub_field('option_value');
                                        $checked = in_array($option_value, $default_value ?? []) ? 'checked' : '';
                                        echo '<label>';
                                        echo '<input type="' . esc_attr($question_type) . '" name="question_' . $sanitized_title . '[]" value="' . esc_attr($option_value) . '" ' . ($is_survey_submitted ? 'disabled' : '') . ' '.$checked.'  />';
                                        echo esc_html($option_value);
                                        echo '</label><br />';
                                    }
                                }
                                break;
                            case 'radio':
                                if (have_rows('question_options')) {
                                    while (have_rows('question_options')) {
                                        the_row();
                                        $option_value = get_sub_field('option_value');
                                        $checked = $option_value == $default_value ? 'checked' : '';
                                        echo '<label>';
                                        echo '<input type="' . esc_attr($question_type) . '" name="question_' . $sanitized_title . '" value="' . esc_attr($option_value) . '" ' . ($is_survey_submitted ? 'disabled' : '') . ' '. $checked .'  />';
                                        echo esc_html($option_value);
                                        echo '</label><br />';
                                    }
                                }
                                break;
                            case 'select':
                                if (have_rows('question_options')) {
                                    echo '<select name="question_' . $sanitized_title . '" ' . ($is_survey_submitted ? 'disabled' : '') . '>';
                                    echo '<option value="">Choose Option</option>';
                                    while (have_rows('question_options')) {
                                        the_row();
                                        $option_value = get_sub_field('option_value');
                                        echo '<option value="' . esc_attr($option_value) . '" ' . (esc_attr($option_value) == $default_value ? 'selected' : '') . '>' . esc_html($option_value) . '</option>';
                                    }
                                    echo '</select> <br>';
                                }
                                break;
                        }
                        echo  '</div>';
                    }
                    echo '<input type="hidden" id="survey_status" name="status" value="In Progress"  />';
                    if(!$is_survey_submitted) {
                        echo '<button type="button" id="surveySaveProgressBtn">Save Progress</button>';
                        echo '<button type="button" id="surveyCompleteBtn">Complete Survey</button>';
                    }
                    echo '</form>';
                } else {
                    echo '<p>No questionnaire available.</p>';
                }
            } else {
                if ($message != "") {
                    wc_add_notice($message, 'error');
                } else {
                    wc_add_notice('You are not allowed to fill this survey', 'error');
                }
            }
        }
    }
    wc_print_notices();
    return ob_get_clean();
}

add_shortcode('svc_questionnaire', 'svc_render_questionnaire');

add_action('wp_ajax_save_questionnaire', 'save_questionnaire');
add_action('wp_ajax_nopriv_save_questionnaire', 'save_questionnaire');

function save_questionnaire() {
    if (!isset($_POST['questionnaire_nonce']) || !wp_verify_nonce($_POST['questionnaire_nonce'], 'save_questionnaire')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }

    if (!isset($_POST['order_id']) || !is_numeric($_POST['order_id'])) {
        wp_send_json_error(['message' => 'Invalid order ID']);
    }
    if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
        wp_send_json_error(['message' => 'Invalid survey ID']);
    }

    if (!isset($_POST['status']) || !in_array($_POST['status'], ['In Progress', 'Submitted']) ) {
        wp_send_json_error(['message' => 'Invalid status']);
    }

    $order_id = intval($_POST['order_id']);
    $product_id = intval($_POST['product_id']);

    $order = wc_get_order($order_id);

    $survey_found = false;
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if ($product->get_id() == $product_id) {
            $survey_found = true;
            break;
        }
    }

    if (!$survey_found) {
        wp_send_json_error(['message' => 'Survey not found!.']);
    }

    $questionnaire_data = [];
    $filled_fields      = 0;
    $total_fields       = 0;

    // Process uploaded files
    if (!empty($_FILES)) {
        foreach ($_FILES as $key => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $file_type = mime_content_type($file['tmp_name']);
                $allowed_types = ['image/jpg', 'image/jpeg', 'image/png', 'image/gif'];

                if (in_array($file_type, $allowed_types)) {

                    $upload = wp_handle_upload($file, ['test_form' => false]);

                    if (!isset($upload['error'])) {
                        $questionnaire_data[$key] = $upload['url'];
                        $filled_fields++;
                    }
                } else {
                    $questionnaire_data[$key] = null;
                    error_log("Invalid file type uploaded: " . $file_type);
                }
            } elseif (isset($_POST[$key . '_existing'])) {
                $questionnaire_data[$key] = sanitize_text_field($_POST[$key. '_existing']);
                $filled_fields++;
            } else {
                $questionnaire_data[$key] = null;
            }
            $total_fields++;
        }
    }

    // Process text inputs and other data
    foreach ($_POST as $key => $value) {
        if ($key !== 'action' && $key !== 'order_id' && $key !== 'questionnaire_nonce' && $key !== 'product_id' && $key !== 'status') {
            if (is_array($value)) {
                $sanitized_values = array_map('sanitize_text_field', $value);
                $questionnaire_data[$key] = $sanitized_values;
                if (!empty($sanitized_values)) {
                    $filled_fields++;
                }
                $total_fields++;
            } else {
                if (strpos($key, '_existing') === false) {
                    $questionnaire_data[$key] = sanitize_text_field($value);
                    $filled_fields += !empty($value) ? 1 : 0;
                    $total_fields++;
                }
            }
        }
    }
    $completion_rate = ($total_fields > 0) ? round(($filled_fields / $total_fields) * 100, 2) : 0;


    $existing_data = $order->get_meta('questionnaire_data', true) ?: [];
    $current_user_id = get_current_user_id();
    if (isset($existing_data[$current_user_id])) {
        $existing_data[$current_user_id] = $questionnaire_data;
    } else {
        $existing_data[$current_user_id] = $questionnaire_data;
    }

    $survey_type = $order->get_meta('_survey_type');
    $status = $_POST['status'];

    if($survey_type == 'duo') {
        $users_submitted = 0;
        $current_user    = wp_get_current_user();
        $survey_purchaser_will_fill_the_survey = $order->get_meta('_survey_purchaser_will_fill_the_survey');
        if($survey_purchaser_will_fill_the_survey && $order->get_user_id() == $current_user_id) {
            $order->update_meta_data('_purchaser_survey_status', $status);
            if($status == 'Submitted') {
                $order->update_meta_data('_purchaser_survey_completed', 100);
            } else {
                $order->update_meta_data('_purchaser_survey_completed', $completion_rate);
            }
        } else {
            $recipients = $order->get_meta('_recipient_data');
            foreach ($recipients as $index => $recipient) {
                if($recipient['email'] == $current_user->user_email) {
                    $recipients[$index]['status'] = $status;
                    if($status == 'Submitted') {
                        $recipients[$index]['completed'] = 100;
                    } else {
                        $recipients[$index]['completed'] = $completion_rate;
                    }
                }
            }
            $order->update_meta_data('_recipient_data', $recipients);
        }
        if($survey_purchaser_will_fill_the_survey) {
            $purchaser_status = $order->get_meta('_purchaser_survey_status', true);
            if($purchaser_status == 'Submitted') {
                $users_submitted += 1;
            }
        }
        $recipients = $order->get_meta('_recipient_data');
        foreach ($recipients as  $recipient) {
            if($recipient['status'] == 'Submitted') {
                $users_submitted += 1;
            }
        }
        if($users_submitted == 2) {
            $subject = 'Your purchased survey '.$order_id.' is completed';
            $message = sprintf(
                "Dear %s %s, Your purchased survey #%s is completed.<br>We have successfully received survey responses and are now processing the final results.<br>You can expect the results to be shared with you within the next 48 hours.
                        If you have any questions or require further assistance, please don't hesitate to reach out.",
                esc_html($order->get_billing_first_name()),
                esc_html($order->get_billing_last_name()),
                esc_html($order_id)
            );

            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($order->get_billing_email(), $subject, $message, $headers);
            $order->update_meta_data('_survey_status', 'Submitted');
            $order->update_meta_data('_admin_survey_status', 'Survey completed');
        } else {
            $order->update_meta_data('_survey_status', 'In Progress');
        }
    }
    elseif($survey_type == 'single') {
        $order->update_meta_data('_survey_status', $status);
        $survey_recipient = $order->get_meta('_recipient_data');
        $recipient_count = is_array($survey_recipient) ? count($survey_recipient) : 0;
        if($recipient_count) {
            $current_user = wp_get_current_user();
            foreach ($survey_recipient as $index => $recipient) {
                if($recipient['email'] == $current_user->user_email) {
                    $survey_recipient[$index]['status'] = $status;
                    if($status == 'Submitted') {
                        $survey_recipient[$index]['completed'] = 100;
                    } else {
                        $survey_recipient[$index]['completed'] = $completion_rate;
                    }
                }
            }
            $order->update_meta_data('_recipient_data', $survey_recipient);
        } else {
            if($status == 'Submitted') {
                $order->update_meta_data('_purchaser_survey_completed', 100);
            } else {
                $order->update_meta_data('_purchaser_survey_completed', $completion_rate);
            }
        }
        if($status == 'Submitted') {
            $subject = 'Your purchased survey #'.$order_id.' is completed';
            $message = sprintf(
                "Dear %s %s, Your purchased survey #%s is completed.<br>We have successfully received survey responses and are now processing the final results.<br>You can expect the results to be shared with you within the next 48 hours.
                        If you have any questions or require further assistance, please don't hesitate to reach out.",
                esc_html($order->get_billing_first_name()),
                esc_html($order->get_billing_last_name()),
                esc_html($order_id)
            );

            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($order->get_billing_email(), $subject, $message, $headers);
            $order->update_meta_data('_admin_survey_status', 'Survey completed');
        }
    }
    $order->update_meta_data('questionnaire_data', $existing_data);
    $order->save();

    wp_send_json_success();
}