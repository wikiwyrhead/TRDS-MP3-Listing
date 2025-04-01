<?php

/**
 * Plugin Name: TRDS MP3 Listing
 * Plugin URI: https://github.com/wikiwyrhead/TRDS-MP3-Listing/
 * Description: A simple plugin to upload, manage, and list MP3 files with download and social media share buttons. Includes a backend for uploading MP3s and a shortcode to display the audio listing on the frontend. Allows customization of button and title colors via a settings submenu under MP3 Files.
 * Version: 1.2.2
 * Author: Arnel Go
 * Author URI: https://arnelgo.info/
 * License: GPLv2 or later
 * Text Domain: trds-mp3-listing
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin version
define('TRDS_MP3_PLUGIN_VERSION', '1.2.2');

function force_download_mp3()
{
    if (!isset($_GET['mp3_id']) || !isset($_GET['download']) || !isset($_GET['nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_GET['nonce'], 'mp3_download_nonce_' . $_GET['mp3_id'])) {
        wp_die('Invalid nonce');
    }

    $mp3_id = intval($_GET['mp3_id']);
    $mp3_url = get_post_meta($mp3_id, '_mp3_url', true);

    if ($mp3_url) {
        // Set up cURL with proper SSL verification
        $ch = curl_init($mp3_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            wp_die('Download failed');
        }

        // Update download count securely
        update_post_meta($mp3_id, '_mp3_combined_count', absint(get_post_meta($mp3_id, '_mp3_combined_count', true)) + 1);

        // Set secure headers
        header('X-Content-Type-Options: nosniff');
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name(basename($mp3_url)) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        echo $data;
        exit;
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
        'publicly_queryable' => false, // Disable single post view
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => false, // Disable query var
        'rewrite'            => false, // Disable URL rewriting
        'capability_type'    => 'post',
        'has_archive'        => false, // Disable archive view
        'hierarchical'       => false,
        'menu_position'      => 20,
        'supports'           => array('title', 'custom-fields'),
        'menu_icon'          => 'dashicons-playlist-audio',
        'show_in_rest'       => true, // Enables Gutenberg editor
    );

    register_post_type('mp3_listing', $args);
}
add_action('init', 'mp3_listing_post_type');

/**
 * Register Playlist Taxonomy
 */
function mp3_listing_register_playlist_taxonomy()
{
    $labels = array(
        'name'              => __('Playlists', 'mp3-listing-plugin'),
        'singular_name'     => __('Playlist', 'mp3-listing-plugin'),
        'search_items'      => __('Search Playlists', 'mp3-listing-plugin'),
        'all_items'         => __('All Playlists', 'mp3-listing-plugin'),
        'parent_item'       => __('Parent Playlist', 'mp3-listing-plugin'),
        'parent_item_colon' => __('Parent Playlist:', 'mp3-listing-plugin'),
        'edit_item'         => __('Edit Playlist', 'mp3-listing-plugin'),
        'update_item'       => __('Update Playlist', 'mp3-listing-plugin'),
        'add_new_item'      => __('Add New Playlist', 'mp3-listing-plugin'),
        'new_item_name'     => __('New Playlist Name', 'mp3-listing-plugin'),
        'menu_name'         => __('Playlists', 'mp3-listing-plugin'),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'mp3-playlist'),
    );

    register_taxonomy('mp3_playlist', 'mp3_listing', $args);
}
add_action('init', 'mp3_listing_register_playlist_taxonomy');

/**
 * Add Playlist Management Page
 */
function mp3_listing_add_playlist_management_page()
{
    add_submenu_page(
        'edit.php?post_type=mp3_listing', // Parent slug
        __('Manage Playlists', 'mp3-listing-plugin'), // Page title
        __('Manage Playlists', 'mp3-listing-plugin'), // Menu title
        'manage_options', // Capability
        'mp3-playlist-management', // Menu slug
        'mp3_listing_render_playlist_management_page' // Callback function
    );
}
add_action('admin_menu', 'mp3_listing_add_playlist_management_page');

