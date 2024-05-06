<?php

function process_custom_payment($order_id) {
    $order = wc_get_order($order_id);
    $payment_method = $order->get_payment_method();

    // Process payment based on selected payment method
    if ($payment_method === 'mobile_money') {
        // Implement mobile money payment processing
        $network = isset($_POST['mobilemoney_network']) ? sanitize_text_field($_POST['mobilemoney_network']) : '';
        $phone_number = isset($_POST['mobilemoney_phone']) ? sanitize_text_field($_POST['mobilemoney_phone']) : '';

        // Validate network and phone number
        if ($network && $phone_number) {
            // Process the payment with mobile money
            // Example: Update order status, save transaction details, etc.
            $order->update_status('processing', 'Mobile Money payment received.');

            // Redirect to thank you page
            wp_redirect($order->get_checkout_order_received_url());
            exit;
        } else {
            wc_add_notice('Please provide both network and phone number.', 'error');
            return;
        }
    } elseif ($payment_method === 'whapple_pay') {
        // Implement Whapple Pay payment processing
        $email = isset($_POST['whapplepay_email']) ? sanitize_email($_POST['whapplepay_email']) : '';
        $password = isset($_POST['whapplepay_password']) ? sanitize_text_field($_POST['whapplepay_password']) : '';

        // Validate email and password
        if ($email && $password) {
            // Process the payment with Whapple Pay
            // Example: Update order status, save transaction details, etc.
            $order->update_status('processing', 'Whapple Pay payment received.');

            // Redirect to thank you page
            wp_redirect($order->get_checkout_order_received_url());
            exit;
        } else {
            wc_add_notice('Please provide both email and password.', 'error');
            return;
        }
    } elseif ($payment_method === 'stripe') {
        // Implement Stripe payment processing
    } elseif ($payment_method === 'paypal') {
        // Implement PayPal payment processing
    }
}
add_action('woocommerce_thankyou', 'process_custom_payment');
