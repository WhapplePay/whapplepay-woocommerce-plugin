<?php
/*
Plugin Name: WhapplePay Integrator for WooCommerce
Plugin URI: https://whapplepay.com
Description: Integrate WhapplePay gateway with WooCommerce for secure payments and enhanced customer trust.
Version: 1.0
Author: Wg Organisation
Author URI: https://wgorganisation/ngwangshalom.com
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: whapplepay-plugin
Domain Path: /languages
Requires at least: 5.0
Tested up to: 5.8
WC requires at least: 3.0
WC tested up to: 5.9
Tags: whapplepay, payment gateway, woocommerce, ecommerce
*/

// Include necessary files
require __DIR__ . '/includes/admin/admin-settings.php';

// Register activation hook
register_activation_hook(__FILE__, 'whapplepay_activate');

function whapplepay_activate() {
    // Default values for options
    $defaults = array(
        'whapplepay_client_id' => '',
        'whapplepay_client_secret' => '', // Add default value for client secret
        'whapplepay_redirect_url_success' => '', // Add default value for success URL
        'whapplepay_redirect_url_fail' => '', // Add default value for failed URL
    );

    // Get existing options, if any
    $existing_options = get_option('whapplepay_options');

    // Merge defaults with existing options (if any)
    $options = is_array($existing_options) ? array_merge($defaults, $existing_options) : $defaults;

    // Update options
    update_option('whapplepay_options', $options);
}

// Enqueue scripts and styles
add_action('admin_enqueue_scripts', 'whapplepay_enqueue_scripts');

function whapplepay_enqueue_scripts() {
    // Enqueue scripts and styles for admin settings page
    wp_enqueue_script('whapplepay-admin-script', plugins_url('assets/js/admin-script.js', __FILE__), array('jquery'), null, true);
}

// Add your WhapplePay payment gateway to WooCommerce checkout
add_filter('woocommerce_payment_gateways', 'add_whapplepay_gateway');

function add_whapplepay_gateway($gateways) {
    if (!class_exists('WC_Gateway_WhapplePay')) {
        class WC_Gateway_WhapplePay extends WC_Payment_Gateway {
            public function __construct() {
                $this->id = 'whapplepay'; // Payment gateway ID
                $this->icon = plugin_dir_url(__FILE__) . 'assets/image/logo.png';  // URL to the gateway's icon
                $this->has_fields = true; // Set to true if you need a custom form for payment details
                $this->method_title = 'WhapplePay'; // Title of the payment method shown to users during checkout
                $this->method_description = 'Securely process payments using WhapplePay, a fast and reliable payment gateway that seamlessly integrates with WooCommerce.'; // Payment method description
                $this->title = $this->get_option('title'); // Title shown during checkout
                $this->description = $this->get_option('description'); // Description shown during checkout

                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            }

            public function get_icon(){
                $icon_html =
                    '<img src="'.plugins_url('assets/image/logo.png', __FILE__).'" alt="WhapplePay" style="float:right;" />';
                return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
            }

            public function init_form_fields() {
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => 'Enable/Disable',
                        'type' => 'checkbox',
                        'label' => 'Enable WhapplePay Payment Gateway',
                        'default' => 'yes',
                    ),
                    'title' => array(
                        'title' => 'Title',
                        'type' => 'text',
                        'description' => 'This controls the title shown during checkout.',
                        'default' => 'Whapple Pay',
                    ),
                    'description' => array(
                        'title' => 'Description',
                        'type' => 'textarea',
                        'description' => 'This description will be displayed to customers during checkout.',
                        'default' => 'Securely process payments using WhapplePay.',
                    ),
                );
            }

            public function process_payment($order_id) {
                $order = wc_get_order($order_id);
            
                // Get item name from the order
                $item_name = '';
                foreach ($order->get_items() as $item) {
                    $item_name .= $item->get_name() . ', ';
                }
                $item_name = rtrim($item_name, ', '); 
            
                // Get merchant ID from options or database
                $merchant_id = get_option('whapplepay_merchant_id'); 
            
                // Get currency details from the order
                $order_currency = $order->get_currency();
                $currency_id = ''; // Initialize to empty
            
                // Map currency codes to currency IDs
                if ($order_currency === 'USD') {
                    $currency_id = 1;
                } elseif ($order_currency === 'XAF' || $order_currency === 'CFA FRANC') {
                    $currency_id = 6;
                } elseif ($order_currency === 'LTC') {
                    $currency_id = 5;
                } elseif ($order_currency === 'NGN') {
                    $currency_id = 7;
                } // Add more conditions as needed for other currencies
            
                // Redirect to the WhapplePay payment form
                $redirect_url = 'http://localhost/WhapplePay/wooPayment'; // Replace with your actual URL
                $redirect_url .= '?merchant=' . $merchant_id; // Merchant ID
                $redirect_url .= '&item_name=' . urlencode($item_name); // Item name
                $redirect_url .= '&currency_id=' . $currency_id; // Currency ID
                $redirect_url .= '&order=' . $order_id; // Order ID
                $redirect_url .= '&amount=' . $order->get_total(); // Total amount
                $redirect_url .= '&custom=thesame'; // Custom parameter (if needed)
            
                do_action('whapplepay_payment_completed', $order_id, 'completed');
    
                return array(
                    'result' => 'success',
                    'redirect' => $redirect_url,
                );
            }
        }
    }

    // Register the payment gateway class
    $gateways[] = 'WC_Gateway_WhapplePay';
    return $gateways;
}



add_action('woocommerce_checkout_update_order_meta', 'whapplepay_custom_checkout_field_update_order_meta');
function whapplepay_custom_checkout_field_update_order_meta($order_id) {
    if (!empty($_POST['whapplepay_custom_field'])) {
        update_post_meta($order_id, 'Custom Information', sanitize_text_field($_POST['whapplepay_custom_field']));
    }
}

// Display custom field data on the order details page
add_action('woocommerce_order_details_after_order_table', 'whapplepay_display_custom_field_data');
function whapplepay_display_custom_field_data($order) {
    $custom_info = get_post_meta($order->get_id(), 'Custom Information', true);
    if ($custom_info) {
        echo '<p><strong>' . __('Custom Information') . ':</strong> ' . esc_html($custom_info) . '</p>';
    }
}

// Plugin settings link
function whapplepay_plugin_settings_link($links) {
    $settings_link = '<a href="admin.php?page=whapplepay-settings">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
$plugin_basename = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin_basename", 'whapplepay_plugin_settings_link');
