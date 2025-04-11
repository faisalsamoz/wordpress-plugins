<?php

// Add plugin short code
add_shortcode('surveycraft', 'svc_short_code');

function svc_short_code($args)
{
    $surveys          = svc_get_all_surveys();
    $all_surveys_html = svc_generate_all_surveys_html($surveys);
    return $all_surveys_html;
}

if(!function_exists('svc_get_all_surveys')) {
    function  svc_get_all_surveys()
    {
        $args = [
            'post_type'      => 'survey',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ];

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $surveys[] = [
                    'ID'          => get_the_ID(),
                    'title'       => get_the_title(),
                    'thumbnail'   => get_the_post_thumbnail(get_the_ID(), 'medium', ['class' => 'survey-card-thumbnail']),
                    'short_desc'  => wp_trim_words(get_the_excerpt(), 20, '...'),
                    'link'        => get_permalink(),
                    'cart_link'   => wc_get_cart_url() . '?add_survey_to_cart=' . get_post_meta(get_the_ID(), '_woo_product_id', true),                ];
            }
            wp_reset_postdata();
            return $surveys;
        } else {
            return [];
        }
    }
}

if(!function_exists('svc_generate_all_surveys_html')) {
    function svc_generate_all_surveys_html($surveys)
    {
        $html = '<div class="survey-cards-container">';
        $default_image = plugin_dir_url(__FILE__) . '../assets/images/default.png';
        foreach ($surveys as $survey) {
            if(count($survey)) {
                $html .= "<div class='survey-card'>";
                $html .=  $survey['thumbnail'] ? $survey['thumbnail'] : "<img src=" . $default_image . " class='survey-card-thumbnail' />";
                $html .= '<h3 class="survey-card-title">' . esc_html($survey['title']) . '</h3>';
                $html .= '<p class="survey-card-description">' . esc_html($survey['short_desc']) . '</p>';
                $html .= '<a href="' . esc_url($survey['link']) . '" class="survey-card-link">' . __('View Survey', 'survey-craft') . '</a>';
                $html .= '<a href="' . esc_url($survey['cart_link']) . '" class="survey-card-link">' . __('Buy', 'survey-craft') . '</a>';
                $html .= "</div>";
            } else {
                $html .= '<p>' . __('No surveys found.', 'survey-craft') . '</p>';
            }
        }
        $html .='</div>';
        return $html;
    }
}