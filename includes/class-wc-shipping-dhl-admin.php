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
		add_filter( 'plugin_action_links_' . plugin_basename( WC_SHIPPING_DHL_PLUGIN_DIR . '/woocommerce-shipping-dhl.php' ), array( $this, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

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

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Used only to scope assets on the settings screen.
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		if ( 'shipping' !== $tab ) {
			return;
		}

		wp_enqueue_style( 'wc-dhl-admin-styles', WC_SHIPPING_DHL_PLUGIN_URL . '/assets/css/dhl-admin.css', array(), WC_SHIPPING_DHL_VERSION );
		wp_enqueue_script( 'wc-dhl-admin-script', WC_SHIPPING_DHL_PLUGIN_URL . '/assets/js/dhl-admin.js', array( 'jquery' ), WC_SHIPPING_DHL_VERSION, true );

		// Add script data.
		wp_localize_script(
			'wc-dhl-admin-script',
			'dhl_admin_params',
			array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'wc-shipping-dhl-admin' ),
				'i18n_testing'    => __( 'Testing credentials...', 'woocommerce-shipping-dhl' ),
				'i18n_success'    => __( 'Credentials validated successfully!', 'woocommerce-shipping-dhl' ),
				'i18n_error'      => __( 'Credentials validation failed!', 'woocommerce-shipping-dhl' ),
				'i18n_connecting' => __( 'Connecting to DHL...', 'woocommerce-shipping-dhl' ),
			)
		);
	}

	/**
	 * Show admin notices.
	 */
	public function admin_notices() {
		Notifier::output_notices();
		$this->maybe_render_configuration_notice();
	}

	/**
	 * Maybe redirect to the settings page.
	 */
	public function maybe_redirect_to_settings() {
		// Redirect only once after plugin activation.
		if ( get_transient( 'wc_shipping_dhl_activation_redirect' ) ) {
			delete_transient( 'wc_shipping_dhl_activation_redirect' );

			// Only redirect if the shipping method class is loaded.
			if ( class_exists( '\WooCommerce\DHL\WC_Shipping_DHL' ) ) {
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
		echo '<li><a href="https://github.com/Onemoremichael/DHL-Plugin/blob/main/docs/STATUS.md" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Status / Known blockers', 'woocommerce-shipping-dhl' ) . '</a></li>';
		echo '<li><a href="https://github.com/Onemoremichael/DHL-Plugin/blob/main/docs/PRODUCTION_BACKLOG_2026-03-07.md" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Production backlog', 'woocommerce-shipping-dhl' ) . '</a></li>';
		echo '<li><a href="https://github.com/Onemoremichael/DHL-Plugin/issues" target="_blank" rel="noopener noreferrer">' . esc_html__( 'GitHub Issues', 'woocommerce-shipping-dhl' ) . '</a></li>';
		echo '<li><a href="https://github.com/Onemoremichael/DHL-Plugin" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Repository', 'woocommerce-shipping-dhl' ) . '</a></li>';
		echo '</ul>';
		echo '</div>';
	}

	/**
	 * Add plugin action links.
	 *
	 * @param array $links Existing plugin action links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=dhl_settings' ) ) . '">' . esc_html__( 'Settings', 'woocommerce-shipping-dhl' ) . '</a>';
		$docs_link     = '<a href="https://github.com/Onemoremichael/DHL-Plugin/blob/main/README.md" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Docs', 'woocommerce-shipping-dhl' ) . '</a>';

		array_unshift( $links, $settings_link, $docs_link );

		return $links;
	}

	/**
	 * Add plugin row meta links.
	 *
	 * @param array  $links Existing row meta links.
	 * @param string $file Plugin file.
	 * @return array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( plugin_basename( WC_SHIPPING_DHL_PLUGIN_DIR . '/woocommerce-shipping-dhl.php' ) !== $file ) {
			return $links;
		}

		$links[] = '<a href="https://github.com/Onemoremichael/DHL-Plugin/issues" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Support', 'woocommerce-shipping-dhl' ) . '</a>';
		$links[] = '<a href="https://github.com/Onemoremichael/DHL-Plugin/blob/main/docs/STATUS.md" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Status', 'woocommerce-shipping-dhl' ) . '</a>';

		return $links;
	}

	/**
	 * Render an instance-aware admin notice when DHL settings are incomplete.
	 *
	 * @return void
	 */
	private function maybe_render_configuration_notice(): void {
		$shipping_method = $this->get_shipping_method_from_request();

		if ( ! $shipping_method instanceof WC_Shipping_DHL ) {
			return;
		}

		$config_error = $shipping_method->get_configuration_error();
		if ( ! $config_error ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>';
		echo esc_html( $config_error->get_error_message() ) . ' ';
		echo '<a href="' . esc_url( $shipping_method->get_settings_url() ) . '">' . esc_html__( 'Open DHL settings', 'woocommerce-shipping-dhl' ) . '</a>';
		echo '</p></div>';
	}

	/**
	 * Resolve the DHL shipping method instance shown on the current admin request.
	 *
	 * @return WC_Shipping_DHL|null
	 */
	private function get_shipping_method_from_request(): ?WC_Shipping_DHL {
		$screen = get_current_screen();
		if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin screen detection.
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		if ( 'shipping' !== $tab ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin screen detection.
		$instance_id = isset( $_GET['instance_id'] ) ? absint( $_GET['instance_id'] ) : 0;
		if ( $instance_id > 0 ) {
			return new WC_Shipping_DHL( $instance_id );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin screen detection.
		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
		if ( 'dhl_settings' === $section ) {
			return new WC_Shipping_DHL();
		}

		return null;
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
		$api_user       = isset( $_POST['api_user'] ) ? sanitize_text_field( wp_unslash( $_POST['api_user'] ) ) : '';
		$api_key        = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
		$shipper_number = isset( $_POST['shipper_number'] ) ? sanitize_text_field( wp_unslash( $_POST['shipper_number'] ) ) : '';
		$environment    = isset( $_POST['environment'] ) ? sanitize_text_field( wp_unslash( $_POST['environment'] ) ) : 'test';

		// Create a temporary shipping method instance.
		$shipping_method = new WC_Shipping_DHL();
		$shipping_method->init_settings();
		$shipping_method->settings['api_user']       = $api_user;
		$shipping_method->settings['api_key']        = $api_key;
		$shipping_method->settings['shipper_number'] = $shipper_number;
		$shipping_method->settings['environment']    = $environment;

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
