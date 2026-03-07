<?php
/**
 * DHL REST Shipment Client.
 *
 * @package WC_Shipping_DHL
 */

namespace WooCommerce\DHL\API\REST;

defined( 'ABSPATH' ) || exit;

use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
use WooCommerce\DHL\WC_Shipping_DHL;
use WP_Error;

/**
 * Shipment_Client class.
 */
class Shipment_Client {

	/**
	 * Endpoint for the DHL API.
	 *
	 * @var array
	 */
	protected static array $endpoints = array(
		'test'       => 'https://express.api.dhl.com/mydhlapi/test',
		'production' => 'https://express.api.dhl.com/mydhlapi',
	);

	/**
	 * Shipping method instance.
	 *
	 * @var WC_Shipping_DHL
	 */
	private WC_Shipping_DHL $shipping_method;

	/**
	 * Constructor.
	 *
	 * @param WC_Shipping_DHL $shipping_method Shipping method instance.
	 */
	public function __construct( WC_Shipping_DHL $shipping_method ) {
		$this->shipping_method = $shipping_method;
	}

	/**
	 * Create a DHL shipment for an order.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array|WP_Error
	 */
	public function create_shipment( WC_Order $order ) {
		$request = $this->build_shipment_request( $order );

		return $this->request( 'POST', '/shipments', $request );
	}

	/**
	 * Book a pickup for an order.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array|WP_Error
	 */
	public function create_pickup( WC_Order $order ) {
		$request = $this->build_pickup_request( $order );

		return $this->request( 'POST', '/pickups', $request );
	}

	/**
	 * Retrieve tracking for a shipment.
	 *
	 * @param string $shipment_tracking_number Shipment tracking number.
	 *
	 * @return array|WP_Error
	 */
	public function get_tracking( string $shipment_tracking_number ) {
		if ( '' === $shipment_tracking_number ) {
			return new WP_Error( 'wc_dhl_missing_tracking_number', __( 'A DHL tracking number is required.', 'woocommerce-shipping-dhl' ) );
		}

		return $this->request(
			'GET',
			'/shipments/' . rawurlencode( $shipment_tracking_number ) . '/tracking',
			array(),
			array(
				'trackingView'  => 'all-checkpoints',
				'levelOfDetail' => 'all',
			)
		);
	}

	/**
	 * Retrieve proof-of-delivery documents for a shipment.
	 *
	 * @param string $shipment_tracking_number Shipment tracking number.
	 * @param string $content_type             POD content type.
	 *
	 * @return array|WP_Error
	 */
	public function get_proof_of_delivery( string $shipment_tracking_number, string $content_type = 'epod-summary' ) {
		if ( '' === $shipment_tracking_number ) {
			return new WP_Error( 'wc_dhl_missing_tracking_number', __( 'A DHL tracking number is required.', 'woocommerce-shipping-dhl' ) );
		}

		return $this->request(
			'GET',
			'/shipments/' . rawurlencode( $shipment_tracking_number ) . '/proof-of-delivery',
			array(),
			array(
				'shipperAccountNumber' => (string) $this->shipping_method->get_shipper_number(),
				'content'              => $content_type,
			)
		);
	}

	/**
	 * Retrieve service points for an order destination.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array|WP_Error
	 */
	public function get_service_points( WC_Order $order ) {
		$query = $this->build_service_points_query( $order );

		return $this->request( 'GET', '/servicepoints', array(), $query );
	}

	/**
	 * Estimate landed cost for an order.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array|WP_Error
	 */
	public function estimate_landed_cost( WC_Order $order ) {
		$request = $this->build_landed_cost_request( $order );

		return $this->request( 'POST', '/landed-cost', $request );
	}

