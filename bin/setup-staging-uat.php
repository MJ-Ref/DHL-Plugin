<?php
/**
 * Seed a WordPress site with repeatable DHL staging UAT settings and fixtures.
 *
 * Usage:
 *   wp eval-file bin/setup-staging-uat.php -- <instance-id>
 *
 * @package WC_Shipping_DHL
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	fwrite( STDERR, "This script must be executed with WP-CLI.\n" );
	exit( 1 );
}

$instance_id = isset( $args[0] ) ? absint( $args[0] ) : 0;

if ( $instance_id <= 0 ) {
	WP_CLI::error( 'Usage: wp eval-file bin/setup-staging-uat.php -- <instance-id>' );
}

$option_key = sprintf( 'woocommerce_dhl_%d_settings', $instance_id );
$settings   = get_option( $option_key, array() );

if ( ! is_array( $settings ) ) {
	$settings = array();
}

$settings['environment']                     = 'test';
$settings['dimension_unit']                  = 'cm';
$settings['weight_unit']                     = 'KG';
$settings['packing_method']                  = 'box_packing';
$settings['service_point_lookup']            = 'yes';
$settings['landed_cost_estimate']            = 'yes';
$settings['tracking_sync']                   = 'yes';
$settings['tracking_customer_notifications'] = 'yes';
$settings['debug']                           = 'yes';
$settings['boxes']                           = array(
	array(
		'id'         => 'dhl_small_carton',
		'name'       => 'DHL Small Carton',
		'length'     => '30',
		'width'      => '22',
		'height'     => '10',
		'box_weight' => '0.15',
		'max_weight' => '2.0',
		'enabled'    => '1',
	),
	array(
		'id'         => 'dhl_medium_carton',
		'name'       => 'DHL Medium Carton',
		'length'     => '40',
		'width'      => '30',
		'height'     => '20',
		'box_weight' => '0.25',
		'max_weight' => '5.0',
		'enabled'    => '1',
	),
);

update_option( $option_key, $settings );

$fixtures = array(
	array(
		'name'           => 'DHL Test Tee',
		'sku'            => 'DHL-TEE',
		'price'          => '35.00',
		'weight'         => '0.40',
		'length'         => '30',
		'width'          => '20',
		'height'         => '3',
		'commodity_code' => '610910',
	),
	array(
		'name'           => 'DHL Test Mug',
		'sku'            => 'DHL-MUG',
		'price'          => '22.00',
		'weight'         => '0.80',
		'length'         => '12',
		'width'          => '12',
		'height'         => '10',
		'commodity_code' => '691200',
	),
	array(
		'name'           => 'DHL Test Charger',
		'sku'            => 'DHL-CHG',
		'price'          => '49.00',
		'weight'         => '0.20',
		'length'         => '10',
		'width'          => '8',
		'height'         => '4',
		'commodity_code' => '850440',
	),
);

foreach ( $fixtures as $fixture ) {
	$product_id = wc_get_product_id_by_sku( $fixture['sku'] );
	$product    = $product_id ? wc_get_product( $product_id ) : new WC_Product_Simple();

	if ( ! $product instanceof WC_Product ) {
		$product = new WC_Product_Simple();
	}

	$product->set_name( $fixture['name'] );
	$product->set_sku( $fixture['sku'] );
	$product->set_regular_price( $fixture['price'] );
	$product->set_price( $fixture['price'] );
	$product->set_catalog_visibility( 'hidden' );
	$product->set_status( 'publish' );
	$product->set_virtual( false );
	$product->set_downloadable( false );
	$product->set_weight( $fixture['weight'] );
	$product->set_length( $fixture['length'] );
	$product->set_width( $fixture['width'] );
	$product->set_height( $fixture['height'] );
	$product->update_meta_data( '_wc_dhl_commodity_code', $fixture['commodity_code'] );
	$product->save();

	WP_CLI::log( sprintf( 'Seeded %s (%d)', $fixture['sku'], $product->get_id() ) );
}

WP_CLI::success( sprintf( 'Updated DHL instance %d (%s). Review credentials, origin address, and shipping zones before running UAT.', $instance_id, $option_key ) );
