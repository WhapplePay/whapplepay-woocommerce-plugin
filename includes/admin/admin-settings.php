<?php
/*
Plugin Name: Whapple Pay Plugin
Plugin URI: https://whapplepay.com
Description: Seamlessly integrates WhapplePay with WooCommerce, providing a secure and efficient payment solution. Developed by WG Organisation to enhance your online shopping experience.
Version: 1.0
Author: Ngwang Shalom Tamnjong
Author URI: https://yourwebsite.com
*/


// Add menu item to the admin menu
add_action('admin_menu', 'whapplepay_plugin_menu');

function whapplepay_plugin_menu() {
    // Define the URL for your custom icon
    $icon_url = plugin_dir_url(__FILE__) . 'assets/images/custom-icon.png';

    add_menu_page(
        'WhapplePay Settings', // Page title
        'WhapplePay', // Menu title
        'manage_options', // Capability required to access menu item
        'whapplepay-settings', // Menu slug
        'whapplepay_settings_page', // Callback function to display settings page
        $icon_url // Custom icon URL
    );
}

// Callback function to display settings page
function whapplepay_settings_page() {
    ?>
    <div class="wrap">
        <h2>Whapple Pay Settings</h2>
        <form method="post" action="">
            <?php settings_fields('whapplepay_options'); ?>
            <?php do_settings_sections('whapplepay-settings'); ?>
            <?php wp_nonce_field('whapplepay_nonce', 'whapplepay_nonce'); ?>
            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}

// Register settings and fields
add_action('admin_init', 'whapplepay_register_settings');

function whapplepay_register_settings() {
    register_setting(
        'whapplepay_options', // Option group
        'whapplepay_merchant_id' // Option name for merchant ID
    );

    register_setting(
        'whapplepay_options', // Option group
        'whapplepay_merchant_email' // Option name for merchant email
    );

    add_settings_section(
        'whapplepay_section_id', // Section ID
        'WhapplePay API Credentials', // Section title
        'whapplepay_section_callback', // Callback function to display section content
        'whapplepay-settings' // Page slug where section will be displayed
    );

    add_settings_field(
        'whapplepay_merchant_id', // Field ID for merchant ID
        'Merchant ID', // Field label
        'whapplepay_merchant_id_callback', // Callback function to display field
        'whapplepay-settings', // Page slug where field will be displayed
        'whapplepay_section_id' // Section ID
    );

    add_settings_field(
        'whapplepay_merchant_email', // Field ID for merchant email
        'Merchant Email', // Field label
        'whapplepay_merchant_email_callback', // Callback function to display field
        'whapplepay-settings', // Page slug where field will be displayed
        'whapplepay_section_id' // Section ID
    );
}

// Callback function to display section content
function whapplepay_section_callback() {
    echo '<p>Enter your WhapplePay API credentials below.</p>';
}

// Callback function to display merchant ID field
function whapplepay_merchant_id_callback() {
    $merchant_id = get_option('whapplepay_merchant_id');
    echo '<input type="text" name="whapplepay_merchant_id" value="' . esc_attr($merchant_id) . '" />';
}

// Callback function to display merchant email field
function whapplepay_merchant_email_callback() {
    $merchant_email = get_option('whapplepay_merchant_email');
    echo '<input type="email" name="whapplepay_merchant_email" value="' . esc_attr($merchant_email) . '" />';
}

// Add nonce verification and form submission handling
add_action('admin_init', 'whapplepay_handle_form_submission');

function whapplepay_handle_form_submission() {
    if (isset($_POST['submit'])) {
        if (!isset($_POST['whapplepay_nonce']) || !wp_verify_nonce($_POST['whapplepay_nonce'], 'whapplepay_nonce')) {
            die('Security check failed');
        }

        update_option('whapplepay_merchant_id', sanitize_text_field($_POST['whapplepay_merchant_id']));
        update_option('whapplepay_merchant_email', sanitize_email($_POST['whapplepay_merchant_email']));

        wp_redirect(admin_url('admin.php?page=whapplepay-settings&settings-updated=true'));
        exit;
    }
}

?>
