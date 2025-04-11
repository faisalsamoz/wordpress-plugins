<?php
function ipg_admin_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'ipg_posts_log';
//    $wpdb->query("TRUNCATE TABLE `$table_name`");
//    $wpdb->query("Delete from `$table_name` where id=59");


    // Pagination
    $items_per_page = 10;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $items_per_page;

    $logs = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $items_per_page,
            $offset
        )
    );

    $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_logs / $items_per_page);

    echo '<div class="wrap">';
    echo '<h1 style="margin-bottom: 10px">Image Post Generator Log</h1>';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><th>#</th><th>Image</th><th>Post</th><th>Input Tags</th><th>Status</th><th>Message</th><th>Created At</th><th>Action</th></tr></thead>';
    echo '<tbody>';

    if (!empty($logs)) {
        foreach ($logs as $index => $log) {
            $post_url = get_permalink($log->post_id);
            $index++;
            echo '<tr>';
            echo '<td>' . $index . '</td>';
            echo '<td><img src="'. esc_url($log->image_url) .'" style="width: 70px; height: 70px" /></td>';
            if($post_url) {
                echo "<td><a href='$post_url' target='_blank' class='mce-btn-small'>View Post</a></td>";
            } else {
                echo "<td><a href='#' class='mce-btn-small'>View Post</a></td>";
            }
            echo '<td>' . esc_html($log->tags) . '</td>';
            echo '<td>' . esc_html($log->status) . '</td>';
            echo '<td>' . esc_html($log->message) . '</td>';
            echo '<td>' . esc_html($log->created_at) . '</td>';
            if($log->status == 'error') {
              echo '<td><a href="' . esc_url(admin_url('admin.php?ipg_post_reschedule=1&log_id=').$log->id) . '">Regenerate</a></td>';
            } else {
                echo '<td>---</td>';
            }
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6" style="text-align: center">No logs found.</td></tr>';
    }

    echo '</tbody>';
    echo '</table>';

    echo '<div class="tablenav"><div class="tablenav-pages">';
    echo paginate_links([
        'base'    => add_query_arg('paged', '%#%'),
        'format'  => '',
        'current' => $current_page,
        'total'   => $total_pages,
    ]);
    echo '</div></div>';
    echo '</div>';
}

function ipg_generate_post_from_image_with_space() {
    global $wpdb;
    $image_url  = sanitize_text_field($_POST['image_url']);
    $input_tags = sanitize_text_field($_POST['input_tags']);
    $image_id  = sanitize_text_field($_POST['image_id']);
    $errors = [];
    $validated = true;

    if(empty($image_url)) {
        $errors[] = "Image is required";
        $validated = false;
    }
    if(empty($image_id)) {
        $errors[] = "Image is required";
        $validated = false;
    }
    if($validated) {
        $table_name = $wpdb->prefix . 'ipg_posts_log';
        $existing_data = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE media_id = %d and status = %s", $image_id, "success"));
        if($existing_data > 0) {
            $errors[] = "The post for this image has already been generated.";
            $validated = false;
        }
    }

    if ($validated) {
      
        if (!wp_next_scheduled('image_processing_in_background', array($image_url, $image_id, $input_tags))) {
            log_media_status($image_id, $image_url, 'processing',null, 'Processing', $input_tags);
            wp_schedule_single_event(time(), 'image_processing_in_background', array($image_url, $image_id, $input_tags));
            wp_send_json_success(['message' => 'The image is processing in the background, and your post will be created shortly. In the meantime, feel free to continue creating new posts.']);
        } else {
            wp_send_json_error(['error' => 'This image is already in processing']);
        }
    } else {
        wp_send_json_error(['errors' => $errors]);
    }
}
add_action('wp_ajax_generate_post_from_image', 'ipg_generate_post_from_image_with_space');

