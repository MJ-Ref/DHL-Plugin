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
	 * Get access token from cache or authenticate.
	 *
	 * @return string|WP_Error
	 */
	public function get_access_token() {
		// Try to get the token from cache first.
		$token = $this->get_cached_token();
		
		if ( $token ) {
			return $token;
		}
		
		// If no cached token, authenticate and get a new one.
		return $this->authenticate();
	}

	/**
	 * Get cached access token.
	 *
	 * @return string|false
	 */
	private function get_cached_token() {
		$transient_key = 'wc_dhl_access_token_' . md5( $this->shipping_method->get_option( 'api_user' ) );
		$token_data = get_transient( $transient_key );
		
		if ( ! $token_data ) {
			return false;
		}
		
		// Expires 5 minutes before actual expiration as a safety buffer.
		$expiry_buffer = 5 * MINUTE_IN_SECONDS;
		
		if ( time() > ( $token_data['timestamp'] + $token_data['expires_in'] - $expiry_buffer ) ) {
			// Token is expired or about to expire.
			delete_transient( $transient_key );
			return false;
		}
		
		$this->logger->info( 'Using cached DHL OAuth token.' );
		
		return $token_data['access_token'];
	}

	/**
	 * Authenticate with DHL API.
	 *
	 * @return string|WP_Error
	 */
	private function authenticate() {
		$this->logger->info( 'Authenticating with DHL API...' );
		
		$api_user = $this->shipping_method->get_option( 'api_user' );
		$api_key = $this->shipping_method->get_option( 'api_key' );
		
		if ( empty( $api_user ) || empty( $api_key ) ) {
			return new WP_Error( 'missing_credentials', __( 'DHL API credentials are missing. Please configure the DHL shipping method.', 'woocommerce-shipping-dhl' ) );
		}
		
		// Create Basic Auth token.
		$access_token = base64_encode( $api_user . ':' . $api_key );
		
		// Store the token in transient for 23 hours (DHL tokens last 24 hours).
		// Adding a timestamp to check expiration more precisely.
		$token_data = array(
			'access_token' => $access_token,
			'expires_in'   => 23 * HOUR_IN_SECONDS,
			'timestamp'    => time(),
		);
		
		$transient_key = 'wc_dhl_access_token_' . md5( $api_user );
		set_transient( $transient_key, $token_data, $token_data['expires_in'] );
		
		$this->logger->info( 'DHL API authentication successful.' );
		
		return $access_token;
	}
}