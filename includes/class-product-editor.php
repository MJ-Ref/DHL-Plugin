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
		add_action( 'woocommerce_product_options_shipping', array( $this, 'product_options_shipping' ) );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'variation_options_shipping' ), 10, 3 );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_product_dimensions' ) );
		add_action( 'woocommerce_admin_process_variation_object', array( $this, 'save_variation_shipping_meta' ), 10, 2 );
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
		$dhl_width  = $product_object->get_meta( '_dhl_width', true );
		$dhl_height = $product_object->get_meta( '_dhl_height', true );

		require WC_SHIPPING_DHL_PLUGIN_DIR . '/includes/views/html-product-dimensions.php';
	}

	/**
	 * Add DHL customs fields to the product shipping options.
	 *
	 * @return void
	 */
	public function product_options_shipping() {
		global $product_object;

		if ( ! $product_object ) {
			return;
		}

		echo '<div class="options_group dhl-customs">';

		woocommerce_wp_text_input(
			array(
				'id'          => '_wc_dhl_commodity_code',
				'label'       => __( 'DHL Commodity Code', 'woocommerce-shipping-dhl' ),
				'description' => __( 'Optional HS / commodity code used for DHL landed-cost and customs flows.', 'woocommerce-shipping-dhl' ),
				'desc_tip'    => true,
				'value'       => $product_object->get_meta( '_wc_dhl_commodity_code', true ),
			)
		);

		echo '</div>';
	}

	/**
	 * Add DHL customs fields to product variations.
	 *
	 * @param int      $loop           Variation loop index.
	 * @param array    $variation_data Variation data.
	 * @param \WP_Post $variation      Variation post object.
	 * @return void
	 */
	public function variation_options_shipping( $loop, $variation_data, $variation ) {
		$variation_product = wc_get_product( $variation->ID );
		if ( ! $variation_product ) {
			return;
		}

		woocommerce_wp_text_input(
			array(
				'id'            => '_wc_dhl_commodity_code_' . $loop,
				'name'          => 'variable_wc_dhl_commodity_code[' . $loop . ']',
				'label'         => __( 'DHL Commodity Code', 'woocommerce-shipping-dhl' ),
				'description'   => __( 'Optional HS / commodity code used for DHL customs flows.', 'woocommerce-shipping-dhl' ),
				'desc_tip'      => true,
				'value'         => $variation_product->get_meta( '_wc_dhl_commodity_code', true ),
				'wrapper_class' => 'form-row form-row-full',
			)
		);
	}

	/**
	 * Save the DHL dimensions when the product is saved.
	 *
	 * @param \WC_Product $product Product being saved.
	 */
	public function save_product_dimensions( $product ) {
		$meta_nonce = isset( $_POST['woocommerce_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ) : '';
		if ( empty( $meta_nonce ) || ! wp_verify_nonce( $meta_nonce, 'woocommerce_save_data' ) ) {
			return;
		}

		if ( isset( $_POST['_dhl_length'] ) ) {
			$dhl_length = sanitize_text_field( wp_unslash( $_POST['_dhl_length'] ) );
			$product->update_meta_data( '_dhl_length', wc_format_decimal( $dhl_length ) );
		}

		if ( isset( $_POST['_dhl_width'] ) ) {
			$dhl_width = sanitize_text_field( wp_unslash( $_POST['_dhl_width'] ) );
			$product->update_meta_data( '_dhl_width', wc_format_decimal( $dhl_width ) );
		}

		if ( isset( $_POST['_dhl_height'] ) ) {
			$dhl_height = sanitize_text_field( wp_unslash( $_POST['_dhl_height'] ) );
			$product->update_meta_data( '_dhl_height', wc_format_decimal( $dhl_height ) );
		}

		if ( isset( $_POST['_wc_dhl_commodity_code'] ) ) {
			$commodity_code = sanitize_text_field( wp_unslash( $_POST['_wc_dhl_commodity_code'] ) );
			$product->update_meta_data( '_wc_dhl_commodity_code', $this->sanitize_commodity_code( $commodity_code ) );
		}
	}

	/**
	 * Save variation customs data.
	 *
	 * @param \WC_Product_Variation $variation Variation being saved.
	 * @param int                   $index Variation loop index.
	 * @return void
	 */
	public function save_variation_shipping_meta( $variation, $index ) {
		$meta_nonce = isset( $_POST['woocommerce_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ) : '';
		if ( empty( $meta_nonce ) || ! wp_verify_nonce( $meta_nonce, 'woocommerce_save_data' ) ) {
			return;
		}

		if ( isset( $_POST['variable_wc_dhl_commodity_code'][ $index ] ) ) {
			$commodity_code = sanitize_text_field( wp_unslash( $_POST['variable_wc_dhl_commodity_code'][ $index ] ) );
			$variation->update_meta_data( '_wc_dhl_commodity_code', $this->sanitize_commodity_code( $commodity_code ) );
		}
	}

	/**
	 * Sanitize a commodity code for storage.
	 *
	 * @param string $commodity_code Raw commodity code.
	 * @return string
	 */
	private function sanitize_commodity_code( string $commodity_code ): string {
		return preg_replace( '/[^0-9]/', '', $commodity_code );
	}
}
