<?php
/**
 * Product dimensions template.
 *
 * @package WC_Shipping_DHL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="options_group dhl-dimensions">
	<p class="form-field dimensions_field">
		<label><?php esc_html_e( 'DHL Dimensions', 'woocommerce-shipping-dhl' ); ?></label>
		<span class="wrap">
			<input id="_dhl_length" placeholder="<?php esc_attr_e( 'Length', 'woocommerce-shipping-dhl' ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="_dhl_length" value="<?php echo esc_attr( $dhl_length ); ?>" />
			<input id="_dhl_width" placeholder="<?php esc_attr_e( 'Width', 'woocommerce-shipping-dhl' ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="_dhl_width" value="<?php echo esc_attr( $dhl_width ); ?>" />
			<input id="_dhl_height" placeholder="<?php esc_attr_e( 'Height', 'woocommerce-shipping-dhl' ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="_dhl_height" value="<?php echo esc_attr( $dhl_height ); ?>" />
		</span>
		<?php echo wc_help_tip( __( 'Custom DHL dimensions (optional). These will be used instead of the default product dimensions when calculating DHL shipping rates.', 'woocommerce-shipping-dhl' ) ); ?>
	</p>
</div> 