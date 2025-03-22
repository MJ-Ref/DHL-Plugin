<?php
/**
 * Notifier class file.
 *
 * @package WC_Shipping_DHL
 */

namespace WooCommerce\DHL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notifier class.
 */
class Notifier {

	/**
	 * Add a notice.
	 *
	 * @param string $message The notice message.
	 * @param string $type    Notice type.
	 * @param string $group   Notice group.
	 */
	public static function add_notice( $message, $type = 'info', $group = '' ) {
		if ( ! is_admin() ) {
			return;
		}

		$all_notices = self::get_notices();

		if ( empty( $all_notices[ $group ] ) ) {
			$all_notices[ $group ] = array();
		}

		$notice = array(
			'message' => $message,
			'type'    => $type,
		);

		$all_notices[ $group ][] = $notice;

		set_transient( 'wc_shipping_dhl_notices', $all_notices, 60 * 60 * 24 );
	}

	/**
	 * Get notices.
	 *
	 * @param string $group Notice group.
	 * @return array
	 */
	public static function get_notices( $group = '' ) {
		$all_notices = get_transient( 'wc_shipping_dhl_notices' );

		if ( ! is_array( $all_notices ) ) {
			$all_notices = array();
		}

		if ( empty( $group ) ) {
			return $all_notices;
		}

		return isset( $all_notices[ $group ] ) ? $all_notices[ $group ] : array();
	}

	/**
	 * Clear notices.
	 *
	 * @param string $group Notice group.
	 */
	public static function clear_notices( $group ) {
		$all_notices = self::get_notices();

		if ( isset( $all_notices[ $group ] ) ) {
			unset( $all_notices[ $group ] );
			set_transient( 'wc_shipping_dhl_notices', $all_notices, 60 * 60 * 24 );
		}
	}

	/**
	 * Output notices.
	 */
	public static function output_notices() {
		if ( ! is_admin() ) {
			return;
		}

		$all_notices = self::get_notices();

		if ( empty( $all_notices ) ) {
			return;
		}

		foreach ( $all_notices as $group => $notices ) {
			foreach ( $notices as $notice ) {
				$type = isset( $notice['type'] ) ? $notice['type'] : 'info';
				$message = isset( $notice['message'] ) ? $notice['message'] : '';

				if ( empty( $message ) ) {
					continue;
				}

				echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . wp_kses_post( $message ) . '</p></div>';
			}
		}

		// Clear all notices after displaying them.
		delete_transient( 'wc_shipping_dhl_notices' );
	}
}