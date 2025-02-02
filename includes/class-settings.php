<?php

class Xchangely_Settings {
    const PLUGIN_ID = 'woocommerce_xchangely';
    const API_CHECKOUT_SESSION_URL = 'https://service-sandbox.tazapay.com/v3/checkout';

    private $settings = array(
        'tazapay_api_key' => '',
        'tazapay_secret_key' => '',
        'enabled_sandbox' => 'no',
        'success_url' => '',
        'cancel_url' => '',
        'webhook_url' => '',
        'customer_fee_percentage' => 0
    );

    public $settings_saved = false;

    public static function instance() {
        if (!isset($GLOBALS['tazapay_wc_settings'])) {
            $GLOBALS['tazapay_wc_settings'] = new static();
        }
        return $GLOBALS['tazapay_wc_settings'];
    }

    public function __construct() {
        $this->settings = array_merge($this->settings, get_option(static::PLUGIN_ID . '_settings', []));
    }

    public function getOptions() {
        return $this->settings;
    }

    public function get($key) {
        return $this->settings[$key] ?? false;
    }

    public function set($key, $value) {
        $this->settings[$key] = $value;
        update_option(static::PLUGIN_ID . '_settings', $this->settings);
    }

    public function is_sandbox(): bool {
        return $this->get('enabled_sandbox') === 'yes';
    }

    public function getApiUrl(): string {
        return $this->is_sandbox() ? self::API_CHECKOUT_SESSION_URL : str_replace('sandbox-', '', self::API_CHECKOUT_SESSION_URL);
    }

    public function createCheckoutSession(array $data): array {
        $url = $this->getApiUrl();
        $headers = [
            'Authorization' => 'Bearer ' . $this->get('tazapay_api_key'),
            'Content-Type' => 'application/json'
        ];

        $response = wp_remote_post($url, [
            'method' => 'POST',
            'timeout' => 45,
            'headers' => $headers,
            'body' => json_encode($data),
        ]);

        if (is_wp_error($response)) {
            error_log('Tazapay API Error: ' . $response->get_error_message());
            return ['success' => false, 'message' => __('Unable to create checkout session.', 'wp-xchangely')];
        }

        $body = json_decode($response['body'], true);
        if (isset($body['status']) && $body['status'] === 'success') {
            return ['success' => true, 'data' => $body['data']];
        }

        return ['success' => false, 'message' => $body['message'] ?? __('Unknown error.', 'wp-xchangely')];
    }

    public function prepareCheckoutData(array $order): array {
        return [
            'invoice_currency' => $order['currency'],
            'amount' => $order['total'] * 100, // Convert to cents
            'customer_details' => [
                'customer' => $order['customer_id']
            ],
            'success_url' => $this->get('success_url') ?: site_url('/checkout-success'),
            'cancel_url' => $this->get('cancel_url') ?: site_url('/checkout-cancel'),
            'webhook_url' => $this->get('webhook_url') ?: site_url('/wc-api/tazapay-webhook'),
            'payment_methods' => $order['payment_methods'] ?? [],
            'transaction_description' => $order['description'],
            'customer_fee_percentage' => (int)$this->get('customer_fee_percentage'),
            'metadata' => $order['metadata'] ?? []
        ];
    }
}
