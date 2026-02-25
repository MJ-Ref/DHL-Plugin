<?php
/**
 * DHL Services and subservices.
 *
 * @package WC_Shipping_DHL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter to modify the DHL services list.
 *
 * @var array List of services.
 *
 * @since 1.0.0
 */
return apply_filters(
	'wc_dhl_services',
	array(
		'0' => array(
			'name'        => __( 'DHL Express Worldwide', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Time definite delivery by end of next possible business day', 'woocommerce-shipping-dhl' ),
		),
		'1' => array(
			'name'        => __( 'DHL Express Domestic', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Time definite delivery by end of next possible business day (domestic)', 'woocommerce-shipping-dhl' ),
		),
		'2' => array(
			'name'        => __( 'DHL Express 9:00', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Delivery by 9:00 am next possible business day', 'woocommerce-shipping-dhl' ),
		),
		'3' => array(
			'name'        => __( 'DHL Express 10:30', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Delivery by 10:30 am next possible business day', 'woocommerce-shipping-dhl' ),
		),
		'4' => array(
			'name'        => __( 'DHL Express 12:00', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Delivery by 12:00 noon next possible business day', 'woocommerce-shipping-dhl' ),
		),
		'5' => array(
			'name'        => __( 'DHL Express Easy', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Drop off at DHL Service point for time definite delivery', 'woocommerce-shipping-dhl' ),
		),
		'7' => array(
			'name'        => __( 'DHL Economy Select', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Day definite delivery, typically 2-3 days within Europe', 'woocommerce-shipping-dhl' ),
		),
		'8' => array(
			'name'        => __( 'DHL Express 12:00', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Delivery by 12:00 noon next possible business day', 'woocommerce-shipping-dhl' ),
		),
		'9' => array(
			'name'        => __( 'DHL Express Envelope', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Letter-sized documents only', 'woocommerce-shipping-dhl' ),
		),
		'B' => array(
			'name'        => __( 'DHL Express Breakbulk', 'woocommerce-shipping-dhl' ),
			'description' => __( 'For large shipments that need to be broken down', 'woocommerce-shipping-dhl' ),
		),
		'C' => array(
			'name'        => __( 'DHL Express Medical Express', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Specialized service for medical shipments', 'woocommerce-shipping-dhl' ),
		),
		'D' => array(
			'name'        => __( 'DHL Express 9:00', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Delivery by 9:00 am next possible business day', 'woocommerce-shipping-dhl' ),
		),
		'E' => array(
			'name'        => __( 'DHL Express 10:30', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Delivery by 10:30 am next possible business day', 'woocommerce-shipping-dhl' ),
		),
		'F' => array(
			'name'        => __( 'DHL Express Freight Worldwide', 'woocommerce-shipping-dhl' ),
			'description' => __( 'For palletized freight over 30kg', 'woocommerce-shipping-dhl' ),
		),
		'G' => array(
			'name'        => __( 'DHL Express Domestic Economy Select', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Day definite delivery within the country', 'woocommerce-shipping-dhl' ),
		),
		'H' => array(
			'name'        => __( 'DHL Express Economy Select', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Day definite delivery with cost-efficient option', 'woocommerce-shipping-dhl' ),
		),
		'I' => array(
			'name'        => __( 'DHL Express Break Bulk Economy', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Economy option for large shipments that need to be broken down', 'woocommerce-shipping-dhl' ),
		),
		'J' => array(
			'name'        => __( 'DHL Express Jumbo Box', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Pre-defined packaging for large shipments', 'woocommerce-shipping-dhl' ),
		),
		'K' => array(
			'name'        => __( 'DHL Express 9:00', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Delivery by 9:00 am next possible business day', 'woocommerce-shipping-dhl' ),
		),
		'L' => array(
			'name'        => __( 'DHL Express 10:30', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Delivery by 10:30 am next possible business day', 'woocommerce-shipping-dhl' ),
		),
		'M' => array(
			'name'        => __( 'DHL Express 12:00', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Delivery by 12:00 noon next possible business day', 'woocommerce-shipping-dhl' ),
		),
		'N' => array(
			'name'        => __( 'DHL Express Domestic Express', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Domestic express delivery', 'woocommerce-shipping-dhl' ),
		),
		'O' => array(
			'name'        => __( 'DHL Express Others', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Other DHL Express services', 'woocommerce-shipping-dhl' ),
		),
		'P' => array(
			'name'        => __( 'DHL Express Worldwide', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Time definite delivery by end of next possible business day', 'woocommerce-shipping-dhl' ),
		),
		'Q' => array(
			'name'        => __( 'DHL Express Medical Express', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Specialized service for medical shipments', 'woocommerce-shipping-dhl' ),
		),
		'R' => array(
			'name'        => __( 'DHL Express GlobalMail Business', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Business mail service with global reach', 'woocommerce-shipping-dhl' ),
		),
		'S' => array(
			'name'        => __( 'DHL Express Same Day', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Same day delivery service', 'woocommerce-shipping-dhl' ),
		),
		'T' => array(
			'name'        => __( 'DHL Express 12:00', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Delivery by 12:00 noon next possible business day', 'woocommerce-shipping-dhl' ),
		),
		'U' => array(
			'name'        => __( 'DHL Express Worldwide', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Time definite delivery by end of next possible business day', 'woocommerce-shipping-dhl' ),
		),
		'V' => array(
			'name'        => __( 'DHL Express Europack', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Special service for European deliveries', 'woocommerce-shipping-dhl' ),
		),
		'W' => array(
			'name'        => __( 'DHL Express Economy Select', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Day definite delivery with cost-efficient option', 'woocommerce-shipping-dhl' ),
		),
		'X' => array(
			'name'        => __( 'DHL Express Envelope', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Letter-sized documents only', 'woocommerce-shipping-dhl' ),
		),
		'Y' => array(
			'name'        => __( 'DHL Express 12:00', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Delivery by 12:00 noon next possible business day', 'woocommerce-shipping-dhl' ),
		),
		'Z' => array(
			'name'        => __( 'DHL Express Destination Charges', 'woocommerce-shipping-dhl' ),
			'description' => __( 'Charges paid at destination', 'woocommerce-shipping-dhl' ),
		),
	)
);
