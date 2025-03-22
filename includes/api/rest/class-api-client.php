<?php
/**
 * DHL REST API Client.
 *
 * @package WC_Shipping_DHL
 */

namespace WooCommerce\DHL\API\REST;

defined( 'ABSPATH' ) || exit;

use WC_Product;
use WooCommerce\DHL\API\Abstract_API_Client;
use WooCommerce\DHL\Notifier;
use WooCommerce\DHL\Util;
use WP_Error;

/**
 * API_Client class.
 */
class API_Client extends Abstract_API_Client {
	
	use Util;

	/**
	 * Endpoint for the DHL Rate API.
	 *
	 * @var array
	 */
	protected static array $endpoints = array(
		'test'       => 'https://express.api.dhl.com/mydhlapi/test',
		'production' => 'https://express.api.dhl.com/mydhlapi',
	);

	/**
	 * Get the API base URL based on environment.
	 *
	 * @return string
	 */
	private function get_api_url() {
		$environment = $this->shipping_method->get_option( 'environment' );
		return self::$endpoints[ $environment ] ?? self::$endpoints['test'];
	}

	/**
	 * Make a request to the rate endpoint.
	 *
	 * @param array $request The formatted request.
	 * @return array|WP_Error
	 */
	protected function post_rate_request( $request ) {
		$access_token = $this->shipping_method->get_dhl_oauth()->get_access_token();

		// If we don't have an access token, return an error.
		if ( ! $access_token ) {
			return new WP_Error( 'post_rate_request_error', __( 'DHL authentication failed.', 'woocommerce-shipping-dhl' ) );
		}

		// Create the request headers.
		$headers = array(
			'Authorization' => 'Basic ' . $access_token,
			'Content-Type'  => 'application/json',
		);

		/**
		 * Filter the request body before sending it to the DHL API.
		 *
		 * @param array  $request          The request body.
		 * @param array  $package_requests The package requests.
		 * @param array  $package          The package.
		 * @param string $class            The class name.
		 *
		 * @since 1.0.0
		 */
		$body = apply_filters( 'woocommerce_shipping_dhl_request', $request, $this->package_requests, $this->package, get_class( $this ) );

		$api_url = $this->get_api_url() . '/rates';

		return wp_remote_post(
			$api_url,
			array(
				'headers' => $headers,
				'body'    => wp_json_encode( $body ),
				'timeout' => 30,
			)
		);
	}

	/**
	 * Build a package element for a packed box which will be added to the rate request.
	 *
	 * @param object $packed_box         The packed box.
	 * @param int    $packed_boxes_count The total number of packed boxes.
	 *
	 * @return array
	 */
	public function build_packed_box_package_for_rate_request( object $packed_box, int $packed_boxes_count ): array {
		// The dimensions are currently in the DHL instance's dimension unit.
		$dimensions = array(
			'length' => $packed_box->length,
			'width'  => $packed_box->width,
			'height' => $packed_box->height,
		);

		// The weight is currently in the DHL instance's weight unit.
		$weight = $packed_box->weight;

		// Create the request array.
		$request = array();

		// Add the package dimensions.
		$this->add_package_dimensions_element( $request, $dimensions['length'], $dimensions['width'], $dimensions['height'] );

		// Add the package weight.
		$this->add_package_weight_element( $request, $weight );

		// Maybe add the package insured value element.
		if ( $this->shipping_method->has_package_service_options( $this->package['destination']['country'] ) ) {
			$this->maybe_add_package_insured_value_element( $request, $packed_box->value );
		}

		return $request;
	}

