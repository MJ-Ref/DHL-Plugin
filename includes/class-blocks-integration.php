<?php
/**
 * Blocks integration class file.
 *
 * @package WC_Shipping_DHL
 */

namespace WooCommerce\DHL;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Class WC_DHL_Blocks_Integration
 */
class WC_DHL_Blocks_Integration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'woocommerce-shipping-dhl';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		// Nothing to initialize.
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array();
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array();
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		return array();
	}
}