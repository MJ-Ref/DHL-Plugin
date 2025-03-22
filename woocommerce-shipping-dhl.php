<?php
/**
 * Plugin Name: WooCommerce DHL Shipping
 * Plugin URI: https://woocommerce.com/products/dhl-shipping-method/
 * Description: WooCommerce DHL Shipping allows a store to obtain shipping rates for your orders dynamically via the DHL Express API.
 * Version: 1.0.0
 * Author: WooCommerce
 * Author URI: https://woocommerce.com
 * Text Domain: woocommerce-shipping-dhl
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * Requires at least: 6.6
 * Tested up to: 6.7
 * WC requires at least: 9.5
 * WC tested up to: 9.7
 * Copyright: © 2025 WooCommerce
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Woo: 18658:83d1524e8f5f1913e58889f83d442c33
 *
 * @package WC_Shipping_DHL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WC_SHIPPING_DHL_VERSION', '1.0.0' );
define( 'WC_SHIPPING_DHL_PLUGIN_DIR', __DIR__ );
define( 'WC_SHIPPING_DHL_PLUGIN_URL', untrailingslashit( plugins_url( '', __FILE__ ) ) );
define( 'WC_SHIPPING_DHL_DIST_DIR', WC_SHIPPING_DHL_PLUGIN_DIR . '/dist' );
define( 'WC_SHIPPING_DHL_DIST_URL', WC_SHIPPING_DHL_PLUGIN_URL . '/dist' );
define( 'WC_SHIPPING_DHL_ENVIRONMENT', get_option( 'wc_shipping_dhl_environment', 'test' ) );
define( 'WC_SHIPPING_DHL_API_VERSION', '2.12.1' );

/**
 * Plugin activation check
 */
function wc_dhl_activation_check() {
	if ( ! function_exists( 'simplexml_load_string' ) ) {
		deactivate_plugins( basename( __FILE__ ) );
		wp_die( "Sorry, but you can't run this plugin, it requires the SimpleXML library installed on your server/hosting to function." );
	}
	
	// Set redirect flag.
	set_transient( 'wc_shipping_dhl_activation_redirect', true, 30 );
}

register_activation_hook( __FILE__, 'wc_dhl_activation_check' );

add_action( 'plugins_loaded', 'wc_shipping_dhl_init' );

// Subscribe to automated translations.
add_filter( 'woocommerce_translations_updates_for_' . basename( __FILE__, '.php' ), '__return_true' );

/**
 * Initialize plugin.
 */
function wc_shipping_dhl_init() {
	// Load dependencies when composer autoload exists.
	if ( file_exists( __DIR__ . '/vendor/autoload_packages.php' ) ) {
		require_once __DIR__ . '/vendor/autoload_packages.php';
	}
	
	if ( ! class_exists( 'WooCommerce' ) ) {
		wc_shipping_dhl_show_woocommerce_deactivated_notice();
		return;
	}
	
	require_once WC_SHIPPING_DHL_PLUGIN_DIR . '/includes/class-wc-shipping-dhl-init.php';
	
	\WooCommerce\DHL\WC_Shipping_DHL_Init::get_instance();
}

/**
 * Show WooCommerce Deactivated Notice.
 */
function wc_shipping_dhl_show_woocommerce_deactivated_notice() {
	/* translators: %s: WooCommerce link */
	echo '<div class="error"><p>' . 
		sprintf(
			// translators: %1$s: WooCommerce link, %2$s: Add plugins link.
			esc_html__( 'WooCommerce DHL Shipping requires %1$s to be installed and active. Install and activate it %2$s.', 'woocommerce-shipping-dhl' ),
			'<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>',
			'<a href="' . esc_url( admin_url( '/plugin-install.php?s=woocommerce&tab=search&type=term' ) ) . '" target="_blank">here</a>'
		)
		. '</p></div>';
}