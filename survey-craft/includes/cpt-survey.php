<?php
//CPT For Surveys

function svc_register_survey_cpt()
{
    $labels = array(
        'name'                  => _x( 'Surveys', 'Post Type General Name', 'survey-craft' ),
        'singular_name'         => _x( 'Survey', 'Post Type Singular Name', 'survey-craft' ),
        'menu_name'             => __( 'Surveys', 'survey-craft' ),
        'name_admin_bar'        => __( 'Survey', 'survey-craft' ),
        'add_new'               => __( 'Add New', 'survey-craft' ),
        'add_new_item'          => __( 'Add New Survey', 'survey-craft' ),
        'edit_item'             => __( 'Edit Survey', 'survey-craft' ),
        'new_item'              => __( 'New Survey', 'survey-craft' ),
        'view_item'             => __( 'View Survey', 'survey-craft' ),
        'search_items'          => __( 'Search Surveys', 'survey-craft' ),
        'not_found'             => __( 'No surveys found', 'survey-craft' ),
        'not_found_in_trash'    => __( 'No surveys found in Trash', 'survey-craft' ),
        'all_items'             => __( 'All Surveys', 'survey-craft' ),
    );

    $args = array(
        'label'                 => __( 'Survey', 'survey-craft' ),
        'description'           => __( 'Surveys for users to fill', 'survey-craft' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-clipboard',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'capability_type'       => 'post',
        'rewrite'               => array( 'slug' => 'survey' ),
    );

    register_post_type( 'survey', $args );
}

add_action('init', "svc_register_survey_cpt");