function image_processing_in_background($image_url, $image_id, $input_tags)
{
    // Step 1: Initialize cURL for the first API request
    $ch = curl_init();
    $token = "your_token";
    $url = "https://nickdigger-joy-caption-alpha-two-qa.hf.space/gradio_api/call/chat";
    if($input_tags != "") {
     $prompt = "Write a medium-length descriptive caption for this image in a casual tone and make sure you mention the image is about $input_tags. and add short title of image after title:";
    } else {
     $prompt = "Write a medium-length descriptive caption for this image in a casual tone. and add short title of image after title:";
    }
    $data = [
        "data" => [
            [
                "text" => "$prompt",
                "files" => [
                    [
                        "orig_name" => basename($image_url),
                        "path" => $image_url,
                        "meta" => [
                            "_type" => "gradio.FileData"
                        ]
                    ]
                ]
            ],
            null,
            0.6,
            0.9,
            1024,
            false
        ]
    ];
    

    // Convert the data array to JSON
    $jsonData = json_encode($data);

    // Configure cURL options for the first request
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $token",
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    // Execute the first request
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        log_media_status($image_id, $image_url, 'error' ,null ,'Error: ' . curl_error($ch), $input_tags);
        exit;
    }

    // Close the cURL session
    curl_close($ch);

    // Parse the response to extract EVENT_ID
    $responseData = json_decode($response, true);
    $eventId = $responseData['event_id'] ?? null;

    if (!$eventId) {
        log_media_status($image_id, $image_url, 'error',null,'Failed to extract EVENT_ID from the API response.', $input_tags);
        exit;
    }

    $streamUrl = $url ."/". $eventId;
    $ch = curl_init($streamUrl);
    $streamData = "";


    // Set cURL options for streaming
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $token",
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use(&$streamData) {
        // Print or handle the streaming data as needed
        $streamData .=$data;
        return strlen($data);
    });

    // Execute the streaming request
    curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        echo "Error: " . curl_error($ch);
    }


    $lines = explode("\n", trim($streamData));

    

    // Initialize variables to store events and data
    $parsedEvents = [];
    $currentEvent = null;

    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, 'event:') === 0) {
            // Capture the event type
            $currentEvent = substr($line, 6);
        } elseif (strpos($line, 'data:') === 0) {
            // Capture the data
            $data = substr($line, 5);
            if ($currentEvent) {
                // Store the event and associated data
                $parsedEvents[] = [
                    'event' => $currentEvent,
                    'data'  => json_decode($data, true) ?? $data, // Attempt to decode JSON
                ];
            }
        }
    }
    // Close the streaming request session
    curl_close($ch);
    $post_content = [];
    foreach ($parsedEvents as $entry) {
        // Ensure event key exists
        if (isset($entry['event'])) {
            $event = trim($entry['event']); // Trim spaces to avoid mismatch

            // Debug log to ensure the event type is being read correctly

            if ($event === 'complete') {

                // Extract data
                $blogContent = $entry['data'][0];
                $lines = preg_split('/\r\n|\r|\n/', $blogContent);

                // Extract title
                $title = '';
                $explicitTitle = false;

                // 1. Check for explicit title ("Title: ..." or "The image is titled: ...")
                foreach ($lines as $line) {
                    $line = trim($line, '" ');
                    
                    if (preg_match('/^(?:The image is titled:|Title:)\s*(.*)/i', $line, $matches)) {
                        $title = trim($matches[1]);
                        $explicitTitle = true;
                        break;
                    }
                }

                // 2. If no explicit title, check for a quoted title at the end of the description
                if (empty($title) && preg_match('/[“"]([^“”"]+)[”"]$/', $blogContent, $matches)) {
                    $title = trim($matches[1]);
                    $explicitTitle = true;
                }

                // 3. If still no title, use the first non-empty line as the title
                if (empty($title)) {
                    foreach ($lines as $line) {
                        $line = trim($line, '" ');
                        if (!empty($line)) {
                            $title = $line;
                            break;
                        }
                    }
                }

                // Limit title length
                $max_title_length = 70;
                if (strlen($title) > $max_title_length) {
                    $title = substr($title, 0, $max_title_length) . '...';
                }

                // Clean title (remove unnecessary characters like **bold**, quotes, and labels)
                $cleanedTitle = preg_replace('/(^“|\*\*.*?:\*\*|“|”|title:)/i', '', $title);

                // Remove extracted title from description
                $description = $blogContent;
                if ($explicitTitle) {
                    $description = preg_replace('/(?:^The image is titled:|^Title:)\s*' . preg_quote($title, '/') . '/i', '', $description);
                    $description = preg_replace('/[“"]' . preg_quote($title, '/') . '[”"]$/', '', $description);
                }

                // Further cleanup
                $description = preg_replace('/#\w+/', '', $description); // Remove hashtags
                $description = preg_replace('/\*\*/', '', $description); // Remove all '**' (bold markers)
                $description = str_replace('Meta Description:', "", $description);
                $description = trim($description);
                $descriptionHtml = '<p>' . str_replace("\n", '</p><p>', nl2br($description)) . '</p>';

                // Output the extracted data
                $post_content["title"] = $cleanedTitle;
                $post_content["description"] = $descriptionHtml;

                break;
            }
        } else {
            log_media_status($image_id, $image_url, 'error',null,'Error while creating post', $input_tags);
        }
    }
    
    if(!empty($post_content)) {
        $post_data = [
            'post_title'   => $post_content['title'],
            'post_content' => $post_content['description'],
            'post_status'  => 'publish',
            'post_type'    => 'post',
        ];
        $tags = generate_tags_for_image($image_url);
        $tags = array_merge($tags, explode(',',$input_tags));
        $post_id = wp_insert_post($post_data);

        if (!is_wp_error($post_id)) {
            // Add tags if the post was created successfully

            wp_set_post_tags($post_id, $tags);

            if ($image_id && get_post($image_id)) {
                set_post_thumbnail($post_id, $image_id);
                $rename_path  = rename_image_based_on_tags($image_id, $input_tags);
                if($rename_path) {
                    $image_url = $rename_path;
                }
            }

            // $input_tags_formated = preg_replace('/\s*-\s*/', '-', str_replace(',', '-', $input_tags));
            // Update the title
            // wp_update_post([
            //     'ID'         => $image_id,
            //     'post_title' => sanitize_text_field($input_tags_formated),
            // ]);

            // Update the alt tag (stored in meta)
            // update_post_meta($image_id, '_wp_attachment_image_alt', sanitize_text_field($input_tags_formated));
 

            // Log success
            log_media_status($image_id, $image_url, 'success', $post_id,"Post created successfully.", $input_tags);
            wp_send_json_success(['message' => 'Post created successfully']);
        } else {
            // Log error
            log_media_status($image_id, $image_url, 'error',null,"Error creating post: " . $post_id->get_error_message(), $input_tags);
            wp_send_json_success(['message' => 'Error while creating post. Try Again!']);
        }
    } else {
        log_media_status($image_id, $image_url, 'error',null, 'Data not generated by the model', $input_tags);
    }
}
add_action('image_processing_in_background', 'image_processing_in_background', 10, 3);

