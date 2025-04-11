<?php
/*
* Plugin Name: Custom Recipient Mails
* Description: Custom Recipient Mails
* Version: 1.0
* Author: Codeavour
*/


if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . '/lib/vendor/autoload.php';

//Profile Builder Save Membership Totals
add_action('wppb_save_form_field', function($field, $user_id, $global_request, $form_type) {
    $dynamic_fields = ['membership_fee', 'chapter_fee', 'membership_tax', 'membership_total'];
    foreach ($dynamic_fields as $field_name) {
        if (isset($global_request[$field_name])) {
            $value = sanitize_text_field($global_request[$field_name]);
            update_user_meta($user_id, $field_name, $value);
        }
    }
    if(isset($global_request['province_bm']) && $global_request['province_bm'] == 'Other') {
        $existing_user_chapters = get_user_meta($user_id, 'user_chapters', true);
        $chapter_id = '22464';
        if ($existing_user_chapters) {
            // User meta exists, append the new chapter ID if it doesn't already exist
            if (!in_array($chapter_id, $existing_user_chapters)) {
                $existing_user_chapters[] = $chapter_id;
                update_user_meta($user_id, 'user_chapters', $existing_user_chapters);
            }
        } else {
            // User meta doesn't exist, create it with the new chapter ID
            $new_user_chapters = array($chapter_id);
            add_user_meta($user_id, 'user_chapters', $new_user_chapters);
        }
    } elseif(isset($global_request['subscription_plans'])) {
        $subcription_plan = $global_request['subscription_plans'];
        $chapter_id = get_post_meta($subcription_plan, 'assign_chapter', true);
        $existing_user_chapters = get_user_meta($user_id, 'user_chapters', true);
        if ($existing_user_chapters) {
            // User meta exists, append the new chapter ID if it doesn't already exist
            if (!in_array($chapter_id, $existing_user_chapters)) {
                $existing_user_chapters[] = $chapter_id;
                update_user_meta($user_id, 'user_chapters', $existing_user_chapters);
            }
        } else {
            // User meta doesn't exist, create it with the new chapter ID
            $new_user_chapters = array($chapter_id);
            add_user_meta($user_id, 'user_chapters', $new_user_chapters);
        }
    }
}, 10, 4);

//mmebership mail after payment
//add_action( 'pms_member_subscription_update', 'pmsc_run_actions_on_member_subscription_update', 20, 3 );
add_action( 'pms_after_checkout_is_processed', 'pmsc_run_actions_on_member_subscription_update', 20, 3 );
function pmsc_run_actions_on_member_subscription_update( $old_data, $form_location){
    $user_id = $old_data->user_id;
    if ( $form_location == 'register' || $form_location == 'new_subscription' ||  $form_location == 'renew_subscription'  ||  $form_location == 'retry_payment' ){
        $user = get_userdata($user_id);
        $recipient_email = $user->user_email ?? '';
        $user_name = $user->first_name . ' ' . $user->last_name ?? '';
        $user_position = get_user_meta($user_id, 'position', true);
        $department = get_user_meta($user_id, 'department', true);
        $healthcare_authority = get_user_meta($user_id, 'healthcare_authority', true);
        $user_address = get_user_meta($user_id, 'custom_address', true);
        $user_city = get_user_meta($user_id, 'city_bm', true);
        $user_province = get_user_meta($user_id, 'province_bm', true);
        $user_province = $user_province == 'Other' ? '' : $user_province;
        $user_postal = get_user_meta($user_id, 'postalcode_bm', true);
        $user_country = get_user_meta($user_id, 'country_bm', true);
//        $national_fee = get_user_meta($user_id, 'membership_fee', true) ?? '0.00';
//        $chapter_fee = get_user_meta($user_id, 'chapter_fee', true) ?? '0.00';
//        $membership_tax = get_user_meta($user_id, 'membership_tax', true) ?? '0.00';
//        $membership_total = get_user_meta($user_id, 'membership_total', true) ?? '0.00';
        $current_unique_id = get_user_meta($user_id, 'unique_member_id', true);

        $plan_description = get_post_meta($old_data->subscription_plan_id, 'pms_subscription_plan_description', true) ?? '';
        $membership_total = get_post_meta($old_data->subscription_plan_id, 'pms_subscription_plan_price', true) ?? 0;

        $matches = [];
        preg_match('/\$(\d+)\s*National Fee/', $plan_description, $matches);
        $national_fee = $matches[1] ?? 0;

        preg_match('/\$(\d+)\s*Chapter Fee/', $plan_description, $matches);
        $chapter_fee = $matches[1] ?? 0;

        $membership_tax = $membership_total - ($national_fee + $chapter_fee);

        $expiry_date = $old_data->expiration_date;
        $plan = pms_get_subscription_plan($old_data->subscription_plan_id);
        $plan_name = $plan->name;

        $args = array(
            'order'   => 'DESC',
            'orderby' => 'id',
            'user_id' => $user_id,
            'subscription_plan_id' => $old_data->subscription_plan_id,
        );

        $payments = pms_get_payments($args);
        $transaction_id = null;

        if(!empty($payments)) {
            foreach ($payments as $payment) {
                if($payment->status == 'completed') {
                    $transaction_id = $payment->transaction_id;
                }
            }
        }
        $category = get_post_meta($old_data->subscription_plan_id, 'membership', true) ?? '';

        if (!empty($recipient_email)) {
            $mail_data = [
                'recipient_email' => $recipient_email,
                'national_fee' => $national_fee,
                'chapter_fee' => $chapter_fee,
                'membership_tax' => $membership_tax,
                'membership_total' => $membership_total,
                'user_name' => $user_name,
                'user_position' => $user_position,
                'department' => $department,
                'healthcare_authority' => $healthcare_authority,
                'user_address' => $user_address,
                'user_city' => $user_city,
                'user_province' => $user_province,
                'user_postal' => $user_postal,
                'user_country' => $user_country,
                'current_unique_id' => $current_unique_id,
                'expiry_date' => $expiry_date,
                'plan_name' => $plan_name,
                'transaction_id' => $transaction_id,
                'category' => $category
            ];
            send_member_registration_mail($mail_data);
        } else {
            error_log("Email address not found in form data.");
        }
    }
}