/**
 * Render Playlist Management Page
 */
function mp3_listing_render_playlist_management_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    // Get all playlists
    $playlists = get_terms(array(
        'taxonomy' => 'mp3_playlist',
        'hide_empty' => false,
    ));

    echo '<div class="wrap">';
    echo '<h1>' . __('Manage Playlists', 'mp3-listing-plugin') . '</h1>';

    if (empty($playlists)) {
        echo '<p>' . __('No playlists found.', 'mp3-listing-plugin') . '</p>';
        return;
    }

    // Add nonce for AJAX operations
    wp_nonce_field('mp3_track_order', 'mp3_track_order_nonce');
    wp_nonce_field('mp3_playlist_operations', 'mp3_playlist_nonce');

    // Display playlists
    echo '<div class="mp3-playlists-container">';
    foreach ($playlists as $playlist) {
        echo '<div class="mp3-playlist-section">';
        echo '<h2 class="playlist-title">' . esc_html($playlist->name) . '</h2>';

        // Get tracks in this playlist
        $tracks = get_posts(array(
            'post_type' => 'mp3_listing',
            'posts_per_page' => 5,
            'tax_query' => array(
                array(
                    'taxonomy' => 'mp3_playlist',
                    'field' => 'term_id',
                    'terms' => $playlist->term_id,
                ),
            ),
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ));

        if (empty($tracks)) {
            echo '<p class="no-tracks">' . __('No tracks in this playlist.', 'mp3-listing-plugin') . '</p>';
        } else {
            echo '<ul class="track-list" data-playlist-id="' . esc_attr($playlist->term_id) . '">';
            foreach ($tracks as $track) {
                echo '<li class="track-item" data-track-id="' . esc_attr($track->ID) . '">
                    <div class="track-handle">☰</div>
                    <div class="track-info">
                        <span class="track-title">' . esc_html($track->post_title) . '</span>
                    </div>
                    <div class="track-actions">
                        <button class="remove-track" data-track-id="' . esc_attr($track->ID) . '">×</button>
                    </div>
                </li>';
            }
            echo '</ul>';

            // Add Load More button if there are more tracks
            $total_tracks = wp_count_posts('mp3_listing')->publish;
            if ($total_tracks > 5) {
                echo '<div class="load-more-container">';
                echo '<button class="load-more-button" 
                             data-playlist-id="' . esc_attr($playlist->term_id) . '" 
                             data-page="2"
                             data-nonce="' . wp_create_nonce('mp3_load_more_nonce') . '">' .
                    __('Load More', 'mp3-listing-plugin') .
                    '</button>';
                echo '</div>';
            }
        }

        echo '</div>'; // Close playlist section
    }
    echo '</div>'; // Close playlists container
    echo '</div>'; // Close wrap
}

/**
 * Update Track Order via AJAX
 */
function mp3_update_track_order()
{
    if (!isset($_POST['playlist_id']) || !isset($_POST['track_order'])) {
        wp_send_json_error('Invalid request.');
    }

    $playlist_id = intval($_POST['playlist_id']);
    $track_order = array_map('intval', $_POST['track_order']);

    foreach ($track_order as $index => $track_id) {
        update_post_meta($track_id, 'playlist_order_' . $playlist_id, $index);
    }

    wp_send_json_success('Track order updated.');
}
add_action('wp_ajax_mp3_update_track_order', 'mp3_update_track_order');

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
        $mp3_url = esc_url_raw($_POST['mp3_url']);

        // Verify file type
        $file_type = wp_check_filetype(basename($mp3_url), array('mp3' => 'audio/mpeg'));
        if ($file_type['ext'] !== 'mp3') {
            return;
        }

        update_post_meta($post_id, '_mp3_url', $mp3_url);
    }
}
add_action('save_post', 'save_mp3_file');

