<?php
/**
 * Product editor tests.
 *
 * @package WC_Shipping_DHL
 */

use WooCommerce\DHL\Product_Editor;

/**
 * Class Product_Editor_Test.
 */
class Product_Editor_Test extends WP_UnitTestCase {

	/**
	 * Product editor under test.
	 *
	 * @var Product_Editor
	 */
	private $product_editor;

	/**
	 * Set up the test case.
	 */
	public function setUp(): void {
		parent::setUp();
		require_once WC_SHIPPING_DHL_PLUGIN_DIR . '/includes/class-product-editor.php';
		$this->product_editor = new Product_Editor();
	}

	/**
	 * Tear down the test case.
	 */
	public function tearDown(): void {
		$_POST = array();
		parent::tearDown();
	}

	/**
	 * Test simple-product commodity code persistence.
	 */
	public function test_save_product_dimensions_persists_commodity_code() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Commodity Product' );
		$product->set_regular_price( '15.00' );
		$product->save();

		$_POST['woocommerce_meta_nonce'] = wp_create_nonce( 'woocommerce_save_data' );
		$_POST['_wc_dhl_commodity_code'] = '61-09.10';

		$this->product_editor->save_product_dimensions( $product );
		$product->save();

		$reloaded = wc_get_product( $product->get_id() );

		$this->assertSame( '610910', $reloaded->get_meta( '_wc_dhl_commodity_code', true ) );
	}

	/**
	 * Test variation commodity code persistence.
	 */
	public function test_save_variation_shipping_meta_persists_commodity_code() {
		$parent = new WC_Product_Variable();
		$parent->set_name( 'Variable Commodity Product' );
		$parent->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $parent->get_id() );
		$variation->set_regular_price( '19.00' );
		$variation->save();

		$_POST['woocommerce_meta_nonce']              = wp_create_nonce( 'woocommerce_save_data' );
		$_POST['variable_wc_dhl_commodity_code'][0] = '8504 40';

		$this->product_editor->save_variation_shipping_meta( $variation, 0 );
		$variation->save();

		$reloaded = wc_get_product( $variation->get_id() );

		$this->assertSame( '850440', $reloaded->get_meta( '_wc_dhl_commodity_code', true ) );
	}
}
