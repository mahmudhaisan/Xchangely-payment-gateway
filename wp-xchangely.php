<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @since             1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       Xchangely WooCommerce Payment Gateway
 * Description:       Xchangely WooCommerce Payment Gateway
 * Version:           1.0.0
 * Author:            Xchangely INC.
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-xchangely
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define('WPPT_VERSION', '1.0.0');

/**
 * Define plugin path and plugin url.
 */
define('WPPT_PATH', plugin_dir_path(__FILE__));
define('WPPTT_BASENAME', plugin_basename(__FILE__));
define('WPPT_URL', plugin_dir_url(__FILE__));
define('WPPT_ASSETS_URL', plugin_dir_url(__FILE__) . 'assets/src/');

// Load autoloader
require plugin_dir_path(__FILE__) . 'vendor/autoload.php';

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-loader.php';


add_action('wp_enqueue_scripts', function () {


	wp_enqueue_style('frontend-css', WPPT_ASSETS_URL . 'css/frontend.css', [],  '1.0');

	wp_enqueue_script('tazapay-checkout', 'https://js.tazapay.com/v3.js', ['jquery'], '1.0', true);
	wp_enqueue_script('frontend-js', WPPT_ASSETS_URL . 'js/frontend.js', ['jquery'], '1.0', true);


});




// Define the action for logged-in users
add_action('wp_ajax_xchangely_get_order_details', 'xchangely_get_order_details_callback');

// Define the action for non-logged-in users (optional)
add_action('wp_ajax_nopriv_xchangely_get_order_details', 'xchangely_get_order_details_callback');

// Callback function to handle the AJAX request
function xchangely_get_order_details_callback() {
    // Check if order_id is provided
    if (isset($_POST['order_id']) && !empty($_POST['order_id'])) {
        $order_id = $_POST['order_id'];

        // Fetch the WooCommerce order
        $order = wc_get_order($order_id);

        if ($order) {
            // Initialize order data array
            $order_data = array(
                'subtotal' => $order->get_subtotal(),  // Get subtotal
                'fees' => $order->get_total_tax(),     // Get taxes/fees
                'total' => $order->get_total(),        // Get total price
                'items' => array()                     // To store product/item details
            );

            // Loop through order items and get product details
            foreach ($order->get_items() as $item_id => $item) {
                $product = $item->get_product();  // Get the product object
                $order_data['items'][] = array(
                    'name' => $item->get_name(),               // Product name
                    'quantity' => $item->get_quantity(),       // Quantity purchased
                    'price' => $item->get_total(),             // Total price for that product
                    'sku' => $product ? $product->get_sku() : '',  // Product SKU
                    'product_url' => $product ? $product->get_permalink() : '',  // Product URL
                    'product_id' => $product ? $product->get_id() : 0  // Product ID
                );
            }

            // Send order data as JSON response
            wp_send_json_success($order_data);
        } else {
            wp_send_json_error(array('message' => 'Order not found'));
        }
    } else {
        wp_send_json_error(array('message' => 'Order ID is missing'));
    }
}













/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function wp_paymenttory_run(): void
{

	new Xchangely_Loader();
}
wp_paymenttory_run();