//memebership mail without payment
add_action('wppb_register_success', 'send_membership_details_email', 10, 3);

function send_membership_details_email($http_request, $form_data, $user_id) {

    $recipient_email = $http_request['email'] ?? '';
    $national_fee = $http_request['membership_fee'] ?? '0.00';
    $chapter_fee = $http_request['chapter_fee'] ?? '0.00';
    $membership_tax = $http_request['membership_tax'] ?? '0.00';
    $membership_total = $http_request['membership_total'] ?? '0.00';
    $user_name = $http_request['first_name'] ?? ''. $http_request['last_name'] ?? '';
    $user_position = $http_request['position'] ?? '';
    $department = $http_request['department'] ?? '';
    $healthcare_authority = $http_request['healthcare_authority'] ?? '';
    $user_address = $http_request['custom_address'] ?? '';
    $user_city = $http_request['city_bm'] ?? '';
    $user_province = $http_request['province_bm'] ?? '';
    $user_province = $user_province == 'Other' ? '' : $user_province;
    $user_postal = $http_request['postalcode_bm'] ?? '';
    $user_country = $http_request['country_bm'] ?? '';
    $current_unique_id = get_user_meta($user_id, 'unique_member_id', true);

    $subscribed_plan = $http_request['subscription_plans'] ?? 0;
    $expiry_date = '';
    $plan_name = '';

    $subscriptions = pms_get_member_subscriptions(['user_id' => $user_id]);

    if (!empty($subscriptions)) {
        foreach ($subscriptions as $subscription) {
            $plan_id = $subscription->subscription_plan_id;
            if($plan_id == $subscribed_plan) {
                $expiry_date = $subscription->expiration_date;
                $plan = pms_get_subscription_plan($plan_id);
                $plan_name = $plan->name;
                break;
            }
        }
    }

    $category = get_post_meta($subscribed_plan, 'membership', true) ?? '';


    if (!empty($recipient_email)) {
        // Prepare email content
        $mail_data = [
            'recipient_email' => $recipient_email,
            'national_fee' => $national_fee,
            'chapter_fee' => $chapter_fee,
            'membership_tax' => $membership_tax,
            'membership_total' => $membership_total,
            'user_name' => $user_name,
            'user_position' => $user_position,
            'department' => $department,
            'healthcare_authority' => $healthcare_authority,
            'user_address' => $user_address,
            'user_city' => $user_city,
            'user_province' => $user_province,
            'user_postal' => $user_postal,
            'user_country' => $user_country,
            'current_unique_id' => $current_unique_id,
            'expiry_date' => $expiry_date,
            'plan_name' => $plan_name,
            'category' => $category
        ];

        send_member_registration_mail($mail_data);
        // Optionally log for debugging
        error_log("Email sent to $recipient_email with membership details.");
    } else {
        error_log("Email address not found in form data.");
    }
}

