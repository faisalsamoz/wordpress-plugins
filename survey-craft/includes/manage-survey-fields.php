<?php
add_action('acf/init', 'svc_register_acf_fields');

function svc_register_acf_fields() {
    if (function_exists('acf_add_local_field_group')) {

        acf_add_local_field_group(array(
            'key' => 'group_survey_detail',
            'title' => 'Survey Details',
            'fields' => array(
                array(
                    'key' => 'field_survey_type',
                    'label' => __('Survey Type', TEXTDOMAIN),
                    'name' => 'survey_type',
                    'type' => 'select',
                    'choices' => array(
                        'single' => 'Single',
                        'duo' => 'Duo',
                    ),
                    'default_value' => 'single',
                ),
                array(
                    'key' => 'field_survey_language',
                    'label' => __('Language', TEXTDOMAIN),
                    'name' => 'survey_language',
                    'type' => 'select',
                    'choices' => array(
                        'en' => 'English',
                    ),
                    'placeholder' => 'Language',
                ),
                array(
                    'key' => 'field_predefined_message',
                    'label' => __('Message', TEXTDOMAIN),
                    'name' => 'predefined_message',
                    'type' => 'textarea',
                ),
                array(
                    'key' => 'field_questionnaire',
                    'label' => __('Questionnaire', TEXTDOMAIN),
                    'name' => 'questionnaire',
                    'type' => 'repeater',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_question_title',
                            'label' => 'Question Title',
                            'name' => 'question_title',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_question_type',
                            'label' => __('Question Type', TEXTDOMAIN),
                            'name' => 'question_type',
                            'type' => 'select',
                            'choices' => array(
                                'text' => 'Text',
                                'textarea' => 'Textarea',
                                'radio' => 'Radio',
                                'checkbox' => 'Checkbox',
                                'select' => 'Select',
                                'file' => 'File',
                            ),
                            'default_value' => 'text',
                        ),
                        array(
                            'key' => 'field_question_options',
                            'label' => __('Options', TEXTDOMAIN),
                            'name' => 'question_options',
                            'type' => 'repeater',
                            'instructions' => 'Add options for Radio, Checkbox, or Select fields.',
                            'conditional_logic' => array(
                                array(
                                    array(
                                        'field' => 'field_question_type',
                                        'operator' => '==',
                                        'value' => 'radio',
                                    ),
                                ),
                                array(
                                    array(
                                        'field' => 'field_question_type',
                                        'operator' => '==',
                                        'value' => 'checkbox',
                                    ),
                                ),
                                array(
                                    array(
                                        'field' => 'field_question_type',
                                        'operator' => '==',
                                        'value' => 'select',
                                    ),
                                ),
                            ),
                            'sub_fields' => array(
                                array(
                                    'key' => 'field_option_value',
                                    'label' => 'Option',
                                    'name' => 'option_value',
                                    'type' => 'text',
                                ),
                            ),
                            'min' => 1,
                            'layout' => 'table',
                            'button_label' => 'Add Option',
                        ),
                    ),
                    'min' => 1,
                    'layout' => 'block',
                    'button_label' => 'Add Question',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'product',
                    ),
                ),
            ),
        ));
    }
}

//save status  of survey
add_action('save_post', 'svc_save_survey_default_status');

function svc_save_survey_default_status($post_id)
{
    if (get_post_type($post_id) !== 'product') {
        return;
    }

    if(!get_post_meta($post_id, 'survey_status')) {
        update_post_meta($post_id, 'survey_status', 'Created');
        update_post_meta($post_id, 'admin_survey_status', 'Created');
    }
}