	/**
	 * Build shipment request payload.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array
	 */
	private function build_shipment_request( WC_Order $order ): array {
		$is_customs_declarable = $this->is_order_customs_declarable( $order );
		$service_code          = $this->get_order_service_code( $order );

		$request = array(
			'plannedShippingDateAndTime' => $this->get_planned_datetime( '12:00:00' ),
			'pickup'                     => array(
				'isRequested' => false,
			),
			'productCode'                => $service_code,
			'accounts'                   => array(
				$this->get_account_payload(),
			),
			'outputImageProperties'      => array(
				'printerDPI'     => 300,
				'encodingFormat' => 'pdf',
				'imageOptions'   => array(
					array(
						'typeCode'     => 'label',
						'templateName' => 'ECOM26_84_001',
						'isRequested'  => true,
					),
					array(
						'typeCode'          => 'waybillDoc',
						'templateName'      => 'ARCH_8X4',
						'isRequested'       => true,
						'hideAccountNumber' => false,
						'numberOfCopies'    => 1,
					),
				),
			),
			'customerDetails'            => array(
				'shipperDetails'  => $this->get_shipper_details(),
				'receiverDetails' => $this->get_receiver_details( $order ),
			),
			'content'                    => array(
				'packages'            => $this->get_order_packages( $order ),
				'isCustomsDeclarable' => $is_customs_declarable,
				'description'         => $this->get_order_content_description( $order ),
				'incoterm'            => 'DAP',
				'unitOfMeasurement'   => $this->get_measurement_system(),
			),
		);

		if ( $is_customs_declarable ) {
			$request['content']['declaredValue']         = (float) wc_format_decimal( $order->get_total(), 2 );
			$request['content']['declaredValueCurrency'] = $order->get_currency();
		}

		return $request;
	}

	/**
	 * Build pickup request payload.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array
	 */
	private function build_pickup_request( WC_Order $order ): array {
		$is_customs_declarable = $this->is_order_customs_declarable( $order );
		$service_code          = $this->get_order_service_code( $order );
		$shipment_detail       = array(
			'productCode'         => $service_code,
			'localProductCode'    => $service_code,
			'accounts'            => array(
				$this->get_account_payload(),
			),
			'isCustomsDeclarable' => $is_customs_declarable,
			'unitOfMeasurement'   => $this->get_measurement_system(),
			'packages'            => $this->get_order_packages( $order ),
		);
		$tracking_number       = (string) $order->get_meta( '_wc_dhl_shipment_tracking_number', true );

		if ( '' !== $tracking_number ) {
			$shipment_detail['shipmentTrackingNumber'] = $tracking_number;
		}

		if ( $is_customs_declarable ) {
			$shipment_detail['declaredValue']         = (float) wc_format_decimal( $order->get_total(), 2 );
			$shipment_detail['declaredValueCurrency'] = $order->get_currency();
		}

		return array(
			'plannedPickupDateAndTime' => $this->get_planned_datetime( '15:00:00' ),
			'closeTime'                => '18:00',
			'location'                 => __( 'Front desk', 'woocommerce-shipping-dhl' ),
			'locationType'             => 'business',
			'accounts'                 => array(
				$this->get_account_payload(),
			),
			'customerDetails'          => array(
				'shipperDetails' => $this->get_shipper_details(),
			),
			'shipmentDetails'          => array( $shipment_detail ),
		);
	}

	/**
	 * Get shipper account payload.
	 *
	 * @return array
	 */
	private function get_account_payload(): array {
		return array(
			'typeCode' => 'shipper',
			'number'   => (string) $this->shipping_method->get_shipper_number(),
		);
	}

	/**
	 * Build shipper details.
	 *
	 * @return array
	 */
	private function get_shipper_details(): array {
		$store_phone = get_option( 'woocommerce_store_phone', '' );
		if ( '' === $store_phone ) {
			$store_phone = '0000000000';
		}

		$details = array(
			'postalAddress'      => array(
				'postalCode'   => $this->clean_value( $this->shipping_method->get_origin_postcode() ),
				'cityName'     => $this->clean_value( $this->shipping_method->get_origin_city() ),
				'countryCode'  => $this->clean_value( $this->shipping_method->get_origin_country() ),
				'addressLine1' => $this->clean_value( $this->shipping_method->get_origin_addressline() ),
			),
			'contactInformation' => array(
				'fullName'    => $this->clean_value( get_bloginfo( 'name' ) ),
				'companyName' => $this->clean_value( get_bloginfo( 'name' ) ),
				'phone'       => $this->clean_value( $store_phone ),
				'email'       => $this->clean_value( get_option( 'admin_email', 'store@example.com' ) ),
			),
			'typeCode'           => 'business',
		);

		if ( '' !== $this->shipping_method->get_origin_state() ) {
			$details['postalAddress']['provinceCode'] = $this->clean_value( $this->shipping_method->get_origin_state() );
		}

		return $details;
	}

