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
	private $wc_logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->wc_logger = wc_get_logger();
	}

	/**
	 * Log a message.
	 *
	 * @param string $message Message to log.
	 * @param string $level   Log level.
	 */
	private function log( $message, $level ) {
		if ( ! is_scalar( $message ) ) {
			$message = print_r( $message, true );
		}

		$this->wc_logger->log( $level, $message, array( 'source' => 'woocommerce-shipping-dhl' ) );
	}

	/**
	 * Log an info message.
	 *
	 * @param string $message Message to log.
	 */
	public function info( $message ) {
		$this->log( $message, 'info' );
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message Message to log.
	 */
	public function warning( $message ) {
		$this->log( $message, 'warning' );
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message Message to log.
	 */
	public function error( $message ) {
		$this->log( $message, 'error' );
	}

	/**
	 * Log a debug message.
	 *
	 * @param string $message Message to log.
	 */
	public function debug( $message ) {
		$this->log( $message, 'debug' );
	}
}