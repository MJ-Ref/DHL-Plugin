<?php
/**
 * PHPStan-only stubs for plugin constants and optional runtime classes.
 *
 * @package WC_Shipping_DHL
 */

if ( ! defined( 'WC_SHIPPING_DHL_VERSION' ) ) {
	define( 'WC_SHIPPING_DHL_VERSION', '1.0.0' );
}

if ( ! defined( 'WC_SHIPPING_DHL_PLUGIN_DIR' ) ) {
	define( 'WC_SHIPPING_DHL_PLUGIN_DIR', __DIR__ );
}

if ( ! defined( 'WC_SHIPPING_DHL_PLUGIN_URL' ) ) {
	define( 'WC_SHIPPING_DHL_PLUGIN_URL', '' );
}

if ( ! defined( 'WC_SHIPPING_DHL_API_VERSION' ) ) {
	define( 'WC_SHIPPING_DHL_API_VERSION', '2.12.1' );
}

namespace WooCommerce\BoxPacker;

/**
 * Minimal PHPStan stub for the box packer abstraction.
 */
abstract class Abstract_Packer {

	/**
	 * Set items for packing.
	 *
	 * @param array $items Packable items.
	 * @return void
	 */
	public function set_items( array $items ): void {}

	/**
	 * Add a box definition.
	 *
	 * @param array $box Box payload.
	 * @return void
	 */
	public function add_box( array $box ): void {}

	/**
	 * Pack items into boxes.
	 *
	 * @return array
	 */
	public function pack(): array {
		return array();
	}
}

/**
 * Minimal PHPStan stub for the WooCommerce box packer implementation.
 */
class WC_Boxpack extends Abstract_Packer {}
