<?php

add_shortcode('cnow', 'cnow_load_form');

function cnow_load_form()
{
    $html = '<button id="open-modal">Open Consultation Form</button>';

    $html .= '
            <div class="schedule_a_call" id="form-modal">
        <div class="modal">
            <div class="modal-content">
                <form method="post" action="" id="cnow_form">
                    <div id="msform">
                        <!-- fieldsets -->
                        <fieldset data-step="1">
                            <span class="close-modal">&times;</span>
                            <!-- progressbar -->
                            <ul id="progressbar">
                                <li class="active">Account Setup</li>
                                <li>Social Profiles</li>
                                <li>Personal Details</li>
                            </ul>
                            <h2 class="fs-title">Free Consultation</h2>
                            <p>Please join us for a 15-minute project consultation. We will call you on the number provided by you.</p>
                            <p>Note: The calendar automatically calculates for different time zones</p>
                            <hr>
                            <!-- <h3 class="fs-subtitle">This is step 1</h3> -->
                            <div class="calendar"></div>
                            <input type="hidden" name="date" id="selected_date" value="'.date('Y-m-d').'">
                            <input type="button" name="next" data-step="1" class="next action-button" value="Next" />
                        </fieldset>
                        <fieldset data-step="2">
                            <span class="close-modal">&times;</span>
                            <!-- progressbar -->
                            <ul id="progressbar">
                                <li>Account Setup</li>
                                <li class="active">Social Profiles</li>
                                <li>Personal Details</li>
                            </ul>
                            <h2 class="fs-title">Free Consultation</h2>
                            <p class="fs-subtitle"><i class="fa fa-calendar" aria-hidden="true"></i> <span class="selected-date-show">'.date('d-l-Y').'</span></p>
                            <div class="select-container">
                                <div class="form-group">
                                <select name="timezone" id="timezone" class="form-control">
                                    <option value="">Select Time Zone</option>
                                    <option value="(GMT -12:00) Eniwetok, Kwajalein">(GMT -12:00) Eniwetok, Kwajalein</option>
                                    <option value="(GMT -11:00) Midway Island, Samoa">(GMT -11:00) Midway Island, Samoa</option>
                                    <option value="(GMT -10:00) Hawaii">(GMT -10:00) Hawaii</option>
                                    <option value="(GMT -9:00) Alaska">(GMT -9:00) Alaska</option>
                                    <option value="(GMT -8:00) Pacific Time (US &amp; Canada)">(GMT -8:00) Pacific Time (US &amp; Canada)</option>
                                    <option value="(GMT -7:00) Mountain Time (US &amp; Canada)">(GMT -7:00) Mountain Time (US &amp; Canada)</option>
                                    <option value="(GMT -6:00) Central Time (US &amp; Canada), Mexico City">(GMT -6:00) Central Time (US &amp; Canada), Mexico City</option>
                                    <option value="(GMT -5:00) Eastern Time (US &amp; Canada), Bogota, Lima">(GMT -5:00) Eastern Time (US &amp; Canada), Bogota, Lima</option>
                                    <option value="(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz">(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz</option>
                                    <option value="(GMT -3:30) Newfoundland">(GMT -3:30) Newfoundland</option>
                                    <option value="(GMT -3:00) Brazil, Buenos Aires, Georgetown">(GMT -3:00) Brazil, Buenos Aires, Georgetown</option>
                                    <option value="(GMT -2:00) Mid-Atlantic">(GMT -2:00) Mid-Atlantic</option>
                                    <option value="(GMT -1:00) Azores, Cape Verde Islands">(GMT -1:00) Azores, Cape Verde Islands</option>
                                    <option value="(GMT 0) Western Europe Time, London, Lisbon, Casablanca">(GMT 0) Western Europe Time, London, Lisbon, Casablanca</option>
                                    <option value="(GMT +1:00) Brussels, Copenhagen, Madrid, Paris">(GMT +1:00) Brussels, Copenhagen, Madrid, Paris</option>
                                    <option value="(GMT +2:00) Kaliningrad, South Africa">(GMT +2:00) Kaliningrad, South Africa</option>
                                    <option value="(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburg">(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburg</option>
                                    <option value="(GMT +3:30) Tehran">(GMT +3:30) Tehran</option>
                                    <option value="(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi">(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi</option>
                                    <option value="(GMT +4:30) Kabul">(GMT +4:30) Kabul</option>
                                    <option value="(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent">(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent</option>
                                    <option value="(GMT +5:30) Bombay, Calcutta, Madras, New Delhi">(GMT +5:30) Bombay, Calcutta, Madras, New Delhi</option>
                                    <option value="(GMT +6:00) Almaty, Dhaka, Colombo">(GMT +6:00) Almaty, Dhaka, Colombo</option>
                                    <option value="(GMT +7:00) Bangkok, Hanoi, Jakarta">(GMT +7:00) Bangkok, Hanoi, Jakarta</option>
                                    <option value="(GMT +8:00) Beijing, Perth, Singapore, Hong Kong">(GMT +8:00) Beijing, Perth, Singapore, Hong Kong</option>
                                    <option value="(GMT +9:00) Tokyo, Seoul, Osaka, Sapporo, Yakutsk">(GMT +9:00) Tokyo, Seoul, Osaka, Sapporo, Yakutsk</option>
                                    <option value="(GMT +9:30) Adelaide, Darwin">(GMT +9:30) Adelaide, Darwin</option>
                                    <option value="(GMT +10:00) Eastern Australia, Guam, Vladivostok">(GMT +10:00) Eastern Australia, Guam, Vladivostok</option>
                                    <option value="(GMT +11:00) Magadan, Solomon Islands, New Caledonia">(GMT +11:00) Magadan, Solomon Islands, New Caledonia</option>
                                    <option value="(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka">(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka</option>
                                </select>
                                </div>
                                
                            </div>
                            <div class="form-group">
                            <div class="time-slot-container">
                                <div class="radio-group" id="time-slots"></div>
                            </div>
                            </div>
                            <div class="btn-container">
                                <input type="button" name="previous" class="previous action-button" value="Previous" style="max-width: 140px;" />
                                <input type="button" name="next" data-step="2" class="next action-button" value="Next" />
                            </div>
                        </fieldset>
                        <fieldset data-step="3">
                            <span class="close-modal">&times;</span>
                            <!-- progressbar -->
                            <ul id="progressbar">
                                <li>Account Setup</li>
                                <li>Social Profiles</li>
                                <li class="active">Personal Details</li>
                            </ul>
                            <h2 class="fs-title">Free Consultation</h2>
                            <p class="fs-subtitle"><i class="fa fa-calendar" aria-hidden="true"></i> <span class="selected-date-show">'.date('d-l-Y').'</span></p>
                            <div class="timezone-select-container">
                                <p class="fs-subtitle"><i class="fa fa-globe" aria-hidden="true"></i> <span class="selected-time-zone-show">Pakistan</span></p>
                            </div>
                            <div class="form-group">
                            <input type="text" name="name" placeholder="First Name" />
                            </div>
                            <div class="form-group">
                            <input type="text" name="email" placeholder="Email" />
                            </div>
                            <div class="form-group">
                            <input type="text" name="phone" placeholder="Phone" />
                            </div>
                            <div class="form-group">
                            <input type="text" name="cname" placeholder="Company Name" />
                            </div>
                            <div class="form-group">
                            <textarea name="address" placeholder="Address" rows="4"></textarea>
                            </div>
                            <button type="submit" name="submit" class="cnow-submit-btn action-button">Submit</button>
                            <div class="form_body" style="display: none;">
                                <i class="fas fa-smile"></i>
                                <h2 class="fs-title">Submitted Successfully</h2>
                            </div>
                        </fieldset>
                    </div>
                </form>
            </div>
        </div>
    </div>
    ';
    return $html;
}

add_action('wp_ajax_save_cnow_data', 'save_cnow_data');
add_action('wp_ajax_nopriv_save_cnow_data', 'save_cnow_data');
function save_cnow_data()
{
    $validated = true;
    $errors = array();

    $data = array(
        'date' => sanitize_text_field($_POST['date']),
        'timezone' => sanitize_text_field($_POST['timezone']),
        'time_slot' => sanitize_text_field($_POST['time_slot']),
        'name' => sanitize_text_field($_POST['name']),
        'email' => sanitize_email($_POST['email']),
        'phone' => sanitize_text_field($_POST['phone']),
        'cname' => sanitize_text_field($_POST['cname']),
        'address' => sanitize_textarea_field($_POST['address'])
    );

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])) {
        $errors['date'] = 'Invalid date format. Please use YYYY-MM-DD.';
        $validated = false;
    }

    if (empty($data['timezone'])) {
        $errors['timezone'] = 'Please select a valid time zone.';
        $validated = false;
    }

    if (empty($data['time_slot'])) {
        $errors['time_slot'] = 'Time slot is invalid.';
        $validated = false;
    }

    if (empty($data['name'])) {
        $errors['name'] = 'Name is required.';
        $validated = false;
    }

    if (!is_email($data['email'])) {
        $errors['email'] = 'Invalid email address.';
        $validated = false;
    }

    if (!preg_match('/^\+?\d{10,15}$/', $data['phone'])) {
        $errors['phone'] = 'Invalid phone number. Please enter a valid phone number with 10-15 digits, optionally starting with +.';
        $validated = false;
    }

    if (empty($data['cname'])) {
        $errors['cname'] = 'Company name is required.';
        $validated = false;
    }

    if (empty($data['address'])) {
        $errors['address'] = 'Address is required.';
        $validated = false;
    }

    if (!$validated) {
        wp_send_json_error(array('errors' => $errors));
    } else {
        $to = get_option('cnow_email');
        if($to) {
            $subject = 'Free Consultation - Appointment Details';

            $message = "
            <html>
            <head>
                <title>{$subject}</title>
            </head>
            <body>
                <p><strong>Appointment Details:</strong></p>
                <table border='1' cellpadding='5'>
                    <tr><td><strong>Date</strong></td><td>{$data['date']}</td></tr>
                    <tr><td><strong>Time Zone</strong></td><td>{$data['timezone']}</td></tr>
                    <tr><td><strong>Time Slot</strong></td><td>{$data['time_slot']}</td></tr>
                    <tr><td><strong>First Name</strong></td><td>{$data['name']}</td></tr>
                    <tr><td><strong>Email</strong></td><td>{$data['email']}</td></tr>
                    <tr><td><strong>Phone</strong></td><td>{$data['phone']}</td></tr>
                    <tr><td><strong>Company Name</strong></td><td>{$data['cname']}</td></tr>
                    <tr><td><strong>Address</strong></td><td>{$data['address']}</td></tr>
                </table>
            </body>
            </html>
        ";

            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
            );

            if (wp_mail($to, $subject, $message, $headers)) {
                wp_send_json_success(array('message' => 'Consultation reservation submitted successfully. will contact you soon'));
            } else {
                wp_send_json_error(array('error' => 'Failed to send email.'));
            }
        } else {
            wp_send_json_success(array('message' => 'Consultation reservation submitted successfully. will contact you soon'));
        }
    }
}