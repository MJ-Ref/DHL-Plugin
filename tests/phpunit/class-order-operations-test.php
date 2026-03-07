<?php
/**
 * Order operations tests.
 *
 * @package WC_Shipping_DHL
 */

use WooCommerce\DHL\Order_Operations;

/**
 * Class Order_Operations_Test
 */
class Order_Operations_Test extends WP_UnitTestCase {

	/**
	 * Cron hook name.
	 *
	 * @var string
	 */
	private $cron_hook = 'wc_dhl_tracking_sync_event';

	/**
	 * Shipping zone under test.
	 *
	 * @var WC_Shipping_Zone
	 */
	private $shipping_zone;

	/**
	 * DHL shipping method instance under test.
	 *
	 * @var WooCommerce\DHL\WC_Shipping_DHL
	 */
	private $shipping_method;

	/**
	 * Setup test state.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_clear_scheduled_hook( $this->cron_hook );

		$this->shipping_zone = new WC_Shipping_Zone();
		$this->shipping_zone->set_zone_name( 'Tracking Sync Zone' );
		$this->shipping_zone->save();
		$this->shipping_zone->add_shipping_method( 'dhl' );

		$shipping_methods      = $this->shipping_zone->get_shipping_methods();
		$this->shipping_method = reset( $shipping_methods );
	}

	/**
	 * Cleanup test state.
	 */
	public function tearDown(): void {
		wp_clear_scheduled_hook( $this->cron_hook );
		if ( $this->shipping_zone instanceof WC_Shipping_Zone ) {
			$this->shipping_zone->delete();
		}
		parent::tearDown();
	}

	/**
	 * Verify tracking cron schedule registration.
	 */
	public function test_add_tracking_cron_schedule_registers_interval() {
		$operations = new Order_Operations();
		$schedules  = $operations->add_tracking_cron_schedule( array() );

		$this->assertArrayHasKey( 'wc_dhl_every_fifteen_minutes', $schedules );
		$this->assertEquals( 15 * MINUTE_IN_SECONDS, $schedules['wc_dhl_every_fifteen_minutes']['interval'] );
	}

	/**
	 * Verify tracking sync event can be scheduled.
	 */
	public function test_maybe_schedule_tracking_sync_requires_enabled_instance() {
		$operations = new Order_Operations();
		$operations->maybe_schedule_tracking_sync();

		$this->assertFalse( wp_next_scheduled( $this->cron_hook ) );
	}

	/**
	 * Verify tracking sync event schedules when an instance enables it.
	 */
	public function test_maybe_schedule_tracking_sync_schedules_event_when_enabled() {
		$this->update_tracking_sync_setting( 'yes' );

		$operations = new Order_Operations();
		$operations->maybe_schedule_tracking_sync();

		$this->assertNotFalse( wp_next_scheduled( $this->cron_hook ) );
	}

	/**
	 * Verify tracking sync event is cleared when all instances disable it.
	 */
	public function test_maybe_schedule_tracking_sync_clears_existing_event_when_disabled() {
		$this->update_tracking_sync_setting( 'yes' );

		$operations = new Order_Operations();
		$operations->maybe_schedule_tracking_sync();
		$this->assertNotFalse( wp_next_scheduled( $this->cron_hook ) );

		$this->update_tracking_sync_setting( 'no' );
		$operations->maybe_schedule_tracking_sync();

		$this->assertFalse( wp_next_scheduled( $this->cron_hook ) );
	}

	/**
	 * Verify scheduled sync candidates prefer never-synced orders first, then oldest sync timestamps.
	 */
	public function test_tracking_sync_candidates_prioritize_never_synced_then_oldest() {
		$operations    = new Order_Operations();
		$old_order_id  = $this->create_tracking_order( 'TRACK-OLD', time() - HOUR_IN_SECONDS );
		$new_order_id  = $this->create_tracking_order( 'TRACK-NEW', time() - ( 15 * MINUTE_IN_SECONDS ) );
		$miss_order_id = $this->create_tracking_order( 'TRACK-MISSING' );

		$reflection = new ReflectionMethod( $operations, 'get_tracking_sync_candidate_order_ids' );
		$reflection->setAccessible( true );

		$order_ids = $reflection->invoke( $operations );

		$this->assertGreaterThanOrEqual( 3, count( $order_ids ) );
		$this->assertSame( $miss_order_id, $order_ids[0] );
		$this->assertSame( $old_order_id, $order_ids[1] );
		$this->assertSame( $new_order_id, $order_ids[2] );
	}

	/**
	 * Persist a tracking sync setting for the test shipping method instance.
	 *
	 * @param string $value Setting value.
	 *
	 * @return void
	 */
	private function update_tracking_sync_setting( string $value ): void {
		$option_key = sprintf( 'woocommerce_%s_%d_settings', $this->shipping_method->id, $this->shipping_method->instance_id );
		$settings   = get_option( $option_key, array() );

		$settings['tracking_sync'] = $value;
		update_option( $option_key, $settings );
	}

	/**
	 * Create a tracked order for sync-query testing.
	 *
	 * @param string   $tracking_number Tracking number.
	 * @param int|null $updated_ts      Optional tracking timestamp.
	 *
	 * @return int
	 */
	private function create_tracking_order( string $tracking_number, ?int $updated_ts = null ): int {
		$order = wc_create_order();
		$order->set_status( 'processing' );
		$order->update_meta_data( '_wc_dhl_shipment_tracking_number', $tracking_number );

		if ( null !== $updated_ts ) {
			$order->update_meta_data( '_wc_dhl_tracking_updated_ts', $updated_ts );
			$order->update_meta_data( '_wc_dhl_tracking_updated_at', gmdate( 'c', $updated_ts ) );
		}

		$order->save();

		return $order->get_id();
	}
}