	/**
	 * Build receiver details from order data.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array
	 */
	private function get_receiver_details( WC_Order $order ): array {
		$shipping_country  = $order->get_shipping_country();
		$shipping_city     = $order->get_shipping_city();
		$shipping_postcode = $order->get_shipping_postcode();
		$shipping_address  = $order->get_shipping_address_1();
		$shipping_state    = $order->get_shipping_state();
		$shipping_phone    = $order->get_billing_phone();
		$shipping_email    = $order->get_billing_email();
		$full_name         = trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() );
		$company_name      = $order->get_shipping_company();

		if ( '' === $shipping_country ) {
			$shipping_country  = $order->get_billing_country();
			$shipping_city     = $order->get_billing_city();
			$shipping_postcode = $order->get_billing_postcode();
			$shipping_address  = $order->get_billing_address_1();
			$shipping_state    = $order->get_billing_state();
			$full_name         = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
			$company_name      = $order->get_billing_company();
		}

		if ( '' === $company_name ) {
			$company_name = $full_name;
		}

		if ( '' === $full_name ) {
			$full_name = $company_name;
		}

		if ( '' === $shipping_phone ) {
			$shipping_phone = '0000000000';
		}

		if ( '' === $shipping_email ) {
			$shipping_email = 'customer@example.com';
		}

		$details = array(
			'postalAddress'      => array(
				'postalCode'   => $this->clean_value( $shipping_postcode ),
				'cityName'     => $this->clean_value( $shipping_city ),
				'countryCode'  => $this->clean_value( $shipping_country ),
				'addressLine1' => $this->clean_value( $shipping_address ),
			),
			'contactInformation' => array(
				'fullName'    => $this->clean_value( $full_name ),
				'companyName' => $this->clean_value( $company_name ),
				'phone'       => $this->clean_value( $shipping_phone ),
				'email'       => $this->clean_value( $shipping_email ),
			),
			'typeCode'           => 'business',
		);

		if ( '' !== $shipping_state ) {
			$details['postalAddress']['provinceCode'] = $this->clean_value( $shipping_state );
		}