//rename file
function rename_image_based_on_tags($image_id, $input_tags) {

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $input_tags = $input_tags != "" ? explode(',', $input_tags) : [];
    $first_3_tags = array_slice($input_tags, 0, 3);
    $new_file_name = implode('-', $first_3_tags);
    $new_file_name = sanitize_title_with_dashes($new_file_name);
     
    $attachment = get_post($image_id);
    $current_file_path = get_attached_file($image_id);

    // Get the file directory and extension
    $file_info = pathinfo($current_file_path);
    $file_dir = $file_info['dirname'];
    $file_ext = $file_info['extension'];

    // Define the new filename (customize this as needed)
    $new_file_path = $file_dir . '/' . $new_file_name . '.' . $file_ext;


    
    // Rename the file
    if ($current_file_path !== $new_file_path && count($input_tags) > 0 && file_exists($current_file_path)) {
        // Get the attachment metadata
        $metadata = wp_get_attachment_metadata($image_id);

        // Delete old thumbnails and generated images
        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size => $size_info) {
                $old_thumbnail_path = $file_dir . '/' . $size_info['file'];
                if (file_exists($old_thumbnail_path)) {
                    unlink($old_thumbnail_path); // Delete the old thumbnail
                }
            }
        }

        rename($current_file_path, $new_file_path);

        // Update the attachment metadata
        update_attached_file($image_id, $new_file_path);

        // Regenerate attachment metadata (e.g., thumbnails)
        wp_generate_attachment_metadata($image_id, $new_file_path);

        $input_tags_formated = preg_replace('/\s*-\s*/', '-', implode('-',$input_tags));

        wp_update_post([
            'ID' => $image_id,
            'post_title' => sanitize_text_field($input_tags_formated),
        ]);

        update_post_meta($image_id, '_wp_attachment_image_alt', sanitize_text_field($input_tags_formated));
        return wp_get_attachment_url($image_id);
    }
    return null;
}


