<?php
/**
 * Services settings template.
 *
 * @package WC_Shipping_DHL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$services = $this->get_dhl_services();
?>
<tr valign="top" id="service_options">
	<th scope="row" class="titledesc">
		<label><?php esc_html_e( 'Services', 'woocommerce-shipping-dhl' ); ?></label>
	</th>
	<td class="forminp">
		<p class="description" style="margin-bottom: 10px;"><?php esc_html_e( 'Select the DHL services you would like to offer to customers. The services shown below are available based on your origin and destination country selections.', 'woocommerce-shipping-dhl' ); ?></p>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Services', 'woocommerce-shipping-dhl' ); ?></legend>
			<table class="dhl-services wc-shipping-services widefat">
				<thead>
					<tr>
						<th class="sort">&nbsp;</th>
						<th class="service_code"><?php esc_html_e( 'Code', 'woocommerce-shipping-dhl' ); ?></th>
						<th class="service_name"><?php esc_html_e( 'Service Name', 'woocommerce-shipping-dhl' ); ?></th>
						<th class="service_enabled"><?php esc_html_e( 'Enabled', 'woocommerce-shipping-dhl' ); ?></th>
						<th class="service_adjustment"><?php esc_html_e( 'Price Adjustment (%)', 'woocommerce-shipping-dhl' ); ?></th>
						<th class="service_adjustment_cost"><?php esc_html_e( 'Price Adjustment (Fixed)', 'woocommerce-shipping-dhl' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$set_services = $this->get_option( 'services', array() );
					$custom_services = array_merge( array_flip( array_keys( $services ) ), (array) $this->get_option( 'custom_services', array() ) );

					foreach ( $services as $code => $name ) {
						$enabled = isset( $custom_services[ $code ]['enabled'] ) ? 1 === $custom_services[ $code ]['enabled'] : false;
						$adjustment_percent = isset( $custom_services[ $code ]['adjustment_percent'] ) ? $custom_services[ $code ]['adjustment_percent'] : '';
						$adjustment = isset( $custom_services[ $code ]['adjustment'] ) ? $custom_services[ $code ]['adjustment'] : '';
						?>
						<tr>
							<td class="sort"><span class="dashicons dashicons-menu"></span></td>
							<td class="service_code"><?php echo esc_html( $code ); ?></td>
							<td class="service_name">
								<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'custom_services' ) ); ?>[<?php echo esc_attr( $code ); ?>][name]" value="<?php echo isset( $custom_services[ $code ]['name'] ) ? esc_attr( $custom_services[ $code ]['name'] ) : esc_attr( $name ); ?>" size="30" />
							</td>
							<td class="service_enabled">
								<input type="checkbox" name="<?php echo esc_attr( $this->get_field_key( 'custom_services' ) ); ?>[<?php echo esc_attr( $code ); ?>][enabled]" <?php checked( $enabled ); ?> value="1" />
							</td>
							<td class="service_adjustment">
								<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'custom_services' ) ); ?>[<?php echo esc_attr( $code ); ?>][adjustment_percent]" value="<?php echo esc_attr( $adjustment_percent ); ?>" placeholder="0" size="4" />%
							</td>
							<td class="service_adjustment_cost">
								<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'custom_services' ) ); ?>[<?php echo esc_attr( $code ); ?>][adjustment]" value="<?php echo esc_attr( $adjustment ); ?>" placeholder="0.00" size="4" />
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</fieldset>
	</td>
</tr>
</table>
<h3><?php esc_html_e( 'Service Options', 'woocommerce-shipping-dhl' ); ?></h3>
<p><?php esc_html_e( 'Select the shipping services that will be available to customers.', 'woocommerce-shipping-dhl' ); ?></p>
<table class="form-table"><?php