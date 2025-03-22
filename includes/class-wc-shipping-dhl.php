<?php
/**
 * Shipping method class.
 *
 * @package WC_Shipping_DHL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WooCommerce\DHL\API\Abstract_API_Client;
use WooCommerce\DHL\API\REST\OAuth as REST_API_OAuth;
use WooCommerce\DHL\API\REST\API_Client as REST_API_Client;
use WooCommerce\DHL\Logger;
use WooCommerce\DHL\Notifier;
use WooCommerce\BoxPacker\Abstract_Packer;
use WooCommerce\BoxPacker\WC_Boxpack;

/**
 * WC_Shipping_DHL class.
 *
 * @version 1.0.0
 * @since   1.0.0
 * @see     WC_Shipping_Method
 */
class WC_Shipping_DHL extends WC_Shipping_Method {

	/**
	 * DHL API user ID.
	 *
	 * @var mixed
	 */
	private $api_user;
	
	/**
	 * DHL API key.
	 *
	 * @var mixed
	 */
	private $api_key;
	
	/**
	 * DHL API shipper number.
	 *
	 * @var mixed
	 */
	private $shipper_number;

	/**
	 * The DHL instance dimension unit.
	 *
	 * @var string
	 */
	private string $dim_unit;
	
	/**
	 * The DHL instance weight unit.
	 *
	 * @var string
	 */
	private string $weight_unit;
	
	/**
	 * Offer all rates or cheapest.
	 *
	 * @var mixed
	 */
	private $offer_rates;
	
	/**
	 * Flag the destination address as residential.
	 *
	 * @var bool
	 */
	private bool $residential;
	
	/**
	 * Is the destination address valid?
	 *
	 * @var bool
	 */
	private bool $is_valid_destination_address = true;
	
	/**
	 * Is the destination address validation enabled?
	 *
	 * @var bool
	 */
	private bool $destination_address_validation;
	
	/**
	 * The fallback cost to use if no rates are returned.
	 *
	 * @var mixed
	 */
	private $fallback;
	
	/**
	 * Whether to pack items into boxes or not.
	 *
	 * @var mixed
	 */
	private $packing_method;
	
	/**
	 * Sets the box packer library to use.
	 *
	 * @var string
	 */
	public $box_packer_library;
	
	/**
	 * The custom boxes defined by the user.
	 *
	 * @var mixed
	 */
	private $boxes;
	
	/**
	 * A flag to determine if the user wants to insure the package.
	 *
	 * @var bool
	 */
	private bool $insuredvalue;
	
	/**
	 * Metric or imperial.
	 *
	 * @var string
	 */
	private string $units;
	
	/**
	 * The origin address line.
	 *
	 * @var string
	 */
	private string $origin_addressline;
	
	/**
	 * The origin city.
	 *
	 * @var string
	 */
	private string $origin_city;
	
	/**
	 * The origin state.
	 *
	 * @var string
	 */
	private string $origin_state;
	
	/**
	 * The origin country.
	 *
	 * @var string
	 */
	private string $origin_country;
	
	/**
	 * The origin postcode.
	 *
	 * @var string
	 */
	private string $origin_postcode;
	
	/**
	 * DHL API type.
	 *
	 * @var string
	 */
	private string $api_type = 'rest';
	
	/**
	 * DHL REST API OAuth instance.
	 *
	 * @var REST_API_OAuth
	 */
	private $rest_api_oauth;
	
	/**
	 * Notifier instance
	 *
	 * @var Notifier
	 */
	public $notifier;
	
	/**
	 * Logger instance
	 *
	 * @var Logger
	 */
	public $logger;
	
	/**
	 * Debug mode
	 *
	 * @var bool
	 */
	public $debug;
	
	/**
	 * Environment
	 *
	 * @var string
	 */
	public $environment;
	
	/**
	 * Services
	 *
	 * @var array
	 */
	private $services;
	
