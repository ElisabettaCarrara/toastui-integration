<?php
/*
Plugin Name: ToastUI Integration
Plugin URI: https://yourwebsite.com/
Description: Integrates ToastUI editor and media uploader into ClassicPress
Version: 1.0
Author: Your Name
Author URI: https://yourwebsite.com/
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

function toastui_enqueue_scripts($hook) {
    if (!in_array($hook, array('post.php', 'post-new.php'))) {
        return;
    }

    wp_enqueue_script('toastui-editor', 'https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js', array(), null, true);
    wp_enqueue_style('toastui-editor-style', 'https://uicdn.toast.com/editor/latest/toastui-editor.min.css');
    
    wp_enqueue_script('filepond', 'https://unpkg.com/filepond/dist/filepond.js', array(), null, true);
    wp_enqueue_style('filepond-style', 'https://unpkg.com/filepond/dist/filepond.css');
    
    wp_enqueue_script('filepond-image-preview', 'https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js', array('filepond'), null, true);
    wp_enqueue_style('filepond-image-preview-style', 'https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css');
    
    wp_enqueue_script('toastui-integration-main', plugin_dir_url(__FILE__) . 'main.js', array('jquery', 'toastui-editor', 'filepond'), '1.0', true);

    wp_localize_script('toastui-integration-main', 'toastuiData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('toastui-integration-nonce')
    ));
}
add_action('admin_enqueue_scripts', 'toastui_enqueue_scripts');

function toastui_replace_editor() {
    remove_action('admin_print_footer_scripts', 'wp_tiny_mce', 25);
    add_action('edit_form_after_title', 'toastui_add_editor');
}
add_action('admin_init', 'toastui_replace_editor');

function toastui_add_editor() {
    echo '<div id="toastui-editor"></div>';
    echo '<input type="hidden" id="toastui-content" name="toastui_content">';
}

function toastui_add_media_button() {
    echo '<button type="button" id="toastui-media-button" class="button">' . esc_html__('Add Media', 'toastui-integration') . '</button>';
    echo '<div id="filepond-container" style="display:none;"></div>';
}
add_action('media_buttons', 'toastui_add_media_button');

function handle_file_upload() {
    check_ajax_referer('toastui-integration-nonce', 'nonce');

    if (!current_user_can('upload_files')) {
        wp_send_json_error('Permission denied');
    }

    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    $uploadedfile = $_FILES['file'];
    
    $upload_overrides = array('test_form' => false);
    $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

    if ($movefile && !isset($movefile['error'])) {
        wp_send_json_success($movefile['url']);
    } else {
        wp_send_json_error($movefile['error']);
    }
}
add_action('wp_ajax_handle_file_upload', 'handle_file_upload');

function toastui_save_post_content($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['toastui_content'])) {
        $content = wp_kses_post($_POST['toastui_content']);
        update_post_meta($post_id, '_toastui_content', $content);
    }
}
add_action('save_post', 'toastui_save_post_content');

function toastui_display_content($content) {
    global $post;
    if (is_singular() && in_the_loop() && is_main_query()) {
        $toastui_content = get_post_meta($post->ID, '_toastui_content', true);
        if (!empty($toastui_content)) {
            return $toastui_content;
        }
    }
    return $content;
}
add_filter('the_content', 'toastui_display_content');