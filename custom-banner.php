<?php
/**
 * Plugin Name: Custom Banner Plugin
 * Description: Display custom banners based on selected tags.
 * Version: 1.1
 * Author: Omid Dev
 */

// Enqueue CSS for the banner
function custom_banner_enqueue_styles() {
    wp_enqueue_style('custom-banner-style', plugins_url('style.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'custom_banner_enqueue_styles');

// Create a menu page for managing banners
function custom_banner_menu() {
    add_menu_page('Manage Banners', 'Banners', 'manage_options', 'custom-banner-manage', 'custom_banner_manage_page');
}
add_action('admin_menu', 'custom_banner_menu');

// Callback function for the custom banner management page
function custom_banner_manage_page() {
    if (isset($_POST['add_banner'])) {
        // Process and save banner data
        $banner_name = sanitize_text_field($_POST['custom_banner_name']);
        $banner_link = esc_url($_POST['custom_banner_link']);
        $banner_image_url = esc_url($_POST['custom_banner_image_url']);
        $banner_tags = $_POST['custom_banner_tags'];

        // Save banner data to the database
        $banner_id = wp_insert_post(array(
            'post_title' => $banner_name,
            'post_type' => 'custom_banner',
            'post_status' => 'publish',
        ));

        update_post_meta($banner_id, '_custom_banner_name', $banner_name);
        update_post_meta($banner_id, '_custom_banner_link', $banner_link);
        update_post_meta($banner_id, '_custom_banner_image_url', $banner_image_url);
        update_post_meta($banner_id, '_custom_banner_tags', $banner_tags);
    }

    // Display the list of banners
    echo '<div class="wrap">';
    echo '<h2>Manage Banners</h2>';

    // Display a form for adding a new banner
    echo '<form method="post" action="">';
    echo '<label for="custom_banner_name">Banner Name:</label>';
    echo '<input type="text" id="custom_banner_name" name="custom_banner_name"><br>';

    echo '<label for="custom_banner_link">Banner Link:</label>';
    echo '<input type="text" id="custom_banner_link" name="custom_banner_link"><br>';

    echo '<label for="custom_banner_image_url">Banner Image:</label>';
    echo '<input type="text" id="custom_banner_image_url" name="custom_banner_image_url"><br>';

/// Get all tags
echo '<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />';
echo '<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>';
echo '<script src="'.plugins_url('main.js',__FILE__).'"></script>';
$tags = get_tags();

echo '<label for="custom_banner_tags">select tag:</label><br>';

// Display a select field for selecting tags
echo '<select id="custom_banner_tags" name="custom_banner_tags[]" multiple>';
foreach ($tags as $tag) {
    echo '<option value="' . esc_attr($tag->term_id) . '">' . esc_html($tag->name) . '</option>';
}
echo '</select>';

// Enqueue Select2 script and styles
function custom_banner_enqueue_scripts() {
    wp_enqueue_script('select2-script', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'));
    wp_enqueue_style('select2-style', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
}
add_action('admin_enqueue_scripts', 'custom_banner_enqueue_scripts');

    echo '<input type="submit" name="add_banner" value="Add ‌Banner">';
    echo '</form>';


// Display the list of existing banners
$banners = get_posts(array(
    'post_type' => 'custom_banner',
    'post_status' => 'publish',
    'numberposts' => -1,
));

echo '<h2>Banner List</h2>';
echo '<table class="wp-list-table widefat fixed striped">';
echo '<thead>';
echo '<tr>';
echo '<th scope="col">ID</th>';
echo '<th scope="col">Banner Name</th>';
echo '<th scope="col">Banner Link</th>';
echo '<th scope="col">Banner Image</th>';
echo '<th scope="col">Tags</th>';
echo '<th scope="col">Actions</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($banners as $banner) {
    $banner_id = $banner->ID;
    $banner_name = get_post_meta($banner_id, '_custom_banner_name', true);
    $banner_link = get_post_meta($banner_id, '_custom_banner_link', true);
    $banner_image_url = get_post_meta($banner_id, '_custom_banner_image_url', true);
    $tags_serialized = get_post_meta($banner_id, '_custom_banner_tags', true);
    $tags = maybe_unserialize($tags_serialized);

    echo '<tr>';
    echo '<td>' . $banner_id . '</td>';
    echo '<td>' . esc_html($banner_name) . '</td>';
    echo '<td>' . esc_html($banner_link) . '</td>';
    echo '<td>' . esc_html($banner_image_url) . '</td>';
    echo '<td>';



    if (!empty($tags)) {
        $tag_names = array();
    
        foreach ($tags as $tag) {
            $tag_names[] = esc_html(get_tag($tag)->name);
        }
    
        echo implode(', ', $tag_names);
    }

    echo '</td>';
    echo '<td>';
    echo '<form method="post">';
    echo '<input type="hidden" name="delete_banner_id" value="' . $banner_id . '" />';
    echo '<button type="submit" class="delete-banner deleteButton">Delete</button>';
    echo '</form>';
    echo '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';
echo '</div>';
}
//delete banner
add_action('init', 'delete_custom_banner');

function delete_custom_banner() {
    if (isset($_POST['delete_banner_id'])) {
        $banner_id = $_POST['delete_banner_id'];
        wp_delete_post($banner_id, true);
    }
}

// Create a custom post type for banners
function custom_banner_post_type() {
    register_post_type('custom_banner',
        array(
            'labels' => array(
                'name' => 'Custom Banners',
                'singular_name' => 'Custom Banner',
            ),
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => 'custom-banner-manage',
            'supports' => array('title'),
        )
    );
}
add_action('init', 'custom_banner_post_type');



// Display the banner on posts based on selected tags
function display_custom_banner() {
    if (is_single()) {
        global $post;
        $post_tags = wp_get_post_tags($post->ID, array('fields' => 'ids'));
        $hasBanner = false;
        foreach ($post_tags as $tag_id) {
            $banner_args = array(
                'post_type' => 'custom_banner',
                'posts_per_page' => 1, 
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => '_custom_banner_tags',
                        'value' => '"' . $tag_id . '"', 
                        'compare' => 'LIKE',
                    ),
                ),
            );

            $custom_banners = new WP_Query($banner_args);

            if ($custom_banners->have_posts()) {
                while ($custom_banners->have_posts()) {
                    $custom_banners->the_post();
                    // Rest of your banner display code here
                    $banner_name = get_post_meta(get_the_ID(), '_custom_banner_name', true);
                    $banner_link = get_post_meta(get_the_ID(), '_custom_banner_link', true);
                    $banner_image_url = get_post_meta(get_the_ID(), '_custom_banner_image_url', true);

    
                    echo '<div class="sticky-banner">';
                    echo '<a href="' . esc_url($banner_link) . '" target="_blank">';
                    echo '<img src="' . esc_url($banner_image_url) . '" alt="' . esc_attr($banner_name) . '">';
                    echo '</a>';
                    echo '</div>';
                    $hasBanner = true;
                }
                wp_reset_postdata();
                break; 
            }
        }


        if (!$hasBanner) {
            echo '<div class="sticky-banner">';
            echo 'Default Banner or Message Here';
            echo '</div>';
        }
    }
}

// Display the banner on posts based on selected tags




add_action('wp_footer', 'display_custom_banner');