	/**
	 * Build a package element for an individually packed product which will be added to the rate request.
	 *
	 * @param array $cart_item The cart item.
	 *
	 * @return array
	 */
	public function build_individually_packed_package_for_rate_request( array $cart_item ): array {
		/**
		 * The cart item data is a WC_Product instance.
		 *
		 * @var WC_Product $product Product instance.
		 */
		$product = $cart_item['data'];

		// Get formatted, converted, sorted product dimensions. Dimensions are in the DHL instance's dimension unit.
		$dimensions = $this->get_processed_product_dimensions( $product );

		$product_has_dimensions = ! empty( floatval( $dimensions['length'] ) ) && ! empty( floatval( $dimensions['width'] ) ) && ! empty( floatval( $dimensions['height'] ) );

		// Convert and format the weight. Weight is in the DHL instance's weight unit.
		$weight = $this->shipping_method->get_formatted_measurement( $this->shipping_method->get_converted_weight( $product->get_weight() ) );

		// Create the request array.
		$request = array();

		// Maybe add the package dimensions.
		if ( $product_has_dimensions ) {
			$this->add_package_dimensions_element( $request, $dimensions['length'], $dimensions['width'], $dimensions['height'] );
		}

		// Add the package weight.
		$this->add_package_weight_element( $request, $weight );

		// Package Service Options.
		if ( $this->shipping_method->has_package_service_options( $this->package['destination']['country'] ) ) {
			// Maybe add package insured value.
			$this->maybe_add_package_insured_value_element( $request, $product->get_price() );
		}

		return $request;
	}

	/**
	 * Add package dimensions to the request.
	 *
	 * @param array $request The request array.
	 * @param mixed $length  The length in the DHL instance's dimension unit.
	 * @param mixed $width   The width in the DHL instance's dimension unit.
	 * @param mixed $height  The height in the DHL instance's dimension unit.
	 *
	 * @return void
	 */
	private function add_package_dimensions_element( array &$request, $length, $width, $height ) {
		$request['dimensions'] = array(
			'length' => (string) round( $length ),
			'width'  => (string) round( $width ),
			'height' => (string) round( $height ),
			'unitOfMeasurement' => $this->shipping_method->get_dimension_unit(),
		);
	}

	/**
	 * Add package weight to the request.
	 *
	 * @param array $request The request array.
	 * @param mixed $weight  The weight in the DHL instance's weight unit.
	 *
	 * @return void
	 */
	private function add_package_weight_element( array &$request, $weight ) {
		$request['weight'] = array(
			'value' => (string) $weight,
			'unitOfMeasurement' => $this->shipping_method->get_weight_unit(),
		);
	}

	/**
	 * Maybe add insured value to the package.
	 *
	 * @param array $request The request array.
	 * @param mixed $value   The value to insure the package for.
	 *
	 * @return void
	 */
	private function maybe_add_package_insured_value_element( array &$request, $value ) {
		if ( $this->shipping_method->is_insured_value_enabled() ) {
			$request['declaredValue'] = array(
				'value'        => (string) $value,
				'currencyCode' => get_woocommerce_currency(),
			);
		}
	}