/**
 * Enqueue frontend scripts and styles
 */
function mp3_frontend_scripts()
{
    // Original frontend styles and scripts
    wp_enqueue_script('mp3-upload', plugin_dir_url(__FILE__) . 'assets/js/mp3-upload.js', array('jquery'), '1.0', true);
    wp_enqueue_style('mp3-listing-style', plugin_dir_url(__FILE__) . 'assets/css/mp3-style.css', array(), '1.0');
    wp_localize_script('mp3-upload', 'mp3_ajax_params', array('ajax_url' => admin_url('admin-ajax.php')));
    
    // Additional frontend JavaScript for load more functionality
    wp_enqueue_script(
        'mp3-frontend-scripts',
        plugins_url('assets/js/mp3-frontend.js', __FILE__),
        array('jquery'),
        filemtime(plugin_dir_path(__FILE__) . 'assets/js/mp3-frontend.js'),
        true
    );

    // Localize script with AJAX URL and nonce for both logged-in and logged-out users
    wp_localize_script('mp3-frontend-scripts', 'mp3_frontend_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mp3_load_more_nonce')
    ));

    // Add loading state styles
    $loading_styles = "
        .load-more-button {
            position: relative;
            transition: all 0.3s ease;
        }
        .load-more-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .load-more-button.is-loading {
            padding-right: 40px;
        }
        .load-more-button.is-loading::after {
            content: '';
            position: absolute;
            right: 12px;
            top: 50%;
            width: 16px;
            height: 16px;
            margin-top: -8px;
            border: 2px solid #fff;
            border-right-color: transparent;
            border-radius: 50%;
            animation: button-loading-spinner 0.75s linear infinite;
        }
        @keyframes button-loading-spinner {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    ";
    wp_add_inline_style('mp3-listing-style', $loading_styles);
}
add_action('wp_enqueue_scripts', 'mp3_frontend_scripts');

/**
 * Enqueue admin scripts and styles
 */
