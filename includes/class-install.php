<?php
/**
 * Plugin install and migration class.
 *
 * @package WC_Shipping_DHL
 */

namespace WooCommerce\DHL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WC_Shipping_Zones;

/**
 * Install class.
 */
class Install {

	/**
	 * Current plugin schema version.
	 */
	private const SCHEMA_VERSION = 1;

	/**
	 * Option storing the installed schema version.
	 */
	private const SCHEMA_OPTION = 'wc_shipping_dhl_schema_version';

	/**
	 * Upgrade notice transient.
	 */
	private const UPGRADE_NOTICE_TRANSIENT = 'wc_shipping_dhl_upgrade_notice';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'maybe_run_migrations' ) );
		add_action( 'admin_notices', array( $this, 'maybe_render_upgrade_notice' ) );
	}

	/**
	 * Run pending schema migrations.
	 *
	 * @return void
	 */
	public function maybe_run_migrations(): void {
		$installed_version = (int) get_option( self::SCHEMA_OPTION, 0 );

		if ( $installed_version >= self::SCHEMA_VERSION ) {
			return;
		}

		$this->run_migrations( $installed_version );
		update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION );

		if ( $installed_version > 0 ) {
			set_transient( self::UPGRADE_NOTICE_TRANSIENT, true, DAY_IN_SECONDS );
		}
	}

	/**
	 * Render a one-time admin notice after migrations run.
	 *
	 * @return void
	 */
	public function maybe_render_upgrade_notice(): void {
		if ( ! get_transient( self::UPGRADE_NOTICE_TRANSIENT ) ) {
			return;
		}

		delete_transient( self::UPGRADE_NOTICE_TRANSIENT );

		echo '<div class="notice notice-success is-dismissible"><p>';
		echo esc_html__( 'WooCommerce DHL Shipping updated its stored settings format and normalized existing DHL instances. Review your DHL shipping methods before the next staging or production run.', 'woocommerce-shipping-dhl' );
		echo '</p></div>';
	}

	/**
	 * Execute all required migrations from the installed version.
	 *
	 * @param int $installed_version Previously installed schema version.
	 * @return void
	 */
	private function run_migrations( int $installed_version ): void {
		if ( $installed_version < 1 ) {
			$this->migrate_to_schema_1();
		}
	}

	/**
	 * Normalize existing DHL settings into the current expected shape.
	 *
	 * @return void
	 */
	private function migrate_to_schema_1(): void {
		foreach ( $this->get_dhl_settings_option_names() as $option_name ) {
			$settings = get_option( $option_name, array() );

			if ( ! is_array( $settings ) ) {
				$settings = array();
			}

			foreach ( array( 'boxes', 'services', 'custom_services' ) as $array_key ) {
				if ( ! isset( $settings[ $array_key ] ) || ! is_array( $settings[ $array_key ] ) ) {
					$settings[ $array_key ] = array();
				}
			}

			foreach ( array( 'debug', 'address_validation', 'residential', 'insuredvalue', 'service_point_lookup', 'landed_cost_estimate', 'tracking_sync', 'tracking_customer_notifications' ) as $checkbox_key ) {
				if ( isset( $settings[ $checkbox_key ] ) ) {
					$settings[ $checkbox_key ] = 'yes' === $settings[ $checkbox_key ] ? 'yes' : 'no';
				}
			}

			update_option( $option_name, $settings );
		}
	}

	/**
	 * Collect all DHL shipping settings option names that may exist in the site.
	 *
	 * @return string[]
	 */
	private function get_dhl_settings_option_names(): array {
		$option_names = array( 'woocommerce_dhl_settings' );

		if ( class_exists( WC_Shipping_Zones::class ) ) {
			$zone_ids = array( 0 );

			foreach ( WC_Shipping_Zones::get_zones() as $zone_key => $zone_data ) {
				$zone_ids[] = isset( $zone_data['zone_id'] ) ? absint( $zone_data['zone_id'] ) : absint( $zone_key );
			}

			foreach ( array_values( array_unique( $zone_ids ) ) as $zone_id ) {
				$zone = new \WC_Shipping_Zone( $zone_id );

				foreach ( $zone->get_shipping_methods( true ) as $method ) {
					if ( 'dhl' === $method->id && ! empty( $method->instance_id ) ) {
						$option_names[] = sprintf( 'woocommerce_dhl_%d_settings', absint( $method->instance_id ) );
					}
				}
			}
		}

		return array_values( array_unique( $option_names ) );
	}
}