	/**
	 * Build the rate request.
	 *
	 * @param array  $package_requests The package requests.
	 * @param string $service_code     A specific service code to get rates for.
	 *
	 * @return array
	 */
	private function build_rate_request( array $package_requests, string $service_code = '' ): array {
		$request = array(
			'customerDetails' => array(
				'shipperDetails' => array(
					'postalCode'  => $this->shipping_method->get_origin_postcode(),
					'cityName'    => $this->shipping_method->get_origin_city(),
					'countryCode' => $this->shipping_method->get_origin_country(),
				),
				'receiverDetails' => array(
					'postalCode'  => $this->package['destination']['postcode'],
					'cityName'    => $this->package['destination']['city'],
					'countryCode' => $this->package['destination']['country'],
				),
			),
			'accounts' => array(
				array(
					'typeCode'   => 'shipper',
					'number'     => $this->shipping_method->get_shipper_number(),
				),
			),
			'plannedShippingDateAndTime' => date( 'Y-m-d\TH:i:s \G\M\TP', strtotime( '+1 day' ) ),
			'unitOfMeasurement'          => $this->shipping_method->get_weight_unit() === 'KG' ? 'metric' : 'imperial',
			'isCustomsDeclarable'        => false,
			'monetaryAmount'             => array(
				array(
					'typeCode'     => 'declaredValue',
					'value'        => 0,
					'currencyCode' => get_woocommerce_currency(),
				),
			),
			'requestAllValueAddedServices' => true,
			'returnStandardProductsOnly'   => false,
			'nextBusinessDay'              => false,
		);

		// Add packages.
		$request['packages'] = $package_requests;

		// Maybe add specific service code.
		if ( ! empty( $service_code ) ) {
			$request['productCode'] = $service_code;
		}

		// Maybe add address line for shipper.
		if ( ! empty( $this->shipping_method->get_origin_addressline() ) ) {
			$request['customerDetails']['shipperDetails']['addressLine1'] = $this->shipping_method->get_origin_addressline();
		}

		// Maybe add state/province.
		if ( ! empty( $this->shipping_method->get_origin_state() ) ) {
			$request['customerDetails']['shipperDetails']['provinceCode'] = $this->shipping_method->get_origin_state();
		}

		// Maybe add state/province for receiver.
		if ( ! empty( $this->package['destination']['state'] ) ) {
			$request['customerDetails']['receiverDetails']['provinceCode'] = $this->package['destination']['state'];
		}

		// Maybe add address line for receiver.
		if ( ! empty( $this->package['destination']['address_1'] ) ) {
			$request['customerDetails']['receiverDetails']['addressLine1'] = $this->package['destination']['address_1'];
		}

		// Maybe add residential indicator.
		if ( $this->shipping_method->is_residential() ) {
			$request['customerDetails']['receiverDetails']['addressType'] = 'residential';
		}

		return $request;
	}

	/**
	 * Get the shipping rates.
	 *
	 * @return array
	 */
	public function get_rates(): array {
		$notice_group = self::$notice_group;

		Notifier::clear_notices( $notice_group );

		if ( empty( $this->package_requests ) ) {
			return array();
		}

		$rates = array();
		$request = $this->build_rate_request( $this->package_requests );
		
		// Try to get a cached response before sending a new request.
		$transient = 'dhl_quote_' . md5( wp_json_encode( $request ) );
		$cached_response = get_transient( $transient );

		if ( false === $cached_response ) {
			$response = $this->post_rate_request( $request );

			if ( is_wp_error( $response ) ) {
				$this->shipping_method->debug( __( 'Cannot retrieve rate: ', 'woocommerce-shipping-dhl' ) . $response->get_error_message(), 'error', array(), $notice_group );
				return array();
			}

			set_transient( $transient, $response['body'], DAY_IN_SECONDS * 30 );
			$response_body = $response['body'];
		} else {
			$response_body = $cached_response;
		}

		$this->shipping_method->debug(
			'DHL: Rate Request',
			'notice',
			$request,
			$notice_group
		);
		$this->shipping_method->debug(
			'DHL: Rate Response',
			'notice',
			json_decode( $response_body, true ),
			$notice_group
		);

		// Parse the response.
		$response_data = json_decode( $response_body );

		// Check if we have products in the response.
		if ( empty( $response_data->products ) ) {
			return array();
		}

		$dhl_services = $response_data->products;

		foreach ( $dhl_services as $service ) {
			$code = $service->productCode;

			// Check if the service is enabled.
			$enabled_service_codes = $this->shipping_method->get_enabled_service_codes();
			if ( empty( $enabled_service_codes ) || ! in_array( $code, $enabled_service_codes, true ) ) {
				continue;
			}

			$rate_id = $this->shipping_method->get_rate_id( $code );
			$currency = $service->totalPrice[0]->priceCurrency ?? get_woocommerce_currency();

			// Get the rate name.
			$rate_name = $this->get_rate_name( $code );

			// Ensure the store currency matches the rate currency.
			if ( ! $this->is_store_currency_equal_to_rate_currency( $response_data, $rate_name, $currency ) ) {
				continue;
			}

			// Get the rate cost.
			$rate_cost = $this->get_rate_cost( $service, $code );

			// Get the sort order.
			$sort = $this->get_sort_order( $code );

			/**
			 * Allow 3rd parties to process the rates returned by DHL.
			 *
			 * @param array       $rate       The rate array.
			 * @param string      $currency   The currency code.
			 * @param object      $service    The service object.
			 * @param API_Client  $api_client The API client instance.
			 *
			 * @since 1.0.0
			 */
			$rates[ $rate_id ] = apply_filters(
				'woocommerce_shipping_dhl_rate',
				array(
					'id'        => $rate_id,
					'label'     => $rate_name,
					'cost'      => $rate_cost,
					'sort'      => $sort,
					'meta_data' => $this->maybe_get_packed_box_details(),
				),
				$currency,
				$service,
				$this
			);
		}

		return $rates;
	}

