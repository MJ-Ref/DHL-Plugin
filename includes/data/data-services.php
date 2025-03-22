<?php
/**
 * DHL Services and subservices
 *
 * @package WC_Shipping_DHL
 */

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
			'name'        => 'DHL Express Worldwide',
			'description' => 'Time definite delivery by end of next possible business day',
		),
		'1' => array(
			'name'        => 'DHL Express Domestic',
			'description' => 'Time definite delivery by end of next possible business day (domestic)',
		),
		'2' => array(
			'name'        => 'DHL Express 9:00',
			'description' => 'Delivery by 9:00 am next possible business day',
		),
		'3' => array(
			'name'        => 'DHL Express 10:30',
			'description' => 'Delivery by 10:30 am next possible business day',
		),
		'4' => array(
			'name'        => 'DHL Express 12:00',
			'description' => 'Delivery by 12:00 noon next possible business day',
		),
		'5' => array(
			'name'        => 'DHL Express Easy',
			'description' => 'Drop off at DHL Service point for time definite delivery',
		),
		'7' => array(
			'name'        => 'DHL Economy Select',
			'description' => 'Day definite delivery, typically 2-3 days within Europe',
		),
		'8' => array(
			'name'        => 'DHL Express 12:00',
			'description' => 'Delivery by 12:00 noon next possible business day',
		),
		'9' => array(
			'name'        => 'DHL Express Envelope',
			'description' => 'Letter-sized documents only',
		),
		'B' => array(
			'name'        => 'DHL Express Breakbulk',
			'description' => 'For large shipments that need to be broken down',
		),
		'C' => array(
			'name'        => 'DHL Express Medical Express',
			'description' => 'Specialized service for medical shipments',
		),
		'D' => array(
			'name'        => 'DHL Express Express 9:00',
			'description' => 'Delivery by 9:00 am next possible business day',
		),
		'E' => array(
			'name'        => 'DHL Express Express 10:30',
			'description' => 'Delivery by 10:30 am next possible business day',
		),
		'F' => array(
			'name'        => 'DHL Express Freight Worldwide',
			'description' => 'For palletized freight over 30kg',
		),
		'G' => array(
			'name'        => 'DHL Express Domestic Economy Select',
			'description' => 'Day definite delivery within the country',
		),
		'H' => array(
			'name'        => 'DHL Express Economy Select',
			'description' => 'Day definite delivery with cost-efficient option',
		),
		'I' => array(
			'name'        => 'DHL Express Break Bulk Economy',
			'description' => 'Economy option for large shipments that need to be broken down',
		),
		'J' => array(
			'name'        => 'DHL Express Jumbo Box',
			'description' => 'Pre-defined packaging for large shipments',
		),
		'K' => array(
			'name'        => 'DHL Express Express 9:00',
			'description' => 'Delivery by 9:00 am next possible business day',
		),
		'L' => array(
			'name'        => 'DHL Express Express 10:30',
			'description' => 'Delivery by 10:30 am next possible business day',
		),
		'M' => array(
			'name'        => 'DHL Express Express 12:00',
			'description' => 'Delivery by 12:00 noon next possible business day',
		),
		'N' => array(
			'name'        => 'DHL Express Domestic Express',
			'description' => 'Domestic express delivery',
		),
		'O' => array(
			'name'        => 'DHL Express Others',
			'description' => 'Other DHL Express services',
		),
		'P' => array(
			'name'        => 'DHL Express Worldwide',
			'description' => 'Time definite delivery by end of next possible business day',
		),
		'Q' => array(
			'name'        => 'DHL Express Medical Express',
			'description' => 'Specialized service for medical shipments',
		),
		'R' => array(
			'name'        => 'DHL Express GlobalMail Business',
			'description' => 'Business mail service with global reach',
		),
		'S' => array(
			'name'        => 'DHL Express Same Day',
			'description' => 'Same day delivery service',
		),
		'T' => array(
			'name'        => 'DHL Express Express 12:00',
			'description' => 'Delivery by 12:00 noon next possible business day',
		),
		'U' => array(
			'name'        => 'DHL Express Express Worldwide',
			'description' => 'Time definite delivery by end of next possible business day',
		),
		'V' => array(
			'name'        => 'DHL Express Europack',
			'description' => 'Special service for European deliveries',
		),
		'W' => array(
			'name'        => 'DHL Express Economy Select',
			'description' => 'Day definite delivery with cost-efficient option',
		),
		'X' => array(
			'name'        => 'DHL Express Express Envelope',
			'description' => 'Letter-sized documents only',
		),
		'Y' => array(
			'name'        => 'DHL Express Express 12:00',
			'description' => 'Delivery by 12:00 noon next possible business day',
		),
		'Z' => array(
			'name'        => 'DHL Express Destination Charges',
			'description' => 'Charges paid at destination',
		),
	)
);