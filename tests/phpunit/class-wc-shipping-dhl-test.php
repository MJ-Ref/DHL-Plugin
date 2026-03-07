<?php
/**
 * DHL Shipping Method Tests
 *
 * @package WC_Shipping_DHL
 */

use WooCommerce\DHL\API\REST\Shipment_Client;

/**
 * Class WC_Shipping_DHL_Test
 */
class WC_Shipping_DHL_Test extends WP_UnitTestCase {

	/**
	 * Shipping zone under test.
	 *
	 * @var WC_Shipping_Zone
	 */
	protected $shipping_zone;

	/**
	 * DHL shipping method under test.
	 *
	 * @var WooCommerce\DHL\WC_Shipping_DHL
	 */
	protected $shipping_method;

	/**
	 * Setup test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		
		// Ensure WooCommerce is loaded.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not available.' );
		}
		
		// Create a shipping zone.
		$this->shipping_zone = new WC_Shipping_Zone();
		$this->shipping_zone->set_zone_name( 'Test Zone' );
		$this->shipping_zone->save();
		
		// Add DHL shipping method to the zone.
		$this->shipping_zone->add_shipping_method( 'dhl' );
		
		// Get an instance of the shipping method.
		$shipping_methods = $this->shipping_zone->get_shipping_methods();
		$this->shipping_method = reset( $shipping_methods );
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->shipping_zone->delete();
	}

	/**
	 * Test if the shipping method is loaded correctly.
	 */
	public function test_shipping_method_loaded() {
		$this->assertInstanceOf( 'WooCommerce\DHL\WC_Shipping_DHL', $this->shipping_method );
	}

	/**
	 * Test if default settings are set correctly.
	 */
	public function test_default_settings() {
		$this->assertEquals( 'DHL Express', $this->shipping_method->get_option( 'title' ) );
		$this->assertEquals( 'test', $this->shipping_method->get_option( 'environment' ) );
		$this->assertEquals( 'in', $this->shipping_method->get_option( 'dimension_unit' ) );
		$this->assertEquals( 'LBS', $this->shipping_method->get_option( 'weight_unit' ) );
		$this->assertEquals( 'per_item', $this->shipping_method->get_option( 'packing_method' ) );
	}

	/**
	 * Test weight conversion.
	 */
	public function test_weight_conversion() {
		// Convert 1 kg to lbs.
		$lbs = $this->shipping_method->get_converted_weight( 1, 'kg', 'LBS' );
		$this->assertEqualsWithDelta( 2.20462, $lbs, 0.0001 );
		
		// Convert 1 lbs to kg.
		$kg = $this->shipping_method->get_converted_weight( 1, 'lbs', 'KG' );
		$this->assertEqualsWithDelta( 0.453592, $kg, 0.0001 );
	}

	/**
	 * Test dimension conversion.
	 */
	public function test_dimension_conversion() {
		// Convert 1 inch to cm.
		$cm = $this->shipping_method->get_converted_dimension( 1, 'in', 'cm' );
		$this->assertEqualsWithDelta( 2.54, $cm, 0.0001 );
		
		// Convert 1 cm to inch.
		$inch = $this->shipping_method->get_converted_dimension( 1, 'cm', 'in' );
		$this->assertEqualsWithDelta( 0.3937, $inch, 0.0001 );
	}

	/**
	 * Test if the rate ID is generated correctly.
	 */
	public function test_rate_id() {
		$rate_id = $this->shipping_method->get_rate_id( '0' );
		$expected_id = 'dhl_0_' . $this->shipping_method->instance_id;
		$this->assertEquals( $expected_id, $rate_id );
	}

	/**
	 * Test if the shipping method supports correct features.
	 */
	public function test_supports() {
		$this->assertTrue( in_array( 'shipping-zones', $this->shipping_method->supports, true ) );
		$this->assertTrue( in_array( 'instance-settings', $this->shipping_method->supports, true ) );
		$this->assertTrue( in_array( 'settings', $this->shipping_method->supports, true ) );
	}