	/**
	 * Custom services
	 *
	 * @var array
	 */
	private $custom_services;

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Instance ID.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'dhl';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'DHL', 'woocommerce-shipping-dhl' );
		$this->method_description = __( 'Get shipping rates from DHL Express.', 'woocommerce-shipping-dhl' );
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
			'settings',
		);
		$this->logger             = new Logger();
		$this->notifier           = new Notifier();
		$this->init();
	}

	/**
	 * Initialize settings.
	 *
	 * @return void
	 */
	public function init() {
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title                          = $this->get_option( 'title' );
		$this->environment                    = $this->get_option( 'environment', 'test' );
		$this->api_user                       = $this->get_option( 'api_user' );
		$this->api_key                        = $this->get_option( 'api_key' );
		$this->shipper_number                 = $this->get_option( 'shipper_number' );
		$this->debug                          = 'yes' === $this->get_option( 'debug' );
		$this->destination_address_validation = 'yes' === $this->get_option( 'address_validation' );
		$this->dim_unit                       = $this->get_option( 'dimension_unit' );
		$this->weight_unit                    = $this->get_option( 'weight_unit' );
		$this->units                          = $this->weight_unit === 'KG' ? 'metric' : 'imperial';
		$this->packing_method                 = $this->get_option( 'packing_method', 'per_item' );
		$this->boxes                          = $this->get_option( 'boxes', array() );
		$this->services                       = $this->get_option( 'services', array() );
		$this->custom_services                = $this->get_option( 'custom_services', array() );
		$this->offer_rates                    = $this->get_option( 'offer_rates', 'all' );
		$this->fallback                       = $this->get_option( 'fallback' );
		$this->residential                    = 'yes' === $this->get_option( 'residential' );
		$this->insuredvalue                   = 'yes' === $this->get_option( 'insuredvalue' );
		$this->origin_addressline             = $this->get_option( 'origin_addressline', '' );
		$this->origin_city                    = $this->get_option( 'origin_city', '' );
		$this->origin_state                   = $this->get_option( 'origin_state', '' );
		$this->origin_country                 = $this->get_option( 'origin_country', WC()->countries->get_base_country() );
		$this->origin_postcode                = $this->get_option( 'origin_postcode', '' );

		// Register actions.
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'admin_notices', array( Notifier::class, 'output_notices' ) );
	}

	/**
	 * Get DHL OAuth instance.
	 *
	 * @return REST_API_OAuth
	 */
	public function get_dhl_oauth() {
		if ( empty( $this->rest_api_oauth ) ) {
			$this->rest_api_oauth = new REST_API_OAuth( $this );
		}

		return $this->rest_api_oauth;
	}

	/**
	 * Define settings form fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->instance_form_fields = array(
			'title' => array(
				'title'       => __( 'Method Title', 'woocommerce-shipping-dhl' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-shipping-dhl' ),
				'default'     => __( 'DHL Express', 'woocommerce-shipping-dhl' ),
				'desc_tip'    => true,
			),
			'api_section' => array(
				'title'       => __( 'API Settings', 'woocommerce-shipping-dhl' ),
				'type'        => 'title',
				'description' => __( 'Enter your DHL Express API credentials below.', 'woocommerce-shipping-dhl' ),
			),
			'environment' => array(
				'title'       => __( 'Environment', 'woocommerce-shipping-dhl' ),
				'type'        => 'select',
				'description' => __( 'Choose whether to use test or production environment.', 'woocommerce-shipping-dhl' ),
				'default'     => 'test',
				'options'     => array(
					'test'       => __( 'Test', 'woocommerce-shipping-dhl' ),
					'production' => __( 'Production', 'woocommerce-shipping-dhl' ),
				),
				'desc_tip'    => true,
			),
			'api_user' => array(
				'title'       => __( 'API User', 'woocommerce-shipping-dhl' ),
				'type'        => 'text',
				'description' => __( 'Your DHL Express API username.', 'woocommerce-shipping-dhl' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'api_key' => array(
				'title'       => __( 'API Key', 'woocommerce-shipping-dhl' ),
				'type'        => 'password',
				'description' => __( 'Your DHL Express API password.', 'woocommerce-shipping-dhl' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'shipper_number' => array(
				'title'       => __( 'Shipper Number', 'woocommerce-shipping-dhl' ),
				'type'        => 'text',
				'description' => __( 'Your DHL Express account number.', 'woocommerce-shipping-dhl' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'debug' => array(
				'title'       => __( 'Debug Mode', 'woocommerce-shipping-dhl' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable debug mode', 'woocommerce-shipping-dhl' ),
				'description' => __( 'Enable debug mode to log API requests and responses for troubleshooting.', 'woocommerce-shipping-dhl' ),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'address_validation' => array(
				'title'       => __( 'Address Validation', 'woocommerce-shipping-dhl' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable address validation', 'woocommerce-shipping-dhl' ),
				'description' => __( 'Enable address validation to verify destination addresses.', 'woocommerce-shipping-dhl' ),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'origin_section' => array(
				'title'       => __( 'Origin Settings', 'woocommerce-shipping-dhl' ),
				'type'        => 'title',
				'description' => __( 'Enter your shipping origin details below.', 'woocommerce-shipping-dhl' ),
			),
			'origin_addressline' => array(
				'title'       => __( 'Address Line', 'woocommerce-shipping-dhl' ),
				'type'        => 'text',
				'description' => __( 'The shipping origin address.', 'woocommerce-shipping-dhl' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'origin_city' => array(
				'title'       => __( 'City', 'woocommerce-shipping-dhl' ),
				'type'        => 'text',
				'description' => __( 'The shipping origin city.', 'woocommerce-shipping-dhl' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'origin_state' => array(
				'title'       => __( 'State', 'woocommerce-shipping-dhl' ),
				'type'        => 'text',
				'description' => __( 'The shipping origin state code (e.g., CA for California).', 'woocommerce-shipping-dhl' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'origin_country' => array(
				'title'       => __( 'Country', 'woocommerce-shipping-dhl' ),
				'type'        => 'select',
				'description' => __( 'The shipping origin country.', 'woocommerce-shipping-dhl' ),
				'default'     => WC()->countries->get_base_country(),
				'options'     => WC()->countries->get_countries(),
				'desc_tip'    => true,
			),
			'origin_postcode' => array(
				'title'       => __( 'Postcode', 'woocommerce-shipping-dhl' ),
				'type'        => 'text',
				'description' => __( 'The shipping origin postcode.', 'woocommerce-shipping-dhl' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'packaging_section' => array(
				'title'       => __( 'Packaging Settings', 'woocommerce-shipping-dhl' ),
				'type'        => 'title',
				'description' => __( 'Configure packaging settings for shipping.', 'woocommerce-shipping-dhl' ),
			),
			'dimension_unit' => array(
				'title'       => __( 'Dimension Unit', 'woocommerce-shipping-dhl' ),
				'type'        => 'select',
				'description' => __( 'The unit of measurement for package dimensions.', 'woocommerce-shipping-dhl' ),
				'default'     => 'in',
				'options'     => array(
					'in' => __( 'Inches', 'woocommerce-shipping-dhl' ),
					'cm' => __( 'Centimeters', 'woocommerce-shipping-dhl' ),
				),
				'desc_tip'    => true,
			),
			'weight_unit' => array(
				'title'       => __( 'Weight Unit', 'woocommerce-shipping-dhl' ),
				'type'        => 'select',
				'description' => __( 'The unit of measurement for package weight.', 'woocommerce-shipping-dhl' ),
				'default'     => 'LBS',
				'options'     => array(
					'LBS' => __( 'Pounds', 'woocommerce-shipping-dhl' ),
					'KG'  => __( 'Kilograms', 'woocommerce-shipping-dhl' ),
				),
				'desc_tip'    => true,
			),
			'packing_method' => array(
				'title'       => __( 'Packing Method', 'woocommerce-shipping-dhl' ),
				'type'        => 'select',
				'description' => __( 'Choose how items are packed for shipping.', 'woocommerce-shipping-dhl' ),
				'default'     => 'per_item',
				'options'     => array(
					'per_item'     => __( 'Pack items individually', 'woocommerce-shipping-dhl' ),
					'box_packing'  => __( 'Pack items into boxes with weights and dimensions', 'woocommerce-shipping-dhl' ),
					'weight_based' => __( 'Weight based packing (calculate based on total order weight)', 'woocommerce-shipping-dhl' ),
				),
				'desc_tip'    => true,
			),
			'boxes' => array(
				'type' => 'boxes',
			),
			'services_section' => array(
				'title'       => __( 'Service Settings', 'woocommerce-shipping-dhl' ),
				'type'        => 'title',
				'description' => __( 'Configure available shipping services.', 'woocommerce-shipping-dhl' ),
			),
			'services' => array(
				'type' => 'services',
			),
			'custom_services' => array(
				'type' => 'services_table',
			),
			'options_section' => array(
				'title'       => __( 'Additional Options', 'woocommerce-shipping-dhl' ),
				'type'        => 'title',
				'description' => __( 'Configure additional shipping options.', 'woocommerce-shipping-dhl' ),
			),
			'residential' => array(
				'title'       => __( 'Residential Address', 'woocommerce-shipping-dhl' ),
				'type'        => 'checkbox',
				'label'       => __( 'Treat destination addresses as residential', 'woocommerce-shipping-dhl' ),
				'description' => __( 'Enabling this option will treat all destination addresses as residential which may increase shipping costs.', 'woocommerce-shipping-dhl' ),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'insuredvalue' => array(
				'title'       => __( 'Insurance', 'woocommerce-shipping-dhl' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable insurance for packages', 'woocommerce-shipping-dhl' ),
				'description' => __( 'Enabling this option will add insurance to packages based on their value.', 'woocommerce-shipping-dhl' ),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'offer_rates' => array(
				'title'       => __( 'Offer Rates', 'woocommerce-shipping-dhl' ),
				'type'        => 'select',
				'description' => __( 'Choose which rates to display to customers.', 'woocommerce-shipping-dhl' ),
				'default'     => 'all',
				'options'     => array(
					'all'      => __( 'Show all rates', 'woocommerce-shipping-dhl' ),
					'cheapest' => __( 'Show cheapest rate only', 'woocommerce-shipping-dhl' ),
				),
				'desc_tip'    => true,
			),
			'fallback' => array(
				'title'       => __( 'Fallback Rate', 'woocommerce-shipping-dhl' ),
				'type'        => 'text',
				'description' => __( 'If DHL returns no rates, this rate will be used. Leave blank to disable.', 'woocommerce-shipping-dhl' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Generate boxes HTML.
	 *
	 * @return string
	 */
	public function generate_boxes_html() {
		ob_start();
		include dirname( __FILE__ ) . '/views/html-box-packing.php';
		return ob_get_clean();
	}

	/**
	 * Generate services HTML.
	 *
	 * @return string
	 */
	public function generate_services_html() {
		ob_start();
		include dirname( __FILE__ ) . '/views/html-services.php';
		return ob_get_clean();
	}

	/**
	 * Generate services table HTML.
	 *
	 * @return string
	 */
	public function generate_services_table_html() {
		ob_start();
		$services = $this->get_dhl_services();
		$custom_services = $this->get_custom_services();
		include dirname( __FILE__ ) . '/views/html-services-table.php';
		return ob_get_clean();
	}

	/**
	 * Calculate shipping.
	 *
	 * @param array $package Package to ship.
	 * @return void
	 */
	public function calculate_shipping( $package = array() ) {
		// Check if the destination address is valid.
		if ( $this->destination_address_validation ) {
			$this->validate_destination_address( $package['destination'] );

			if ( ! $this->is_valid_destination_address ) {
				return;
			}
		}

		$api_client = $this->get_api_client( $package );

		// Prepare package requests based on packing method.
		$requests = $this->prepare_package_requests( $package );

		if ( empty( $requests ) ) {
			$this->debug( __( 'No packages to ship.', 'woocommerce-shipping-dhl' ), 'error' );
			return;
		}

		$api_client->set_package_requests( $requests );

		// Get the shipping rates.
		$rates = $api_client->get_rates();

		if ( empty( $rates ) ) {
			$this->debug( __( 'No shipping rates returned from DHL.', 'woocommerce-shipping-dhl' ), 'error' );

			// Use fallback rate if available.
			if ( ! empty( $this->fallback ) ) {
				$this->add_rate( array(
					'id'    => $this->get_rate_id( 'fallback' ),
					'label' => $this->title,
					'cost'  => $this->fallback,
				) );
			}
			return;
		}

		// Filter rates based on settings.
		$this->process_and_add_rates( $rates );
	}

	/**
	 * Prepare package requests based on packing method.
	 *
	 * @param array $package Package to ship.
	 * @return array
	 */
	protected function prepare_package_requests( $package ) {
		switch ( $this->packing_method ) {
			case 'box_packing':
				return $this->box_shipping( $package );

			case 'weight_based':
				return $this->weight_based_shipping( $package );

			case 'per_item':
			default:
				return $this->per_item_shipping( $package );
		}
	}

	/**
	 * Process rates and add them to WooCommerce.
	 *
	 * @param array $rates Shipping rates.
	 * @return void
	 */
	protected function process_and_add_rates( $rates ) {
		if ( 'cheapest' === $this->offer_rates ) {
			$cheapest_rate = null;
			$cheapest_cost = null;

			foreach ( $rates as $rate ) {
				if ( is_null( $cheapest_cost ) || $rate['cost'] < $cheapest_cost ) {
					$cheapest_cost = $rate['cost'];
					$cheapest_rate = $rate;
				}
			}

			if ( ! is_null( $cheapest_rate ) ) {
				$this->add_rate( $cheapest_rate );
			}
		} else {
			// Add all rates.
			foreach ( $rates as $rate ) {
				$this->add_rate( $rate );
			}
		}
	}

	/**
	 * Per item shipping.
	 *
	 * @param array $package Package to ship.
	 * @return array
	 */
	protected function per_item_shipping( $package ) {
		$requests = array();
		$api_client = $this->get_api_client( $package );

		// Add each item as its own package.
		foreach ( $package['contents'] as $item_id => $values ) {
			if ( ! $values['data']->needs_shipping() ) {
				continue;
			}

			$request = $api_client->build_individually_packed_package_for_rate_request( $values );
			
			// Skip packages with zero weight
			if ( empty( $request['weight']['value'] ) || '0' === $request['weight']['value'] ) {
				continue;
			}

			$requests[] = $request;
		}

		return $requests;
	}

	/**
	 * Box shipping.
	 *
	 * @param array $package Package to ship.
	 * @return array
	 */
	protected function box_shipping( $package ) {
		$requests = array();
		$api_client = $this->get_api_client( $package );

		// Get the box packer.
		$box_packer = $this->get_box_packer();
		$box_packer->set_items( $this->get_packable_items( $package ) );

		$boxes = $this->get_shipping_boxes();
		
		if ( empty( $boxes ) ) {
			return $this->per_item_shipping( $package );
		}

		// Add boxes to the packer.
		foreach ( $boxes as $key => $box ) {
			$box_packer->add_box( $box );
		}
		
		// Pack the boxes.
		$packed_boxes = $box_packer->pack();
		$packages_count = count( $packed_boxes );

		foreach ( $packed_boxes as $packed_box ) {
			$request = $api_client->build_packed_box_package_for_rate_request( $packed_box, $packages_count );
			
			// Skip packages with zero weight
			if ( empty( $request['weight']['value'] ) || '0' === $request['weight']['value'] ) {
				continue;
			}

			$requests[] = $request;
		}

		return $requests;
	}

	/**
	 * Weight based shipping.
	 *
	 * @param array $package Package to ship.
	 * @return array
	 */
	protected function weight_based_shipping( $package ) {
		// Calculate total weight.
		$total_weight = 0;

		foreach ( $package['contents'] as $item_id => $values ) {
			if ( ! $values['data']->needs_shipping() ) {
				continue;
			}

			$total_weight += $values['data']->get_weight() * $values['quantity'];
		}

		// Convert the weight if needed.
		$total_weight = $this->get_converted_weight( $total_weight );

		// Create a single package request with total weight.
		$request = array(
			'weight' => array(
				'value' => (string) $this->get_formatted_measurement( $total_weight ),
				'unitOfMeasurement' => $this->get_weight_unit(),
			),
		);

		// Skip packages with zero weight
		if ( empty( $request['weight']['value'] ) || '0' === $request['weight']['value'] ) {
			return array();
		}

		return array( $request );
	}

	/**
	 * Get items that can be packed.
	 *
	 * @param array $package Package to ship.
	 * @return array
	 */
	protected function get_packable_items( $package ) {
		$items = array();

		foreach ( $package['contents'] as $item_id => $values ) {
			$product = $values['data'];

			if ( ! $product->needs_shipping() ) {
				continue;
			}

			// Skip products with no weight or dimensions.
			if ( empty( $product->get_weight() ) && 
				( empty( $product->get_length() ) || empty( $product->get_width() ) || empty( $product->get_height() ) ) ) {
				continue;
			}

			$item = array(
				'data'     => $product,
				'quantity' => $values['quantity'],
			);

			$items[ $item_id ] = $item;
		}

		return $items;
	}

	/**
	 * Get the box packer.
	 *
	 * @return Abstract_Packer
	 */
	protected function get_box_packer() {
		return new WC_Boxpack();
	}

	/**
	 * Get shipping boxes.
	 *
	 * @return array
	 */
	public function get_shipping_boxes() {
		$boxes = array();
		
		if ( empty( $this->boxes ) ) {
			return $boxes;
		}

		foreach ( $this->boxes as $box ) {
			if ( empty( $box['enabled'] ) ) {
				continue;
			}

			$box_id = isset( $box['id'] ) ? $box['id'] : '';
			
			$new_box = array(
				'id'       => $box_id,
				'name'     => isset( $box['name'] ) ? $box['name'] : '',
				'length'   => isset( $box['length'] ) ? $this->get_formatted_measurement( $box['length'] ) : 0,
				'width'    => isset( $box['width'] ) ? $this->get_formatted_measurement( $box['width'] ) : 0,
				'height'   => isset( $box['height'] ) ? $this->get_formatted_measurement( $box['height'] ) : 0,
				'box_weight' => isset( $box['box_weight'] ) ? $this->get_formatted_measurement( $box['box_weight'] ) : 0,
				'max_weight' => isset( $box['max_weight'] ) ? $this->get_formatted_measurement( $box['max_weight'] ) : 0,
			);
			
			$boxes[] = $new_box;
		}
		
		return $boxes;
	}

	/**
	 * Validate the destination address.
	 *
	 * @param array $destination_address Destination address.
	 * @return void
	 */
	protected function validate_destination_address( $destination_address ) {
		$api_client = $this->get_api_client();
		$api_client->validate_destination_address( $destination_address );
	}

	/**
	 * Get the API client.
	 *
	 * @param array $package Package to ship.
	 * @return Abstract_API_Client
	 */
	public function get_api_client( $package = array() ) {
		return new REST_API_Client( $this, $package );
	}

	/**
	 * Check if the destination is domestic.
	 *
	 * @param string $country_code Country code.
	 * @return bool
	 */
	public function is_domestic_destination( $country_code ) {
		return $this->get_origin_country() === $country_code;
	}

	/**
	 * Get formatted measurement.
	 *
	 * @param mixed $value Value to format.
	 * @return float
	 */
	public function get_formatted_measurement( $value ) {
		return round( (float) $value, 2 );
	}

	/**
	 * Convert dimensions.
	 *
	 * @param float  $dimension       Dimension to convert.
	 * @param string $from_unit       Unit to convert from.
	 * @param string $to_unit         Unit to convert to.
	 * @return float
	 */
	public function get_converted_dimension( $dimension, $from_unit = null, $to_unit = null ) {
		if ( empty( $from_unit ) ) {
			$from_unit = get_option( 'woocommerce_dimension_unit' );
		}

		if ( empty( $to_unit ) ) {
			$to_unit = $this->dim_unit;
		}

		if ( $from_unit === $to_unit ) {
			return $dimension;
		}

		// Convert to cm first.
		switch ( $from_unit ) {
			case 'in':
				$dimension *= 2.54;
				break;
			case 'm':
				$dimension *= 100;
				break;
			case 'mm':
				$dimension *= 0.1;
				break;
			case 'yd':
				$dimension *= 91.44;
				break;
		}

		// Convert from cm to desired unit.
		switch ( $to_unit ) {
			case 'in':
				$dimension *= 0.3937;
				break;
			case 'm':
				$dimension *= 0.01;
				break;
			case 'mm':
				$dimension *= 10;
				break;
			case 'yd':
				$dimension *= 0.010936133;
				break;
		}

		return $dimension;
	}

	/**
	 * Convert weight.
	 *
	 * @param float  $weight    Weight to convert.
	 * @param string $from_unit Unit to convert from.
	 * @param string $to_unit   Unit to convert to.
	 * @return float
	 */
	public function get_converted_weight( $weight, $from_unit = null, $to_unit = null ) {
		if ( empty( $from_unit ) ) {
			$from_unit = get_option( 'woocommerce_weight_unit' );
		}

		if ( empty( $to_unit ) ) {
			$to_unit = $this->weight_unit;
		}

		if ( $from_unit === $to_unit ) {
			return $weight;
		}

		// Convert to kg first.
		switch ( $from_unit ) {
			case 'lbs':
			case 'LBS':
				$weight *= 0.453592;
				break;
			case 'oz':
			case 'OZS':
				$weight *= 0.0283495;
				break;
			case 'g':
				$weight *= 0.001;
				break;
		}

		// Convert from kg to desired unit.
		switch ( $to_unit ) {
			case 'lbs':
			case 'LBS':
				$weight *= 2.20462;
				break;
			case 'oz':
			case 'OZS':
				$weight *= 35.274;
				break;
			case 'g':
				$weight *= 1000;
				break;
		}

		return $weight;
	}

	/**
	 * Debug message.
	 *
	 * @param string $message Message to log.
	 * @param string $type    Log type.
	 * @param array  $data    Additional data.
	 * @param string $group   Notice group.
	 * @return void
	 */
	public function debug( $message, $type = 'notice', $data = array(), $group = '' ) {
		if ( ! $this->debug ) {
			return;
		}

		if ( is_array( $message ) || is_object( $message ) ) {
			$message = print_r( $message, true );
		}

		if ( ! empty( $data ) ) {
			$message .= ' | ' . print_r( $data, true );
		}

		switch ( $type ) {
			case 'error':
				$this->logger->error( $message );
				
				if ( ! empty( $group ) ) {
					Notifier::add_notice( $message, 'error', $group );
				}
				break;
			case 'warning':
				$this->logger->warning( $message );
				
				if ( ! empty( $group ) ) {
					Notifier::add_notice( $message, 'warning', $group );
				}
				break;
			default:
				$this->logger->info( $message );
				
				if ( ! empty( $group ) ) {
					Notifier::add_notice( $message, 'info', $group );
				}
				break;
		}
	}

	/**
	 * Get the rate ID.
	 *
	 * @param string $code Service code.
	 * @return string
	 */
	public function get_rate_id( $code ) {
		return $this->id . '_' . $code . '_' . $this->instance_id;
	}

	/**
	 * Get DHL services.
	 *
	 * @return array
	 */
	public function get_dhl_services() {
		return array(
			'0' => __( 'DHL Express Worldwide', 'woocommerce-shipping-dhl' ),
			'1' => __( 'DHL Express Domestic', 'woocommerce-shipping-dhl' ),
			'2' => __( 'DHL Express 9:00', 'woocommerce-shipping-dhl' ),
			'3' => __( 'DHL Express 10:30', 'woocommerce-shipping-dhl' ),
			'4' => __( 'DHL Express 12:00', 'woocommerce-shipping-dhl' ),
			'5' => __( 'DHL Express Easy', 'woocommerce-shipping-dhl' ),
			'7' => __( 'DHL Economy Select', 'woocommerce-shipping-dhl' ),
			'8' => __( 'DHL Express 12:00', 'woocommerce-shipping-dhl' ),
			'9' => __( 'DHL Express Envelope', 'woocommerce-shipping-dhl' ),
			'B' => __( 'DHL Express Breakbulk', 'woocommerce-shipping-dhl' ),
			'C' => __( 'DHL Express Medical Express', 'woocommerce-shipping-dhl' ),
			'D' => __( 'DHL Express Express 9:00', 'woocommerce-shipping-dhl' ),
			'E' => __( 'DHL Express Express 10:30', 'woocommerce-shipping-dhl' ),
			'F' => __( 'DHL Express Freight Worldwide', 'woocommerce-shipping-dhl' ),
			'G' => __( 'DHL Express Domestic Economy Select', 'woocommerce-shipping-dhl' ),
			'H' => __( 'DHL Express Economy Select', 'woocommerce-shipping-dhl' ),
			'I' => __( 'DHL Express Break Bulk Economy', 'woocommerce-shipping-dhl' ),
			'J' => __( 'DHL Express Jumbo Box', 'woocommerce-shipping-dhl' ),
			'K' => __( 'DHL Express Express 9:00', 'woocommerce-shipping-dhl' ),
			'L' => __( 'DHL Express Express 10:30', 'woocommerce-shipping-dhl' ),
			'M' => __( 'DHL Express Express 12:00', 'woocommerce-shipping-dhl' ),
			'N' => __( 'DHL Express Domestic Express', 'woocommerce-shipping-dhl' ),
			'O' => __( 'DHL Express Others', 'woocommerce-shipping-dhl' ),
			'P' => __( 'DHL Express Worldwide', 'woocommerce-shipping-dhl' ),
			'Q' => __( 'DHL Express Medical Express', 'woocommerce-shipping-dhl' ),
			'R' => __( 'DHL Express GlobalMail Business', 'woocommerce-shipping-dhl' ),
			'S' => __( 'DHL Express Same Day', 'woocommerce-shipping-dhl' ),
			'T' => __( 'DHL Express Express 12:00', 'woocommerce-shipping-dhl' ),
			'U' => __( 'DHL Express Express Worldwide', 'woocommerce-shipping-dhl' ),
			'V' => __( 'DHL Express Europack', 'woocommerce-shipping-dhl' ),
			'W' => __( 'DHL Express Economy Select', 'woocommerce-shipping-dhl' ),
			'X' => __( 'DHL Express Express Envelope', 'woocommerce-shipping-dhl' ),
			'Y' => __( 'DHL Express Express 12:00', 'woocommerce-shipping-dhl' ),
			'Z' => __( 'DHL Express Destination Charges', 'woocommerce-shipping-dhl' ),
		);
	}

	/**
	 * Get custom services.
	 *
	 * @return array
	 */
	public function get_custom_services() {
		return empty( $this->custom_services ) ? array() : $this->custom_services;
	}

	/**
	 * Get enabled service codes.
	 *
	 * @return array
	 */
	public function get_enabled_service_codes() {
		$enabled_services = array();
		$services = $this->get_dhl_services();
		$custom_services = $this->get_custom_services();

		foreach ( $services as $code => $name ) {
			if ( isset( $custom_services[ $code ]['enabled'] ) && $custom_services[ $code ]['enabled'] ) {
				$enabled_services[] = $code;
			}
		}

		return $enabled_services;
	}

	/**
	 * Get the origin address line.
	 *
	 * @return string
	 */
	public function get_origin_addressline() {
		return $this->origin_addressline;
	}

	/**
	 * Get the origin city.
	 *
	 * @return string
	 */
	public function get_origin_city() {
		return $this->origin_city;
	}

	/**
	 * Get the origin state.
	 *
	 * @return string
	 */
	public function get_origin_state() {
		return $this->origin_state;
	}

	/**
	 * Get the origin country.
	 *
	 * @return string
	 */
	public function get_origin_country() {
		return $this->origin_country;
	}

	/**
	 * Get the origin postcode.
	 *
	 * @return string
	 */
	public function get_origin_postcode() {
		return $this->origin_postcode;
	}

	/**
	 * Get the dimension unit.
	 *
	 * @return string
	 */
	public function get_dimension_unit() {
		return $this->dim_unit;
	}

	/**
	 * Get the weight unit.
	 *
	 * @return string
	 */
	public function get_weight_unit() {
		return $this->weight_unit;
	}

	/**
	 * Check if the destination address is residential.
	 *
	 * @return bool
	 */
	public function is_residential() {
		return $this->residential;
	}

	/**
	 * Check if debug mode is enabled.
	 *
	 * @return bool
	 */
	public function is_debug_mode_enabled() {
		return $this->debug;
	}

	/**
	 * Check if insured value is enabled.
	 *
	 * @return bool
	 */
	public function is_insured_value_enabled() {
		return $this->insuredvalue;
	}

	/**
	 * Get the shipper number.
	 *
	 * @return string
	 */
	public function get_shipper_number() {
		return $this->shipper_number;
	}

	/**
	 * Set the valid destination address flag.
	 *
	 * @param bool $valid Valid flag.
	 * @return void
	 */
	public function set_is_valid_destination_address( $valid ) {
		$this->is_valid_destination_address = $valid;
	}

	/**
	 * Check if a package has service options for the destination country.
	 *
	 * @param string $country_code Country code.
	 * @return bool
	 */
	public function has_package_service_options( $country_code ) {
		// Check if the destination is supported for service options. 
		// For now, return true for all countries, but this can be refined if needed.
		return true;
	}
}