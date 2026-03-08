<?php
/**
 * Order operations class file.
 *
 * @package WC_Shipping_DHL
 */

namespace WooCommerce\DHL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WC_Order;
use WC_Shipping_Method;
use WC_Shipping_Zones;
use WooCommerce\DHL\API\REST\Shipment_Client;
use WP_Error;

/**
 * Order_Operations class.
 */
class Order_Operations {

	/**
	 * Order meta keys.
	 */
	private const META_TRACKING_NUMBER     = '_wc_dhl_shipment_tracking_number';
	private const META_TRACKING_URL        = '_wc_dhl_tracking_url';
	private const META_LABEL_URL           = '_wc_dhl_label_url';
	private const META_LABEL_PATH          = '_wc_dhl_label_path';
	private const META_POD_URL             = '_wc_dhl_pod_url';
	private const META_POD_PATH            = '_wc_dhl_pod_path';
	private const META_DISPATCH_NUMBERS    = '_wc_dhl_dispatch_confirmation_numbers';
	private const META_TRACKING_STATUS     = '_wc_dhl_tracking_status';
	private const META_TRACKING_EVENT      = '_wc_dhl_tracking_last_event';
	private const META_TRACKING_UPDATED_AT = '_wc_dhl_tracking_updated_at';
	private const META_TRACKING_UPDATED_TS = '_wc_dhl_tracking_updated_ts';
	private const META_PIECE_NUMBERS       = '_wc_dhl_piece_tracking_numbers';
	private const META_SERVICE_POINTS      = '_wc_dhl_service_points';
	private const META_LANDED_COST         = '_wc_dhl_landed_cost';

	/**
	 * Tracking sync hook name.
	 */
	private const TRACKING_SYNC_HOOK = 'wc_dhl_tracking_sync_event';

	/**
	 * Tracking sync schedule key.
	 */
	private const TRACKING_SYNC_SCHEDULE = 'wc_dhl_every_fifteen_minutes';

	/**
	 * Admin-post action used to serve private carrier documents.
	 */
	private const DOCUMENT_DOWNLOAD_ACTION = 'wc_dhl_download_document';

	/**
	 * Private document storage directory within uploads.
	 */
	private const DOCUMENT_STORAGE_DIR = 'wc-dhl-private';

	/**
	 * Maximum number of orders to process per sync run.
	 */
	private const TRACKING_SYNC_BATCH_SIZE = 20;

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger = new Logger();

		add_filter( 'cron_schedules', array( $this, 'add_tracking_cron_schedule' ) );
		add_action( 'init', array( $this, 'maybe_schedule_tracking_sync' ) );
		add_action( self::TRACKING_SYNC_HOOK, array( $this, 'run_scheduled_tracking_sync' ) );
		add_action( 'admin_post_' . self::DOCUMENT_DOWNLOAD_ACTION, array( $this, 'download_document' ) );
		add_action( 'woocommerce_dhl_settings_saved', array( $this, 'maybe_schedule_tracking_sync' ) );

