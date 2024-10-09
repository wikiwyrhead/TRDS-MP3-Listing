<?php

/**
 * Plugin Name: TRDS MP3 Listing
 * Plugin URI: https://github.com/wikiwyrhead/mp3-listing-plugin
 * Description: A simple plugin to upload, manage, and list MP3 files with download and social media share buttons. Includes a backend for uploading MP3s and a shortcode to display the audio listing on the frontend. Allows customization of button and title colors via a settings submenu under MP3 Files.
 * Version: 1.4.0
 * Author: Arnel Go
 * Author URI: https://arnelgo.info/
 * License: GPLv2 or later
 * Text Domain: mp3-listing-plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function force_download_mp3()
{
    if (isset($_GET['mp3_id'])) {
        $mp3_id = intval($_GET['mp3_id']);
        $mp3_url = get_post_meta($mp3_id, '_mp3_url', true);

        if ($mp3_url) {
            // Set headers to force download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($mp3_url) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($mp3_url));
            flush(); // Flush system output buffer
            readfile($mp3_url);
            exit;
        }
    }
}
add_action('template_redirect', 'force_download_mp3');


/**
 * Register the custom post type for MP3 files
 */
function mp3_listing_post_type()
{
    $labels = array(
        'name'               => __('TRDS MP3 Listings', 'mp3-listing-plugin'),
        'singular_name'      => __('TRDS MP3 Listing', 'mp3-listing-plugin'),
        'menu_name'          => __('TRDS MP3 Listings', 'mp3-listing-plugin'),
        'name_admin_bar'     => __('TRDS MP3 Listing', 'mp3-listing-plugin'),
        'add_new'            => __('Add New', 'mp3-listing-plugin'),
        'add_new_item'       => __('Add New MP3 Listing', 'mp3-listing-plugin'),
        'new_item'           => __('New MP3 Listing', 'mp3-listing-plugin'),
        'edit_item'          => __('Edit MP3 Listing', 'mp3-listing-plugin'),
        'view_item'          => __('View MP3 Listing', 'mp3-listing-plugin'),
        'all_items'          => __('All MP3 Listings', 'mp3-listing-plugin'),
        'search_items'       => __('Search MP3 Listings', 'mp3-listing-plugin'),
        'parent_item_colon'  => __('Parent MP3 Listings:', 'mp3-listing-plugin'),
        'not_found'          => __('No MP3 Listings found.', 'mp3-listing-plugin'),
        'not_found_in_trash' => __('No MP3 Listings found in Trash.', 'mp3-listing-plugin')
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __('A custom post type for managing MP3 Listings.', 'mp3-listing-plugin'),
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'mp3-listings'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'supports'           => array('title', 'custom-fields'),
        'menu_icon'          => 'dashicons-media-audio',
        'show_in_rest'       => true, // Enables Gutenberg editor
    );

    register_post_type('mp3_listing', $args);
}
add_action('init', 'mp3_listing_post_type');

/**
 * Add MP3 upload field to the custom post type
 */