//genereta tags for image
function generate_tags_for_image($image_url) {
    // Step 1: Initialize cURL for the first API request
    $ch = curl_init();
    $token = "your_token";
    $url = "https://nickdigger-joy-caption-alpha-two-qa.hf.space/gradio_api/call/chat";

    $data = [
        "data" => [
            ["text" => "Write a short list of Booru tags for this image",
            "files" => [
                [
                    "orig_name" => basename($image_url),
                    "path" => $image_url,
                    "meta" => [
                        "_type" => "gradio.FileData"
                    ]
                ]
            ]
            ],
            null,
            0.6,
            0.9,
            1024,
            false
        ]
    ];

    // Convert the data array to JSON
    $jsonData = json_encode($data);

    // Configure cURL options for the first request
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $token",
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    // Execute the first request
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        return [];
    }

    // Close the cURL session
    curl_close($ch);

    // Parse the response to extract EVENT_ID
    $responseData = json_decode($response, true);
    $eventId = $responseData['event_id'] ?? null;

    if (!$eventId) {
        return  [];
    }
   

    $streamUrl = $url ."/". $eventId;
    $ch = curl_init($streamUrl);
    $streamData = "";


    // Set cURL options for streaming
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $token",
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use(&$streamData) {
        // Print or handle the streaming data as needed
        $streamData .=$data;
        return strlen($data);
    });

    // Execute the streaming request
    curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        echo "Error: " . curl_error($ch);
    }


    $lines = explode("\n", trim($streamData));
   

    // Initialize variables to store events and data
    $parsedEvents = [];
    $currentEvent = null;

    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, 'event:') === 0) {
            // Capture the event type
            $currentEvent = substr($line, 6);
        } elseif (strpos($line, 'data:') === 0) {
            // Capture the data
            $data = substr($line, 5);
            if ($currentEvent) {
                // Store the event and associated data
                $parsedEvents[] = [
                    'event' => $currentEvent,
                    'data'  => json_decode($data, true) ?? $data,
                ];
            }
        }
    }
    // Close the streaming request session
    curl_close($ch);
    $imageTags = [];
    foreach ($parsedEvents as $entry) {
        // Ensure event key exists
        if (isset($entry['event'])) {
            $event = trim($entry['event']);

            if ($event === 'complete') {
                // Extract data
                $imageTags = trim($entry['data'][0]);
                $imageTags = explode(',', $imageTags);
            }
        }
    }
    return $imageTags;
}



function log_media_status($media_id, $image_url, $status, $post_id = null, $message = null, $tags = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ipg_posts_log';
    $data = [
        'media_id'   => $media_id,
        'image_url'  => $image_url,
        'post_id'    => $post_id,
        'status'     => $status,
        'message'    => $message,
        'tags'       => $tags,
        'created_at' => current_time('mysql'),
    ];

    $format = [
        '%d',
        '%s',
        '%d',
        '%s',
        '%s',
        '%s',
        '%s',
    ];

    $where = [
        'media_id' => $media_id,
    ];

    $existing_data = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE media_id = %d", $media_id));
   if($existing_data > 0) {
       $wpdb->update($table_name, $data, $where, $format);
   } else {
       $wpdb->insert($table_name, $data, $format);
   }
}

