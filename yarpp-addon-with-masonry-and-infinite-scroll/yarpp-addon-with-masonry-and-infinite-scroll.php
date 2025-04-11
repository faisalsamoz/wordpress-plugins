<?php
/**
 * Plugin Name: YARPP  Addon with Masonry and Infinite Scroll
 * Description: Adds Masonry and infinite scroll to YARPP's related posts.
 * Version: 1.3
 * Author: Codeavour
 */

// Check if a yarpp plugin is active
function my_plugin_activation_check() {
    if (!function_exists('yarpp_related')) {
        wp_die(
            'Sorry, this plugin requires "YARPP" to be active. Please activate the required plugin and try again.',
            'Plugin Activation Error',
            array('back_link' => true)
        );
    }
}
register_activation_hook(__FILE__, 'my_plugin_activation_check');

// Enqueue Infinite Scroll and Masonry script
function enqueue_infinite_scroll_script() {
    wp_enqueue_script('masonry', 'https://unpkg.com/masonry-layout@4/dist/masonry.pkgd.min.js', array('jquery'), null, true);
    wp_enqueue_script('infinite-scroll', 'https://cdn.jsdelivr.net/npm/infinite-scroll@4.0.1/dist/infinite-scroll.pkgd.min.js', array('jquery'), null, true);

    // Enqueue your custom JS
    wp_enqueue_script('yarpp-infinite-scroll', plugin_dir_url(__FILE__) . 'js/yarpp-infinite-scroll.js', array('jquery', 'masonry', 'infinite-scroll'), null, true);

    // Localize the script to pass the ajaxurl to the frontend JavaScript
    wp_localize_script('yarpp-infinite-scroll', 'yarpp_ajax_obj', array(
        'ajax_url' => admin_url('admin-ajax.php') // Pass the ajax URL
    ));

    // Enqueue styles (optional)
    wp_enqueue_style('yarpp-infinite-scroll-styles', plugin_dir_url(__FILE__) . 'css/styles.css');
}
add_action('wp_enqueue_scripts', 'enqueue_infinite_scroll_script');


// Handle AJAX request to load more related posts
add_action('wp_ajax_load_more_yarpp', 'load_more_yarpp_related');
add_action('wp_ajax_nopriv_load_more_yarpp', 'load_more_yarpp_related');

function load_more_yarpp_related() {
    if (!isset($_GET['post_id']) || !isset($_GET['page']) || !isset($_GET['fallback'])) {
        wp_send_json_error('Invalid request.');
    }

    $post_id = intval($_GET['post_id']);
    $page = intval($_GET['page']);
    $posts_per_page = 5;
    if (!get_post($post_id)) {
        wp_send_json_error('No posts found.');
    }

    // Check whether to use related posts or fallback to all posts
    $fallback = $_GET['fallback'];
    if ($fallback == "false") {
        // Fetch related posts using YARPP's internal function
        $related_posts = yarpp_get_related(array(
            'post_id' => $post_id
        ), $post_id, false);

        if (empty($related_posts)) {
           $page = 1;
           $fallback = "true";
        } else {
            // Pagination calculation
            $start = ($page - 1) * $posts_per_page;
            $paginated_posts = array_slice($related_posts, $start, $posts_per_page);
            if (empty($paginated_posts)) {
                $page = 1;
                $fallback = "true";
            }
        }
    }
    if($fallback == "true") {
        $query_args = array(
            'post_type' => 'post',
            'posts_per_page' => $posts_per_page,
            'paged' => $page,
        );
        $query = new WP_Query($query_args);
        $paginated_posts = $query->posts;
    }

    if (empty($paginated_posts)) {
        wp_send_json_error('No posts found.');
    }

    $default_image = plugin_dir_url(__FILE__) . 'images/default.png';

    // Prepare the HTML output
    $new_posts = '';
    foreach ($paginated_posts as $post) {
        $new_posts .= '<div class="masonry-item">';
        $new_posts .='<div class="masonry-item-iner">';
        $new_posts .= '<a href="' . get_permalink($post->ID) . '">';
        $input_tags = [];
        if(function_exists('get_ipg_post_tags')) {
            $input_tags = get_ipg_post_tags($post->ID);
        }

        if (has_post_thumbnail($post->ID)) {
            $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'medium');
            if(!empty($thumbnail_url)) {
                $new_posts .= '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr(get_the_title($post->ID)) . '">';
            } else {
                $new_posts .= '<img src="' . esc_url($default_image) . '" alt="default image">';
            }
        } else {
            $new_posts .= '<img src="' . esc_url($default_image) . '" alt="default image">';
        }
        $new_posts .= '</a>';
        $new_posts .= '<div class="tags">';
       foreach ($input_tags as $tag=>$link) {
            $new_posts .= '<a class="post-tag" href="'.$link.'">#'. $tag.'</a>';
        }    
        $new_posts .= '</div>';
        $new_posts .= '</div>';
        $new_posts .= '</div>';
    }

    wp_send_json_success(['html' => $new_posts, 'fallback' => $fallback]);
}