		return $details;
	}

	/**
	 * Build package list from order.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array
	 */
	private function get_order_packages( WC_Order $order ): array {
		$package_requests = $this->build_order_package_requests( $order );
		if ( empty( $package_requests ) ) {
			return array( $this->build_fallback_shipment_package( $order ) );
		}

		$packages = array();
		foreach ( $package_requests as $index => $package_request ) {
			if ( ! is_array( $package_request ) ) {
				continue;
			}

			$packages[] = $this->build_shipment_package_from_rate_request( $package_request, ( $index + 1 ), $order );
		}

		return ! empty( $packages ) ? $packages : array( $this->build_fallback_shipment_package( $order ) );
	}

	/**
	 * Build package requests for an order using the configured shipping packing mode.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array
	 */
	private function build_order_package_requests( WC_Order $order ): array {
		$package = $this->build_rate_package_from_order( $order );
		if ( empty( $package['contents'] ) ) {
			return array();
		}

		$package_requests = $this->shipping_method->prepare_package_requests( $package );

		return is_array( $package_requests ) ? $package_requests : array();
	}

	/**
	 * Build a WooCommerce-style rate package array from an order.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array
	 */
	private function build_rate_package_from_order( WC_Order $order ): array {
		$shipping_country  = $order->get_shipping_country();
		$shipping_state    = $order->get_shipping_state();
		$shipping_postcode = $order->get_shipping_postcode();
		$shipping_city     = $order->get_shipping_city();
		$shipping_address  = $order->get_shipping_address_1();
		$shipping_address2 = $order->get_shipping_address_2();

		if ( '' === $shipping_country ) {
			$shipping_country  = $order->get_billing_country();
			$shipping_state    = $order->get_billing_state();
			$shipping_postcode = $order->get_billing_postcode();
			$shipping_city     = $order->get_billing_city();
			$shipping_address  = $order->get_billing_address_1();
			$shipping_address2 = $order->get_billing_address_2();
		}

		$package = array(
			'destination' => array(
				'country'   => $shipping_country,
				'state'     => $shipping_state,
				'postcode'  => $shipping_postcode,
				'city'      => $shipping_city,
				'address'   => $shipping_address,
				'address_1' => $shipping_address,
				'address_2' => $shipping_address2,
			),
			'contents'    => array(),
		);

		foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();
			if ( ! $product instanceof WC_Product || ! $product->needs_shipping() ) {
				continue;
			}

			$package['contents'][ $item_id ] = array(
				'data'     => $product,
				'quantity' => max( 1, (int) $item->get_quantity() ),
			);
		}

		return $package;
	}

	/**
	 * Convert a rate-style package request into a shipment package payload.
	 *
	 * @param array    $package_request Rate package request.
	 * @param int      $package_index   Package sequence.
	 * @param WC_Order $order           Order.
	 *
	 * @return array
	 */
	private function build_shipment_package_from_rate_request( array $package_request, int $package_index, WC_Order $order ): array {
		$weight_value = isset( $package_request['weight']['value'] ) ? (float) $package_request['weight']['value'] : 0.0;
		$weight_value = max( 0.1, $weight_value );

		$package = array(
			'typeCode'           => '2BP',
			'weight'             => (float) wc_format_decimal( $weight_value, 3 ),
			'customerReferences' => array(
				array(
					'typeCode' => 'CU',
					'value'    => (string) $order->get_order_number() . ( $package_index > 1 ? '-' . $package_index : '' ),
				),
			),
		);

		if ( ! empty( $package_request['dimensions'] ) && is_array( $package_request['dimensions'] ) ) {
			$package['dimensions'] = array(
				'length' => (int) max( 1, round( (float) ( $package_request['dimensions']['length'] ?? 0 ) ) ),
				'width'  => (int) max( 1, round( (float) ( $package_request['dimensions']['width'] ?? 0 ) ) ),
				'height' => (int) max( 1, round( (float) ( $package_request['dimensions']['height'] ?? 0 ) ) ),
			);
		}

		return $package;
	}

	/**
	 * Build a conservative fallback shipment package when packing data cannot be derived.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array
	 */
	private function build_fallback_shipment_package( WC_Order $order ): array {
		$package_dimensions = $this->get_order_package_dimensions( $order );
		$total_weight       = $this->get_order_total_weight( $order );

		return array(
			'typeCode'           => '2BP',
			'weight'             => (float) wc_format_decimal( $this->shipping_method->format_weight( $total_weight, $this->shipping_method->get_weight_unit() ), 3 ),
			'dimensions'         => array(
				'length' => (int) max( 1, $this->shipping_method->format_dimension( $package_dimensions['length'], $this->shipping_method->get_dimension_unit() ) ),
				'width'  => (int) max( 1, $this->shipping_method->format_dimension( $package_dimensions['width'], $this->shipping_method->get_dimension_unit() ) ),
				'height' => (int) max( 1, $this->shipping_method->format_dimension( $package_dimensions['height'], $this->shipping_method->get_dimension_unit() ) ),
			),
			'customerReferences' => array(
				array(
					'typeCode' => 'CU',
					'value'    => (string) $order->get_order_number(),
				),
			),
		);
	}

	/**
	 * Get total order weight converted to DHL method unit.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return float
	 */
	private function get_order_total_weight( WC_Order $order ): float {
		$total_weight = 0.0;

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();
			if ( ! $product instanceof WC_Product || ! $product->needs_shipping() ) {
				continue;
			}

			$quantity       = max( 1, (int) $item->get_quantity() );
			$product_weight = (float) $product->get_weight();

			if ( $product_weight <= 0 ) {
				$product_weight = 0.1;
			}

			$total_weight += $this->shipping_method->get_converted_weight( $product_weight ) * $quantity;
		}

		if ( $total_weight <= 0 ) {
			$total_weight = 0.5;
		}

		return $total_weight;
	}

	/**
	 * Get package dimensions from order products.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array
	 */
	private function get_order_package_dimensions( WC_Order $order ): array {
		$dimensions = array(
			'length' => 0.0,
			'width'  => 0.0,
			'height' => 0.0,
		);

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();
			if ( ! $product instanceof WC_Product || ! $product->needs_shipping() ) {
				continue;
			}

			$dimensions['length'] = max( $dimensions['length'], (float) $this->shipping_method->get_converted_dimension( $product->get_length() ) );
			$dimensions['width']  = max( $dimensions['width'], (float) $this->shipping_method->get_converted_dimension( $product->get_width() ) );
			$dimensions['height'] = max( $dimensions['height'], (float) $this->shipping_method->get_converted_dimension( $product->get_height() ) );
		}

		if ( $dimensions['length'] <= 0 ) {
			$dimensions['length'] = 10;
		}

		if ( $dimensions['width'] <= 0 ) {
			$dimensions['width'] = 10;
		}

		if ( $dimensions['height'] <= 0 ) {
			$dimensions['height'] = 10;
		}

		return $dimensions;
	}

	/**
	 * Get order content description.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return string
	 */
	private function get_order_content_description( WC_Order $order ): string {
		$product_names = array();

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$product_name = (string) $item->get_name();
			if ( '' !== $product_name ) {
				$product_names[] = $product_name;
			}

			if ( count( $product_names ) >= 3 ) {
				break;
			}
		}

		if ( empty( $product_names ) ) {
			return sprintf(
				/* translators: %s: order number. */
				__( 'Order %s', 'woocommerce-shipping-dhl' ),
				$order->get_order_number()
			);
		}

		$description = implode( ', ', $product_names );
		$description = $this->clean_value( $description );

		if ( strlen( $description ) > 70 ) {
			$description = substr( $description, 0, 70 );
		}

		return $description;
	}

	/**
	 * Build service points query parameters.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array
	 */
	private function build_service_points_query( WC_Order $order ): array {
		$shipping_country  = $order->get_shipping_country();
		$shipping_city     = $order->get_shipping_city();
		$shipping_postcode = $order->get_shipping_postcode();
		$shipping_address  = $order->get_shipping_address_1();

		if ( '' === $shipping_country ) {
			$shipping_country  = $order->get_billing_country();
			$shipping_city     = $order->get_billing_city();
			$shipping_postcode = $order->get_billing_postcode();
			$shipping_address  = $order->get_billing_address_1();
		}

		$address_parts = array_filter(
			array(
				$this->clean_value( $shipping_address ),
				$this->clean_value( $shipping_city ),
				$this->clean_value( $shipping_postcode ),
			)
		);

		$primary_package = $this->get_primary_order_package( $order );
		$dimension_unit  = 'cm' === $this->shipping_method->get_dimension_unit() ? 'cm' : 'in';
		$weight_unit     = 'KG' === $this->shipping_method->get_weight_unit() ? 'kg' : 'lb';

		return array(
			'address'             => implode( ', ', $address_parts ),
			'countryCode'         => strtoupper( $shipping_country ),
			'language'            => 'eng',
			'servicePointResults' => '10',
			'capability'          => '81,74',
			'weight'              => (string) ( $primary_package['weight'] ?? '0.5' ),
			'weightUom'           => $weight_unit,
			'length'              => (string) (int) max( 1, (int) ( $primary_package['dimensions']['length'] ?? 10 ) ),
			'width'               => (string) (int) max( 1, (int) ( $primary_package['dimensions']['width'] ?? 10 ) ),
			'height'              => (string) (int) max( 1, (int) ( $primary_package['dimensions']['height'] ?? 10 ) ),
			'dimensionsUom'       => $dimension_unit,
		);
	}

	/**
	 * Get the primary shipment package for APIs that only accept a single package summary.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array
	 */
	private function get_primary_order_package( WC_Order $order ): array {
		$packages = $this->get_order_packages( $order );
		if ( empty( $packages ) ) {
			return $this->build_fallback_shipment_package( $order );
		}

		usort(
			$packages,
			static function ( $left, $right ) {
				return (float) ( $right['weight'] ?? 0 ) <=> (float) ( $left['weight'] ?? 0 );
			}
		);

		return is_array( $packages[0] ) ? $packages[0] : $this->build_fallback_shipment_package( $order );
	}

	/**
	 * Build landed cost request payload.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array
	 */
	private function build_landed_cost_request( WC_Order $order ): array {
		$is_customs_declarable = $this->is_order_customs_declarable( $order );
		$service_code          = $this->get_order_service_code( $order );
		$currency_code         = (string) $order->get_currency();
		$items                 = $this->get_landed_cost_items( $order, $currency_code );
		$request               = array(
			'customerDetails'             => array(
				'shipperDetails'  => $this->get_shipper_details(),
				'receiverDetails' => $this->get_receiver_details( $order ),
			),
			'accounts'                    => array(
				$this->get_account_payload(),
			),
			'productCode'                 => $service_code,
			'localProductCode'            => $service_code,
			'unitOfMeasurement'           => $this->get_measurement_system(),
			'currencyCode'                => $currency_code,
			'isCustomsDeclarable'         => $is_customs_declarable,
			'isDTPRequested'              => false,
			'isInsuranceRequested'        => $this->shipping_method->is_insured_value_enabled(),
			'getCostBreakdown'            => true,
			'shipmentPurpose'             => 'commercial',
			'transportationMode'          => 'air',
			'merchantSelectedCarrierName' => 'DHL',
			'packages'                    => $this->get_order_packages( $order ),
			'items'                       => $items,
		);

		$shipping_total = (float) $order->get_shipping_total();
		if ( $shipping_total > 0 ) {
			$request['charges'] = array(
				array(
					'typeCode'     => 'freight',
					'amount'       => (float) wc_format_decimal( $shipping_total, 2 ),
					'currencyCode' => $currency_code,
				),
			);
		}

		return $request;
	}

	/**
	 * Build landed cost item lines from order products.
	 *
	 * @param WC_Order $order         Order.
	 * @param string   $currency_code Currency code.
	 *
	 * @return array
	 */
	private function get_landed_cost_items( WC_Order $order, string $currency_code ): array {
		$items       = array();
		$line_number = 1;

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();
			$name    = $item->get_name();

			if ( ! $product instanceof WC_Product || '' === $name ) {
				continue;
			}

			$quantity = max( 1, (int) $item->get_quantity() );
			$total    = (float) $item->get_total();
			$subtotal = (float) $item->get_subtotal();
			$line_sum = $total > 0 ? $total : $subtotal;
			$unit     = $line_sum > 0 ? ( $line_sum / $quantity ) : (float) $product->get_price();

			if ( $unit <= 0 ) {
				$unit = 1.0;
			}

			$weight = (float) $product->get_weight();
			if ( $weight <= 0 ) {
				$weight = 0.1;
			}

			$item_payload = array(
				'number'                  => $line_number,
				'name'                    => $this->clean_value( $name ),
				'description'             => $this->clean_value( $name ),
				'manufacturerCountry'     => strtoupper( $this->shipping_method->get_origin_country() ),
				'partNumber'              => $this->clean_value( (string) $product->get_sku() ),
				'quantity'                => (float) $quantity,
				'quantityType'            => 'prt',
				'unitPrice'               => (float) wc_format_decimal( $unit, 5 ),
				'unitPriceCurrencyCode'   => $currency_code,
				'weight'                  => (float) wc_format_decimal( $this->shipping_method->get_converted_weight( $weight ), 3 ),
				'weightUnitOfMeasurement' => $this->get_measurement_system(),
			);

			$commodity_code = (string) $product->get_meta( '_wc_dhl_commodity_code', true );
			if ( '' !== $commodity_code ) {
				$item_payload['commodityCode'] = preg_replace( '/[^0-9]/', '', $commodity_code );
			}

			$items[] = $item_payload;
			++$line_number;
		}

		if ( ! empty( $items ) ) {
			return $items;
		}

		return array(
			array(
				'number'                  => 1,
				'name'                    => sprintf(
					/* translators: %s: order number. */
					__( 'Order %s item', 'woocommerce-shipping-dhl' ),
					$order->get_order_number()
				),
				'description'             => sprintf(
					/* translators: %s: order number. */
					__( 'Order %s', 'woocommerce-shipping-dhl' ),
					$order->get_order_number()
				),
				'manufacturerCountry'     => strtoupper( $this->shipping_method->get_origin_country() ),
				'quantity'                => 1,
				'quantityType'            => 'prt',
				'unitPrice'               => (float) wc_format_decimal( max( 1, (float) $order->get_total() ), 5 ),
				'unitPriceCurrencyCode'   => $currency_code,
				'weight'                  => 0.1,
				'weightUnitOfMeasurement' => $this->get_measurement_system(),
			),
		);
	}

	/**
	 * Check if the order is customs declarable.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return bool
	 */
	private function is_order_customs_declarable( WC_Order $order ): bool {
		$destination_country = $order->get_shipping_country();

		if ( '' === $destination_country ) {
			$destination_country = $order->get_billing_country();
		}

		return strtoupper( $this->shipping_method->get_origin_country() ) !== strtoupper( $destination_country );
	}

	/**
	 * Get order service code.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return string
	 */
	private function get_order_service_code( WC_Order $order ): string {
		foreach ( $order->get_items( 'shipping' ) as $shipping_item ) {
			$service_code = (string) $shipping_item->get_meta( 'service_code', true );
			if ( '' !== $service_code ) {
				return strtoupper( $service_code );
			}
		}

		return 'P';
	}

	/**
	 * Get measurement system for DHL request payload.
	 *
	 * @return string
	 */
	private function get_measurement_system(): string {
		return 'KG' === $this->shipping_method->get_weight_unit() ? 'metric' : 'imperial';
	}

	/**
	 * Build planned datetime in required format.
	 *
	 * @param string $time Time.
	 *
	 * @return string
	 */
	private function get_planned_datetime( string $time ): string {
		return gmdate( 'Y-m-d', strtotime( '+1 day' ) ) . 'T' . $time . ' GMT+00:00';
	}

	/**
	 * Get API base URL based on environment.
	 *
	 * @return string
	 */
	private function get_api_url(): string {
		$environment = $this->shipping_method->get_option( 'environment', 'test' );

		return self::$endpoints[ $environment ] ?? self::$endpoints['test'];
	}

	/**
	 * Get headers for API requests.
	 *
	 * @return array|WP_Error
	 */
	private function get_request_headers() {
		$access_token = $this->shipping_method->get_dhl_oauth()->get_access_token();

		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}

		return array(
			'Authorization' => 'Basic ' . $access_token,
			'Content-Type'  => 'application/json',
			'Accept'        => 'application/json',
			'x-version'     => WC_SHIPPING_DHL_API_VERSION,
		);
	}

	/**
	 * Make request to DHL API.
	 *
	 * @param string $method HTTP method.
	 * @param string $path Path.
	 * @param array  $body Body.
	 * @param array  $query Query.
	 *
	 * @return array|WP_Error
	 */
	private function request( string $method, string $path, array $body = array(), array $query = array() ) {
		$headers = $this->get_request_headers();

		if ( is_wp_error( $headers ) ) {
			return $headers;
		}

		$url = untrailingslashit( $this->get_api_url() ) . '/' . ltrim( $path, '/' );
		if ( ! empty( $query ) ) {
			$url = add_query_arg( $query, $url );
		}

		$args = array(
			'method'  => $method,
			'headers' => $headers,
			'timeout' => 30,
		);

		if ( ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code  = wp_remote_retrieve_response_code( $response );
		$raw_body     = wp_remote_retrieve_body( $response );
		$decoded_body = '' !== $raw_body ? json_decode( $raw_body, true ) : array();

		if ( $status_code < 200 || $status_code >= 300 ) {
			$error_message = $this->get_api_error_message( $decoded_body, $status_code );

			return new WP_Error(
				'wc_dhl_rest_request_error',
				$error_message,
				array(
					'status_code' => $status_code,
					'response'    => $decoded_body,
				)
			);
		}

		if ( '' === $raw_body ) {
			return array();
		}

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded_body ) ) {
			return new WP_Error( 'wc_dhl_rest_parse_error', __( 'Could not parse DHL API response.', 'woocommerce-shipping-dhl' ) );
		}

		return $decoded_body;
	}

	/**
	 * Build an API error message from response payload.
	 *
	 * @param mixed $response_body Response body.
	 * @param int   $status_code Status code.
	 *
	 * @return string
	 */
	private function get_api_error_message( $response_body, int $status_code ): string {
		if ( is_array( $response_body ) ) {
			if ( ! empty( $response_body['detail'] ) ) {
				return (string) $response_body['detail'];
			}

			if ( ! empty( $response_body['title'] ) ) {
				return (string) $response_body['title'];
			}

			if ( ! empty( $response_body['message'] ) ) {
				return (string) $response_body['message'];
			}
		}

		return sprintf(
			/* translators: %d: HTTP status code. */
			__( 'DHL API request failed with status %d.', 'woocommerce-shipping-dhl' ),
			$status_code
		);
	}

	/**
	 * Sanitize value to fit carrier constraints.
	 *
	 * @param string $value Raw value.
	 *
	 * @return string
	 */
	private function clean_value( string $value ): string {
		$value = $this->shipping_method->clean_string( $value );

		if ( '' === $value ) {
			return '-';
		}

		return $value;
	}
}