		if ( is_admin() ) {
			add_filter( 'woocommerce_order_actions', array( $this, 'add_order_actions' ) );
			add_action( 'woocommerce_order_action_wc_dhl_create_shipment_label', array( $this, 'create_shipment_label' ) );
			add_action( 'woocommerce_order_action_wc_dhl_book_pickup', array( $this, 'book_pickup' ) );
			add_action( 'woocommerce_order_action_wc_dhl_refresh_tracking', array( $this, 'refresh_tracking' ) );
			add_action( 'woocommerce_order_action_wc_dhl_fetch_proof_of_delivery', array( $this, 'fetch_proof_of_delivery' ) );
			add_action( 'woocommerce_order_action_wc_dhl_refresh_service_points', array( $this, 'refresh_service_points' ) );
			add_action( 'woocommerce_order_action_wc_dhl_estimate_landed_cost', array( $this, 'estimate_landed_cost' ) );
			add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'render_order_shipping_meta' ) );
		}
	}

	/**
	 * Add tracking cron schedule.
	 *
	 * @param array $schedules Existing schedules.
	 *
	 * @return array
	 */
	public function add_tracking_cron_schedule( $schedules ) {
		if ( isset( $schedules[ self::TRACKING_SYNC_SCHEDULE ] ) ) {
			return $schedules;
		}

		$schedules[ self::TRACKING_SYNC_SCHEDULE ] = array(
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every 15 minutes (DHL tracking sync)', 'woocommerce-shipping-dhl' ),
		);

		return $schedules;
	}

	/**
	 * Ensure tracking cron event is scheduled.
	 *
	 * @return void
	 */
	public function maybe_schedule_tracking_sync(): void {
		$next_scheduled = wp_next_scheduled( self::TRACKING_SYNC_HOOK );

		if ( ! $this->has_tracking_sync_enabled_instances() ) {
			if ( false !== $next_scheduled ) {
				wp_clear_scheduled_hook( self::TRACKING_SYNC_HOOK );
			}
			return;
		}

		if ( false !== $next_scheduled ) {
			return;
		}

		wp_schedule_event( time() + MINUTE_IN_SECONDS, self::TRACKING_SYNC_SCHEDULE, self::TRACKING_SYNC_HOOK );
	}

	/**
	 * Run scheduled tracking sync for DHL orders.
	 *
	 * @return void
	 */
	public function run_scheduled_tracking_sync(): void {
		$order_ids = $this->get_tracking_sync_candidate_order_ids();

		if ( ! is_array( $order_ids ) || empty( $order_ids ) ) {
			return;
		}

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			if ( ! $this->order_uses_dhl( $order ) ) {
				continue;
			}

			if ( ! $this->is_tracking_sync_enabled_for_order( $order ) ) {
				continue;
			}

			if ( $this->should_skip_scheduled_tracking_sync( $order ) ) {
				continue;
			}

			$this->refresh_tracking_for_order( $order, true );
		}
	}

	/**
	 * Get the next batch of orders eligible for tracking sync.
	 *
	 * @return array
	 */
	private function get_tracking_sync_candidate_order_ids(): array {
		$order_ids = wc_get_orders(
			array(
				'limit'   => -1,
				'return'  => 'ids',
				'status'  => $this->get_active_tracking_order_statuses(),
				'orderby' => 'date',
				'order'   => 'ASC',
			)
		);

		if ( ! is_array( $order_ids ) || empty( $order_ids ) ) {
			return array();
		}

		$orders = array();
		foreach ( array_map( 'absint', $order_ids ) as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			$tracking_number = (string) $order->get_meta( self::META_TRACKING_NUMBER, true );
			if ( '' === $tracking_number ) {
				continue;
			}

			$updated_ts = absint( $order->get_meta( self::META_TRACKING_UPDATED_TS, true ) );
			if ( $updated_ts <= 0 ) {
				$updated_at = (string) $order->get_meta( self::META_TRACKING_UPDATED_AT, true );
				$updated_ts = '' !== $updated_at ? (int) strtotime( $updated_at ) : 0;
			}

			$created_at = $order->get_date_created();
			$orders[]   = array(
				'id'         => $order_id,
				'updated_ts' => $updated_ts,
				'created_ts' => $created_at ? $created_at->getTimestamp() : 0,
			);
		}

		usort(
			$orders,
			static function ( array $left, array $right ) {
				if ( $left['updated_ts'] !== $right['updated_ts'] ) {
					if ( 0 === $left['updated_ts'] ) {
						return -1;
					}

					if ( 0 === $right['updated_ts'] ) {
						return 1;
					}

					return $left['updated_ts'] <=> $right['updated_ts'];
				}

				if ( $left['created_ts'] !== $right['created_ts'] ) {
					return $left['created_ts'] <=> $right['created_ts'];
				}

				return $left['id'] <=> $right['id'];
			}
		);

		return array_slice( array_column( $orders, 'id' ), 0, self::TRACKING_SYNC_BATCH_SIZE );
	}

	/**
	 * Get the order statuses eligible for scheduled tracking sync.
	 *
	 * @return array
	 */
	private function get_active_tracking_order_statuses(): array {
		return array_values(
			array_diff(
				array_keys( wc_get_order_statuses() ),
				array( 'wc-cancelled', 'wc-failed', 'wc-refunded' )
			)
		);
	}

	/**
	 * Check whether any DHL shipping instance has tracking sync enabled.
	 *
	 * @return bool
	 */
	private function has_tracking_sync_enabled_instances(): bool {
		foreach ( $this->get_dhl_shipping_method_instances() as $shipping_method ) {
			if ( $shipping_method instanceof WC_Shipping_DHL && $shipping_method->is_tracking_sync_enabled() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get all configured DHL shipping method instances across shipping zones.
	 *
	 * @return array
	 */
	private function get_dhl_shipping_method_instances(): array {
		if ( ! class_exists( WC_Shipping_Zones::class ) ) {
			return array();
		}

		$zone_ids = array( 0 );
		foreach ( WC_Shipping_Zones::get_zones() as $zone_key => $zone_data ) {
			$zone_id    = isset( $zone_data['zone_id'] ) ? absint( $zone_data['zone_id'] ) : absint( $zone_key );
			$zone_ids[] = $zone_id;
		}

		$shipping_methods = array();
		foreach ( array_values( array_unique( $zone_ids ) ) as $zone_id ) {
			$zone = new \WC_Shipping_Zone( $zone_id );

			foreach ( $zone->get_shipping_methods( true ) as $method ) {
				if ( $method instanceof WC_Shipping_DHL ) {
					$shipping_methods[] = $method;
				} elseif ( $method instanceof WC_Shipping_Method && 'dhl' === $method->id ) {
					$shipping_methods[] = new WC_Shipping_DHL( $method->instance_id );
				}
			}
		}

		return $shipping_methods;
	}

	/**
	 * Add custom order actions for DHL shipments.
	 *
	 * @param array $actions Existing actions.
	 *
	 * @return array
	 */
	public function add_order_actions( $actions ) {
		$order = $this->get_current_order_from_request();

		if ( ! $order instanceof WC_Order || ! $this->order_uses_dhl( $order ) ) {
			return $actions;
		}

		$shipping_method = $this->get_order_shipping_method_instance( $order );
		if ( ! $shipping_method instanceof WC_Shipping_DHL || $shipping_method->get_configuration_error() ) {
			return $actions;
		}

		$actions['wc_dhl_create_shipment_label']   = __( 'DHL: Create Shipment & Label', 'woocommerce-shipping-dhl' );
		$actions['wc_dhl_book_pickup']             = __( 'DHL: Book Pickup', 'woocommerce-shipping-dhl' );
		$actions['wc_dhl_refresh_tracking']        = __( 'DHL: Refresh Tracking', 'woocommerce-shipping-dhl' );
		$actions['wc_dhl_fetch_proof_of_delivery'] = __( 'DHL: Fetch Proof of Delivery', 'woocommerce-shipping-dhl' );

		if ( $shipping_method instanceof WC_Shipping_DHL && $shipping_method->is_service_point_lookup_enabled() ) {
			$actions['wc_dhl_refresh_service_points'] = __( 'DHL: Refresh Service Points', 'woocommerce-shipping-dhl' );
		}

		if ( $shipping_method instanceof WC_Shipping_DHL && $shipping_method->is_landed_cost_estimate_enabled() ) {
			$actions['wc_dhl_estimate_landed_cost'] = __( 'DHL: Estimate Landed Cost', 'woocommerce-shipping-dhl' );
		}

		return $actions;
	}

	/**
	 * Create shipment and label.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return void
	 */
	public function create_shipment_label( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$client = $this->get_shipment_client_for_order( $order );
		if ( is_wp_error( $client ) ) {
			$this->handle_operation_error( $order, $client );
			return;
		}

		$response = $client->create_shipment( $order );
		if ( is_wp_error( $response ) ) {
			$this->handle_operation_error( $order, $response );
			return;
		}

		$this->persist_shipment_response_meta( $order, $response );

		$label_path = $this->persist_label_document( $order, $response );
		if ( is_wp_error( $label_path ) ) {
			$this->handle_operation_error( $order, $label_path );
			return;
		}

		$tracking_number = (string) $order->get_meta( self::META_TRACKING_NUMBER, true );
		$order_note      = __( 'DHL shipment created successfully.', 'woocommerce-shipping-dhl' );

		if ( '' !== $tracking_number ) {
			$order_note .= ' ' . sprintf(
				/* translators: %s: tracking number. */
				__( 'Tracking number: %s.', 'woocommerce-shipping-dhl' ),
				$tracking_number
			);
		}

		$order_note .= ' ' . __( 'Shipment label saved securely to the order.', 'woocommerce-shipping-dhl' );

		$order->add_order_note( $order_note );
	}

	/**
	 * Book pickup action.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return void
	 */
	public function book_pickup( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$client = $this->get_shipment_client_for_order( $order );
		if ( is_wp_error( $client ) ) {
			$this->handle_operation_error( $order, $client );
			return;
		}

		$response = $client->create_pickup( $order );
		if ( is_wp_error( $response ) ) {
			$this->handle_operation_error( $order, $response );
			return;
		}

		$dispatch_numbers = array();
		if ( ! empty( $response['dispatchConfirmationNumbers'] ) && is_array( $response['dispatchConfirmationNumbers'] ) ) {
			$dispatch_numbers = array_map( 'strval', $response['dispatchConfirmationNumbers'] );
		} elseif ( ! empty( $response['dispatchConfirmationNumber'] ) ) {
			$dispatch_numbers = array( (string) $response['dispatchConfirmationNumber'] );
		}

		if ( ! empty( $dispatch_numbers ) ) {
			$order->update_meta_data( self::META_DISPATCH_NUMBERS, implode( ',', $dispatch_numbers ) );
			$order->save();
		}

		$pickup_note = __( 'DHL pickup booked successfully.', 'woocommerce-shipping-dhl' );

		if ( ! empty( $dispatch_numbers ) ) {
			$pickup_note .= ' ' . sprintf(
				/* translators: %s: dispatch confirmation numbers. */
				__( 'Dispatch confirmation number(s): %s', 'woocommerce-shipping-dhl' ),
				implode( ', ', $dispatch_numbers )
			);
		}

		$order->add_order_note( $pickup_note );
	}

	/**
	 * Refresh tracking details for an order.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return void
	 */
	public function refresh_tracking( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$this->refresh_tracking_for_order( $order, false );
	}

	/**
	 * Fetch proof-of-delivery action.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return void
	 */
	public function fetch_proof_of_delivery( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$tracking_number = (string) $order->get_meta( self::META_TRACKING_NUMBER, true );
		if ( '' === $tracking_number ) {
			$this->handle_operation_error(
				$order,
				new WP_Error( 'wc_dhl_tracking_not_available', __( 'No DHL tracking number is stored on this order yet.', 'woocommerce-shipping-dhl' ) )
			);
			return;
		}

		$client = $this->get_shipment_client_for_order( $order );
		if ( is_wp_error( $client ) ) {
			$this->handle_operation_error( $order, $client );
			return;
		}

		$response = $client->get_proof_of_delivery( $tracking_number );
		if ( is_wp_error( $response ) ) {
			$this->handle_operation_error( $order, $response );
			return;
		}

		$pod_path = $this->persist_pod_document( $order, $response );
		if ( is_wp_error( $pod_path ) ) {
			$this->handle_operation_error( $order, $pod_path );
			return;
		}

		$order->add_order_note(
			__( 'DHL proof of delivery retrieved and stored securely on the order.', 'woocommerce-shipping-dhl' )
		);
	}

	/**
	 * Refresh service points action.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return void
	 */
	public function refresh_service_points( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$client = $this->get_shipment_client_for_order( $order );
		if ( is_wp_error( $client ) ) {
			$this->handle_operation_error( $order, $client );
			return;
		}

		$response = $client->get_service_points( $order );
		if ( is_wp_error( $response ) ) {
			$this->handle_operation_error( $order, $response );
			return;
		}

		$service_points = $this->parse_service_points_response( $response );
		$order->update_meta_data( self::META_SERVICE_POINTS, $service_points );
		$order->save();

		if ( empty( $service_points ) ) {
			$order->add_order_note( __( 'No DHL service points were returned for this destination.', 'woocommerce-shipping-dhl' ) );
			return;
		}

		$order->add_order_note(
			sprintf(
				/* translators: 1: count of service points. 2: point summary. */
				__( 'DHL service points refreshed (%1$d found). %2$s', 'woocommerce-shipping-dhl' ),
				count( $service_points ),
				$this->format_service_points_preview( $service_points )
			)
		);
	}

	/**
	 * Estimate landed cost action.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return void
	 */
	public function estimate_landed_cost( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$client = $this->get_shipment_client_for_order( $order );
		if ( is_wp_error( $client ) ) {
			$this->handle_operation_error( $order, $client );
			return;
		}

		$response = $client->estimate_landed_cost( $order );
		if ( is_wp_error( $response ) ) {
			$this->handle_operation_error( $order, $response );
			return;
		}

		$summary = $this->build_landed_cost_summary( $response );
		$order->update_meta_data( self::META_LANDED_COST, $summary );
		$order->save();

		$currency = (string) $summary['currency'];
		$order->add_order_note(
			sprintf(
				/* translators: 1: total 2: duty 3: tax 4: fee */
				__( 'DHL landed cost estimate: total %1$s (duty %2$s, tax %3$s, fee %4$s).', 'woocommerce-shipping-dhl' ),
				$this->format_currency_amount( (float) $summary['total'], $currency ),
				$this->format_currency_amount( (float) $summary['duty'], $currency ),
				$this->format_currency_amount( (float) $summary['tax'], $currency ),
				$this->format_currency_amount( (float) $summary['fee'], $currency )
			)
		);

		if ( ! empty( $summary['warnings'] ) && is_array( $summary['warnings'] ) ) {
			$order->add_order_note( __( 'DHL landed cost warnings: ', 'woocommerce-shipping-dhl' ) . implode( ' | ', array_map( 'strval', $summary['warnings'] ) ) );
		}
	}

	/**
	 * Render DHL shipment details in order admin.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return void
	 */
	public function render_order_shipping_meta( $order ) {
		if ( ! $order instanceof WC_Order || ! $this->order_uses_dhl( $order ) ) {
			return;
		}

		$shipping_method = $this->get_order_shipping_method_instance( $order );

		$tracking_number = (string) $order->get_meta( self::META_TRACKING_NUMBER, true );
		$tracking_url    = (string) $order->get_meta( self::META_TRACKING_URL, true );
		$label_url       = $this->get_document_download_url( $order, 'label' );
		$pod_url         = $this->get_document_download_url( $order, 'pod' );
		$pickup_numbers  = (string) $order->get_meta( self::META_DISPATCH_NUMBERS, true );
		$tracking_status = (string) $order->get_meta( self::META_TRACKING_EVENT, true );
		$landed_cost     = $this->get_array_order_meta( $order, self::META_LANDED_COST );
		$service_points  = $this->get_array_order_meta( $order, self::META_SERVICE_POINTS );

		echo '<div class="wc-dhl-order-shipment">';
		echo '<p><strong>' . esc_html__( 'DHL Shipment', 'woocommerce-shipping-dhl' ) . '</strong></p>';

		if ( $shipping_method instanceof WC_Shipping_DHL ) {
			$config_error = $shipping_method->get_configuration_error();
			if ( $config_error ) {
				echo '<p><em>' . esc_html( $config_error->get_error_message() ) . '</em></p>';
			}
		}

		if ( '' !== $tracking_number ) {
			echo '<p>' . esc_html__( 'Tracking Number:', 'woocommerce-shipping-dhl' ) . ' ' . esc_html( $tracking_number ) . '</p>';
		}

		if ( '' !== $tracking_url ) {
			echo '<p><a href="' . esc_url( $tracking_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Track shipment', 'woocommerce-shipping-dhl' ) . '</a></p>';
		}

		if ( '' !== $label_url ) {
			echo '<p><a href="' . esc_url( $label_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Download label', 'woocommerce-shipping-dhl' ) . '</a></p>';
		}

		if ( '' !== $pod_url ) {
			echo '<p><a href="' . esc_url( $pod_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Download proof of delivery', 'woocommerce-shipping-dhl' ) . '</a></p>';
		}

		if ( '' !== $pickup_numbers ) {
			echo '<p>' . esc_html__( 'Pickup Confirmation:', 'woocommerce-shipping-dhl' ) . ' ' . esc_html( $pickup_numbers ) . '</p>';
		}

		if ( '' !== $tracking_status ) {
			echo '<p>' . esc_html__( 'Latest Tracking:', 'woocommerce-shipping-dhl' ) . ' ' . esc_html( $tracking_status ) . '</p>';
		}

		if ( ! empty( $landed_cost ) ) {
			$currency = isset( $landed_cost['currency'] ) ? (string) $landed_cost['currency'] : get_woocommerce_currency();
			echo '<p><strong>' . esc_html__( 'Landed Cost Estimate', 'woocommerce-shipping-dhl' ) . '</strong></p>';
			echo '<p>' . esc_html__( 'Total:', 'woocommerce-shipping-dhl' ) . ' ' . esc_html( $this->format_currency_amount( (float) ( $landed_cost['total'] ?? 0 ), $currency ) ) . '</p>';
			echo '<p>' . esc_html__( 'Duties:', 'woocommerce-shipping-dhl' ) . ' ' . esc_html( $this->format_currency_amount( (float) ( $landed_cost['duty'] ?? 0 ), $currency ) ) . '</p>';
			echo '<p>' . esc_html__( 'Taxes:', 'woocommerce-shipping-dhl' ) . ' ' . esc_html( $this->format_currency_amount( (float) ( $landed_cost['tax'] ?? 0 ), $currency ) ) . '</p>';
			echo '<p>' . esc_html__( 'Fees:', 'woocommerce-shipping-dhl' ) . ' ' . esc_html( $this->format_currency_amount( (float) ( $landed_cost['fee'] ?? 0 ), $currency ) ) . '</p>';
		}

		if ( ! empty( $service_points ) ) {
			echo '<p><strong>' . esc_html__( 'Nearby DHL Service Points', 'woocommerce-shipping-dhl' ) . '</strong></p>';
			echo '<ul>';

			foreach ( array_slice( $service_points, 0, 3 ) as $service_point ) {
				if ( ! is_array( $service_point ) ) {
					continue;
				}

				$label = (string) ( $service_point['name'] ?? '' );
				if ( ! empty( $service_point['distance'] ) ) {
					$label .= ' (' . (string) $service_point['distance'] . ')';
				}

				if ( ! empty( $service_point['address'] ) ) {
					$label .= ' - ' . (string) $service_point['address'];
				}

				echo '<li>' . esc_html( $label ) . '</li>';
			}

			echo '</ul>';
		}

		if ( '' === $tracking_number && '' === $label_url && '' === $pickup_numbers && '' === $tracking_status && '' === $pod_url && empty( $landed_cost ) && empty( $service_points ) ) {
			echo '<p>' . esc_html__( 'No DHL shipment data has been generated for this order yet.', 'woocommerce-shipping-dhl' ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Stream a private DHL carrier document to an authorized admin user.
	 *
	 * @return void
	 */
	public function download_document(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to download this document.', 'woocommerce-shipping-dhl' ) );
		}

		$order_id = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is checked below.
		$document = isset( $_GET['document'] ) ? sanitize_key( wp_unslash( $_GET['document'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is checked below.

		if ( $order_id <= 0 || '' === $document ) {
			wp_die( esc_html__( 'Invalid DHL document request.', 'woocommerce-shipping-dhl' ) );
		}

		check_admin_referer( 'wc_dhl_download_document_' . $order_id . '_' . $document );

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order || ! $this->order_uses_dhl( $order ) ) {
			wp_die( esc_html__( 'The requested order could not be loaded.', 'woocommerce-shipping-dhl' ) );
		}

		$meta_keys = $this->get_document_meta_keys( $document );
		if ( empty( $meta_keys['path'] ) ) {
			wp_die( esc_html__( 'The requested DHL document type is not supported.', 'woocommerce-shipping-dhl' ) );
		}

		$file_path = (string) $order->get_meta( $meta_keys['path'], true );
		if ( '' === $file_path || ! file_exists( $file_path ) || ! is_readable( $file_path ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists,WordPress.WP.AlternativeFunctions.file_system_operations_is_readable -- Streaming a local file after auth checks.
			wp_die( esc_html__( 'The requested DHL document is no longer available.', 'woocommerce-shipping-dhl' ) );
		}

		$file_name = sanitize_file_name( basename( $file_path ) );
		$file_type = wp_check_filetype( $file_name );
		$mime_type = ! empty( $file_type['type'] ) ? (string) $file_type['type'] : 'application/octet-stream';
		$file_size = filesize( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_filesize -- Streaming a local file after auth checks.

		nocache_headers();
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: ' . $mime_type );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'X-Robots-Tag: noindex, nofollow', true );

		if ( false !== $file_size ) {
			header( 'Content-Length: ' . (string) $file_size );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Streaming a local file after auth checks.
		readfile( $file_path );
		exit;
	}

	/**
	 * Refresh tracking data for an order.
	 *
	 * @param WC_Order $order        Order.
	 * @param bool     $is_scheduled Whether this call came from scheduled sync.
	 *
	 * @return bool
	 */
	private function refresh_tracking_for_order( WC_Order $order, bool $is_scheduled ): bool {
		$tracking_number = (string) $order->get_meta( self::META_TRACKING_NUMBER, true );
		if ( '' === $tracking_number ) {
			$this->handle_operation_error(
				$order,
				new WP_Error( 'wc_dhl_tracking_not_available', __( 'No DHL tracking number is stored on this order yet.', 'woocommerce-shipping-dhl' ) ),
				! $is_scheduled
			);

			return false;
		}

		$client = $this->get_shipment_client_for_order( $order );
		if ( is_wp_error( $client ) ) {
			$this->handle_operation_error( $order, $client, ! $is_scheduled );
			return false;
		}

		$response = $client->get_tracking( $tracking_number );
		if ( is_wp_error( $response ) ) {
			$this->handle_operation_error( $order, $response, ! $is_scheduled );
			return false;
		}

		$shipment = $response['shipments'][0] ?? array();
		$status   = isset( $shipment['status'] ) ? (string) $shipment['status'] : __( 'Unknown', 'woocommerce-shipping-dhl' );
		$event    = $this->get_latest_tracking_event( $shipment );
		$summary  = $status;

		if ( ! empty( $event ) ) {
			$summary .= ' - ' . $this->format_tracking_event_summary( $event );
		}

			$previous_summary = (string) $order->get_meta( self::META_TRACKING_EVENT, true );
			$current_ts       = time();

			$order->update_meta_data( self::META_TRACKING_STATUS, $status );
			$order->update_meta_data( self::META_TRACKING_EVENT, $summary );
			$order->update_meta_data( self::META_TRACKING_UPDATED_AT, gmdate( 'c' ) );
			$order->update_meta_data( self::META_TRACKING_UPDATED_TS, $current_ts );
			$order->save();

		if ( $summary === $previous_summary ) {
			return true;
		}

		$order->add_order_note(
			sprintf(
				/* translators: %s: tracking summary. */
				__( 'DHL tracking update: %s', 'woocommerce-shipping-dhl' ),
				$summary
			)
		);

		/**
		 * Fires when DHL tracking status changes for an order.
		 *
		 * @since 1.0.0
		 *
		 * @param WC_Order $order        WooCommerce order.
		 * @param string   $summary      Human-friendly tracking summary.
		 * @param array    $shipment     DHL shipment payload.
		 * @param bool     $is_scheduled Whether sync was scheduled.
		 */
		do_action( 'woocommerce_dhl_tracking_status_changed', $order, $summary, $shipment, $is_scheduled );

		if ( $is_scheduled && $this->is_tracking_customer_notifications_enabled_for_order( $order ) ) {
			$customer_note = sprintf(
				/* translators: %s: tracking summary */
				__( 'DHL shipping update: %s', 'woocommerce-shipping-dhl' ),
				$summary
			);

			$tracking_url = (string) $order->get_meta( self::META_TRACKING_URL, true );
			if ( '' !== $tracking_url ) {
				$customer_note .= ' ' . sprintf(
					/* translators: %s: tracking url */
					__( 'Track: %s', 'woocommerce-shipping-dhl' ),
					$tracking_url
				);
			}

			$order->add_order_note( $customer_note, true );

			/**
			 * Fires after a customer-facing tracking notification note is added.
			 *
			 * @since 1.0.0
			 *
			 * @param WC_Order $order    WooCommerce order.
			 * @param string   $summary  Human-friendly tracking summary.
			 * @param array    $shipment DHL shipment payload.
			 */
			do_action( 'woocommerce_dhl_tracking_customer_notification_sent', $order, $summary, $shipment );
		}

		return true;
	}

	/**
	 * Save shipment response into order meta.
	 *
	 * @param WC_Order $order    Order.
	 * @param array    $response Shipment response.
	 *
	 * @return void
	 */
	private function persist_shipment_response_meta( WC_Order $order, array $response ): void {
		$tracking_number = isset( $response['shipmentTrackingNumber'] ) ? (string) $response['shipmentTrackingNumber'] : '';
		$tracking_url    = isset( $response['trackingUrl'] ) ? (string) $response['trackingUrl'] : '';

		if ( '' !== $tracking_number ) {
			$order->update_meta_data( self::META_TRACKING_NUMBER, $tracking_number );
		}

		if ( '' !== $tracking_url ) {
			$order->update_meta_data( self::META_TRACKING_URL, $tracking_url );
		}

		if ( ! empty( $response['dispatchConfirmationNumber'] ) ) {
			$order->update_meta_data( self::META_DISPATCH_NUMBERS, (string) $response['dispatchConfirmationNumber'] );
		}

		$piece_numbers = $this->get_piece_tracking_numbers( $response );
		if ( ! empty( $piece_numbers ) ) {
			$order->update_meta_data( self::META_PIECE_NUMBERS, implode( ',', $piece_numbers ) );
		}

		$order->save();
	}

	/**
	 * Persist label document from shipment response.
	 *
	 * @param WC_Order $order    Order.
	 * @param array    $response Shipment response.
	 *
	 * @return string|WP_Error
	 */
	private function persist_label_document( WC_Order $order, array $response ) {
		$documents = isset( $response['documents'] ) && is_array( $response['documents'] ) ? $response['documents'] : array();

		if ( empty( $documents ) ) {
			return new WP_Error( 'wc_dhl_label_missing', __( 'DHL did not return any shipment documents.', 'woocommerce-shipping-dhl' ) );
		}

		$selected_document = null;
		foreach ( $documents as $document ) {
			$type_code = isset( $document['typeCode'] ) ? (string) $document['typeCode'] : '';
			if ( in_array( $type_code, array( 'label', 'waybillDoc' ), true ) ) {
				$selected_document = $document;
				break;
			}
		}

		if ( ! is_array( $selected_document ) ) {
			return new WP_Error( 'wc_dhl_label_type_missing', __( 'DHL did not return a label document.', 'woocommerce-shipping-dhl' ) );
		}

		$base64_content = isset( $selected_document['content'] ) ? (string) $selected_document['content'] : '';
		$image_format   = isset( $selected_document['imageFormat'] ) ? strtoupper( (string) $selected_document['imageFormat'] ) : 'PDF';

		if ( '' === $base64_content ) {
			return new WP_Error( 'wc_dhl_label_content_missing', __( 'DHL returned an empty label document.', 'woocommerce-shipping-dhl' ) );
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- DHL label documents are returned as base64 payloads.
		$binary_content = base64_decode( $base64_content, true );
		if ( false === $binary_content ) {
			return new WP_Error( 'wc_dhl_label_decode_failed', __( 'Could not decode DHL label document.', 'woocommerce-shipping-dhl' ) );
		}

		$extension_by_format = array(
			'PDF'  => 'pdf',
			'PNG'  => 'png',
			'JPG'  => 'jpg',
			'JPEG' => 'jpg',
			'TIFF' => 'tiff',
			'ZPL'  => 'zpl',
		);
		$file_extension      = $extension_by_format[ $image_format ] ?? 'pdf';

		return $this->persist_private_document(
			$order,
			$binary_content,
			$file_extension,
			self::META_LABEL_PATH,
			self::META_LABEL_URL,
			'label'
		);
	}

	/**
	 * Persist proof-of-delivery document from DHL response.
	 *
	 * @param WC_Order $order    Order.
	 * @param array    $response POD response.
	 *
	 * @return string|WP_Error
	 */
	private function persist_pod_document( WC_Order $order, array $response ) {
		$documents = isset( $response['documents'] ) && is_array( $response['documents'] ) ? $response['documents'] : array();
		if ( empty( $documents ) ) {
			return new WP_Error( 'wc_dhl_pod_missing', __( 'DHL did not return a proof-of-delivery document.', 'woocommerce-shipping-dhl' ) );
		}

		$selected_document = null;
		foreach ( $documents as $document ) {
			if ( ! empty( $document['content'] ) ) {
				$selected_document = $document;
				break;
			}
		}

		if ( ! is_array( $selected_document ) ) {
			return new WP_Error( 'wc_dhl_pod_content_missing', __( 'DHL returned an empty proof-of-delivery document.', 'woocommerce-shipping-dhl' ) );
		}

		$base64_content = isset( $selected_document['content'] ) ? (string) $selected_document['content'] : '';
		$encoding       = isset( $selected_document['encodingFormat'] ) ? strtoupper( (string) $selected_document['encodingFormat'] ) : 'PDF';

		if ( '' === $base64_content ) {
			return new WP_Error( 'wc_dhl_pod_content_missing', __( 'DHL returned an empty proof-of-delivery document.', 'woocommerce-shipping-dhl' ) );
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- DHL POD documents are returned as base64 payloads.
		$binary_content = base64_decode( $base64_content, true );
		if ( false === $binary_content ) {
			return new WP_Error( 'wc_dhl_pod_decode_failed', __( 'Could not decode DHL proof-of-delivery document.', 'woocommerce-shipping-dhl' ) );
		}

		$extension_by_format = array(
			'PDF'  => 'pdf',
			'PNG'  => 'png',
			'JPG'  => 'jpg',
			'JPEG' => 'jpg',
			'TIFF' => 'tiff',
		);

		$file_extension = $extension_by_format[ $encoding ] ?? 'pdf';

		return $this->persist_private_document(
			$order,
			$binary_content,
			$file_extension,
			self::META_POD_PATH,
			self::META_POD_URL,
			'pod'
		);
	}

	/**
	 * Get shipment client for an order.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return Shipment_Client|WP_Error
	 */
	private function get_shipment_client_for_order( WC_Order $order ) {
		$shipping_method = $this->get_order_shipping_method_instance( $order );
		if ( ! $shipping_method instanceof WC_Shipping_DHL ) {
			return new WP_Error( 'wc_dhl_shipping_method_missing', __( 'Could not load DHL shipping settings for this order.', 'woocommerce-shipping-dhl' ) );
		}

		$config_error = $shipping_method->get_configuration_error();
		if ( $config_error instanceof WP_Error ) {
			return $config_error;
		}

		return new Shipment_Client( $shipping_method );
	}

	/**
	 * Handle an operation error for an order action.
	 *
	 * @param WC_Order $order          Order.
	 * @param WP_Error $error          Error.
	 * @param bool     $add_order_note Add admin order note.
	 *
	 * @return void
	 */
	private function handle_operation_error( WC_Order $order, WP_Error $error, bool $add_order_note = true ): void {
		$message = $error->get_error_message();

		if ( $add_order_note ) {
			$order->add_order_note(
				sprintf(
					/* translators: %s: error message. */
					__( 'DHL operation failed: %s', 'woocommerce-shipping-dhl' ),
					$message
				)
			);
		}

		$this->logger->error( 'DHL order operation failed for order #' . $order->get_id() . ': ' . $message );
	}

	/**
	 * Check if the order uses DHL shipping.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return bool
	 */
	private function order_uses_dhl( WC_Order $order ): bool {
		foreach ( $order->get_items( 'shipping' ) as $shipping_item ) {
			if ( 'dhl' === $shipping_item->get_method_id() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Resolve DHL shipping method instance for an order.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return WC_Shipping_DHL|null
	 */
	private function get_order_shipping_method_instance( WC_Order $order ): ?WC_Shipping_DHL {
		foreach ( $order->get_items( 'shipping' ) as $shipping_item ) {
			if ( 'dhl' !== $shipping_item->get_method_id() ) {
				continue;
			}

			$instance_id = method_exists( $shipping_item, 'get_instance_id' ) ? absint( $shipping_item->get_instance_id() ) : 0;
			if ( $instance_id > 0 && class_exists( WC_Shipping_Zones::class ) ) {
				$method = WC_Shipping_Zones::get_shipping_method( $instance_id );
				if ( $method instanceof WC_Shipping_DHL ) {
					return $method;
				}

				if ( $method instanceof WC_Shipping_Method && 'dhl' === $method->id ) {
					return new WC_Shipping_DHL( $instance_id );
				}
			}
		}

		return null;
	}

	/**
	 * Check if tracking sync is enabled for order's DHL method.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return bool
	 */
	private function is_tracking_sync_enabled_for_order( WC_Order $order ): bool {
		$shipping_method = $this->get_order_shipping_method_instance( $order );
		return $shipping_method instanceof WC_Shipping_DHL && $shipping_method->is_tracking_sync_enabled();
	}

	/**
	 * Check if customer tracking notifications are enabled for order's DHL method.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return bool
	 */
	private function is_tracking_customer_notifications_enabled_for_order( WC_Order $order ): bool {
		$shipping_method = $this->get_order_shipping_method_instance( $order );
		return $shipping_method instanceof WC_Shipping_DHL && $shipping_method->is_tracking_customer_notifications_enabled();
	}

	/**
	 * Determine whether an order should be skipped by scheduled sync.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return bool
	 */
	private function should_skip_scheduled_tracking_sync( WC_Order $order ): bool {
		$tracking_number = (string) $order->get_meta( self::META_TRACKING_NUMBER, true );
		if ( '' === $tracking_number ) {
			return true;
		}

		$updated_ts = absint( $order->get_meta( self::META_TRACKING_UPDATED_TS, true ) );
		if ( $updated_ts <= 0 ) {
			$updated_at = (string) $order->get_meta( self::META_TRACKING_UPDATED_AT, true );
			$updated_ts = '' !== $updated_at ? (int) strtotime( $updated_at ) : 0;
		}

		if ( $updated_ts > 0 && ( time() - $updated_ts ) < ( 10 * MINUTE_IN_SECONDS ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get order instance from current request.
	 *
	 * @return WC_Order|null
	 */
	private function get_current_order_from_request(): ?WC_Order {
		$order_id = 0;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only usage to scope available order actions.
		if ( isset( $_GET['id'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only usage to scope available order actions.
			$order_id = absint( wp_unslash( $_GET['id'] ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only usage to scope available order actions.
		if ( $order_id <= 0 && isset( $_GET['post'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only usage to scope available order actions.
			$order_id = absint( wp_unslash( $_GET['post'] ) );
		}

		if ( $order_id <= 0 ) {
			return null;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return null;
		}

		return $order;
	}

	/**
	 * Get piece tracking numbers from create shipment response.
	 *
	 * @param array $response Shipment response.
	 *
	 * @return array
	 */
	private function get_piece_tracking_numbers( array $response ): array {
		$piece_numbers = array();

		if ( empty( $response['packages'] ) || ! is_array( $response['packages'] ) ) {
			return $piece_numbers;
		}

		foreach ( $response['packages'] as $package ) {
			if ( ! empty( $package['trackingNumber'] ) ) {
				$piece_numbers[] = (string) $package['trackingNumber'];
			}
		}

		return array_values( array_unique( $piece_numbers ) );
	}

	/**
	 * Get latest tracking event from a shipment payload.
	 *
	 * @param array $shipment Shipment details.
	 *
	 * @return array
	 */
	private function get_latest_tracking_event( array $shipment ): array {
		if ( empty( $shipment['events'] ) || ! is_array( $shipment['events'] ) ) {
			return array();
		}

		$events = $shipment['events'];
		usort(
			$events,
			static function ( $left, $right ) {
				$left_value  = (string) ( ( $left['date'] ?? '' ) . ' ' . ( $left['time'] ?? '' ) );
				$right_value = (string) ( ( $right['date'] ?? '' ) . ' ' . ( $right['time'] ?? '' ) );

				return strcmp( $right_value, $left_value );
			}
		);

		return is_array( $events[0] ) ? $events[0] : array();
	}

	/**
	 * Format tracking event summary.
	 *
	 * @param array $event Event payload.
	 *
	 * @return string
	 */
	private function format_tracking_event_summary( array $event ): string {
		$parts = array();

		if ( ! empty( $event['description'] ) ) {
			$parts[] = (string) $event['description'];
		}

		if ( ! empty( $event['date'] ) ) {
			$parts[] = (string) $event['date'];
		}

		if ( ! empty( $event['time'] ) ) {
			$parts[] = (string) $event['time'];
		}

		return implode( ' | ', $parts );
	}

	/**
	 * Parse service points response.
	 *
	 * @param array $response Service points response payload.
	 *
	 * @return array
	 */
	private function parse_service_points_response( array $response ): array {
		$service_points = array();
		$raw_points     = isset( $response['servicePoints'] ) && is_array( $response['servicePoints'] ) ? $response['servicePoints'] : array();

		foreach ( $raw_points as $point ) {
			if ( ! is_array( $point ) ) {
				continue;
			}

			$name = isset( $point['servicePointNameFormatted'] ) ? (string) $point['servicePointNameFormatted'] : '';
			if ( '' === $name && ! empty( $point['servicePointName'] ) ) {
				$name = (string) $point['servicePointName'];
			}

			$identifier = isset( $point['facilityId'] ) ? (string) $point['facilityId'] : '';
			if ( '' === $identifier && isset( $point['contactDetails']['servicePointId'] ) ) {
				$identifier = (string) $point['contactDetails']['servicePointId'];
			}

			$address_parts = array();
			if ( ! empty( $point['address'] ) && is_array( $point['address'] ) ) {
				$address_parts = array_filter(
					array(
						$point['address']['addressLine1'] ?? '',
						$point['address']['city'] ?? '',
						$point['address']['zipCode'] ?? '',
						$point['address']['country'] ?? '',
					)
				);
			}

			$service_points[] = array(
				'id'       => $identifier,
				'name'     => $name,
				'distance' => isset( $point['distance'] ) ? (string) $point['distance'] : '',
				'address'  => implode( ', ', $address_parts ),
			);
		}

		return array_slice( $service_points, 0, 10 );
	}

	/**
	 * Build compact service point note preview.
	 *
	 * @param array $service_points Service points list.
	 *
	 * @return string
	 */
	private function format_service_points_preview( array $service_points ): string {
		$preview = array();

		foreach ( array_slice( $service_points, 0, 3 ) as $service_point ) {
			if ( ! is_array( $service_point ) || empty( $service_point['name'] ) ) {
				continue;
			}

			$label = (string) $service_point['name'];
			if ( ! empty( $service_point['distance'] ) ) {
				$label .= ' (' . (string) $service_point['distance'] . ')';
			}

			$preview[] = $label;
		}

		return implode( ' | ', $preview );
	}

	/**
	 * Get order meta value as array.
	 *
	 * @param WC_Order $order    Order.
	 * @param string   $meta_key Meta key.
	 *
	 * @return array
	 */
	private function get_array_order_meta( WC_Order $order, string $meta_key ): array {
		$meta_value = $order->get_meta( $meta_key, true );
		if ( is_array( $meta_value ) ) {
			return $meta_value;
		}

		if ( is_string( $meta_value ) && '' !== $meta_value ) {
			$decoded = json_decode( $meta_value, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		return array();
	}

	/**
	 * Build landed-cost summary from DHL response payload.
	 *
	 * @param array $response Landed-cost response.
	 *
	 * @return array
	 */
	private function build_landed_cost_summary( array $response ): array {
		$summary = array(
			'currency' => get_woocommerce_currency(),
			'total'    => 0.0,
			'duty'     => 0.0,
			'tax'      => 0.0,
			'fee'      => 0.0,
			'warnings' => array(),
		);

		$product = isset( $response['products'][0] ) && is_array( $response['products'][0] ) ? $response['products'][0] : array();
		if ( ! empty( $product['totalPrice'] ) && is_array( $product['totalPrice'] ) && isset( $product['totalPrice'][0] ) && is_array( $product['totalPrice'][0] ) ) {
			$total_price = $product['totalPrice'][0];

			if ( ! empty( $total_price['priceCurrency'] ) ) {
				$summary['currency'] = (string) $total_price['priceCurrency'];
			}

			if ( isset( $total_price['price'] ) ) {
				$summary['total'] = (float) $total_price['price'];
			}
		}

		$breakdown = array();
		if ( ! empty( $product['detailedPriceBreakdown'] ) && is_array( $product['detailedPriceBreakdown'] ) ) {
			foreach ( $product['detailedPriceBreakdown'] as $detailed_price ) {
				if ( ! empty( $detailed_price['breakdown'] ) && is_array( $detailed_price['breakdown'] ) ) {
					$breakdown = $detailed_price['breakdown'];
					break;
				}
			}
		}

		$summary['duty'] = $this->extract_landed_cost_amount_by_type( $breakdown, 'DUTY' );
		$summary['tax']  = $this->extract_landed_cost_amount_by_type( $breakdown, 'TAX' );
		$summary['fee']  = $this->extract_landed_cost_amount_by_type( $breakdown, 'FEE' );

		if ( ! empty( $response['warnings'] ) && is_array( $response['warnings'] ) ) {
			$summary['warnings'] = array_map( 'strval', $response['warnings'] );
		}

		return $summary;
	}

	/**
	 * Sum landed-cost breakdown amount by type.
	 *
	 * @param array  $breakdown Breakdown rows.
	 * @param string $type_code Type code to sum.
	 *
	 * @return float
	 */
	private function extract_landed_cost_amount_by_type( array $breakdown, string $type_code ): float {
		$total = 0.0;

		foreach ( $breakdown as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$current_type = isset( $entry['typeCode'] ) ? strtoupper( (string) $entry['typeCode'] ) : '';
			if ( $current_type !== $type_code ) {
				continue;
			}

			if ( isset( $entry['price'] ) ) {
				$total += (float) $entry['price'];
			}
		}

		return $total;
	}

	/**
	 * Persist a private DHL carrier document and remove any legacy public URL.
	 *
	 * @param WC_Order $order        Order.
	 * @param string   $binary       Raw binary payload.
	 * @param string   $extension    File extension.
	 * @param string   $path_meta_key Order meta key for the file path.
	 * @param string   $url_meta_key Legacy order meta key for public URLs.
	 * @param string   $prefix       File name prefix.
	 *
	 * @return string|WP_Error
	 */
	private function persist_private_document( WC_Order $order, string $binary, string $extension, string $path_meta_key, string $url_meta_key, string $prefix ) {
		$target_dir = $this->get_private_document_directory();
		if ( is_wp_error( $target_dir ) ) {
			return $target_dir;
		}

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			return new WP_Error( 'wc_dhl_filesystem_not_ready', __( 'The WordPress filesystem API is not available.', 'woocommerce-shipping-dhl' ) );
		}

		$file_name = sprintf(
			'%s-%s.%s',
			sanitize_file_name( $prefix ),
			wp_generate_uuid4(),
			ltrim( strtolower( $extension ), '.' )
		);
		$file_path = trailingslashit( $target_dir ) . $file_name;

		if ( ! $wp_filesystem->put_contents( $file_path, $binary, FS_CHMOD_FILE ) ) {
			return new WP_Error( 'wc_dhl_document_write_failed', __( 'Could not save the DHL document.', 'woocommerce-shipping-dhl' ) );
		}

		$order->update_meta_data( $path_meta_key, $file_path );
		$order->delete_meta_data( $url_meta_key );
		$order->save();

		return $file_path;
	}

	/**
	 * Get the private document storage directory and drop best-effort guard files into it.
	 *
	 * @return string|WP_Error
	 */
	private function get_private_document_directory() {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return new WP_Error( 'wc_dhl_upload_dir_error', (string) $uploads['error'] );
		}

		$target_dir = trailingslashit( $uploads['basedir'] ) . self::DOCUMENT_STORAGE_DIR;
		if ( ! wp_mkdir_p( $target_dir ) ) {
			return new WP_Error( 'wc_dhl_document_directory_failed', __( 'Could not create the DHL document directory.', 'woocommerce-shipping-dhl' ) );
		}

		$this->maybe_write_private_directory_guard_files( $target_dir );

		return $target_dir;
	}

	/**
	 * Best-effort guard files for the private document directory.
	 *
	 * @param string $target_dir Directory path.
	 *
	 * @return void
	 */
	private function maybe_write_private_directory_guard_files( string $target_dir ): void {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			return;
		}

		$guard_files = array(
			'index.html' => '',
			'.htaccess'  => "Options -Indexes\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n",
		);

		foreach ( $guard_files as $file_name => $contents ) {
			$file_path = trailingslashit( $target_dir ) . $file_name;
			if ( ! $wp_filesystem->exists( $file_path ) ) {
				$wp_filesystem->put_contents( $file_path, $contents, FS_CHMOD_FILE );
			}
		}
	}

	/**
	 * Build an authenticated admin download URL for a private carrier document.
	 *
	 * @param WC_Order $order         Order.
	 * @param string   $document_type Document type slug.
	 *
	 * @return string
	 */
	private function get_document_download_url( WC_Order $order, string $document_type ): string {
		$meta_keys = $this->get_document_meta_keys( $document_type );
		if ( empty( $meta_keys['path'] ) ) {
			return '';
		}

		$file_path = (string) $order->get_meta( $meta_keys['path'], true );
		if ( '' === $file_path ) {
			return '';
		}

		$download_url = add_query_arg(
			array(
				'action'   => self::DOCUMENT_DOWNLOAD_ACTION,
				'order_id' => $order->get_id(),
				'document' => $document_type,
			),
			admin_url( 'admin-post.php' )
		);

		return wp_nonce_url( $download_url, 'wc_dhl_download_document_' . $order->get_id() . '_' . $document_type );
	}

	/**
	 * Map document type slugs to stored order meta keys.
	 *
	 * @param string $document_type Document type slug.
	 *
	 * @return array
	 */
	private function get_document_meta_keys( string $document_type ): array {
		switch ( $document_type ) {
			case 'label':
				return array(
					'path' => self::META_LABEL_PATH,
					'url'  => self::META_LABEL_URL,
				);

			case 'pod':
				return array(
					'path' => self::META_POD_PATH,
					'url'  => self::META_POD_URL,
				);
		}

		return array();
	}

	/**
	 * Format currency amount for notes/admin display.
	 *
	 * @param float  $amount   Amount.
	 * @param string $currency Currency.
	 *
	 * @return string
	 */
	private function format_currency_amount( float $amount, string $currency ): string {
		if ( function_exists( 'wc_price' ) ) {
			return wp_strip_all_tags( wc_price( $amount, array( 'currency' => $currency ) ) );
		}

		return sprintf( '%s %s', $currency, wc_format_decimal( $amount, 2 ) );
	}
}
