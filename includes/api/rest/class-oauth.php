<?php
/**
 * OAuth class file.
 *
 * @package WC_Shipping_DHL
 */

namespace WooCommerce\DHL\API\REST;

use WooCommerce\DHL\Logger;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OAuth class for DHL API authentication.
 */
class OAuth {

	/**
	 * Shipping method instance.
	 *
	 * @var \WC_Shipping_DHL
	 */
	private $shipping_method;

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param \WC_Shipping_DHL $shipping_method Shipping method instance.
	 */
	public function __construct( $shipping_method ) {
		$this->shipping_method = $shipping_method;
		$this->logger          = new Logger();
	}

	/**
	 * Get the access token for API authentication.
	 *
	 * @return string|false
	 */
	public function get_access_token() {
		// Check for a cached token first.
		$cached_token = $this->get_cached_token();
		if ( false !== $cached_token ) {
			return $cached_token;
		}

		// If no cached token, authenticate and get a new one.
		return $this->authenticate();
	}

	/**
	 * Get the cached token if it exists and is not expired.
	 *
	 * @return string|false
	 */
	private function get_cached_token() {
		$token_data = get_transient( 'wc_dhl_api_token' );

		if ( false === $token_data ) {
			return false;
		}

		// Check if the token is expired.
		$expires_at = isset( $token_data['expires_at'] ) ? $token_data['expires_at'] : 0;
		if ( time() >= $expires_at ) {
			// Delete the expired token.
			delete_transient( 'wc_dhl_api_token' );
			return false;
		}

		return isset( $token_data['token'] ) ? $token_data['token'] : false;
	}

	/**
	 * Authenticate with the DHL API and get an access token.
	 *
	 * @return string|false
	 */
	private function authenticate() {
		// DHL MyDHL API uses Basic Auth for authentication.
		$api_user = $this->shipping_method->get_option( 'api_user' );
		$api_key  = $this->shipping_method->get_option( 'api_key' );

		if ( empty( $api_user ) || empty( $api_key ) ) {
			$this->logger->error( __( 'DHL API credentials are missing. Please check your settings.', 'woocommerce-shipping-dhl' ) );
			return false;
		}

		// For DHL, we'll use the API credentials directly, as it uses Basic Auth instead of OAuth tokens
		$token = base64_encode( $api_user . ':' . $api_key );

		// Store the token as a transient (cached for 23 hours to be safe)
		$token_data = array(
			'token'      => $token,
			'expires_at' => time() + ( 23 * HOUR_IN_SECONDS ),
		);

		set_transient( 'wc_dhl_api_token', $token_data, 24 * HOUR_IN_SECONDS );

		return $token;
	}
}