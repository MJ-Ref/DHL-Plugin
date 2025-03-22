<?php
/**
 * Plugin initialization class.
 *
 * @package WC_Shipping_DHL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Shipping_DHL_Init class.
 */
class WC_Shipping_DHL_Init {

	/**
	 * Instance of the class.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Get an instance of the class.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->includes();
		$this->hooks();
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		// Core classes.
		require_once WC_SHIPPING_DHL_PLUGIN_DIR . '/includes/class-wc-shipping-dhl.php';
		require_once WC_SHIPPING_DHL_PLUGIN_DIR . '/includes/class-logger.php';
		require_once WC_SHIPPING_DHL_PLUGIN_DIR . '/includes/class-notifier.php';
		
		// API classes.
		require_once WC_SHIPPING_DHL_PLUGIN_DIR . '/includes/api/class-abstract-api-client.php';
		require_once WC_SHIPPING_DHL_PLUGIN_DIR . '/includes/api/class-abstract-address-validator.php';
		require_once WC_SHIPPING_DHL_PLUGIN_DIR . '/includes/api/rest/class-api-client.php';
		require_once WC_SHIPPING_DHL_PLUGIN_DIR . '/includes/api/rest/class-oauth.php';
		require_once WC_SHIPPING_DHL_PLUGIN_DIR . '/includes/api/rest/class-address-validator.php';
	}

	/**
	 * Set up hooks.
	 */
	private function hooks() {
		add_action( 'woocommerce_shipping_init', array( $this, 'shipping_init' ) );
		add_filter( 'woocommerce_shipping_methods', array( $this, 'add_shipping_method' ) );
		add_action( 'woocommerce_blocks_loaded', array( $this, 'register_blocks_integration' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
	}

	/**
	 * Initialize shipping method.
	 */
	public function shipping_init() {
		$this->load_textdomain();
	}

	/**
	 * Add shipping method to WooCommerce.
	 *
	 * @param array $methods Shipping methods.
	 * @return array
	 */
	public function add_shipping_method( $methods ) {
		$methods['dhl'] = 'WC_Shipping_DHL';
		return $methods;
	}

	/**
	 * Register blocks integration.
	 */
	public function register_blocks_integration() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface' ) ) {
			require_once WC_SHIPPING_DHL_PLUGIN_DIR . '/includes/class-blocks-integration.php';
			
			add_action(
				'woocommerce_blocks_checkout_block_registration',
				function( $integration_registry ) {
					$integration_registry->register( new WC_DHL_Blocks_Integration() );
				}
			);
		}
	}

	/**
	 * Load plugin textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'woocommerce-shipping-dhl', false, dirname( plugin_basename( WC_SHIPPING_DHL_PLUGIN_DIR ) ) . '/languages/' );
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function admin_scripts() {
		$screen = get_current_screen();

		// Only on shipping settings page.
		if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id ) {
			return;
		}

		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
		
		if ( 'dhl' !== $section ) {
			return;
		}

		wp_enqueue_style( 'wc-dhl-admin-styles', WC_SHIPPING_DHL_PLUGIN_URL . '/assets/css/dhl-admin.css', array(), WC_SHIPPING_DHL_VERSION );
		wp_enqueue_script( 'wc-dhl-admin-script', WC_SHIPPING_DHL_PLUGIN_URL . '/assets/js/dhl-admin.js', array( 'jquery' ), WC_SHIPPING_DHL_VERSION, true );
	}

	/**
	 * Enqueue frontend scripts.
	 */
	public function frontend_scripts() {
		if ( is_checkout() ) {
			wp_enqueue_script( 'wc-dhl-checkout', WC_SHIPPING_DHL_PLUGIN_URL . '/assets/js/checkout.js', array( 'jquery' ), WC_SHIPPING_DHL_VERSION, true );
		}
	}
}