// Add Meta Box for Image Post Generator Button
function add_image_post_generator_button($which) {
    $screen = get_current_screen();
    if($which == 'top' && $screen->id === 'edit-post') {
        $image_loader_url = plugin_dir_url(__FILE__) . '../assets/images/ipg_loader.gif';
        echo '<button type="button" id="image_post_generator_button" class="button">Image Post Generator</button>
        <div id="image_post_generator_popup" style="display:none; background: rgba(0, 0, 0, 0.8); position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; justify-content: center; align-items: center;">
            <div class="image_post_generator_popup_box" style="background-color: white; padding: 20px; border-radius: 5px;">
                <h2>Hugging Face API</h2>
                <button type="button" id="openImagePopup" class="button">Choose Image</button>
                <div id="selectedImageContainer" style="margin-top: 20px;"></div>
                <div class="form-group input-tags-group" style="display: none">
                <label>Image Tags (<small>comma seprated</small>)</label> <br>
                <input type="text" name="input_tags" id="input_tags"  />
                </div>
                <ul class="errors"></ul>
                <p class="ipg-message" style="display: none; margin-top: 5px; margin-bottom: 5px">Please wait while your post is being submitted for processing.</p>
                <div style="display:flex; justify-content: end; width: 100%; align-items: center; gap: 10px; margin-top: 10px">
                  <img id="ipg_post_loader" src="'.$image_loader_url.'" /> 
                  <button type="button" id="generatePostButton" class="button" style="display: none;">Generate Post</button>
                  <button type="button" id="close_popup_button" class="button">Cancel</button>
                </div>
            </div>
        </div>
    ';
    }
}
add_action('manage_posts_extra_tablenav', 'add_image_post_generator_button');


function get_ipg_post_tags($post_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'ipg_posts_log';
    $result = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT tags FROM $table_name WHERE post_id = %d",
            $post_id
        )
    );
    

    $tags = !empty($result) && isset($result->tags) ? explode(',', $result->tags) : [];
    $tag_links = [];

    if (!empty($tags)) {
        foreach ($tags as $tag) {
            $tag = trim($tag);
            $term = get_term_by('name', $tag, 'post_tag');
            if ($term) {
                $tag_links[$tag] = $term ? get_term_link($term) : "#";
            }
        }
    }
    return $tag_links;
}

//reschedule
add_action('admin_init', function () {
    if (isset($_GET['ipg_post_reschedule']) && isset($_GET['log_id'])) {
        global $wpdb;
         $log_id = $_GET['log_id'];
         $redirect_url = admin_url('admin.php?page=ipg-post-generator');
         $table_name = $wpdb->prefix . 'ipg_posts_log';
         $existing_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $log_id));
         if($existing_data->status == 'error') {
            $image_url = $existing_data->image_url;
            $image_id = $existing_data->media_id;
            $input_tags = $existing_data->tags;
            if (!wp_next_scheduled('image_processing_in_background', array($image_url, $image_id, $input_tags))) {
                log_media_status($image_id, $image_url, 'processing',null, 'Processing', $input_tags);
                wp_schedule_single_event(time(), 'image_processing_in_background', array($image_url, $image_id, $input_tags));
                set_transient('ipg_process_message_success', 'The image processing has been successfully rescheduled.', 30);
            } else {
                set_transient('ipg_process_message_error', 'This image is already in processing.', 30);
            }
         } else {
            set_transient('ipg_process_message_error', 'Post already created for this image.', 30);
         }
        wp_redirect($redirect_url);
        exit;
    }
});

add_action('admin_notices', function () {
    if ($message = get_transient('ipg_process_message_success')) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        delete_transient('ipg_process_message_success');
    }
    if ($message = get_transient('ipg_process_message_error')) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($message) . '</p></div>';
        delete_transient('ipg_process_message_error');
    }
});