function mp3_admin_scripts($hook)
{
    $post_type = get_post_type();

    // Enqueue scripts for the MP3 listing post type
    if (('post.php' === $hook || 'post-new.php' === $hook) && 'mp3_listing' === $post_type) {
        wp_enqueue_media();
        wp_enqueue_script('mp3-upload', plugins_url('assets/js/mp3-upload.js', __FILE__), array('jquery'), '1.0', true);
    }

    // Only load playlist management scripts on our plugin's admin pages
    if ('mp3_listing_page_mp3-playlist-management' === $hook || 'mp3_listing_page_mp3-settings' === $hook) {
        // Enqueue jQuery UI
        wp_enqueue_script('jquery-ui-sortable');

        // Enqueue color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // Enqueue admin CSS
        wp_enqueue_style(
            'mp3-admin-styles',
            plugins_url('assets/css/mp3-admin.css', __FILE__),
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'assets/css/mp3-admin.css')
        );

        // Enqueue admin JavaScript
        wp_enqueue_script(
            'mp3-admin-scripts',
            plugins_url('assets/js/mp3-admin.js', __FILE__),
            array('jquery', 'jquery-ui-sortable', 'wp-color-picker'),
            filemtime(plugin_dir_path(__FILE__) . 'assets/js/mp3-admin.js'),
            true
        );

        // Add loading state styles
        $admin_loading_styles = "
            .load-more-button {
                position: relative;
                transition: all 0.3s ease;
            }
            .load-more-button:disabled {
                opacity: 0.7;
                cursor: not-allowed;
            }
            .load-more-button.is-loading {
                padding-right: 40px;
            }
            .load-more-button.is-loading::after {
                content: '';
                position: absolute;
                right: 12px;
                top: 50%;
                width: 16px;
                height: 16px;
                margin-top: -8px;
                border: 2px solid #fff;
                border-right-color: transparent;
                border-radius: 50%;
                animation: button-loading-spinner 0.75s linear infinite;
            }
            @keyframes button-loading-spinner {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        ";
        wp_add_inline_style('mp3-admin-styles', $admin_loading_styles);

        // Add color picker initialization
        wp_add_inline_script('mp3-admin-scripts', '
            jQuery(document).ready(function($) {
                $(".color-picker").wpColorPicker();
            });
        ');
    }
}
add_action('admin_enqueue_scripts', 'mp3_admin_scripts');

/**
 * Add wrapper class to the plugin output
 */
function mp3_listing_wrapper_start()
{
    echo '<div class="mp3-listing-plugin">';
}
add_action('mp3_listing_before_content', 'mp3_listing_wrapper_start');

/**
 * Close wrapper class
 */
function mp3_listing_wrapper_end()
{
    echo '</div>';
}
add_action('mp3_listing_after_content', 'mp3_listing_wrapper_end');

/**
 * Add admin wrapper class
 */
function mp3_admin_wrapper_start()
{
    echo '<div class="mp3-listing-plugin-admin">';
}
add_action('mp3_admin_before_content', 'mp3_admin_wrapper_start');

/**
 * Close admin wrapper class
 */
function mp3_admin_wrapper_end()
{
    echo '</div>';
}
add_action('mp3_admin_after_content', 'mp3_admin_wrapper_end');

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
        'mp3-settings', // Menu slug
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
            settings_fields('mp3_listing_settings');
            do_settings_sections('mp3_listing_settings');
            submit_button(__('Save Changes', 'mp3-listing-plugin'));
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
    // Register settings with sanitization
    register_setting('mp3_listing_settings', 'mp3_download_button_color', 'sanitize_hex_color');
    register_setting('mp3_listing_settings', 'mp3_share_button_color', 'sanitize_hex_color');
    register_setting('mp3_listing_settings', 'mp3_title_color', 'sanitize_hex_color');
    register_setting('mp3_listing_settings', 'mp3_audio_player_color', 'sanitize_hex_color');

    // Add settings section
    add_settings_section(
        'mp3_listing_style_section',
        __('Button and Title Colors', 'mp3-listing-plugin'),
        'mp3_listing_settings_section_callback',
        'mp3_listing_settings'
    );

    // Add settings fields
    $fields = array(
        'mp3_download_button_color' => array(
            'title' => __('Download Button Color', 'mp3-listing-plugin'),
            'callback' => 'mp3_download_button_color_callback'
        ),
        'mp3_share_button_color' => array(
            'title' => __('Share Button Color', 'mp3-listing-plugin'),
            'callback' => 'mp3_share_button_color_callback'
        ),
        'mp3_title_color' => array(
            'title' => __('MP3 Title Color', 'mp3-listing-plugin'),
            'callback' => 'mp3_title_color_callback'
        ),
        'mp3_audio_player_color' => array(
            'title' => __('Audio Player Color', 'mp3-listing-plugin'),
            'callback' => 'mp3_audio_player_color_callback'
        )
    );

    foreach ($fields as $id => $field) {
        add_settings_field(
            $id,
            $field['title'],
            $field['callback'],
            'mp3_listing_settings',
            'mp3_listing_style_section'
        );
    }
}

function mp3_listing_settings_section_callback()
{
    echo '<p>' . __('Customize the colors and appearance of the MP3 player interface.', 'mp3-listing-plugin') . '</p>';
}

// Color picker callbacks with default values
function mp3_download_button_color_callback()
{
    $color = get_option('mp3_download_button_color', '#436aa3');
    echo '<input type="color" id="mp3_download_button_color" name="mp3_download_button_color" value="' . esc_attr($color) . '" class="mp3-color-picker" />';
}

function mp3_share_button_color_callback()
{
    $color = get_option('mp3_share_button_color', '#87a9d8');
    echo '<input type="color" id="mp3_share_button_color" name="mp3_share_button_color" value="' . esc_attr($color) . '" class="mp3-color-picker" />';
}

