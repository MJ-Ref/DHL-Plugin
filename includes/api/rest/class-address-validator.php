<?php
/**
 * Address Validator class file.
 *
 * @package WC_Shipping_DHL
 */

namespace WooCommerce\DHL\API\REST;

use WooCommerce\DHL\API\Abstract_Address_Validator;
use WooCommerce\DHL\Logger;

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
		
		// Get the appropriate API endpoint based on environment.
		$environment = defined( 'WC_SHIPPING_DHL_ENVIRONMENT' ) ? WC_SHIPPING_DHL_ENVIRONMENT : 'test';
		$endpoint = self::$endpoints[ $environment ] ?? self::$endpoints['test'];

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

		$body = wp_remote_retrieve_body( $response );
		$this->response = json_decode( $body, true );

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

		// The validation is considered successful if the 'matchLevel' is provided and it's not 'none'.
		if ( isset( $this->response['matchLevel'] ) && 'none' !== $this->response['matchLevel'] ) {
			return true;
		}

		// Also check if there are capabilityDetails and at least one has a pickup capability.
		if ( isset( $this->response['capabilityDetails'] ) && is_array( $this->response['capabilityDetails'] ) ) {
			foreach ( $this->response['capabilityDetails'] as $capability ) {
				if ( isset( $capability['pickupCapability'] ) && $capability['pickupCapability'] ) {
					return true;
				}
			}
		}

		return false;
	}
}