function mp3_upload_meta_box()
{
    add_meta_box(
        'mp3_upload_meta_box',
        __('MP3 Details', 'mp3-listing-plugin'),
        'mp3_upload_callback',
        'mp3_listing',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'mp3_upload_meta_box');

/**
 * Callback function to display the meta box fields
 */
function mp3_upload_callback($post)
{
    // Retrieve existing values from the database
    $mp3_url = get_post_meta($post->ID, '_mp3_url', true);
    wp_nonce_field('mp3_save_meta_box_data', 'mp3_meta_box_nonce'); // Nonce for security
?>
    <p>
        <label for="mp3_url"><strong><?php _e('Upload MP3 File', 'mp3-listing-plugin'); ?></strong></label><br />
        <input type="text" name="mp3_url" id="mp3_url" value="<?php echo esc_attr($mp3_url); ?>" style="width: 80%;" />
        <input type="button" class="button upload-mp3-button" value="<?php _e('Upload MP3', 'mp3-listing-plugin'); ?>" />
    </p>
<?php
}

/**
 * Save MP3 file URL with nonce verification and sanitization
 */
function save_mp3_file($post_id)
{
    // Check if our nonce is set.
    if (!isset($_POST['mp3_meta_box_nonce'])) {
        return $post_id;
    }

    $nonce = $_POST['mp3_meta_box_nonce'];

    // Verify that the nonce is valid.
    if (!wp_verify_nonce($nonce, 'mp3_save_meta_box_data')) {
        return $post_id;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    // Check the user's permissions.
    if (isset($_POST['post_type']) && 'mp3_listing' == $_POST['post_type']) {
        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }
    }

    // Sanitize and save the MP3 URL.
    if (isset($_POST['mp3_url'])) {
        $mp3_url = sanitize_text_field($_POST['mp3_url']);
        update_post_meta($post_id, '_mp3_url', $mp3_url);
    }
}
add_action('save_post', 'save_mp3_file');

/**
 * Enqueue necessary scripts for admin and frontend
 */
function mp3_admin_scripts($hook)
{
    global $post_type;

    // Enqueue scripts for the MP3 listing post type
    if (('post.php' === $hook || 'post-new.php' === $hook) && 'mp3_listing' === $post_type) {
        wp_enqueue_media();
        wp_enqueue_script('mp3-upload', plugin_dir_url(__FILE__) . 'mp3-upload.js', array('jquery'), '1.0', true);
    }

    // Enqueue color picker scripts on settings submenu
    if ($hook === 'mp3_listing_page_mp3-color-settings') {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('mp3-color-picker', plugin_dir_url(__FILE__) . 'mp3-color-picker.js', array('wp-color-picker', 'jquery'), '1.0', true);
    }
}
add_action('admin_enqueue_scripts', 'mp3_admin_scripts');

/**
 * Enqueue scripts and styles for frontend
 */
function mp3_frontend_scripts()
{
    wp_enqueue_script('mp3-share', plugin_dir_url(__FILE__) . 'mp3-upload.js', array('jquery'), '1.0', true);
    wp_enqueue_style('mp3-listing-style', plugin_dir_url(__FILE__) . 'mp3-style.css', array(), '1.0');
}
add_action('wp_enqueue_scripts', 'mp3_frontend_scripts');

/**
 * Add Settings Submenu under MP3 Files
 */
function mp3_listing_add_settings_submenu()
{
    add_submenu_page(
        'edit.php?post_type=mp3_listing', // Parent slug
        __('MP3 Listing Settings', 'mp3-listing-plugin'), // Page title
        __('Settings', 'mp3-listing-plugin'), // Menu title
        'manage_options', // Capability
        'mp3-color-settings', // Menu slug
        'mp3_listing_render_settings_page' // Callback function
    );
}
add_action('admin_menu', 'mp3_listing_add_settings_submenu');

/**
 * Render Settings Submenu Page
 */
function mp3_listing_render_settings_page()
{
?>
    <div class="wrap">
        <h1><?php _e('MP3 Listing Settings', 'mp3-listing-plugin'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('mp3_listing_settings_group');
            do_settings_sections('mp3-color-settings');
            submit_button();
            ?>
        </form>
    </div>
<?php
}

/**
 * Register and Define Settings
 */
function mp3_listing_register_settings()
{
    // Register settings
    register_setting('mp3_listing_settings_group', 'mp3_download_button_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#436aa3',
    ));
    register_setting('mp3_listing_settings_group', 'mp3_share_button_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#87a9d8',
    ));
    register_setting('mp3_listing_settings_group', 'mp3_title_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#000000',
    ));

    // Add settings section
    add_settings_section(
        'mp3_listing_settings_section',
        __('Button and Title Colors', 'mp3-listing-plugin'),
        'mp3_listing_settings_section_callback',
        'mp3-color-settings'
    );

    // Add settings fields
    add_settings_field(
        'mp3_download_button_color',
        __('Download Button Color', 'mp3-listing-plugin'),
        'mp3_download_button_color_callback',
        'mp3-color-settings',
        'mp3_listing_settings_section'
    );

    add_settings_field(
        'mp3_share_button_color',
        __('Share Button Color', 'mp3-listing-plugin'),
        'mp3_share_button_color_callback',
        'mp3-color-settings',
        'mp3_listing_settings_section'
    );

    add_settings_field(
        'mp3_title_color',
        __('MP3 Title Color', 'mp3-listing-plugin'),
        'mp3_title_color_callback',
        'mp3-color-settings',
        'mp3_listing_settings_section'
    );
}
add_action('admin_init', 'mp3_listing_register_settings');

/**
 * Settings Section Callback
 */
function mp3_listing_settings_section_callback()
{
    echo __('Customize the colors of the download and share buttons, as well as the MP3 titles displayed on the frontend.', 'mp3-listing-plugin');
}

/**
 * Download Button Color Field Callback
 */
function mp3_download_button_color_callback()
{
    $color = get_option('mp3_download_button_color', '#436aa3');
?>
    <input type="text" name="mp3_download_button_color" value="<?php echo esc_attr($color); ?>" class="mp3-color-field" data-default-color="#436aa3" />
<?php
}

/**
 * Share Button Color Field Callback
 */
function mp3_share_button_color_callback()
{
    $color = get_option('mp3_share_button_color', '#87a9d8');
?>
    <input type="text" name="mp3_share_button_color" value="<?php echo esc_attr($color); ?>" class="mp3-color-field" data-default-color="#87a9d8" />
<?php
}

/**
 * MP3 Title Color Field Callback
 */
function mp3_title_color_callback()
{
    $color = get_option('mp3_title_color', '#000000');
?>
    <input type="text" name="mp3_title_color" value="<?php echo esc_attr($color); ?>" class="mp3-color-field" data-default-color="#000000" />
<?php
}