function mp3_title_color_callback()
{
    $color = get_option('mp3_title_color', '#333333');
    echo '<input type="color" id="mp3_title_color" name="mp3_title_color" value="' . esc_attr($color) . '" class="mp3-color-picker" />';
}

function mp3_audio_player_color_callback()
{
    $color = get_option('mp3_audio_player_color', '#2271b1');
    echo '<input type="color" id="mp3_audio_player_color" name="mp3_audio_player_color" value="' . esc_attr($color) . '" class="mp3-color-picker" />';
}
add_action('admin_init', 'mp3_listing_register_settings');

/**
 * Shortcode to render the MP3 listing on the frontend
 */
function mp3_listing_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'posts_per_page' => 10,
        'playlist' => '', // Add playlist parameter
    ), $atts);

    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

    $args = array(
        'post_type' => 'mp3_listing',
        'posts_per_page' => $atts['posts_per_page'],
        'paged' => $paged,
        'orderby' => 'date',
        'order' => 'DESC'
    );

    // Add taxonomy query if playlist is specified
    if (!empty($atts['playlist'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'mp3_playlist',
                'field' => 'slug',
                'terms' => $atts['playlist']
            )
        );
    }

    $query = new WP_Query($args);

    // Output container with original classes
    $output = '<div class="mp3-listing-container">
        <ul class="mp3-list">';

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $mp3_id = get_the_ID();
            $mp3_url = get_post_meta($mp3_id, '_mp3_url', true);
            $post_title = get_the_title();

            // Get combined count
            $combined_count = get_post_meta($mp3_id, '_mp3_combined_count', true);
            $combined_count = $combined_count ? intval($combined_count) : 0;

            // Create share URLs
            $encoded_title = urlencode($post_title);
            $encoded_mp3_url = urlencode($mp3_url);
            $facebook_share_url = 'https://www.facebook.com/sharer/sharer.php?u=' . $encoded_mp3_url;
            $twitter_share_url = 'https://x.com/intent/tweet?text=' . urlencode($post_title . ' | ' . $mp3_url);
            $whatsapp_share_url = 'https://api.whatsapp.com/send?text=' . urlencode($post_title . ' | ' . $mp3_url);
            $reddit_share_url = 'https://www.reddit.com/submit?url=' . $encoded_mp3_url . '&title=' . $encoded_title;
            $linkedin_share_url = 'https://www.linkedin.com/sharing/share-offsite/?text=' . urlencode($post_title . ' | ' . $mp3_url);
            $email_subject = 'Check out this MP3: ' . $post_title;
            $email_body = "I thought you might like this MP3:\n\n" . $post_title . "\n" . $mp3_url;
            $email_share_url = 'mailto:?subject=' . rawurlencode($email_subject) . '&body=' . rawurlencode($email_body);

            // Get color options
            $title_color = get_option('mp3_title_color', '#333333');
            $download_button_color = get_option('mp3_download_button_color', '#436aa3');
            $share_button_color = get_option('mp3_share_button_color', '#87a9d8');
            $audio_player_color = get_option('mp3_audio_player_color', '#2271b1');

            $output .= '<li style="color: ' . esc_attr($title_color) . ';">
                <div class="mp3-item">
                    <span class="mp3-title">' . esc_html($post_title) . '</span>
                    <div class="mp3-actions">
                        <audio controls class="mp3-audio" data-mp3-id="' . esc_attr($mp3_id) . '" data-nonce="' . wp_create_nonce('mp3_play_count_nonce') . '" src="' . esc_url($mp3_url) . '" style="background-color: ' . esc_attr($audio_player_color) . '"></audio>
                        <a href="' . esc_url(add_query_arg(array('mp3_id' => $mp3_id, 'download' => '1', 'nonce' => wp_create_nonce('mp3_download_nonce_' . $mp3_id)), home_url('/'))) . '" 
                           class="download-button" 
                           style="background-color: ' . esc_attr($download_button_color) . ';">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#fff" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;">
                                <path d="M12 16l4-4h-3V4h-2v8H8l4 4zM5 18h14v2H5v-2z" stroke="#fff" stroke-width="1.5" fill="#fff"/>
                            </svg>
                        </a>
                        <div class="share-button-wrapper">
                            <button class="share-button" data-title="' . esc_attr($post_title) . '" data-url="' . esc_url($mp3_url) . '" style="background-color: ' . esc_attr($share_button_color) . ';">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="#fff" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;">
                                    <path d="M18 8c1.25 0 2.25-1 2.25-2.25S19.25 3.5 18 3.5 15.75 4.5 15.75 5.75c0 .15.02.3.06.45l-7.8 4.05c-.33-.17-.7-.3-1.1-.3-1.2 0-2.25 1.05-2.25 2.25s1.05 2.25 2.25 2.25c.55 0 1.04-.2 1.41-.53l7.8 4.05c-.03.13-.06.28-.06.43 0 1.25 1 2.25 2.25 2.25s2.25-1 2.25-2.25-1-2.25-2.25-2.25c-.55 0-1.04.2-1.41.53l-7.8-4.05c.04-.15.06-.3.06-.45 0-.15-.02-.3-.06-.45l7.8-4.05c.37.34.86.53 1.41.53z" stroke="#fff" stroke-width="1.5" fill="#fff"/>
                                </svg>
                            </button>
                            <div class="share-dropdown">
                                <a href="' . esc_url($facebook_share_url) . '" target="_blank" rel="noopener noreferrer">
                                    <img src="' . esc_url(plugins_url('assets/icons/facebook.png', __FILE__)) . '" alt="Share on Facebook" /> Facebook
                                </a>
                                <a href="' . esc_url($twitter_share_url) . '" target="_blank" rel="noopener noreferrer">
                                    <img src="' . esc_url(plugins_url('assets/icons/twitter.png', __FILE__)) . '" alt="Share on X" /> X
                                </a>
                                <a href="' . esc_url($linkedin_share_url) . '" target="_blank" rel="noopener noreferrer">
                                    <img src="' . esc_url(plugins_url('assets/icons/linkedin.png', __FILE__)) . '" alt="Share on LinkedIn" /> LinkedIn
                                </a>
                                <a href="' . esc_url($reddit_share_url) . '" target="_blank" rel="noopener noreferrer">
                                    <img src="' . esc_url(plugins_url('assets/icons/reddit.png', __FILE__)) . '" alt="Share on Reddit" /> Reddit
                                </a>
                                <a href="' . esc_url($whatsapp_share_url) . '" target="_blank" rel="noopener noreferrer">
                                    <img src="' . esc_url(plugins_url('assets/icons/whatsapp.png', __FILE__)) . '" alt="Share on WhatsApp" /> WhatsApp
                                </a>
                                <a href="' . esc_url($email_share_url) . '">
                                    <img src="' . esc_url(plugins_url('assets/icons/email.png', __FILE__)) . '" alt="Share via Email" /> Email
                                </a>
                            </div>
                        </div>
                        <span class="view-count">👁️ ' . $combined_count . '</span>
                    </div>
                </div>
            </li>';
        }
    }

    $output .= '</ul>';

    // Add load more button if there are more posts
    if ($query->max_num_pages > 1) {
        $output .= '<div class="load-more-container">
            <button class="load-more-button" 
                data-page="2" 
                data-posts-per-page="' . esc_attr($atts['posts_per_page']) . '"
                data-playlist="' . esc_attr($atts['playlist']) . '">
                Load More
            </button>
        </div>';
    }

    $output .= '</div>';

    wp_reset_postdata();
    return $output;
}
add_shortcode('mp3_listing', 'mp3_listing_shortcode');

