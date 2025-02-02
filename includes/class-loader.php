<?php

/**
 * The core plugin class for Xchangely.
 *
 * Defines internationalization, admin-specific hooks, public-facing hooks, 
 * and integrates with WooCommerce as a payment gateway.
 *
 * @since 1.0.0
 */
class Xchangely_Loader {

    /**
     * Unique identifier for this plugin.
     *
     * @since 1.0.0
     * @var string $plugin_name
     */
    protected $plugin_name;

    /**
     * Current version of the plugin.
     *
     * @since 1.0.0
     * @var string $version
     */
    protected $version;

    /**
     * Instance of the settings class.
     *
     * @var Xchangely_Settings
     */
    private $settings;

    /**
     * Instance of the checkout handler.
     *
     * @var Xchangely_Checkout
     */
    private $checkout;

    /**
     * Instance of the payment gateway handler.
     *
     * @var Xchangely_Gateway
     */
    private $gateway;

    /**
     * Initialize the core functionality of the plugin.
     *
     * Sets the plugin name and version, loads dependencies, sets the locale, 
     * and defines hooks for admin and public-facing functionalities.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->version = defined('WPPT_VERSION') ? WPPT_VERSION : '1.0.0';
        $this->plugin_name = 'wp-paymenttory';

        $this->load_dependencies();
        $this->set_locale();
        $this->initialize_hooks();
    }

    /**
     * Get the plugin name.
     *
     * @since 1.0.0
     * @return string The plugin name.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Get the plugin version.
     *
     * @since 1.0.0
     * @return string The plugin version.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Set the plugin's text domain for translation.
     *
     * @since 1.0.0
     */
    private function set_locale() {
        add_action('init', [$this, 'load_plugin_textdomain']);
    }

    /**
     * Load the plugin's text domain for translation.
     *
     * @since 1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'wp-xchangely',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    /**
     * Load required dependencies.
     *
     * @since 1.0.0
     */
    private function load_dependencies() {
        include_once __DIR__ . '/class-usage.php';
    }

    /**
     * Initialize hooks and setup plugin functionality.
     *
     * @since 1.0.0
     */
    private function initialize_hooks() {
        add_action('plugins_loaded', [$this, 'on_plugins_loaded']);
        add_filter('auto_update_plugin', [$this, 'auto_update_this_plugin'], 10, 2);
        new Xchangely_Usage();
    }

    /**
     * Enable auto-updates for this plugin.
     *
     * @param bool $update Current update status.
     * @param object $item Plugin data object.
     * @return bool Updated status.
     */
    public function auto_update_this_plugin($update, $item) {
        $plugins = [$this->plugin_name];
        return in_array($item->slug, $plugins) ? true : $update;
    }

    /**
     * Add a settings link to the plugin's action links.
     *
     * @param array $links Existing action links.
     * @return array Updated action links.
     */
    public function plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=xchangely') . '">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Load and initialize everything after WooCommerce is loaded.
     *
     * @since 1.0.0
     */
    public function on_plugins_loaded() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning"><p>WP Xchangely requires WooCommerce to be installed and active.</p></div>';
            });
            return;
        }

        // Include dependencies for the gateway and settings
        include_once __DIR__ . '/class-gateway.php';
        include_once __DIR__ . '/class-settings.php';

        $this->gateway = new Xchangely_Gateway($this->settings);
      
        // Register the gateway with WooCommerce
        add_filter('woocommerce_payment_gateways', [$this, 'register_gateway']);

        // Add settings link to plugin action links
        add_filter('plugin_action_links_' . WPPTT_BASENAME, [$this, 'plugin_action_links']);
    }

    /**
     * Register the payment gateway with WooCommerce.
     *
     * @param array $methods Existing payment methods.
     * @return array Updated payment methods.
     */
    public function register_gateway($methods) {
        $methods[] = 'Xchangely_Gateway';
        return $methods;
    }
}