function send_member_registration_mail($data)
{
    $recipient_email = $data['recipient_email'];
    $national_fee = (float) $data['national_fee'];
    $chapter_fee = (float) $data['chapter_fee'];
    $membership_tax = (float) $data['membership_tax'];
    $membership_total = (float) $data['membership_total'];
    $user_name = $data['user_name'];
    $user_position = $data['user_position'];
    $department = $data['department'];
    $healthcare_authority = $data['healthcare_authority'];
    $user_address = $data['user_address'];
    $user_city = $data['user_city'];
    $user_province = $data['user_province'];
    $user_postal = $data['user_postal'];
    $user_country = $data['user_country'];
    $current_unique_id = $data['current_unique_id'];
    $expiry_date = $data['expiry_date'];
    $plan_name = $data['plan_name'];
    $transaction_id = $data['transaction_id'] ?? null;
    $category = $data['category'] ?? null;

    $tax_percentage = 0;
    if($national_fee > 0) {
        $tax_percentage = ($membership_tax / ($national_fee + $chapter_fee )) * 100;
        $tax_percentage = number_format($tax_percentage, 2, '.', '');
    }

    $subject = 'Your Membership Details / Détails de votre adhésion';
    $headers = array('Content-Type: text/html; charset=UTF-8');

    $logo_url = plugins_url('assets/img/membership_mail_logo.png', __FILE__);

    $options = [
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $logo_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logo_url, false, $context));

    ob_start();
    include plugin_dir_path(__FILE__) . 'recepient-mail-parts/header-member-subscription.php';
    $email_header = ob_get_clean();

    ob_start();
    include plugin_dir_path(__FILE__) . 'recepient-mail-parts/footer-member-subscription.php';
    $email_footer = ob_get_clean();

    $timestamp = time();

    $fmt_en = new IntlDateFormatter('en_US', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
    $fmt_en->setPattern('MMMM dd, yyyy');
    $date_en = $fmt_en->format($timestamp);

    $fmt_fr = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
    $fmt_fr->setPattern('d MMMM yyyy');
    $date_fr = $fmt_fr->format($timestamp);

    $body = '
					<td style="width:100%; padding:0pt 5.4pt; vertical-align:top">
					
						<p style="margin-right:15.45pt; margin-left:12.6pt">
							&#xa0;
						</p>
						<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">'.$date_en.' / '.$date_fr.'</span>
						</p>
						<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">&#xa0;</span>
						</p>
						<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">'.$user_name.'</span>
						</p>
						<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">'.$user_position.'</span>
						</p>
						<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">'.$department.'</span>
						</p>
						<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">'.$healthcare_authority.'</span>
							<br /><span style="font-family:Arial">'.$user_address.'</span>
						</p>
						<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">'.$user_city.' '.$user_province.'</span><span style="font-family:Arial">&#xa0; </span><span style="font-family:Arial">'.$user_postal.' '.$user_country.'</span>
						</p>
						<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">&#xa0;</span>
						</p>
						<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">Email / Courriel: <a href="mailto:'.$recipient_email.'">'.$recipient_email.'</a></span>
						</p>
						<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">Membership Expiry / Expiration de l\'adhésion: '.date('m/d/Y', strtotime($expiry_date)).'</span>
						</p>
						<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">Membership Category / Catégorie de membre: '.$category.'</span>
						</p>
						
						<p style="margin-right:15.45pt; margin-left:12.6pt; text-align:center">
							<span style="font-family:"Arial"">&#xa0;</span>
						</p>
						<div style="margin-right:15.45pt; margin-left:12.6pt;">
							<h5 style="text-align:left; text-decoration:none; margin: 0px">
								<span style="font-family:Arial">'.(isset($transaction_id) && $transaction_id != null ? 'Receipt for': 'Invoice for').' '.date('Y').'-'.date('Y', strtotime('+1 year', strtotime(date('Y-m-d')))).' Membership Fees</span> /
								<span style="font-family:Arial">'.(isset($transaction_id) && $transaction_id != null ? 'Reçu pour': 'Facture pour').' les frais d\'adhésion '.date('Y').'-'.date('Y', strtotime('+1 year', strtotime(date('Y-m-d')))).'</span>
							</h5>
						</div>
						<p style="margin-right:15.45pt; margin-left:12.6pt; text-align:center; font-size:14pt">
							<span style="font-family:"Arial"">&#xa0;</span>
						</p>
						
						<table style="width:100%; border: none; border-collapse: collapse; margin-left:12pt; margin-top:10pt">
							<tr>
								<td style="width: 70%; font-family: Arial; font-size: 10pt;">National Dues / Cotisation nationale:</td>
								<td style="width: 30%; font-family: Arial; font-size: 10pt;">$ '.  number_format($national_fee, 2, '.', '') .'</td>
							</tr>
							<tr>
								<td colspan="2" style="height: 10px;"></td> <!-- Empty row for spacing -->
							</tr>
							<tr>
								<td style="width: 70%; font-family: Arial; font-size: 10pt;">Provincial Dues / Cotisation provinciale:</td>
								<td style="width: 30%; font-family: Arial; font-size: 10pt; text-decoration: underline;">$ '. number_format($chapter_fee, 2, '.', '')  .'</td>
							</tr>
							<tr>
								<td colspan="2" style="height: 10px;"></td> <!-- Empty row for spacing -->
							</tr>
							<tr>
								<td style="width: 70%; font-family: Arial; font-size: 10pt;">Subtotal / Sous-total:</td>
								<td style="width: 30%; font-family: Arial; font-size: 10pt;">$ '. number_format($chapter_fee + $national_fee, 2, '.', ''). '</td>
							</tr>
							<tr>
								<td colspan="2" style="height: 10px;"></td> <!-- Empty row for spacing -->
							</tr>
							<tr>
								<td style="width: 70%; font-family: Arial; font-size: 10pt;">Tax / Impôt: <br>
                                  GST/TPS (118833193RT0001): <br>
                                  QST/TVQ (1218421241TQ0001):
                                </td>
								<td style="width: 30%; font-family: Arial; font-size: 10pt;text-decoration: underline;">$ '.  number_format($membership_tax, 2, '.', '') .'</td>
							</tr>
							<tr>
								<td colspan="2" style="height: 10px;"></td> <!-- Empty row for spacing -->
							</tr>
							';

//    if(isset($transaction_id) && $transaction_id != null) {
//        $body .= '<tr>
//                                             <td style="width: 70%; font-family: Arial; font-size: 10pt;">Total Paid / Paiement reçu on ' . date('m/d/Y') . ':
//                                                            (Stripe ' . $transaction_id . ')</td>
//                                             <td style="width: 30%; font-family: Arial; font-size: 10pt;">$ ' . number_format($membership_total, 2, '.', '')  . '</td>
//                                        </tr>
//                                        <tr>
//                                              <td colspan="2" style="height: 10px;"></td> <!-- Empty row for spacing -->
//                                         </tr>';
//    }
    $body.='
							<tr>
								<td style="width: 70%; font-family: Arial; font-size: 10pt; font-weight: bold;">TOTAL AMOUNT  / MONTANT TOTAL:</td>
								<td style="width: 30%; font-family: Arial; font-size: 10pt; font-weight: bold;">$ ' . number_format($membership_total, 2, '.', '')  .'</td>
							</tr>
					    </table>

						<p style="margin-top:40pt">
							<span style="font-family: Arial"></span>
						</p>
						<p style="margin-right:36pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">Taxes are applicable to all dues as follows / Les taxes s\'appliquent à toutes les cotisations comme suit:</span>
						</p>
                        <p style="margin-right:36pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">BC, AB, SK, MB, YT, NT, NU: 5% GST/TPS 5 %</span>
						</p>
                        <p style="margin-right:36pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">ON: 13% HST/TVH 13 %</span>
						</p>
                        <p style="margin-right:36pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">QC: 5% GST & 9.975% QST/TPS 5 % et TVQ 9,975 %</span>
						</p>
                        <p style="margin-right:36pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">NB, NS, PE, NL: 15% HST/TVH 15 %</span>
						</p>
						
						<p>
							&#xa0;
						</p>
					</td>
		';

    $message = $email_header . $body . $email_footer;

    error_log($message);

    $pdf_content = str_replace('{{PLACEHOLDER_SRC}}', $logo_base64, $message);

    $options = new \Dompdf\Options();

    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);

    $dompdf = new \Dompdf\Dompdf($options);


    $dompdf->loadHtml($pdf_content);

    // Set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');

    // Render the PDF
    $dompdf->render();

    $upload_dir = wp_upload_dir();
    $pdf_file_path = $upload_dir['basedir'] . '/member-subscription-recipient-'.$current_unique_id.'generated.pdf';

    file_put_contents($pdf_file_path, $dompdf->output());

    $attachments = array($pdf_file_path);

    $mail_content = str_replace('{{PLACEHOLDER_SRC}}', $logo_url, $message);

    wp_mail($recipient_email, $subject, $mail_content, $headers, $attachments);

    unlink($pdf_file_path);
}