	/**
	 * Test that new DHL production-readiness feature toggles are present.
	 */
	public function test_feature_toggle_fields_exist() {
		$this->assertArrayHasKey( 'service_point_lookup', $this->shipping_method->instance_form_fields );
		$this->assertArrayHasKey( 'landed_cost_estimate', $this->shipping_method->instance_form_fields );
		$this->assertArrayHasKey( 'tracking_sync', $this->shipping_method->instance_form_fields );
		$this->assertArrayHasKey( 'tracking_customer_notifications', $this->shipping_method->instance_form_fields );
	}

	/**
	 * Test feature-toggle defaults.
	 */
	public function test_feature_toggle_defaults_disabled() {
		$this->assertFalse( $this->shipping_method->is_service_point_lookup_enabled() );
		$this->assertFalse( $this->shipping_method->is_landed_cost_estimate_enabled() );
		$this->assertFalse( $this->shipping_method->is_tracking_sync_enabled() );
		$this->assertFalse( $this->shipping_method->is_tracking_customer_notifications_enabled() );
	}

	/**
	 * Test if the correct DHL services are returned.
	 */
	public function test_get_dhl_services() {
		$services = $this->shipping_method->get_dhl_services();
		$this->assertIsArray( $services );
		$this->assertArrayHasKey( '0', $services );
		$this->assertArrayHasKey( 'P', $services );
	}

	/**
	 * Test if address validity is updated correctly.
	 */
	public function test_set_is_valid_destination_address() {
		$this->shipping_method->set_is_valid_destination_address( true );
		$reflection = new ReflectionClass( $this->shipping_method );
		$property = $reflection->getProperty( 'is_valid_destination_address' );
		$property->setAccessible( true );
		$this->assertTrue( $property->getValue( $this->shipping_method ) );
		
		$this->shipping_method->set_is_valid_destination_address( false );
		$this->assertFalse( $property->getValue( $this->shipping_method ) );
	}

	/**
	 * Test per-item packing emits one package request per quantity.
	 */
	public function test_per_item_shipping_honours_quantity() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Quantity Product' );
		$product->set_regular_price( '15.00' );
		$product->set_weight( '2.5' );
		$product->set_length( '10' );
		$product->set_width( '8' );
		$product->set_height( '4' );
		$product->save();

		$package = array(
			'destination' => array(
				'country'   => 'US',
				'state'     => 'NY',
				'postcode'  => '10001',
				'city'      => 'New York',
				'address_1' => '123 Test Street',
			),
			'contents'    => array(
				123 => array(
					'data'     => $product,
					'quantity' => 3,
				),
			),
		);

		$requests = $this->shipping_method->prepare_package_requests( $package );

		$this->assertCount( 3, $requests );
		$this->assertSame( '2.5', $requests[0]['weight']['value'] );
	}

	/**
	 * Test shipment payload packages reuse the configured packing logic.
	 */
	public function test_shipment_client_builds_multiple_packages_from_order() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Shipment Product' );
		$product->set_regular_price( '12.00' );
		$product->set_weight( '1.2' );
		$product->set_length( '9' );
		$product->set_width( '6' );
		$product->set_height( '3' );
		$product->save();

		$order = wc_create_order();
		$order->set_address(
			array(
				'first_name' => 'Test',
				'last_name'  => 'User',
				'address_1'  => '123 Shipping Ln',
				'city'       => 'New York',
				'state'      => 'NY',
				'postcode'   => '10001',
				'country'    => 'US',
			),
			'shipping'
		);
		$order->add_product( $product, 2 );
		$order->calculate_totals();

		$shipment_client = new Shipment_Client( $this->shipping_method );
		$reflection      = new ReflectionMethod( $shipment_client, 'get_order_packages' );
		$reflection->setAccessible( true );

		$packages = $reflection->invoke( $shipment_client, $order );

		$this->assertCount( 2, $packages );
		$this->assertSame( 1.2, $packages[0]['weight'] );
		$this->assertSame( 9, $packages[0]['dimensions']['length'] );
	}
}
