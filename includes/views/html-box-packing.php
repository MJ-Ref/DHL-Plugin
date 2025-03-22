<?php
/**
 * Box packing settings template.
 *
 * @package WC_Shipping_DHL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get box settings.
$boxes = $this->get_option( 'boxes', array() );

?>
<tr valign="top" class="dhl-box-packing">
	<th scope="row" class="titledesc">
		<?php esc_html_e( 'Boxes', 'woocommerce-shipping-dhl' ); ?>
		<span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Add boxes to be used for packing items.', 'woocommerce-shipping-dhl' ); ?>"></span>
	</th>
	<td class="forminp">
		<div class="wc-dhl-boxes">
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'woocommerce-shipping-dhl' ); ?></th>
						<th><?php esc_html_e( 'Length', 'woocommerce-shipping-dhl' ); ?></th>
						<th><?php esc_html_e( 'Width', 'woocommerce-shipping-dhl' ); ?></th>
						<th><?php esc_html_e( 'Height', 'woocommerce-shipping-dhl' ); ?></th>
						<th><?php esc_html_e( 'Box Weight', 'woocommerce-shipping-dhl' ); ?></th>
						<th><?php esc_html_e( 'Max Weight', 'woocommerce-shipping-dhl' ); ?></th>
						<th><?php esc_html_e( 'Enabled', 'woocommerce-shipping-dhl' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $boxes ) ) : ?>
						<?php foreach ( $boxes as $key => $box ) : ?>
							<tr class="wc-dhl-box-row">
								<td>
									<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[<?php echo esc_attr( $key ); ?>][name]" value="<?php echo esc_attr( $box['name'] ); ?>" />
								</td>
								<td>
									<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[<?php echo esc_attr( $key ); ?>][length]" value="<?php echo esc_attr( $box['length'] ); ?>" />
								</td>
								<td>
									<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[<?php echo esc_attr( $key ); ?>][width]" value="<?php echo esc_attr( $box['width'] ); ?>" />
								</td>
								<td>
									<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[<?php echo esc_attr( $key ); ?>][height]" value="<?php echo esc_attr( $box['height'] ); ?>" />
								</td>
								<td>
									<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[<?php echo esc_attr( $key ); ?>][box_weight]" value="<?php echo esc_attr( $box['box_weight'] ); ?>" />
								</td>
								<td>
									<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[<?php echo esc_attr( $key ); ?>][max_weight]" value="<?php echo esc_attr( $box['max_weight'] ); ?>" />
								</td>
								<td>
									<input type="checkbox" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[<?php echo esc_attr( $key ); ?>][enabled]" <?php checked( ! empty( $box['enabled'] ) ); ?> value="1" />
								</td>
								<td>
									<a href="#" class="wc-dhl-remove-box"><?php esc_html_e( 'Remove', 'woocommerce-shipping-dhl' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
					<tr class="wc-dhl-box-row template" style="display: none;">
						<td>
							<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[template][name]" value="" disabled />
						</td>
						<td>
							<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[template][length]" value="" disabled />
						</td>
						<td>
							<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[template][width]" value="" disabled />
						</td>
						<td>
							<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[template][height]" value="" disabled />
						</td>
						<td>
							<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[template][box_weight]" value="" disabled />
						</td>
						<td>
							<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[template][max_weight]" value="" disabled />
						</td>
						<td>
							<input type="checkbox" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[template][enabled]" checked value="1" disabled />
						</td>
						<td>
							<a href="#" class="wc-dhl-remove-box"><?php esc_html_e( 'Remove', 'woocommerce-shipping-dhl' ); ?></a>
						</td>
					</tr>
					<tr class="wc-dhl-add-box-row">
						<td colspan="8">
							<a href="#" class="button wc-dhl-add-box"><?php esc_html_e( 'Add Box', 'woocommerce-shipping-dhl' ); ?></a>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</td>
</tr>
</table>
<h3><?php esc_html_e( 'Packing Settings', 'woocommerce-shipping-dhl' ); ?></h3>
<p><?php esc_html_e( 'These are the packing options for the box packing method. These settings determine how items will be packed into boxes.', 'woocommerce-shipping-dhl' ); ?></p>
<table class="form-table"><?php