//Add Attachment to Gravity Form
add_filter( 'gform_pre_send_email', function( $email, $message_format, $notification, $entry ) {
    // Check if the form ID is 6 and the notification name is 'User Notification'
    if ($notification['name'] == 'User Notification' ) {
        $pdf_path = null;
        if($entry['form_id'] == 21) {
            //Sponsorship Contract Gravity Form 21
            $pdf_path =  process_entry_by_field_name_21($entry);
        }

        if($entry['form_id'] == 2) {
            //webinar  Gravity Form 2 (guest user)
            $pdf_path =  process_entry_by_field_name_2($entry);
        }

        if($entry['form_id'] == 3) {
            //webinar  Gravity Form 2 (logined user)
            $pdf_path =  process_entry_by_field_name_3($entry);
        }

        if($entry['form_id'] == 18) {
            //CanHCC  Gravity Form 18
            $pdf_path =  process_entry_by_field_name_18($entry);
        }

        if($entry['form_id'] == 16) {
            //Confrence Registration Form 16
            $pdf_path =  process_entry_by_field_name_16($entry);
        }

        if($entry['form_id'] == 23) {
            //Manitoba Exhibit Contract Gravity Form 23
            $pdf_path =  process_entry_by_field_name_23($entry);
        }

        if ( $pdf_path !=null && file_exists( $pdf_path ) ) {
            $email['attachments'][] = $pdf_path;
        }
    }

    return $email;
}, 10, 4 );

//Sponsorship Contract Gravity Form 21
function process_entry_by_field_name_21( $entry) {
    $form = GFAPI::get_form( $entry['form_id'] );
    $field_data = [];
    $tax_rate = 15;
    foreach ( $form['fields'] as $field ) {
        if ( isset( $field->label ) ) {
            $field_name = sanitize_title( $field->label );
            if ( $field->type === 'address' ) {
                if ( isset( $field->inputs ) && is_array( $field->inputs ) ) {
                    foreach ( $field->inputs as $input ) {
                        $input_id = (string) $input['id'];
                        $label = sanitize_title( $input['label'] );
                        $value = rgar( $entry, $input_id );

                        if ( ! empty( $value ) ) {
                            $field_data[ $field_name . '_' . $label ] = $value;
                        }
                    }
                }
            } else {
                if ( isset( $entry[ $field->id ] ) ) {
                    $field_data[ $field_name ] = rgar( $entry, $field->id );
                }
            }
        }

        if ( strpos( $field->cssClass, 'form-total' ) !== false ) {
            preg_match( '/tax-(\d+)/', $field->cssClass, $matches );

            if ( !empty($matches) && isset($matches[1]) ) {
                $tax_rate = $matches[1];
            }
        }
    }


    $entry_id = $entry['id'];
    $email_data = [];
    $email_data['items_info'] = gform_get_meta( $entry_id, 'gform_product_info__');
    $email_data['title'] = $form['title'] ?? '';
    $email_data['first_name'] = $field_data['first-name'] ?? '';
    $email_data['last_name'] = $field_data['last-name'] ?? '';
    $email_data['email'] = $field_data['e-mail-address'] ?? '';
    $email_data['position_title'] = $field_data['position-title'] ?? '';
    $email_data['company_origination'] = $field_data['company-origination'] ?? '';
    $email_data['total'] = $field_data['total'] ?? 0;
    $email_data['payment_method'] = $field_data['payment_method'] ?? '';
    $email_data['transaction_id'] = $entry['transaction_id'] ?? '';
    $email_data['address_street'] = $field_data['address_street-address'] ?? '';
    $email_data['address_city'] = $field_data['address_city'] ?? '';
    $email_data['address_state'] = $field_data['address_state-province'] ?? '';
    $email_data['address_postal'] = $field_data['address_zip-postal-code'] ?? '';
    $email_data['address_country'] = $field_data['address_country'] ?? '';
    $email_data['tax_amount'] = 0;
    $email_data['tax_rate'] = $tax_rate;

    if ($tax_rate > 0) {
        $originalAmount = $email_data['total'] / (1 + ($tax_rate / 100));
        $email_data['tax_amount'] = $email_data['total'] - $originalAmount;
    }

    if(!empty($email_data['email']) && $email_data['total'] != 0 && !empty($email_data['items_info'])) {
        return generate_recepit_pdf($email_data);
    }
    return null;
}

//webinar  Gravity Form 2  (guest user)
function process_entry_by_field_name_2( $entry) {
    $form = GFAPI::get_form( $entry['form_id'] );
    $field_data = [];
    $tax_rate = 15;
    foreach ( $form['fields'] as $field ) {
        if ( isset( $field->label ) ) {
            $field_name = sanitize_title( $field->label );
            if ( $field->type === 'address' ) {
                if ( isset( $field->inputs ) && is_array( $field->inputs ) ) {
                    foreach ( $field->inputs as $input ) {
                        $input_id = (string) $input['id'];
                        $label = sanitize_title( $input['label'] );
                        $value = rgar( $entry, $input_id );

                        if ( ! empty( $value ) ) {
                            $field_data[ $field_name . '_' . $label ] = $value;
                        }
                    }
                }
            } else {
                if ( isset( $entry[ $field->id ] ) ) {
                    $field_data[ $field_name ] = rgar( $entry, $field->id );
                }
            }
        }

        if ( strpos( $field->cssClass, 'form-total' ) !== false ) {
            preg_match( '/tax-(\d+)/', $field->cssClass, $matches );

            if ( !empty($matches) && isset($matches[1]) ) {
                $tax_rate = $matches[1];
            }
        }
    }


    $entry_id = $entry['id'];
    $email_data = [];
    $email_data['items_info'] = ['products' => [['name' => $field_data['event-name'], 'price' => $field_data['registration-fee']]]];
    $email_data['title'] = isset($field_data['event-name']) ? ($field_data['event-name'] == 'Webinar Series' ? $field_data['event-name'] : 'Webinar '.$field_data['event-name']) : '';
    $email_data['first_name'] = $field_data['first-name'] ?? '';
    $email_data['last_name'] = $field_data['last-name'] ?? '';
    $email_data['email'] = $field_data['email'] ?? '';
    $email_data['position_title'] = $field_data['position-title'] ?? '';
    $email_data['company_origination'] = $field_data['organization'] ?? '';
    $email_data['total'] = $field_data['registration-fee'] ?? 0;
    $email_data['payment_method'] = $field_data['payment_method'] ?? '';
    $email_data['transaction_id'] = $entry['transaction_id'] ?? '';
    $email_data['address_street'] = $field_data['address'] ?? '';
    $email_data['address_city'] = $field_data['city'] ?? '';
    $email_data['address_state'] = $field_data['province-territory'] ?? '';
    $email_data['address_postal'] = $field_data['postal-code'] ?? '';
    $email_data['address_country'] = $field_data['address_country'] ?? '';
    $email_data['tax_amount'] = 0;
    $email_data['tax_rate'] = $tax_rate;

    if ($tax_rate > 0) {
        $originalAmount = $email_data['total'] / (1 + ($tax_rate / 100));
        $email_data['tax_amount'] = $email_data['total'] - $originalAmount;
        $email_data['tax_amount'] = number_format($email_data['tax_amount'], 2, '.', '');
    }

    if(!empty($email_data['email']) && $email_data['total'] != 0 && !empty($email_data['items_info'])) {
        return generate_recepit_pdf($email_data);
    }
    return null;
}

