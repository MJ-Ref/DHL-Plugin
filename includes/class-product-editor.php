<?php
/**
 * Product Editor Integration class file.
 *
 * @package WC_Shipping_DHL
 */

namespace WooCommerce\DHL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Product_Editor class.
 */
class Product_Editor {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_product_options_dimensions', array( $this, 'product_options_dimensions' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_product_dimensions' ) );
	}

	/**
	 * Add dimension fields to the product data meta box.
	 */
	public function product_options_dimensions() {
		global $product_object;

		if ( ! $product_object ) {
			return;
		}

		$dhl_length = $product_object->get_meta( '_dhl_length', true );
		$dhl_width = $product_object->get_meta( '_dhl_width', true );
		$dhl_height = $product_object->get_meta( '_dhl_height', true );

		require WC_SHIPPING_DHL_PLUGIN_DIR . '/includes/views/html-product-dimensions.php';
	}

	/**
	 * Save the DHL dimensions when the product is saved.
	 *
	 * @param \WC_Product $product Product being saved.
	 */
	public function save_product_dimensions( $product ) {
		if ( isset( $_POST['_dhl_length'] ) ) {
			$product->update_meta_data( '_dhl_length', wc_clean( wp_unslash( $_POST['_dhl_length'] ) ) );
		}

		if ( isset( $_POST['_dhl_width'] ) ) {
			$product->update_meta_data( '_dhl_width', wc_clean( wp_unslash( $_POST['_dhl_width'] ) ) );
		}

		if ( isset( $_POST['_dhl_height'] ) ) {
			$product->update_meta_data( '_dhl_height', wc_clean( wp_unslash( $_POST['_dhl_height'] ) ) );
		}
	}
} 