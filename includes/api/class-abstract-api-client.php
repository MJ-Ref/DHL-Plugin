<?php
/**
 * Abstract API client class file.
 *
 * @package WC_Shipping_DHL
 */

namespace WooCommerce\DHL\API;

use WooCommerce\DHL\Logger;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract_API_Client class.
 */
abstract class Abstract_API_Client {

	/**
	 * Shipping method instance.
	 *
	 * @var \WC_Shipping_DHL
	 */
	protected $shipping_method;

	/**
	 * The package to ship.
	 *
	 * @var array
	 */
	protected $package;

	/**
	 * Package requests.
	 *
	 * @var array
	 */
	protected $package_requests = array();

	/**
	 * Address validator instance.
	 *
	 * @var Abstract_Address_Validator
	 */
	protected $address_validator;

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	protected $logger;

	/**
	 * Notice group.
	 *
	 * @var string
	 */
	public static $notice_group = 'wc_shipping_dhl';

	/**
	 * Constructor.
	 *
	 * @param \WC_Shipping_DHL $shipping_method Shipping method instance.
	 * @param array            $package         Package to ship.
	 */
	public function __construct( $shipping_method, $package = array() ) {
		$this->shipping_method = $shipping_method;
		$this->package         = $package;
		$this->logger          = new Logger();
	}

	/**
	 * Process and prepare the package requests.
	 *
	 * @param array $requests Package requests.
	 * @return void
	 */
	public function set_package_requests( $requests ) {
		$this->package_requests = $requests;
	}

	/**
	 * Get the package requests.
	 *
	 * @return array
	 */
	public function get_package_requests() {
		return $this->package_requests;
	}

	/**
	 * Set the address validator.
	 *
	 * @param Abstract_Address_Validator $validator Address validator instance.
	 * @return void
	 */
	public function set_address_validator( $validator ) {
		$this->address_validator = $validator;
	}

	/**
	 * Get the address validator.
	 *
	 * @return Abstract_Address_Validator
	 */
	public function get_address_validator() {
		return $this->address_validator;
	}

	/**
	 * Set the package.
	 *
	 * @param array $package Package to ship.
	 * @return void
	 */
	public function set_package( $package ) {
		$this->package = $package;
	}

	/**
	 * Get the shipping rates.
	 *
	 * @return array
	 */
	abstract public function get_rates();

	/**
	 * Validate the destination address.
	 *
	 * @param array $destination_address Destination address.
	 * @return void
	 */
	abstract public function validate_destination_address( $destination_address );

	/**
	 * Get product dimensions sorted by size in descending order.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return array
	 */
	public function get_processed_product_dimensions( $product ) {
		$dimensions = array(
			'length' => $this->shipping_method->get_formatted_measurement(
				$this->shipping_method->get_converted_dimension( $product->get_length() )
			),
			'width'  => $this->shipping_method->get_formatted_measurement(
				$this->shipping_method->get_converted_dimension( $product->get_width() )
			),
			'height' => $this->shipping_method->get_formatted_measurement(
				$this->shipping_method->get_converted_dimension( $product->get_height() )
			),
		);

		arsort( $dimensions );

		return $dimensions;
	}

	/**
	 * Get the sort order for the rate.
	 *
	 * @param string $code Service code.
	 * @return int
	 */
	public function get_sort_order( $code ) {
		$sort = 0;

		if ( ! empty( $this->shipping_method->get_custom_services()[ $code ]['order'] ) ) {
			$sort = $this->shipping_method->get_custom_services()[ $code ]['order'];
		}

		return $sort;
	}

	/**
	 * Get the rate name.
	 *
	 * @param string $code Service code.
	 * @return string
	 */
	public function get_rate_name( $code ) {
		$name = '';

		if ( ! empty( $this->shipping_method->get_custom_services()[ $code ]['name'] ) ) {
			$name = $this->shipping_method->get_custom_services()[ $code ]['name'];
		}

		return $name;
	}

	/**
	 * Check if the store currency is equal to the rate currency.
	 *
	 * @param object $rate_response Rate response.
	 * @param string $rate_name     Rate name.
	 * @param string $currency      Currency code.
	 * @return bool
	 */
	public function is_store_currency_equal_to_rate_currency( $rate_response, $rate_name, $currency ) {
		$store_currency = get_woocommerce_currency();

		if ( $store_currency !== $currency ) {
			$error_message = sprintf(
				/* translators: %1$s: Currency code, %2$s: Rate name, %3$s: Store currency code */
				__( 'DHL returned the %1$s currency for %2$s rate but your store uses %3$s.', 'woocommerce-shipping-dhl' ),
				$currency,
				$rate_name,
				$store_currency
			);

			$this->logger->error( $error_message );
			$this->shipping_method->debug( $error_message, 'error' );

			return false;
		}

		return true;
	}

	/**
	 * Check if a package is eligible for specific services.
	 *
	 * @param int $total_packages_count The total number of packages.
	 * @return bool
	 */
	public function is_package_eligible_for_service( $total_packages_count ) {
		// Example check for domestic service eligibility.
		if ( $this->shipping_method->is_domestic_destination( $this->package['destination']['country'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Set whether the destination address is valid.
	 *
	 * @param Abstract_Address_Validator $validator Address validator instance.
	 * @return void
	 */
	protected function set_is_valid_destination_address( $validator ) {
		if ( ! $validator->is_valid() ) {
			$this->shipping_method->set_is_valid_destination_address( false );
			return;
		}

		$this->shipping_method->set_is_valid_destination_address( true );
	}

	/**
	 * Get packed box details for meta data.
	 *
	 * @param array  $meta_data     Existing meta data.
	 * @param object $request_object Request object.
	 * @param int    $box_number     Box number.
	 * @return array
	 */
	protected function maybe_get_packed_box_details_meta( $meta_data, $request_object, $box_number ) {
		if ( ! isset( $request_object->Dimensions ) ) {
			return $meta_data;
		}

		$length = $request_object->Dimensions->Length ?? '';
		$width  = $request_object->Dimensions->Width ?? '';
		$height = $request_object->Dimensions->Height ?? '';
		$weight = $request_object->PackageWeight->Weight ?? '';

		if ( empty( $length ) || empty( $width ) || empty( $height ) || empty( $weight ) ) {
			return $meta_data;
		}

		$meta_data[ 'box_' . $box_number ] = array(
			'dimensions' => array(
				'length' => $length,
				'width'  => $width,
				'height' => $height,
				'unit'   => $request_object->Dimensions->UnitOfMeasurement->Code ?? '',
			),
			'weight'     => array(
				'value' => $weight,
				'unit'  => $request_object->PackageWeight->UnitOfMeasurement->Code ?? '',
			),
		);

		return $meta_data;
	}
}