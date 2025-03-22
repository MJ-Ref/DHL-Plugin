<?php
/**
 * WC_Shipping_DHL_Admin class file.
 *
 * @package WC_Shipping_DHL
 */

namespace WooCommerce\DHL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Shipping_DHL_Admin class.
 */
class WC_Shipping_DHL_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Admin hooks.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_init', array( $this, 'maybe_redirect_to_settings' ) );
		add_filter( 'woocommerce_get_sections_shipping', array( $this, 'add_shipping_sections' ) );
		add_action( 'woocommerce_shipping_settings_after_dhl_settings', array( $this, 'shipping_settings_after_dhl' ) );

		// AJAX hooks.
		add_action( 'wp_ajax_wc_shipping_dhl_validate_credentials', array( $this, 'ajax_validate_credentials' ) );
	}

	/**
	 * Add the shipping sections.
	 *
	 * @param array $sections Shipping sections.
	 * @return array
	 */
	public function add_shipping_sections( $sections ) {
		$sections['dhl_settings'] = __( 'DHL Express', 'woocommerce-shipping-dhl' );
		return $sections;
	}

	/**
	 * Admin scripts.
	 */
	public function admin_scripts() {
		$screen = get_current_screen();

		// Only on shipping settings page.
		if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id ) {
			return;
		}

		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
		
		if ( 'dhl' !== $section && 'dhl_settings' !== $section ) {
			return;
		}

		wp_enqueue_style( 'wc-dhl-admin-styles', WC_SHIPPING_DHL_PLUGIN_URL . '/assets/css/dhl-admin.css', array(), WC_SHIPPING_DHL_VERSION );
		wp_enqueue_script( 'wc-dhl-admin-script', WC_SHIPPING_DHL_PLUGIN_URL . '/assets/js/dhl-admin.js', array( 'jquery' ), WC_SHIPPING_DHL_VERSION, true );

		// Add script data.
		wp_localize_script( 'wc-dhl-admin-script', 'dhl_admin_params', array(
			'ajax_url'       => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'wc-shipping-dhl-admin' ),
			'i18n_testing'   => __( 'Testing credentials...', 'woocommerce-shipping-dhl' ),
			'i18n_success'   => __( 'Credentials validated successfully!', 'woocommerce-shipping-dhl' ),
			'i18n_error'     => __( 'Credentials validation failed!', 'woocommerce-shipping-dhl' ),
			'i18n_connecting' => __( 'Connecting to DHL...', 'woocommerce-shipping-dhl' ),
		) );
	}

	/**
	 * Show admin notices.
	 */
	public function admin_notices() {
		Notifier::output_notices();
	}

	/**
	 * Maybe redirect to the settings page.
	 */
	public function maybe_redirect_to_settings() {
		// Redirect only once after plugin activation.
		if ( get_transient( 'wc_shipping_dhl_activation_redirect' ) ) {
			delete_transient( 'wc_shipping_dhl_activation_redirect' );
			
			// Only redirect if the WC_Shipping_DHL class exists.
			if ( class_exists( 'WC_Shipping_DHL' ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=dhl_settings' ) );
				exit;
			}
		}
	}

	/**
	 * Show content after DHL settings.
	 */
	public function shipping_settings_after_dhl() {
		echo '<div class="dhl-support-info">';
		echo '<h3>' . esc_html__( 'Support', 'woocommerce-shipping-dhl' ) . '</h3>';
		echo '<p>' . esc_html__( 'For support, feature requests, or bug reporting, please contact:', 'woocommerce-shipping-dhl' ) . '</p>';
		echo '<ul>';
		echo '<li><a href="https://woocommerce.com/my-account/create-a-ticket/" target="_blank">' . esc_html__( 'WooCommerce Help Center', 'woocommerce-shipping-dhl' ) . '</a></li>';
		echo '<li><a href="mailto:help@woocommerce.com">' . esc_html__( 'help@woocommerce.com', 'woocommerce-shipping-dhl' ) . '</a></li>';
		echo '</ul>';
		echo '</div>';
	}

	/**
	 * AJAX validate API credentials.
	 */
	public function ajax_validate_credentials() {
		check_ajax_referer( 'wc-shipping-dhl-admin', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'woocommerce-shipping-dhl' ) ) );
		}

		// Get the API credentials from the request.
		$api_user = isset( $_POST['api_user'] ) ? sanitize_text_field( wp_unslash( $_POST['api_user'] ) ) : '';
		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
		$shipper_number = isset( $_POST['shipper_number'] ) ? sanitize_text_field( wp_unslash( $_POST['shipper_number'] ) ) : '';
		$environment = isset( $_POST['environment'] ) ? sanitize_text_field( wp_unslash( $_POST['environment'] ) ) : 'test';

		// Create a temporary shipping method instance.
		$shipping_method = new WC_Shipping_DHL();
		$shipping_method->init_settings();
		$shipping_method->settings['api_user'] = $api_user;
		$shipping_method->settings['api_key'] = $api_key;
		$shipping_method->settings['shipper_number'] = $shipper_number;
		$shipping_method->settings['environment'] = $environment;

		// Create an API client.
		$api_client = new API\REST\API_Client( $shipping_method );

		// Validate the credentials.
		$is_valid = $api_client->validate_credentials();

		if ( $is_valid ) {
			wp_send_json_success( array( 'message' => __( 'Credentials validated successfully!', 'woocommerce-shipping-dhl' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Credentials validation failed!', 'woocommerce-shipping-dhl' ) ) );
		}
	}
} 