/**
 * AJAX handler for loading more tracks
 */
function mp3_load_more_tracks()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mp3_load_more_nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    // Check if this is an admin request
    $is_admin = isset($_POST['is_admin']) && $_POST['is_admin'] === 'true';

    if ($is_admin) {
        // Backend playlist management
        $playlist_id = isset($_POST['playlist_id']) ? absint($_POST['playlist_id']) : 0;
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $posts_per_page = 5;

        $args = array(
            'post_type' => 'mp3_listing',
            'posts_per_page' => $posts_per_page,
            'paged' => $page,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        );

        if ($playlist_id > 0) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'mp3_playlist',
                    'field' => 'term_id',
                    'terms' => $playlist_id
                )
            );
        }

        $query = new WP_Query($args);
        ob_start();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $mp3_id = get_the_ID();
                $post_title = get_the_title();

                echo '<li class="track-item" data-track-id="' . esc_attr($mp3_id) . '">
                    <div class="track-handle">☰</div>
                    <div class="track-info">
                        <span class="track-title">' . esc_html($post_title) . '</span>
                    </div>
                    <div class="track-actions">
                        <button class="remove-track" data-track-id="' . esc_attr($mp3_id) . '">×</button>
                    </div>
                </li>';
            }
        }

        wp_reset_postdata();
        $output = ob_get_clean();
        wp_send_json_success($output);
    } else {
        // Frontend load more
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 10;
        $playlist = isset($_POST['playlist']) ? sanitize_text_field($_POST['playlist']) : '';

        $args = array(
            'post_type' => 'mp3_listing',
            'posts_per_page' => $posts_per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC'
        );

        // Add taxonomy query if playlist is specified
        if (!empty($playlist)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'mp3_playlist',
                    'field' => 'slug',
                    'terms' => $playlist
                )
            );
        }

        $query = new WP_Query($args);
        ob_start();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $mp3_id = get_the_ID();
                $mp3_url = get_post_meta($mp3_id, '_mp3_url', true);
                $post_title = get_the_title();
                $combined_count = get_post_meta($mp3_id, '_mp3_combined_count', true);
                $combined_count = $combined_count ? intval($combined_count) : 0;

                // Create share URLs
                $encoded_title = urlencode($post_title);
                $encoded_mp3_url = urlencode($mp3_url);
                $facebook_share_url = 'https://www.facebook.com/sharer/sharer.php?u=' . $encoded_mp3_url;
                $twitter_share_url = 'https://x.com/intent/tweet?text=' . urlencode($post_title . ' | ' . $mp3_url);
                $whatsapp_share_url = 'https://api.whatsapp.com/send?text=' . urlencode($post_title . ' | ' . $mp3_url);
                $reddit_share_url = 'https://www.reddit.com/submit?url=' . $encoded_mp3_url . '&title=' . $encoded_title;
                $linkedin_share_url = 'https://www.linkedin.com/sharing/share-offsite/?text=' . urlencode($post_title . ' | ' . $mp3_url);
                $email_subject = 'Check out this MP3: ' . $post_title;
                $email_body = "I thought you might like this MP3:\n\n" . $post_title . "\n" . $mp3_url;
                $email_share_url = 'mailto:?subject=' . rawurlencode($email_subject) . '&body=' . rawurlencode($email_body);

                // Get color options
                $title_color = get_option('mp3_title_color', '#333333');
                $download_button_color = get_option('mp3_download_button_color', '#436aa3');
                $share_button_color = get_option('mp3_share_button_color', '#87a9d8');
                $audio_player_color = get_option('mp3_audio_player_color', '#2271b1');

                echo '<li style="color: ' . esc_attr($title_color) . ';">
                    <div class="mp3-item">
                        <span class="mp3-title">' . esc_html($post_title) . '</span>
                        <div class="mp3-actions">
                            <audio controls class="mp3-audio" data-mp3-id="' . esc_attr($mp3_id) . '" data-nonce="' . wp_create_nonce('mp3_play_count_nonce') . '" src="' . esc_url($mp3_url) . '" style="background-color: ' . esc_attr($audio_player_color) . '"></audio>
                            <a href="' . esc_url(add_query_arg(array('mp3_id' => $mp3_id, 'download' => '1', 'nonce' => wp_create_nonce('mp3_download_nonce_' . $mp3_id)), home_url('/'))) . '" 
                               class="download-button" 
                               style="background-color: ' . esc_attr($download_button_color) . ';">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="#fff" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;">
                                    <path d="M12 16l4-4h-3V4h-2v8H8l4 4zM5 18h14v2H5v-2z" stroke="#fff" stroke-width="1.5" fill="#fff"/>
                                </svg>
                            </a>
                            <div class="share-button-wrapper">
                                <button class="share-button" data-title="' . esc_attr($post_title) . '" data-url="' . esc_url($mp3_url) . '" style="background-color: ' . esc_attr($share_button_color) . ';">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="#fff" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;">
                                        <path d="M18 8c1.25 0 2.25-1 2.25-2.25S19.25 3.5 18 3.5 15.75 4.5 15.75 5.75c0 .15.02.3.06.45l-7.8 4.05c-.33-.17-.7-.3-1.1-.3-1.2 0-2.25 1.05-2.25 2.25s1.05 2.25 2.25 2.25c.55 0 1.04-.2 1.41-.53l7.8 4.05c-.03.13-.06.28-.06.43 0 1.25 1 2.25 2.25 2.25s2.25-1 2.25-2.25-1-2.25-2.25-2.25c-.55 0-1.04.2-1.41.53l-7.8-4.05c.04-.15.06-.3.06-.45 0-.15-.02-.3-.06-.45l7.8-4.05c.37.34.86.53 1.41.53z" stroke="#fff" stroke-width="1.5" fill="#fff"/>
                                    </svg>
                                </button>
                                <div class="share-dropdown">
                                    <a href="' . esc_url($facebook_share_url) . '" target="_blank" rel="noopener noreferrer">
                                        <img src="' . esc_url(plugins_url('assets/icons/facebook.png', __FILE__)) . '" alt="Share on Facebook" /> Facebook
                                    </a>
                                    <a href="' . esc_url($twitter_share_url) . '" target="_blank" rel="noopener noreferrer">
                                        <img src="' . esc_url(plugins_url('assets/icons/twitter.png', __FILE__)) . '" alt="Share on X" /> X
                                    </a>
                                    <a href="' . esc_url($linkedin_share_url) . '" target="_blank" rel="noopener noreferrer">
                                        <img src="' . esc_url(plugins_url('assets/icons/linkedin.png', __FILE__)) . '" alt="Share on LinkedIn" /> LinkedIn
                                    </a>
                                    <a href="' . esc_url($reddit_share_url) . '" target="_blank" rel="noopener noreferrer">
                                        <img src="' . esc_url(plugins_url('assets/icons/reddit.png', __FILE__)) . '" alt="Share on Reddit" /> Reddit
                                    </a>
                                    <a href="' . esc_url($whatsapp_share_url) . '" target="_blank" rel="noopener noreferrer">
                                        <img src="' . esc_url(plugins_url('assets/icons/whatsapp.png', __FILE__)) . '" alt="Share on WhatsApp" /> WhatsApp
                                    </a>
                                    <a href="' . esc_url($email_share_url) . '">
                                        <img src="' . esc_url(plugins_url('assets/icons/email.png', __FILE__)) . '" alt="Share via Email" /> Email
                                    </a>
                                </div>
                            </div>
                            <span class="view-count">👁️ ' . $combined_count . '</span>
                        </div>
                    </div>
                </li>';
            }
        }

        wp_reset_postdata();
        $output = ob_get_clean();
        wp_send_json_success($output);
    }
}
add_action('wp_ajax_mp3_load_more_tracks', 'mp3_load_more_tracks');
add_action('wp_ajax_nopriv_mp3_load_more_tracks', 'mp3_load_more_tracks');