//webinar Registration Update  Gravity Form 3 (logined user)
function process_entry_by_field_name_3( $entry) {
    $form = GFAPI::get_form( $entry['form_id'] );
    $field_data = [];
    $tax_rate = 15;
    foreach ( $form['fields'] as $field ) {
        if ( isset( $field->label ) ) {
            $field_name = sanitize_title( $field->label );
            if ( $field->type === 'address' ) {
                if ( isset( $field->inputs ) && is_array( $field->inputs ) ) {
                    foreach ( $field->inputs as $input ) {
                        $input_id = (string) $input['id'];
                        $label = sanitize_title( $input['label'] );
                        $value = rgar( $entry, $input_id );

                        if ( ! empty( $value ) ) {
                            $field_data[ $field_name . '_' . $label ] = $value;
                        }
                    }
                }
            } else {
                if ( isset( $entry[ $field->id ] ) ) {
                    $field_data[ $field_name ] = rgar( $entry, $field->id );
                }
            }
        }

        if ( strpos( $field->cssClass, 'form-total' ) !== false ) {
            preg_match( '/tax-(\d+)/', $field->cssClass, $matches );

            if ( !empty($matches) && isset($matches[1]) ) {
                $tax_rate = $matches[1];
            }
        }
    }


    $entry_id = $entry['id'];
    $email_data = [];
    $email_data['items_info'] = ['products' => [['name' => $field_data['event-name'], 'price' => $field_data['registration-fee']]]];
    $email_data['title'] = isset($field_data['event-name']) ? ($field_data['event-name'] == 'Webinar Series' ? $field_data['event-name'] : 'Webinar '.$field_data['event-name']) : '';
    $email_data['first_name'] = $field_data['first-name'] ?? '';
    $email_data['last_name'] = $field_data['last-name'] ?? '';
    $email_data['email'] = $field_data['email'] ?? '';
    $email_data['position_title'] = $field_data['position-title'] ?? '';
    $email_data['company_origination'] = $field_data['organization'] ?? '';
    $email_data['total'] = $field_data['registration-fee'] ?? 0;
    $email_data['payment_method'] = $field_data['payment_method'] ?? '';
    $email_data['transaction_id'] = $entry['transaction_id'] ?? '';
    $email_data['address_street'] = $field_data['address'] ?? '';
    $email_data['address_city'] = $field_data['city'] ?? '';
    $email_data['address_state'] = $field_data['province-territory'] ?? '';
    $email_data['address_postal'] = $field_data['postal-code'] ?? '';
    $email_data['address_country'] = $field_data['address_country'] ?? '';
    $email_data['tax_amount'] = 0;
    $email_data['tax_rate'] = $tax_rate;

    if ($tax_rate > 0) {
        $originalAmount = $email_data['total'] / (1 + ($tax_rate / 100));
        $email_data['tax_amount'] = $email_data['total'] - $originalAmount;
        $email_data['tax_amount'] = number_format($email_data['tax_amount'], 2, '.', '');
    }

    if(!empty($email_data['email']) && $email_data['total'] != 0 && !empty($email_data['items_info'])) {
        return generate_recepit_pdf($email_data);
    }
    return null;
}

