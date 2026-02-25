<?php
/**
 * Address Validator class file.
 *
 * @package WC_Shipping_DHL
 */

namespace WooCommerce\DHL\API\REST;

use WooCommerce\DHL\API\Abstract_Address_Validator;
use WooCommerce\DHL\Logger;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Address_Validator class.
 */
class Address_Validator extends Abstract_Address_Validator {

	/**
	 * API endpoint for address validation.
	 *
	 * @var array
	 */
	protected static array $endpoints = array(
		'test'       => 'https://express.api.dhl.com/mydhlapi/test/address-validate',
		'production' => 'https://express.api.dhl.com/mydhlapi/address-validate',
	);

	/**
	 * Environment to call address validation in.
	 *
	 * @var string
	 */
	private string $environment = 'test';

	/**
	 * Constructor.
	 *
	 * @param array  $address      Address to validate.
	 * @param string $access_token Access token for authentication.
	 * @param string $environment  API environment (test|production).
	 */
	public function __construct( array $address, string $access_token, string $environment = 'test' ) {
		$this->environment = in_array( $environment, array( 'test', 'production' ), true ) ? $environment : 'test';

		parent::__construct( $address, $access_token );
	}

	/**
	 * Build the API request.
	 *
	 * @return array
	 */
	protected function build_request() {
		$request = array(
			'postalCode'  => $this->address['postcode'] ?? '',
			'cityName'    => $this->address['city'] ?? '',
			'countryCode' => $this->address['country'] ?? '',
		);

		// Add state/province if available.
		if ( ! empty( $this->address['state'] ) ) {
			$request['provinceCode'] = $this->address['state'];
		}

		// Add address line if available.
		if ( ! empty( $this->address['address_1'] ) ) {
			$request['addressLine1'] = $this->address['address_1'];
		}

		return $request;
	}

	/**
	 * Validate the address.
	 *
	 * @return bool
	 */
	public function validate() {
		$logger = new Logger();

		$endpoint = self::$endpoints[ $this->environment ] ?? self::$endpoints['test'];

		// Create the request headers.
		$headers = array(
			'Authorization' => 'Basic ' . $this->access_token,
			'Content-Type'  => 'application/json',
		);

		// Make the API request.
		$response = wp_remote_get(
			add_query_arg( $this->request, $endpoint ),
			array(
				'headers' => $headers,
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			$logger->error( 'DHL Address Validation Error: ' . $response->get_error_message() );
			$this->response = $response;
			return false;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code < 200 || $status_code >= 300 ) {
			$error_body    = wp_remote_retrieve_body( $response );
			$error_data    = json_decode( $error_body, true );
			$error_message = $error_data['detail'] ?? $error_data['title'] ?? sprintf( 'Address validation failed with status %d.', $status_code );

			$logger->error( 'DHL Address Validation Error: ' . $error_message );
			$this->response = new WP_Error( 'dhl_address_validation_error', $error_message );

			return false;
		}

		$body           = wp_remote_retrieve_body( $response );
		$this->response = json_decode( $body, true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $this->response ) ) {
			$this->response = new WP_Error( 'dhl_address_validation_parse_error', __( 'Could not parse DHL address validation response.', 'woocommerce-shipping-dhl' ) );
			return false;
		}

		return true;
	}

	/**
	 * Check if the address is valid.
	 *
	 * @return bool
	 */
	public function is_valid() {
		if ( is_wp_error( $this->response ) ) {
			return false;
		}

		// Legacy payloads can expose a match level.
		if ( isset( $this->response['matchLevel'] ) && 'none' !== strtolower( (string) $this->response['matchLevel'] ) ) {
			return true;
		}

		// MyDHL address-validate commonly returns normalized address candidates.
		if ( isset( $this->response['address'] ) && is_array( $this->response['address'] ) ) {
			return ! empty( $this->response['address'] );
		}

		// Some payloads include capability details instead of normalized addresses.
		if ( isset( $this->response['capabilityDetails'] ) && is_array( $this->response['capabilityDetails'] ) ) {
			foreach ( $this->response['capabilityDetails'] as $capability ) {
				$delivery_capability = $capability['deliveryCapability'] ?? null;
				$pickup_capability   = $capability['pickupCapability'] ?? null;

				if ( $this->is_capability_enabled( $delivery_capability ) || $this->is_capability_enabled( $pickup_capability ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Convert a capability value to boolean.
	 *
	 * @param mixed $value Capability value.
	 *
	 * @return bool
	 */
	private function is_capability_enabled( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		$normalized_value = strtolower( trim( (string) $value ) );

		return in_array( $normalized_value, array( '1', 'true', 'y', 'yes' ), true );
	}
}
