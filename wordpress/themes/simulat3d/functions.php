<?php
defined('ABSPATH') || exit;
add_action('after_setup_theme', function () { add_theme_support('wp-block-styles'); add_theme_support('editor-styles'); add_editor_style('style.css'); });
add_action('wp_enqueue_scripts', function () { wp_enqueue_style('simulat3d', get_stylesheet_uri(), [], '1.0.0'); });
// No third-party fonts, avatars or visitor analytics on the public site.
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
add_filter('show_admin_bar', '__return_false');