	/**
	 * Get the rate cost for the service.
	 *
	 * @param object $service The service object.
	 * @param string $code    The service code.
	 *
	 * @return float
	 */
	public function get_rate_cost( object $service, string $code ): float {
		$rate_cost = 0;

		if ( ! empty( $service->totalPrice ) && is_array( $service->totalPrice ) ) {
			foreach ( $service->totalPrice as $price ) {
				if ( isset( $price->price ) ) {
					$rate_cost += (float) $price->price;
				}
			}
		}

		// Cost adjustment %.
		if ( ! empty( $this->shipping_method->get_custom_services()[ $code ]['adjustment_percent'] ) ) {
			$rate_cost = $rate_cost + ( $rate_cost * ( floatval( $this->shipping_method->get_custom_services()[ $code ]['adjustment_percent'] ) / 100 ) );
		}
		// Cost adjustment.
		if ( ! empty( $this->shipping_method->get_custom_services()[ $code ]['adjustment'] ) ) {
			$rate_cost = $rate_cost + floatval( $this->shipping_method->get_custom_services()[ $code ]['adjustment'] );
		}

		return $rate_cost;
	}

	/**
	 * Extract the packed box dimensions and weights if available and return in an array.
	 *
	 * @return array|false
	 */
	protected function maybe_get_packed_box_details() {
		$meta_data = array();
		foreach ( $this->package_requests as $index => $request ) {
			$request_object = json_decode( wp_json_encode( $request ), false );
			$meta_data = $this->maybe_get_packed_box_details_meta( $meta_data, $request_object, ( $index + 1 ) );
		}

		return ! empty( $meta_data ) ? $meta_data : false;
	}

	/**
	 * Calculate shipping rates.
	 *
	 * @param array $package Package to ship.
	 * @return void
	 */
	public function calculate_shipping( $package ) {
		$this->set_package( $package );
		
		// Prepare package requests based on packing method.
		$requests = $this->shipping_method->prepare_package_requests( $package );
		
		if ( empty( $requests ) ) {
			$this->shipping_method->debug( __( 'No packages to ship.', 'woocommerce-shipping-dhl' ), 'error' );
			return;
		}
		
		$this->set_package_requests( $requests );
		
		// Get the shipping rates.
		$rates = $this->get_rates();
		
		if ( empty( $rates ) ) {
			$this->shipping_method->debug( __( 'No shipping rates returned from DHL.', 'woocommerce-shipping-dhl' ), 'error' );

			// Use fallback rate if available.
			if ( ! empty( $this->shipping_method->fallback ) ) {
				$this->shipping_method->add_rate( array(
					'id'    => $this->shipping_method->get_rate_id( 'fallback' ),
					'label' => $this->shipping_method->title,
					'cost'  => $this->shipping_method->fallback,
				) );
			}
			return;
		}

		// Process and add the rates.
		$this->shipping_method->process_and_add_rates( $rates );
	}

