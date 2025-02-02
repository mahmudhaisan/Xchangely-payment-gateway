<?php



class Xchangely_Gateway extends WC_Payment_Gateway
{

    private $xchangely_settings;



    public function __construct($settings = null)
    {


        $this->xchangely_settings = isset($settings) ? $settings : new Xchangely_Settings();

        $this->id                 = 'xchangely';
        $this->method_title       = __('Xchangely Payment Gateway', 'wp-xchangely');
        $this->method_description = __('Allow customers to securely checkout with credit cards or PayPal', 'wp-xchangely');
        $this->title              = $this->xchangely_settings->get('title');
        $this->description        = $this->xchangely_settings->get('description');
        $this->enabled            = $this->xchangely_settings->get('enabled');
        $this->has_fields         = true;


        $this->init_form_fields();
        $this->init_settings();


        // add_filter('re
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_thankyou_' . $this->id, [$this, 'thank_you_page']);
        add_action('wp_enqueue_scripts', [$this, 'payment_scripts']);
    }




    public function init_form_fields(): void
    {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'wp-xchangely'),
                'type'    => 'checkbox',
                'label'   => __('Enable Xchangely Payment Gateway', 'wp-xchangely'),
                'default' => 'yes',
            ],
            'sandbox_mode' => [
                'title'       => __('Sandbox Mode', 'wp-xchangely'),
                'type'        => 'checkbox',
                'label'       => __('Enable Sandbox Mode', 'wp-xchangely'),
                'description' => __('Use the sandbox environment for testing purposes.', 'wp-xchangely'),
                'default'     => 'no',
                'desc_tip'    => true,
            ],
            'tazapay_api_key' => [
                'title'       => __('Tazapay API Key', 'wp-xchangely'),
                'type'        => 'text',
                'description' => __('Enter your Tazapay API Key.', 'wp-xchangely'),
                'default'     => '',
            ],
            'tazapay_secret_key' => [
                'title'       => __('Tazapay Secret Key', 'wp-xchangely'),
                'type'        => 'text',
                'description' => __('Enter your Tazapay Secret Key.', 'wp-xchangely'),
                'default'     => '',
            ],
            // Add Webhook URL to Plugin Settings
            'webhook_url' => [
                'title'       => __('Webhook URL', 'wp-xchangely'),
                'type'        => 'text',
                'description' => __('Set this URL as the webhook endpoint in your Tazapay dashboard.', 'wp-xchangely'),
                'default'     => site_url('/wp-json/wp-xchangely/v1/webhook'),
            ],
            'title' => [
                'title'       => __('Title', 'wp-xchangely'),
                'type'        => 'text',
                'description' => __('This controls the title seen by users at checkout.', 'wp-xchangely'),
                'default'     => __('Xchangely Payment', 'wp-xchangely'),
            ],
            'description' => [
                'title'       => __('Description', 'wp-xchangely'),
                'type'        => 'textarea',
                'description' => __('This controls the description seen by users at checkout.', 'wp-xchangely'),
                'default'     => __('Pay securely using Xchangely.', 'wp-xchangely'),
            ],
        ];
    }



    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $client_token = $this->generate_tazapay_client_token($order);

        if (empty($client_token)) {
            wc_add_notice(__('Failed to initiate payment with Tazapay.', 'wp-xchangely'), 'error');
            return ['result' => 'failure'];
        }

        // Store client_token in order meta
        $order->update_meta_data('tazapay_client_token', $client_token);
        $order->save();

        // Build WC_AJAX URL for order completion
        $wc_ajax_url = esc_url(add_query_arg([
            'action'   => 'xchangely_complete_order',
            'order_id' => $order_id,
            // 'nonce'    => wp_create_nonce('xchangely_complete_order'),
        ], admin_url('admin-ajax.php')));


        

        return [
            'result'   => 'success',
            'redirect' => '#xchangely-payment:' . ($wc_ajax_url),  // ✅ Hash instead of direct redirect
        ];
    }


    private function generate_tazapay_client_token($order)
    {
        $settings = get_option('woocommerce_xchangely_settings');

        if (!$settings) {
            error_log('Tazapay settings not found.');
            return ''; 
        }

        $api_url = $settings['sandbox_mode'] == 'yes'
            ? 'https://service-sandbox.tazapay.com/v3'
            : 'https://service.tazapay.com/v3';

        $api_key = $settings['tazapay_api_key'];
        $secret_key = $settings['tazapay_secret_key'];

        // Ensure a valid transaction description (limit to 2000 characters)
        $transaction_description = 'Payment for order #' . $order->get_id();
        if (strlen($transaction_description) > 2000) {
            $transaction_description = substr($transaction_description, 0, 1999); // Trim if too long
        }

        $data = [
            'invoice_currency' => get_woocommerce_currency(),
            'amount'           => (int) ($order->get_total() * 100), // Convert to cents
            'customer_details' => [
                'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'country' => $order->get_billing_country(),
            ],
            'success_url'      => $this->get_return_url($order),
            'cancel_url'       => $order->get_cancel_order_url(),
            'transaction_description' => $transaction_description, // ✅ Fix: Ensure valid description
            'metadata'         => ['order_id' => $order->get_id()],
        ];

        // error_log('Tazapay Request Data: ' . print_r($data, true));

        $response = wp_remote_post("$api_url/payin", [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("$api_key:$secret_key"),
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode($data),
        ]);

        // Log API Response
        if (is_wp_error($response)) {
            error_log('Tazapay API Error: ' . $response->get_error_message());
            return '';
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        // error_log("Tazapay API Response Code: " . $response_code);
        // error_log("Tazapay API Response Body: " . print_r($body, true));

        if ($response_code !== 200 || empty($body['data']['client_token'])) {
            error_log('Tazapay API returned an error: ' . ($body['message'] ?? 'Unknown error'));
            return '';
        }

        error_log('Tazapay Client Token: ' . $body['data']['client_token']);
        return $body['data']['client_token'];
    }



    public function payment_scripts()
    {

        // Load Tazapay SDK
        wp_enqueue_script('tazapay-sandbox-sdk', 'https://js-sandbox.tazapay.com/v3.js', [], '[]', false);
        // wp_enqueue_script('tazapay-sdk', 'https://js.tazapay.com/v3.js', [], '[]', true);

        // Load Custom JS for handling the popup
        wp_enqueue_script('wc-tazapay-popup', WPPT_ASSETS_URL . 'js/tazapay-popup.js', ['jquery'], '1.0', true);
        // Pass AJAX URL and settings to JavaScript
        wp_localize_script('wc-tazapay-popup', 'xchangely_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
    }
}
