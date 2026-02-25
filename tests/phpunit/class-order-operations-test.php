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
	 * Setup test state.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_clear_scheduled_hook( $this->cron_hook );
	}

	/**
	 * Cleanup test state.
	 */
	public function tearDown(): void {
		wp_clear_scheduled_hook( $this->cron_hook );
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
	public function test_maybe_schedule_tracking_sync_schedules_event() {
		$operations = new Order_Operations();
		$operations->maybe_schedule_tracking_sync();

		$this->assertNotFalse( wp_next_scheduled( $this->cron_hook ) );
	}
}
