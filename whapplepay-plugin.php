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

// require_once plugin_dir_path(__FILE__) . './includes/whapplepay-details.php';

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
               // $this->supported_currencies=array('XAF','NEG','NGN','USD','BTC');
                $this->method_title = 'WhapplePay'; // Title of the payment method shown to users during checkout
                $this->method_description = 'Securely process payments using WhapplePay, a fast and reliable payment gateway that seamlessly integrates with WooCommerce. WhapplePay ensures a smooth checkout experience for your customers by offering a secure and convenient payment method. With WhapplePay, you can accept payments securely, enhance customer trust, and optimize your online store\'s payment processing.';
               
                // Initialize form fields and settings
                $this->init_form_fields();
                $this->init_settings();

                $this->title = $this->get_option('title');
                $this->description = $this->get_option('description');

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
                        'description' => 'This description will be displayed to customers during checkout to inform them about the benefits of using WhapplePay for payment processing.',
                        'default' => 'Securely process payments using WhapplePay, a fast and reliable payment gateway that seamlessly integrates with WooCommerce. WhapplePay ensures a smooth checkout experience for your customers by offering a secure and convenient payment method. With WhapplePay, you can accept payments securely, enhance customer trust, and optimize your online stores payment processing.',
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
                $redirect_url = 'http://192.168.43.227/WhapplePay/wooPayment'; // Replace with your actual URL
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

        // require_once plugin_dir_path(__FILE__) . 'includes/whapplepay-details.php';

        function add_plugin_view_details_link($actions, $plugin_file, $plugin_data, $context) {
            // Get the absolute server path to the main plugin file
            $plugin_main_file = plugin_dir_path(__FILE__) . 'includes/whapplepay-details.php';
        
            // Check if the main plugin file is part of the plugin file being checked
            if (strpos($plugin_file, plugin_dir_path(__FILE__) . 'includes/whapplepay-details.php')) {
                $view_details_url = admin_url('plugin-install.php?tab=plugin-information&plugin=whapplepay-integrator-for-woocommerce&TB_iframe=true&width=772&height=780');
                $actions['view_details'] = sprintf('<a href="%s" class="thickbox" aria-label="View %s details">%s</a>',
                    esc_url($view_details_url),
                    esc_attr__('WhapplePay Integrator for WooCommerce'),
                    esc_html__('View Details')
                );
            }
            return $actions;
        }
        add_filter('plugin_row_meta', 'add_plugin_view_details_link', 10, 4);
        

        function update_payment_status($order_id, $new_status) {
            $order = wc_get_order($order_id);
            if (!$order) {
                return; 
            }
        
            // Update order status
            $order->update_status($new_status);
        }
        
        // Hook into payment completion process and update status
        add_action('whapplepay_payment_completed', 'update_payment_status', 10, 2);
    }
    function whapplepay_plugin_settings_link($links) {
        $settings_link = '<a href="admin.php?page=whapplepay-settings">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    $plugin_basename = plugin_basename(__FILE__);
    add_filter("plugin_action_links_$plugin_basename", 'whapplepay_plugin_settings_link');
    
    $gateways[] = 'WC_Gateway_WhapplePay';
    return $gateways;
  

}
?>
