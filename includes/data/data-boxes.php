<?php
/**
 * DHL default box dimensions data file.
 *
 * @package WC_Shipping_DHL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	// Standard DHL Express boxes with dimensions in cm.
	'dhl_express_box_1' => array(
		'name'       => __( 'DHL Express Box 1', 'woocommerce-shipping-dhl' ),
		'length'     => 33.7,
		'width'      => 32.2,
		'height'     => 5.2,
		'box_weight' => 0.3,
		'max_weight' => 25,
		'enabled'    => true,
	),
	'dhl_express_box_2' => array(
		'name'       => __( 'DHL Express Box 2', 'woocommerce-shipping-dhl' ),
		'length'     => 33.7,
		'width'      => 32.2,
		'height'     => 10,
		'box_weight' => 0.4,
		'max_weight' => 25,
		'enabled'    => true,
	),
	'dhl_express_box_3' => array(
		'name'       => __( 'DHL Express Box 3', 'woocommerce-shipping-dhl' ),
		'length'     => 45.8,
		'width'      => 41.7,
		'height'     => 19.1,
		'box_weight' => 0.9,
		'max_weight' => 30,
		'enabled'    => true,
	),
	'dhl_express_box_4' => array(
		'name'       => __( 'DHL Express Box 4', 'woocommerce-shipping-dhl' ),
		'length'     => 40.4,
		'width'      => 32.4,
		'height'     => 28.0,
		'box_weight' => 1.2,
		'max_weight' => 30,
		'enabled'    => true,
	),
	'dhl_express_box_5' => array(
		'name'       => __( 'DHL Express Box 5', 'woocommerce-shipping-dhl' ),
		'length'     => 47.2,
		'width'      => 32.4,
		'height'     => 33.0,
		'box_weight' => 1.5,
		'max_weight' => 30,
		'enabled'    => true,
	),
	'dhl_express_tube' => array(
		'name'       => __( 'DHL Express Tube', 'woocommerce-shipping-dhl' ),
		'length'     => 96.5,
		'width'      => 15.2,
		'height'     => 15.2,
		'box_weight' => 0.5,
		'max_weight' => 20,
		'enabled'    => true,
	),
	'dhl_express_envelope' => array(
		'name'       => __( 'DHL Express Envelope', 'woocommerce-shipping-dhl' ),
		'length'     => 35.3,
		'width'      => 27.5,
		'height'     => 1.0,
		'box_weight' => 0.1,
		'max_weight' => 0.5,
		'enabled'    => true,
	),
	// Generic box sizes.
	'small_box' => array(
		'name'       => __( 'Small Box', 'woocommerce-shipping-dhl' ),
		'length'     => 20,
		'width'      => 15,
		'height'     => 10,
		'box_weight' => 0.25,
		'max_weight' => 10,
		'enabled'    => true,
	),
	'medium_box' => array(
		'name'       => __( 'Medium Box', 'woocommerce-shipping-dhl' ),
		'length'     => 30,
		'width'      => 20,
		'height'     => 15,
		'box_weight' => 0.5,
		'max_weight' => 20,
		'enabled'    => true,
	),
	'large_box' => array(
		'name'       => __( 'Large Box', 'woocommerce-shipping-dhl' ),
		'length'     => 40,
		'width'      => 30,
		'height'     => 20,
		'box_weight' => 1,
		'max_weight' => 30,
		'enabled'    => true,
	),
); 