	/**
	 * Validate the API credentials.
	 *
	 * @return bool
	 */
	public function validate_credentials() {
		$access_token = $this->shipping_method->get_dhl_oauth()->get_access_token();
		
		if ( ! $access_token ) {
			return false;
		}
		
		// Create a simple API request to test credentials
		$api_url = $this->get_api_url() . '/products';
		$request = array(
			'customerDetails' => array(
				'shipperDetails' => array(
					'postalCode'  => $this->shipping_method->get_origin_postcode(),
					'cityName'    => $this->shipping_method->get_origin_city(),
					'countryCode' => $this->shipping_method->get_origin_country(),
				),
				'receiverDetails' => array(
					'postalCode'  => $this->shipping_method->get_origin_postcode(),
					'cityName'    => $this->shipping_method->get_origin_city(),
					'countryCode' => $this->shipping_method->get_origin_country(),
				),
			),
			'accounts' => array(
				array(
					'typeCode'   => 'shipper',
					'number'     => $this->shipping_method->get_shipper_number(),
				),
			),
			'plannedShippingDateAndTime' => date( 'Y-m-d\TH:i:s \G\M\TP', strtotime( '+1 day' ) ),
		);
		
		$headers = array(
			'Authorization' => 'Basic ' . $access_token,
			'Content-Type'  => 'application/json',
		);
		
		$response = wp_remote_get(
			add_query_arg( $request, $api_url ),
			array(
				'headers' => $headers,
				'timeout' => 30,
			)
		);
		
		if ( is_wp_error( $response ) ) {
			$this->shipping_method->debug( __( 'DHL API credentials validation failed: ', 'woocommerce-shipping-dhl' ) . $response->get_error_message(), 'error' );
			return false;
		}
		
		$response_code = wp_remote_retrieve_response_code( $response );
		
		if ( 200 !== $response_code ) {
			$this->shipping_method->debug( __( 'DHL API credentials validation failed. HTTP response code: ', 'woocommerce-shipping-dhl' ) . $response_code, 'error' );
			return false;
		}
		
		return true;
	}

	/**
	 * Validate the destination address.
	 *
	 * @param array $destination_address The destination address.
	 * @return void
	 */
	public function validate_destination_address( array $destination_address ) {
		$access_token = $this->shipping_method->get_dhl_oauth()->get_access_token();

		// If we don't have an access token, return an error.
		if ( ! $access_token ) {
			$this->shipping_method->debug( __( 'DHL authentication failed.', 'woocommerce-shipping-dhl' ), 'error' );
			return;
		}

		$this->shipping_method->set_is_valid_destination_address( false );

		// Validate the address.
		$this->set_address_validator( new Address_Validator( $destination_address, $access_token ) );
		$this->get_address_validator()->validate();

		$notice_group = $this->get_address_validator()::$notice_group;

		Notifier::clear_notices( $notice_group );

		// Print the request.
		$this->shipping_method->debug( __( 'DHL: Address Validation Request', 'woocommerce-shipping-dhl' ), 'notice', array( $this->get_address_validator()->get_request() ), $notice_group );

		// Print the response.
		$validation_response = $this->get_address_validator()->get_response();
		if ( is_wp_error( $validation_response ) ) {
			$this->shipping_method->debug( __( 'DHL: Address Validation Error', 'woocommerce-shipping-dhl' ), 'error', array( $validation_response->get_error_message() ), $notice_group );

			// We should not block the user from checking out when there appears to be an API issue.
			$this->shipping_method->set_is_valid_destination_address( true );
			return;
		}

		$this->shipping_method->debug( __( 'DHL: Address Validation Response', 'woocommerce-shipping-dhl' ), 'notice', $validation_response, $notice_group );

		// Set whether the destination address is valid.
		$this->set_is_valid_destination_address( $this->get_address_validator() );
	}
}