//CanHCC  Gravity Form 18
function process_entry_by_field_name_18( $entry) {
    $form = GFAPI::get_form( $entry['form_id'] );
    $field_data = [];
    $tax_rate = 5;
    foreach ( $form['fields'] as $field ) {
        if ( isset( $field->label ) ) {
            $field_name = sanitize_title( $field->label );
            if ( $field->type === 'product' ) {
                if ( isset( $field->inputs ) && is_array( $field->inputs ) ) {
                    foreach ( $field->inputs as $input ) {
                        $input_id = (string) $input['id'];
                        $label = sanitize_title( $input['label'] );
                        $value = rgar( $entry, $input_id );

                        if ( ! empty( $value ) ) {
                            $field_data[ $field_name . '_' . $label ] = $value;
                        }
                    }
                }
            } else {
                if ( isset( $entry[ $field->id ] ) ) {
                    $field_data[ $field_name ] = rgar( $entry, $field->id );
                }
            }
        }
    }


    $entry_id = $entry['id'];
    $email_data = [];
    $email_data['items_info'] = ['products' => [[
        'name' => $field_data['registration-fee_name'],
        'price' => (float) filter_var($field_data['registration-fee_price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)
    ]]];
    $email_data['title'] = $field_data['registration-session'] ?? '';
    $email_data['first_name'] = $field_data['first-name'] ?? '';
    $email_data['last_name'] = $field_data['last-name'] ?? '';
    $email_data['email'] = $field_data['email'] ?? '';
    $email_data['position_title'] = $field_data['position'] ?? '';
    $email_data['company_origination'] = $field_data['organization'] ?? '';
    $email_data['total'] = $field_data['total'] ?? 0;
    $email_data['payment_method'] = $field_data['payment_method'] ?? '';
    $email_data['transaction_id'] = $entry['transaction_id'] ?? '';
    $email_data['address_street'] = $field_data['address'] ?? '';
    $email_data['address_city'] = $field_data['city'] ?? '';
    $email_data['address_state'] = $field_data['province-territory'] ?? '';
    $email_data['address_postal'] = $field_data['postal-code'] ?? '';
    $email_data['address_country'] = $field_data['country'] ?? '';
    $email_data['tax_amount'] =  (float) filter_var($field_data['tax_price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $email_data['item_price_tax_dedected'] = true;
    if($email_data['tax_amount'] == 0) {
        $email_data['tax_rate'] = 0;
    } else {
        $email_data['tax_rate'] = $tax_rate;
    }

    if(!empty($email_data['email']) && $email_data['total'] != 0 && !empty($email_data['items_info'])) {
        return generate_recepit_pdf($email_data);
    }
    return null;
}

//Confrence Registration Gravity Form 16
function process_entry_by_field_name_16( $entry) {
    $form = GFAPI::get_form( $entry['form_id'] );
    $field_data = [];
    $tax_rate = 15;
    foreach ( $form['fields'] as $field ) {
        if ( isset( $field->label ) ) {
            $field_name = sanitize_title( $field->label );
            if ( $field->type === 'address' ) {
                if ( isset( $field->inputs ) && is_array( $field->inputs ) ) {
                    foreach ( $field->inputs as $input ) {
                        $input_id = (string) $input['id'];
                        $label = sanitize_title( $input['label'] );
                        $value = rgar( $entry, $input_id );

                        if ( ! empty( $value ) ) {
                            $field_data[ $field_name . '_' . $label ] = $value;
                        }
                    }
                }
            } else {
                if ( isset( $entry[ $field->id ] ) ) {
                    $field_data[ $field_name ] = rgar( $entry, $field->id );
                }
            }
        }

        if ( strpos( $field->cssClass, 'form-total' ) !== false ) {
            preg_match( '/tax-(\d+)/', $field->cssClass, $matches );

            if ( !empty($matches) && isset($matches[1]) ) {
                $tax_rate = $matches[1];
            }
        }
    }


    $entry_id = $entry['id'];
    $email_data = [];
    $email_data['items_info'] = gform_get_meta( $entry_id, 'gform_product_info__');
    $email_data['title'] = $form['title'] ?? '';
    $email_data['first_name'] = $field_data['first-name'] ?? '';
    $email_data['last_name'] = $field_data['surname'] ?? '';
    $email_data['email'] = $field_data['unique-email-address'] ?? '';
    $email_data['position_title'] = $field_data['position'] ?? '';
    $email_data['company_origination'] = $field_data['institution'] ?? '';
    $email_data['total'] = $field_data['total'] ?? 0;
    $email_data['payment_method'] = $field_data['payment_method'] ?? '';
    $email_data['transaction_id'] = $entry['transaction_id'] ?? '';
    $email_data['address_street'] = $field_data['mailing-address'] ?? '';
    $email_data['address_city'] = $field_data['city'] ?? '';
    $email_data['address_state'] = $field_data['province'] ?? '';
    $email_data['address_postal'] = $field_data['postal-code'] ?? '';
    $email_data['address_country'] = $field_data['country'] ?? '';
    $email_data['tax_amount'] = 0;
    $email_data['tax_rate'] = $tax_rate;

    if ($tax_rate > 0) {
        $originalAmount = $email_data['total'] / (1 + ($tax_rate / 100));
        $email_data['tax_amount'] = $email_data['total'] - $originalAmount;
    }

    if(!empty($email_data['email']) && $email_data['total'] != 0 && !empty($email_data['items_info'])) {
        return generate_recepit_pdf($email_data);
    }
    return null;
}

//Manitoba Exhibit Contract Gravity Form 23
function process_entry_by_field_name_23( $entry) {
    $form = GFAPI::get_form( $entry['form_id'] );
    $field_data = [];
    $tax_rate = 0;
    foreach ( $form['fields'] as $field ) {
        if ( isset( $field->label ) ) {
            $field_name = sanitize_title( $field->label );
            if ( $field->type === 'address' ) {
                if ( isset( $field->inputs ) && is_array( $field->inputs ) ) {
                    foreach ( $field->inputs as $input ) {
                        $input_id = (string) $input['id'];
                        $label = sanitize_title( $input['label'] );
                        $value = rgar( $entry, $input_id );

                        if ( ! empty( $value ) ) {
                            $field_data[ $field_name . '_' . $label ] = $value;
                        }
                    }
                }
            } else {
                if ( isset( $entry[ $field->id ] ) ) {
                    $field_data[ $field_name ] = rgar( $entry, $field->id );
                }
            }
        }
    }


    $entry_id = $entry['id'];
    $email_data = [];
    $email_data['items_info'] = ['products' => [[
        'name' => $form['title'],
        'price' => (float) filter_var($field_data['total'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)
    ]]];
    $email_data['title'] = $form['title'] ?? '';
    $email_data['first_name'] = $field_data['first-name'] ?? '';
    $email_data['last_name'] = $field_data['last-name'] ?? '';
    $email_data['email'] = $field_data['e-mail-address'] ?? '';
    $email_data['position_title'] = $field_data['position-title'] ?? '';
    $email_data['company_origination'] = $field_data['company-origination'] ?? '';
    $email_data['total'] = $field_data['total'] ?? 0;
    $email_data['payment_method'] = $field_data['payment_method'] ?? '';
    $email_data['transaction_id'] = $entry['transaction_id'] ?? '';
    $email_data['address_street'] = $field_data['address_street-address'] ?? '';
    $email_data['address_city'] = $field_data['address_city'] ?? '';
    $email_data['address_state'] = $field_data['address_state-province'] ?? '';
    $email_data['address_postal'] = $field_data['address_zip-postal-code'] ?? '';
    $email_data['address_country'] = $field_data['address_country'] ?? '';
    $email_data['tax_amount'] = 0;
    $email_data['tax_rate'] = $tax_rate;
    $email_data['item_price_tax_dedected'] = true;

    if ($tax_rate > 0) {
        $originalAmount = $email_data['total'] / (1 + ($tax_rate / 100));
        $email_data['tax_amount'] = $email_data['total'] - $originalAmount;
    }

    if(!empty($email_data['email']) && $email_data['total'] != 0 && !empty($email_data['items_info'])) {
        return generate_recepit_pdf($email_data);
    }
    return null;
}


function generate_recepit_pdf($data) {

    $items_info = $data['items_info'];
    $title = $data['title'] ?? '';
    $first_name = $data['first_name'] ?? '';
    $last_name = $data['last_name'] ?? '';
    $email_address = $data['email'] ?? '';
    $position_title = $data['position_title'] ?? '';
    $company_origination = $data['company_origination'] ?? '';
    $total = $data['total'] ?? 0;
    $payment_method = $data['payment_method'] ?? '';
    $transaction_id = $data['transaction_id'] ?? '';
    $address_street = $data['address_street'] ?? '';
    $address_city = $data['address_city'] ?? '';
    $address_state = $data['address_state'] ?? '';
    $address_postal = $data['address_postal'] ?? '';
    $address_country = $data['address_country'] ?? '';
    $tax_amount = $data['tax_amount'];
    $tax_rate = $data['tax_rate'];
    $item_price_tax_dedected = $data['item_price_tax_dedected'] ?? false;

    $subject = 'New submission from ' . $title;
    $headers = array('Content-Type: text/html; charset=UTF-8');

    $logo_url = plugins_url('assets/img/membership_mail_logo.png', __FILE__);
    $options = [
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $logo_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logo_url, false, $context));

    ob_start();
    include plugin_dir_path(__FILE__) . 'recepient-mail-parts/header-gf.php';
    $email_header = ob_get_clean();

    ob_start();
    include plugin_dir_path(__FILE__) . 'recepient-mail-parts/footer-gf.php';
    $email_footer = ob_get_clean();

    $timestamp = time();

    $fmt_en = new IntlDateFormatter('en_US', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
    $fmt_en->setPattern('MMMM dd, yyyy');
    $date_en = $fmt_en->format($timestamp);

    $fmt_fr = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
    $fmt_fr->setPattern('d MMMM yyyy');
    $date_fr = $fmt_fr->format($timestamp);

    $body = '
				<td style="width:100%; padding:0pt 5.4pt; vertical-align:top">
					<p style="margin-left:12.6pt">
						&#xa0;
					</p>
					<p>
						&#xa0;
					</p>
					<p style="margin-right:15.45pt; margin-left:12.6pt">
						&#xa0;
					</p>
					<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:11pt">
						<span style="font-family:Arial">'.$date_en.' / '.$date_fr.'</span>
					</p>
					<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:11pt">
						<span style="font-family:Arial">&#xa0;</span>
					</p>
					<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:11pt">
						<span style="font-family:Arial">&#xa0;</span>
					</p>
					<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:11pt">
						<span style="font-family:Arial">' . $first_name . ' ' . $last_name . '</span>
					</p>
					<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:11pt">
						<span style="font-family:Arial">' . $position_title . '</span>
					</p>
					<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:11pt">
						<span style="font-family:Arial">' . $company_origination . '</span>
					</p>
					<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:11pt">
						<span style="font-family:Arial">'.$address_street.'</span>
					</p>
					<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:11pt">
						<span style="font-family:Arial">' . $address_city . ' ' . $address_state . '</span><span style="font-family:Arial">&#xa0; </span><span style="font-family:Arial">' . $address_postal . ' ' . $address_country . '</span>
					</p>
					<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:11pt">
						<span style="font-family:Arial">&#xa0;</span>
					</p>
					<p style="margin-right:15.45pt; margin-left:12.6pt; font-size:11pt">
						<span style="font-family:Arial">Via Email / Courriel: ' . $email_address . '</span>
					</p>
					<p style="margin-right:15.45pt; margin-left:12.6pt; text-align:center">
						<span style="font-family:"Bodoni MT"">&#xa0;</span>
					</p>
					<div style="margin-right:15.45pt; margin-left:12.6pt;">
						<h1 style="text-align:left; text-decoration:none">
							<span style="font-family:Arial">Receipt for / Reçu pour ' . $title . '</span>
						</h1>
					</div>
					<p style="margin-right:15.45pt; margin-left:12.6pt; text-align:center; font-size:14pt">
						<span style="font-family:"Bodoni MT"">&#xa0;</span>
					</p>
					';

    $body .= '
					   <table style="width:100%; border: none; border-collapse: collapse; margin-left:12pt; margin-top:10pt">
					';
    $subtotal = 0;
    foreach ($items_info['products'] as $item) {
        if($item_price_tax_dedected) {
            $price =  $item['price'];
        } else {
            $price =  $item['price'] / (1+($tax_rate/100));
        }

        $subtotal += $price;
        $price = number_format($price, 2, '.', '');
        $item_name = $item['name'];
        $item_name = preg_replace('/\s?\(.*?\)\s?/', '', $item_name);
        $item_name = preg_replace('/\s?\+.*$/', '', $item_name);
        $body .= '
					   <tr>
							<td style="width: 70%; font-family: Arial; font-size: 10pt;">'.$item_name.':</td>
							<td style="width: 30%; font-family: Arial; font-size: 10pt; text-decoration: underline;">$ '.  $price .'</td>
						</tr>
						<tr>
							<td colspan="2" style="height: 10px;"></td> <!-- Empty row for spacing -->
						</tr>
					';
    }
    $subtotal = number_format($subtotal, 2, '.', '');

    $body .= '
				        <tr>
							<td style="width: 70%; font-family: Arial; font-size: 10pt;">Subtotal / Sous-total:</td>
							<td style="width: 30%; font-family: Arial; font-size: 10pt;">$ '.$subtotal. '</td>
						</tr>
						<tr>
							<td colspan="2" style="height: 10px;"></td> <!-- Empty row for spacing -->
						</tr>
					   <tr>
							<td style="width: 70%; font-family: Arial; font-size: 10pt;">Tax:
                            <br>
                                  GST/TPS #: 118833193RT0001 <br>
                                  QST/TVQ #: 1218421241TQ0001
                            </td>
							<td style="width: 30%; font-family: Arial; font-size: 10pt; text-decoration: underline;">$ '.  (number_format($tax_amount, 2,'.', '')) .'</td>
						</tr>
						<tr>
							<td colspan="2" style="height: 10px;"></td> <!-- Empty row for spacing -->
						</tr>
						<tr>
							<td style="width: 70%; font-family: Arial; font-size: 10pt;">Total Fees / Frais total:</td>
							<td style="width: 30%; font-family: Arial; font-size: 10pt;">$ '.  (number_format($total, 2,'.', '')) .'</td>
						</tr>
						<tr>
							<td colspan="2" style="height: 10px;"></td> <!-- Empty row for spacing -->
						</tr>
					';

    if(!empty($transaction_id)) {
        $body .='
						<tr>
							<td style="width: 70%; font-family: Arial; font-size: 10pt;">Total Paid / Paiement reçu on '.date('m/d/Y').':
							('.$transaction_id.')</td>
							<td style="width: 30%; font-family: Arial; font-size: 10pt;">$ '. (number_format($total, 2,'.', '')) .'</td>
						</tr>
						<tr>
							<td colspan="2" style="height: 10px;"></td> <!-- Empty row for spacing -->
						</tr>
						';
    }

    $body .= '
					<tr>
							<td style="width: 70%; font-family: Arial; font-size: 10pt; font-weight: bold;">TOTAL AMOUNT OWING / MONTANT TOTAL:</td>
							<td style="width: 30%; font-family: Arial; font-size: 10pt; font-weight: bold;">$ ' . (number_format($total, 2,'.', '')) .'</td>
						</tr> 
				  </table>';

    $body .='
				
					<p style="margin-top:40pt">
							<span style="font-family: Arial"></span>
						</p>
						<p style="margin-right:36pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">Taxes are applicable to all dues as follows / Les taxes s\'appliquent à toutes les cotisations comme suit:</span>
						</p>
                        <p style="margin-right:36pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">BC, AB, SK, MB, YT, NT, NU: 5% GST/TPS 5 %</span>
						</p>
                        <p style="margin-right:36pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">ON: 13% HST/TVH 13 %</span>
						</p>
                        <p style="margin-right:36pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">QC: 5% GST & 9.975% QST/TPS 5 % et TVQ 9,975 %</span>
						</p>
                        <p style="margin-right:36pt; margin-left:12.6pt; font-size:10pt">
							<span style="font-family:Arial">NB, NS, PE, NL: 15% HST/TVH 15 %</span>
						</p>
						
						<p>
							&#xa0;
						</p>
				</td>
	';

    $message = $email_header . $body . $email_footer;

    $dompdf = new \Dompdf\Dompdf();

    $pdf_content = str_replace('{{PLACEHOLDER_SRC}}', $logo_base64, $message);

    $dompdf->loadHtml($pdf_content);

    // Set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');

    // Render the PDF
    $dompdf->render();

    $upload_dir = wp_upload_dir();
    $pdf_file_path = $upload_dir['basedir'] . '/' . str_replace(' ', '_', $title) . '-' . time() . '-fees-recipient.pdf';


    file_put_contents($pdf_file_path, $dompdf->output());

    $attachments = array($pdf_file_path);
    return $pdf_file_path;

    // $mail_content = str_replace('{{PLACEHOLDER_SRC}}', $logo_url, $message);

    // // Send the email
    // wp_mail($email_address, $subject, $mail_content, $headers, $attachments);
}
// add_action( 'gform_pre_submission_23', function( $form ) {
// 	$entry = $_POST;
// 	$field_data = [];
// 	foreach ( $form['fields'] as $field ) {
//         if ( isset( $field->label ) ) {
//             $field_name = sanitize_title( $field->label );
//             if ( $field->type === 'address' ) {
//                 if ( isset( $field->inputs ) && is_array( $field->inputs ) ) {
//                     foreach ( $field->inputs as $input ) {
//                         $input_id = (string) $input['id'];
// 						$input_id=str_replace('.','_',$input_id);

//                         $label = sanitize_title( $input['label'] );
//                         $value = rgar( $entry, 'input_'.$input_id );

//                         if ( ! empty( $value ) ) {
//                             $field_data[ $field_name . '_' . $label ] = $value;
//                         }
//                     }
//                 }
//             } else {
//                 if ( isset( $entry['input_'.$field->id ] ) ) {
//                     $field_data[ $field_name ] = rgar( $entry, 'input_'.$field->id );
//                 }
//             }
//         }

// 		if ( strpos( $field->cssClass, 'form-total' ) !== false ) {
// 			preg_match( '/tax-(\d+)/', $field->cssClass, $matches );

// 			if ( !empty($matches) && isset($matches[1]) ) {
// 				$tax_rate = $matches[1];
// 			}
// 	    }
//     }
//     echo '<pre>';
// 	print_r($_POST);
// 	echo '</pre>';
// 	echo '<pre>';
// 	print_r($field_data);
// 	echo '</pre>';
// 	echo '<pre>';
// 	print_r($form);
// 	echo '</pre>';
// 	die();

// }, 10 );



//custom profile builder validation
add_action( 'wp_ajax_custom_profile_builder_form_validation', 'custom_profile_builder_form_validation' );
add_action( 'wp_ajax_nopriv_custom_profile_builder_form_validation', 'custom_profile_builder_form_validation' );

function custom_profile_builder_form_validation() {
    $step_fields = isset($_POST['stepFields']) ? $_POST['stepFields'] : [];
    $errors = [];
    $username = '';
    $email = '';
    $password1 = '';
    $password2 = '';

    foreach ($step_fields as $field) {
        if ($field['required'] == "true" && empty($field['value'])) {
            $errors[$field['name']] = '<span class="wppb-form-error wppb-custom-form-error">This field is required.</span>';
        }

        if ($field['name'] === 'username') {
            $username = $field['value'];
        } elseif ($field['name'] === 'email') {
            $email = $field['value'];
        } elseif ($field['name'] === 'passw1') {
            $password1 = $field['value'];
        } elseif ($field['name'] === 'passw2') {
            $password2 = $field['value'];
        }
    }

    // Validate unique username
    if(!is_user_logged_in()) {
        if (!empty($username)) {
            if (username_exists($username)) {
                $errors['username'] = '<span class="wppb-form-error wppb-custom-form-error">Username is already taken.</span>';
            }
            if (preg_match('/\.(com|net|org|gov|edu|info|biz)$/i', $username)) {
                $errors['username'] = '<span class="wppb-form-error wppb-custom-form-error">Username is not valid.</span>';
            }
        }

        // Validate unique email
        if (!empty($email) && email_exists($email)) {
            $errors['email'] = '<span class="wppb-form-error wppb-custom-form-error">Email is already registered.</span>';
        }
    }

    // Validate passwords match
    if (!empty($password1) && !empty($password2) && $password1 !== $password2) {
        $errors['passw2'] = '<span class="wppb-form-error wppb-custom-form-error">Passwords do not match.</span>';
    }


    if (!empty($errors)) {
        wp_send_json_error(['errors' => $errors]);
    } else {
        wp_send_json_success(['message' => 'Validation passed!']);
    }
}
