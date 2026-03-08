<?php
/**
 * Services table settings template.
 *
 * @package WC_Shipping_DHL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_array( $services ) ) {
	$services = array();
}

if ( ! is_array( $custom_services ) ) {
	$custom_services = array();
}
?>
<tr valign="top">
	<th scope="row" class="titledesc">
		<?php esc_html_e( 'Service Settings', 'woocommerce-shipping-dhl' ); ?>
		<span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Customize the DHL service names and price adjustments shown to customers.', 'woocommerce-shipping-dhl' ); ?>"></span>
	</th>
	<td class="forminp">
		<div class="wc-dhl-service-settings">
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Service', 'woocommerce-shipping-dhl' ); ?></th>
						<th><?php esc_html_e( 'Custom Name', 'woocommerce-shipping-dhl' ); ?></th>
						<th><?php esc_html_e( 'Price Adjustment (%)', 'woocommerce-shipping-dhl' ); ?></th>
						<th><?php esc_html_e( 'Price Adjustment (Fixed)', 'woocommerce-shipping-dhl' ); ?></th>
						<th><?php esc_html_e( 'Enabled', 'woocommerce-shipping-dhl' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $services as $code => $name ) :
						$service_settings   = isset( $custom_services[ $code ] ) && is_array( $custom_services[ $code ] ) ? $custom_services[ $code ] : array();
						$enabled            = ! empty( $service_settings['enabled'] );
						$custom_name        = isset( $service_settings['name'] ) ? $service_settings['name'] : $name;
						$adjustment_percent = isset( $service_settings['adjustment_percent'] ) ? $service_settings['adjustment_percent'] : '';
						$adjustment         = isset( $service_settings['adjustment'] ) ? $service_settings['adjustment'] : '';
						?>
						<tr>
							<td>
								<?php echo esc_html( $name ); ?>
								<input type="hidden" name="<?php echo esc_attr( $this->get_field_key( 'custom_services' ) ); ?>[<?php echo esc_attr( $code ); ?>][code]" value="<?php echo esc_attr( $code ); ?>" />
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'custom_services' ) ); ?>[<?php echo esc_attr( $code ); ?>][name]" value="<?php echo esc_attr( $custom_name ); ?>" placeholder="<?php echo esc_attr( $name ); ?>" />
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'custom_services' ) ); ?>[<?php echo esc_attr( $code ); ?>][adjustment_percent]" value="<?php echo esc_attr( $adjustment_percent ); ?>" placeholder="0" />%
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'custom_services' ) ); ?>[<?php echo esc_attr( $code ); ?>][adjustment]" value="<?php echo esc_attr( $adjustment ); ?>" placeholder="0.00" />
							</td>
							<td>
								<input type="checkbox" class="wc-dhl-service-toggle" name="<?php echo esc_attr( $this->get_field_key( 'custom_services' ) ); ?>[<?php echo esc_attr( $code ); ?>][enabled]" <?php checked( $enabled ); ?> value="1" />
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</td>
</tr>
