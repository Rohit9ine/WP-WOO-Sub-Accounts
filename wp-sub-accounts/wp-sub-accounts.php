<?php
/*
Plugin Name: WP Sub Accounts
Description: Allows primary users to add sub-accounts with specific page access.
Version: 1.0
Author: Rohit Kumar
Author URI: https://iamrohit.net/
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include necessary files
include_once(plugin_dir_path(__FILE__) . 'includes/functions.php');
include_once(plugin_dir_path(__FILE__) . 'includes/shortcodes.php');

// Load scripts and styles
function wp_sub_accounts_enqueue_scripts() {
    wp_enqueue_script('wp-sub-accounts-js', plugin_dir_url(__FILE__) . 'assets/js/scripts.js', array('jquery'), '1.0', true);
    wp_enqueue_style('wp-sub-accounts-css', plugin_dir_url(__FILE__) . 'assets/css/style.css');
}
add_action('wp_enqueue_scripts', 'wp_sub_accounts_enqueue_scripts');

// Enqueue AJAX
function wp_sub_accounts_enqueue_ajax() {
    wp_localize_script('wp-sub-accounts-js', 'wp_sub_accounts_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'wp_sub_accounts_enqueue_ajax');