/**
 * Shortcode to render the MP3 listing on the frontend
 */
function mp3_listing_shortcode()
{
    // Get global color settings
    $download_button_color = get_option('mp3_download_button_color', '#436aa3');
    $share_button_color = get_option('mp3_share_button_color', '#87a9d8');
    $title_color = get_option('mp3_title_color', '#000000');

    $query = new WP_Query(array(
        'post_type' => 'mp3_listing',
        'posts_per_page' => -1
    ));

    if ($query->have_posts()) {
        $output = '<ul class="mp3-list">';
        while ($query->have_posts()) {
            $query->the_post();
            $mp3_url = get_post_meta(get_the_ID(), '_mp3_url', true);
            $post_title = get_the_title();
            $post_url = get_permalink(); // Get the post URL for sharing

            // Encode the post title and URL for use in URLs
            $encoded_title = urlencode($post_title);
            $encoded_url = urlencode($post_url);

            // Create share URLs for social media platforms
            $facebook_share_url = 'https://www.facebook.com/sharer/sharer.php?u=' . $encoded_url;
            $twitter_share_url = 'https://twitter.com/intent/tweet?text=' . $encoded_title . '&url=' . $encoded_url;
            $whatsapp_share_url = 'https://api.whatsapp.com/send?text=' . $encoded_title . '%20' . $encoded_url;
            $linkedin_share_url = 'https://www.linkedin.com/sharing/share-offsite/?url=' . $encoded_url;
            $reddit_share_url = 'https://www.reddit.com/submit?url=' . $encoded_url . '&title=' . $encoded_title;
            $pinterest_share_url = 'https://pinterest.com/pin/create/button/?url=' . $encoded_url . '&description=' . $encoded_title;

            // Create Email Share URL using mailto:
            $email_subject = 'Check out this MP3: ' . $post_title;
            $email_body = 'I thought you might like this MP3:\n\n' . $post_title . '\n' . $post_url;
            $email_share_url = 'mailto:?subject=' . urlencode($email_subject) . '&body=' . urlencode($email_body);

            $output .= '<li style="color: ' . esc_attr($title_color) . ';">
                <div class="mp3-item">
                    <span class="mp3-title">' . esc_html($post_title) . '</span>
                    <div class="mp3-actions">
                        <a href="' . esc_url(add_query_arg('mp3_id', get_the_ID(), home_url('/'))) . '" class="download-button" style="background-color: ' . esc_attr($download_button_color) . '; color: #fff;">⬇️</a> 
                        <div class="share-button-wrapper">
                            <button class="share-button" data-title="' . esc_attr($post_title) . '" data-url="' . esc_url($post_url) . '" style="background-color: ' . esc_attr($share_button_color) . '; color: #fff;">Share</button>
                            <div class="share-dropdown" style="display: none;">
                                <a href="' . esc_url($facebook_share_url) . '" target="_blank" title="Share on Facebook"><img src="' . plugin_dir_url(__FILE__) . 'icons/facebook.png" alt="Facebook" /> Facebook</a>
                                <a href="' . esc_url($twitter_share_url) . '" target="_blank" title="Share on Twitter"><img src="' . plugin_dir_url(__FILE__) . 'icons/twitter.png" alt="Twitter" /> Twitter</a>
                                <a href="' . esc_url($linkedin_share_url) . '" target="_blank" title="Share on LinkedIn"><img src="' . plugin_dir_url(__FILE__) . 'icons/linkedin.png" alt="LinkedIn" /> LinkedIn</a>
                                <a href="' . esc_url($reddit_share_url) . '" target="_blank" title="Share on Reddit"><img src="' . plugin_dir_url(__FILE__) . 'icons/reddit.png" alt="Reddit" /> Reddit</a>
                                <a href="' . esc_url($pinterest_share_url) . '" target="_blank" title="Share on Pinterest"><img src="' . plugin_dir_url(__FILE__) . 'icons/pinterest.png" alt="Pinterest" /> Pinterest</a>
                                <a href="' . esc_url($whatsapp_share_url) . '" target="_blank" title="Share on WhatsApp"><img src="' . plugin_dir_url(__FILE__) . 'icons/whatsapp.png" alt="WhatsApp" /> WhatsApp</a>
                                <a href="' . esc_url($email_share_url) . '" target="_blank" title="Share via Email"><img src="' . plugin_dir_url(__FILE__) . 'icons/email.png" alt="Email" /> Email</a>
                            </div>
                        </div>
                    </div>
                </div>
            </li>';
        }
        $output .= '</ul>';
        wp_reset_postdata();
        return $output;
    } else {
        return __('No MP3 files found.', 'mp3-listing-plugin');
    }
}
add_shortcode('mp3_listing', 'mp3_listing_shortcode');


/**
 * CSS File: mp3-style.css
 * 
 * Make sure to create or update this file in your plugin directory with the following content.
 */
?>