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
$enabled_services = $this->get_enabled_service_codes();

?>
<tr valign="top">
	<th scope="row" class="titledesc">
		<?php esc_html_e( 'Services', 'woocommerce-shipping-dhl' ); ?>
		<span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Select the DHL services you want to offer to customers.', 'woocommerce-shipping-dhl' ); ?>"></span>
	</th>
	<td class="forminp">
		<div class="wc-dhl-services">
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Service', 'woocommerce-shipping-dhl' ); ?></th>
						<th><?php esc_html_e( 'Enabled', 'woocommerce-shipping-dhl' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $services as $code => $name ) : ?>
						<tr>
							<td>
								<?php echo esc_html( $name ); ?>
							</td>
							<td>
								<input type="checkbox" name="<?php echo esc_attr( $this->get_field_key( 'services' ) ); ?>[]" value="<?php echo esc_attr( $code ); ?>" <?php checked( in_array( $code, $enabled_services, true ) ); ?> />
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</td>
</tr>
</table>
<h3><?php esc_html_e( 'Service Options', 'woocommerce-shipping-dhl' ); ?></h3>
<p><?php esc_html_e( 'Select the shipping services that will be available to customers.', 'woocommerce-shipping-dhl' ); ?></p>
<table class="form-table"><?php