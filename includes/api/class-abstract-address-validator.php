<?php
/**
 * Abstract address validator class file.
 *
 * @package WC_Shipping_DHL
 */

namespace WooCommerce\DHL\API;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract_Address_Validator class.
 */
abstract class Abstract_Address_Validator {

	/**
	 * Address to validate.
	 *
	 * @var array
	 */
	protected $address;

	/**
	 * Access token for authentication.
	 *
	 * @var string
	 */
	protected $access_token;

	/**
	 * API request.
	 *
	 * @var array
	 */
	protected $request;

	/**
	 * API response.
	 *
	 * @var array|WP_Error
	 */
	protected $response;

	/**
	 * Notice group.
	 *
	 * @var string
	 */
	public static $notice_group = 'wc_dhl_address_validation';

	/**
	 * Constructor.
	 *
	 * @param array  $address      Address to validate.
	 * @param string $access_token Access token for authentication.
	 */
	public function __construct( $address, $access_token ) {
		$this->address      = $address;
		$this->access_token = $access_token;
		$this->request      = $this->build_request();
	}

	/**
	 * Build the API request.
	 *
	 * @return array
	 */
	abstract protected function build_request();

	/**
	 * Validate the address.
	 *
	 * @return bool
	 */
	abstract public function validate();

	/**
	 * Check if the address is valid.
	 *
	 * @return bool
	 */
	abstract public function is_valid();

	/**
	 * Get the API request.
	 *
	 * @return array
	 */
	public function get_request() {
		return $this->request;
	}

	/**
	 * Get the API response.
	 *
	 * @return array|WP_Error
	 */
	public function get_response() {
		return $this->response;
	}
}