<?php
/**
 * DHL Shipping Method Tests
 *
 * @package WC_Shipping_DHL
 */

/**
 * Class WC_Shipping_DHL_Test
 */
class WC_Shipping_DHL_Test extends WP_UnitTestCase {

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
}