// Add settings link in the plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'yarpp_addon_settings_link');
function yarpp_addon_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=yarpp-addon-settings">Settings</a>';
    array_push($links, $settings_link);
    return $links;
}

// Add settings menu for the plugin
add_action('admin_menu', 'yarpp_addon_admin_menu');
function yarpp_addon_admin_menu() {
    add_options_page(
        'YARPP Addon Settings',
        'YARPP Addon Settings',
        'manage_options',
        'yarpp-addon-settings',
        'yarpp_addon_settings_page'
    );
}

// Display the settings page
function yarpp_addon_settings_page() {
    ?>
    <div class="wrap">
        <h1>YARPP Addon Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('yarpp_addon_settings_group');
            do_settings_sections('yarpp-addon-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register and initialize settings
add_action('admin_init', 'yarpp_addon_register_settings');
function yarpp_addon_register_settings() {
    register_setting(
        'yarpp_addon_settings_group',
        'yarpp_addon_settings',
        'yarpp_addon_sanitize_settings'
    );

    add_settings_section(
        'yarpp_addon_main_section',
        'Display Settings',
        null,
        'yarpp-addon-settings'
    );

    add_settings_field(
        'posts_desktop',
        'Posts to Show on Desktop',
        'yarpp_addon_posts_desktop_callback',
        'yarpp-addon-settings',
        'yarpp_addon_main_section'
    );

    add_settings_field(
        'posts_tablet',
        'Posts to Show on Tablet',
        'yarpp_addon_posts_tablet_callback',
        'yarpp-addon-settings',
        'yarpp_addon_main_section'
    );

    add_settings_field(
        'posts_mobile',
        'Posts to Show on Mobile',
        'yarpp_addon_posts_mobile_callback',
        'yarpp-addon-settings',
        'yarpp_addon_main_section'
    );
}

// Sanitize and validate settings
function yarpp_addon_sanitize_settings($input) {
    $sanitized_input = array();
    $sanitized_input['posts_desktop'] = isset($input['posts_desktop']) ? intval($input['posts_desktop']) : 6;
    $sanitized_input['posts_tablet'] = isset($input['posts_tablet']) ? intval($input['posts_tablet']) : 6;
    $sanitized_input['posts_mobile'] = isset($input['posts_mobile']) ? intval($input['posts_mobile']) : 6;

    return $sanitized_input;
}

// Callback for Desktop setting
function yarpp_addon_posts_desktop_callback() {
    $options = get_option('yarpp_addon_settings');
    ?>
    <input type="number" name="yarpp_addon_settings[posts_desktop]" value="<?php echo isset($options['posts_desktop']) ? esc_attr($options['posts_desktop']) : 5; ?>" min="1">
    <p class="description">Number of related posts to show on Desktop.</p>
    <?php
}

// Callback for Tablet setting
function yarpp_addon_posts_tablet_callback() {
    $options = get_option('yarpp_addon_settings');
    ?>
    <input type="number" name="yarpp_addon_settings[posts_tablet]" value="<?php echo isset($options['posts_tablet']) ? esc_attr($options['posts_tablet']) : 5; ?>" min="1">
    <p class="description">Number of related posts to show on Tablet.</p>
    <?php
}

// Callback for Mobile setting
function yarpp_addon_posts_mobile_callback() {
    $options = get_option('yarpp_addon_settings');
    ?>
    <input type="number" name="yarpp_addon_settings[posts_mobile]" value="<?php echo isset($options['posts_mobile']) ? esc_attr($options['posts_mobile']) : 5; ?>" min="1">
    <p class="description">Number of related posts to show on Mobile.</p>
    <?php
}

//handle settings on front end

function get_yarpp_addon_settings() {
    $settings = get_option('yarpp_addon_settings');
    if ($settings) {
        return $settings;
    }
    return array(
        'posts_desktop' => 4,
        'posts_tablet' => 3,
        'posts_mobile' => 2,
    );
}

function yarpp_add_dynamic_masonry_css() {
    $desktop_posts = 25;
    $tablet_posts = 50;
    $mobile_posts = 100;

    // Inject the dynamic CSS into the page
    echo "<style>
        /* Default for mobile */
        .yarpp-related-posts .masonry-item {
            width: " . esc_attr($desktop_posts) . "%;
        }
        .yarpp-related-posts .masonry-item.masonry-item--width2 {
            width: 50%;
        }

        /* For tablet screens */
        @media (max-width: 768px) {
            .yarpp-related-posts .masonry-item {
                width: " . esc_attr($tablet_posts) . "%;
            }
            .yarpp-related-posts .masonry-item.masonry-item--width2 {
                width: 100%;
            }
        }

        /* For desktop screens */
        @media (max-width: 600px) {
            .yarpp-related-posts .masonry-item {
                width: " . esc_attr($mobile_posts) . "%;
            }
            .yarpp-related-posts .masonry-item.masonry-item--width2 {
                width: 100%;
            }
        }
    </style>";
}

add_action('wp_head', 'yarpp_add_dynamic_masonry_css');

?>