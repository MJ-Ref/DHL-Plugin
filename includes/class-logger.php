<?php
/**
 * Logger class file.
 *
 * @package WC_Shipping_DHL
 */

namespace WooCommerce\DHL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logger class.
 */
class Logger {

	/**
	 * WC_Logger instance.
	 *
	 * @var \WC_Logger
	 */
	private static $logger;

	/**
	 * Log an error message.
	 *
	 * @param string $message The message to be logged.
	 * @param array  $context Optional. Additional information for log handlers.
	 */
	public static function error( $message, $context = array() ) {
		self::log( 'error', $message, $context );
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message The message to be logged.
	 * @param array  $context Optional. Additional information for log handlers.
	 */
	public static function warning( $message, $context = array() ) {
		self::log( 'warning', $message, $context );
	}

	/**
	 * Log an info message.
	 *
	 * @param string $message The message to be logged.
	 * @param array  $context Optional. Additional information for log handlers.
	 */
	public static function info( $message, $context = array() ) {
		self::log( 'info', $message, $context );
	}

	/**
	 * Log a debug message.
	 *
	 * @param string $message The message to be logged.
	 * @param array  $context Optional. Additional information for log handlers.
	 */
	public static function debug( $message, $context = array() ) {
		self::log( 'debug', $message, $context );
	}

	/**
	 * Log message with any level.
	 *
	 * @param string $level   Log level.
	 * @param string $message The message to be logged.
	 * @param array  $context Optional. Additional information for log handlers.
	 */
	public static function log( $level, $message, $context = array() ) {
		if ( empty( self::$logger ) ) {
			self::$logger = wc_get_logger();
		}

		if ( is_array( $message ) || is_object( $message ) ) {
			$message = print_r( $message, true );
		}

		$context = array_merge(
			$context,
			array( 'source' => 'woocommerce-shipping-dhl' )
		);

		self::$logger->log( $level, $message, $context );
	}
}