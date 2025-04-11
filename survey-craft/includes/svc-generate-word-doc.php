<?php

add_action('wp_ajax_download_order_word_doc', 'download_order_word_doc');
add_action('wp_ajax_nopriv_download_order_word_doc', 'download_order_word_doc');
function download_order_word_doc() {
    // Get the order ID from the POST request
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

    // Retrieve the order object
    $order = wc_get_order($order_id);
    $questionnaire_data = $order->get_meta('questionnaire_data'); // Assuming it's stored as an array

    // Check if the order exists
    if (!$order) {
        wp_send_json_error('Order not found');
        exit;
    }

    // Create a new PHPWord document
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $section = $phpWord->addSection();


    // Add the product item name and details
    $items = $order->get_items();
    $options = [
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $imagePaths= [];

    foreach ($items as $item) {

        $product = wc_get_product( $item->get_product_id());

        // Get the desired information
        $product_name = $product->get_name();
        $product_summary = $product->get_short_description();
        $product_image_id = $product->get_image_id();


        $product_image_url = $product_image_id ? wp_get_attachment_url($product_image_id) : null;

        $section->addText($product_name, ['bold' => true, 'size' => 16]);
        $section->addTextBreak();

        if ($product_summary) {
            $section->addText( ucfirst($product_summary), ['size' => 12]);
        }
        $section->addTextBreak();

        if ($product_image_url) {
            $imageContent = file_get_contents($product_image_url, false, $context);
            if($imageContent) {
                $tempImagePath = tempnam(sys_get_temp_dir(), 'image') . '.jpg';
                file_put_contents($tempImagePath, $imageContent);
                try {
                    $section->addImage($tempImagePath, [
                        'width' => 300,
                        'height' => 200,
                        'wrappingStyle' => 'behind',
                        'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
                    ]);
                    $imagePaths[] = $tempImagePath;
                } catch (Exception $e) {
                    $section->addText('Answer: Image could not be added from URL.', ['size' => 10, 'color' => 'red']);
                }
            }
        }
        $section->addTextBreak();
    }

    // Add customer name and email
    $section->addText('Purchaser Detail:', ['size' => 12, 'bold' => true]);
    $section->addText('Name: ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
    $section->addText('Email: ' . $order->get_billing_email());
    $section->addText('Date: ' . date('Y-m-d', strtotime($order->get_date_created())));
    $section->addTextBreak();

    // Add a heading for questionnaire data
    $section->addText('Questionnaire Data', ['size' => 12, 'bold' => true]);
    $section->addTextBreak();


    // Loop through questionnaire data
    if (is_array($questionnaire_data) && !empty($questionnaire_data)) {
        foreach ($questionnaire_data as $user_id => $answers) {
            $user_info = get_userdata($user_id);
            $user_name = $user_info ? $user_info->display_name : 'Unknown User';


            $section->addText("Submitted By: $user_name", ['italic' => true, 'bold' => true]);
            $section->addTextBreak();

            foreach ($answers as $question => $answer) {
                $section->addText('Question: '.ucfirst(str_replace('_', ' ', $question)), ['size' => 10, 'bold' => true]);
                $section->addTextBreak();
                if(is_array($answer) && count($answer)) {
                    $section->addText('Answer: ' . implode(', ', $answer) , ['size' => 10]);
                } elseif (!empty($answer) && filter_var($answer, FILTER_VALIDATE_URL)) {
                    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                    $extension = pathinfo(parse_url($answer, PHP_URL_PATH), PATHINFO_EXTENSION);

                    if (in_array(strtolower($extension), $imageExtensions)) {
                        $imageContent = file_get_contents($answer, false, $context);
                        if($imageContent) {
                            $tempImagePath = tempnam(sys_get_temp_dir(), 'image') . '.jpg';
                            file_put_contents($tempImagePath, $imageContent);
                            try {
                                $section->addImage($tempImagePath, [
                                    'width' => 300,
                                    'height' => 200,
                                    'wrappingStyle' => 'behind'
                                ]);
                                $imagePaths[] = $tempImagePath;
                            } catch (Exception $e) {
                                $section->addText('Answer: Image could not be added from URL.', ['size' => 10, 'color' => 'red']);
                            }
                        }

                    }
                } else {
                    $section->addText(!empty($answer) ? 'Answer: ' . $answer : 'Answer: No answer provided', ['size' => 10]);
                }
                $section->addTextBreak();
            }

            $section->addTextBreak(); // Add spacing between users
        }
    } else {
        $section->addText('No questionnaire data available.', ['size' => 10, 'bold' => true]);
    }

    // Create a temporary file in the system's temp directory
    $tempDir = sys_get_temp_dir();
    $filePath = tempnam($tempDir, 'order_' . $order_id . '_') . '.docx';

    // Create a Word 2007 document writer
    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($filePath);

    foreach($imagePaths as $path) {
        unlink($path);
    }

    // Check if the file was created successfully
    if (!file_exists($filePath)) {
        wp_send_json_error('Error creating the Word document');
        exit;
    }

    // Read the file content into a variable
    $fileContent = file_get_contents($filePath);
    if ($fileContent === false) {
        wp_send_json_error('Error reading the file');
        exit;
    }

    // Encode the file content to base64
    $base64Content = base64_encode($fileContent);


    // Unlink (delete) the temporary file after reading
    unlink($filePath);

    // Return the base64-encoded string as JSON response
    wp_send_json_success(['fileContent' => $base64Content, 'filename' => 'order_' . $order_id . '.docx']);
}