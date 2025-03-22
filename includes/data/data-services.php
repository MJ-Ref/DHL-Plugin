<?php
/**
 * DHL services data file.
 *
 * @package WC_Shipping_DHL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	// Express services.
	'EXPRESS_DOMESTIC' => array(
		'name' => __( 'DHL Express Domestic', 'woocommerce-shipping-dhl' ),
		'description' => __( 'Fast delivery within your country', 'woocommerce-shipping-dhl' ),
		'domestic' => true,
		'international' => false,
	),
	'EXPRESS_WORLDWIDE' => array(
		'name' => __( 'DHL Express Worldwide', 'woocommerce-shipping-dhl' ),
		'description' => __( 'Worldwide express delivery', 'woocommerce-shipping-dhl' ),
		'domestic' => false,
		'international' => true,
	),
	'EXPRESS_WORLDWIDE_EU' => array(
		'name' => __( 'DHL Express Worldwide EU', 'woocommerce-shipping-dhl' ),
		'description' => __( 'Express delivery within EU', 'woocommerce-shipping-dhl' ),
		'domestic' => false,
		'international' => true,
	),
	'EXPRESS_9:00' => array(
		'name' => __( 'DHL Express 9:00', 'woocommerce-shipping-dhl' ),
		'description' => __( 'Delivery by 9:00 AM next business day', 'woocommerce-shipping-dhl' ),
		'domestic' => true,
		'international' => true,
	),
	'EXPRESS_10:30' => array(
		'name' => __( 'DHL Express 10:30', 'woocommerce-shipping-dhl' ),
		'description' => __( 'Delivery by 10:30 AM next business day', 'woocommerce-shipping-dhl' ),
		'domestic' => true,
		'international' => true,
	),
	'EXPRESS_12:00' => array(
		'name' => __( 'DHL Express 12:00', 'woocommerce-shipping-dhl' ),
		'description' => __( 'Delivery by noon next business day', 'woocommerce-shipping-dhl' ),
		'domestic' => true,
		'international' => true,
	),
	'EXPRESS_EASY' => array(
		'name' => __( 'DHL Express Easy', 'woocommerce-shipping-dhl' ),
		'description' => __( 'Simple and flexible international shipping', 'woocommerce-shipping-dhl' ),
		'domestic' => false,
		'international' => true,
	),
	'ECONOMY_SELECT' => array(
		'name' => __( 'DHL Economy Select', 'woocommerce-shipping-dhl' ),
		'description' => __( 'Cost-effective international shipping', 'woocommerce-shipping-dhl' ),
		'domestic' => false,
		'international' => true,
	),
	'BREAKBULK_EXPRESS' => array(
		'name' => __( 'DHL Breakbulk Express', 'woocommerce-shipping-dhl' ),
		'description' => __( 'Express delivery for pallet or larger shipments', 'woocommerce-shipping-dhl' ),
		'domestic' => false,
		'international' => true,
	),
	'MEDICAL_EXPRESS' => array(
		'name' => __( 'DHL Medical Express', 'woocommerce-shipping-dhl' ),
		'description' => __( 'Express delivery for medical shipments', 'woocommerce-shipping-dhl' ),
		'domestic' => true,
		'international' => true,
	),
	'EXPRESS_ENVELOPE' => array(
		'name' => __( 'DHL Express Envelope', 'woocommerce-shipping-dhl' ),
		'description' => __( 'Express delivery for documents', 'woocommerce-shipping-dhl' ),
		'domestic' => false,
		'international' => true,
	),
	'EXPRESS_EASY_DOC' => array(
		'name' => __( 'DHL Express Easy Doc', 'woocommerce-shipping-dhl' ),
		'description' => __( 'Fast delivery for documents', 'woocommerce-shipping-dhl' ),
		'domestic' => false,
		'international' => true,
	),
);