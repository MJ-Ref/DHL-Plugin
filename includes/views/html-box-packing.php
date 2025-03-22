<?php
/**
 * Box packing view.
 *
 * @package WC_Shipping_DHL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$boxes = $this->get_option( 'boxes', array() );
$dim_unit = $this->get_option( 'dimension_unit' );
$weight_unit = $this->get_option( 'weight_unit' );
$packed_boxes = $this->get_shipping_boxes();
?>
<tr valign="top" id="packing_options">
	<th scope="row" class="titledesc">
		<label><?php esc_html_e( 'Custom Boxes', 'woocommerce-shipping-dhl' ); ?></label>
	</th>
	<td class="forminp">
		<table class="dhl-boxes widefat">
			<thead>
				<tr>
					<th class="check-column"><input type="checkbox" /></th>
					<th><?php esc_html_e( 'Name', 'woocommerce-shipping-dhl' ); ?></th>
					<th><?php esc_html_e( 'Length', 'woocommerce-shipping-dhl' ); ?></th>
					<th><?php esc_html_e( 'Width', 'woocommerce-shipping-dhl' ); ?></th>
					<th><?php esc_html_e( 'Height', 'woocommerce-shipping-dhl' ); ?></th>
					<th><?php esc_html_e( 'Box Weight', 'woocommerce-shipping-dhl' ); ?></th>
					<th><?php esc_html_e( 'Max Weight', 'woocommerce-shipping-dhl' ); ?></th>
					<th><?php esc_html_e( 'Enabled', 'woocommerce-shipping-dhl' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( ! empty( $boxes ) ) {
					foreach ( $boxes as $key => $box ) {
						?>
						<tr>
							<td class="check-column">
								<input type="checkbox" name="select" />
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[<?php echo esc_attr( $key ); ?>][name]" value="<?php echo esc_attr( $box['name'] ); ?>" />
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[<?php echo esc_attr( $key ); ?>][length]" value="<?php echo esc_attr( $box['length'] ); ?>" />
								<?php echo esc_html( $dim_unit ); ?>
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[<?php echo esc_attr( $key ); ?>][width]" value="<?php echo esc_attr( $box['width'] ); ?>" />
								<?php echo esc_html( $dim_unit ); ?>
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[<?php echo esc_attr( $key ); ?>][height]" value="<?php echo esc_attr( $box['height'] ); ?>" />
								<?php echo esc_html( $dim_unit ); ?>
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[<?php echo esc_attr( $key ); ?>][box_weight]" value="<?php echo esc_attr( $box['box_weight'] ); ?>" />
								<?php echo esc_html( $weight_unit ); ?>
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[<?php echo esc_attr( $key ); ?>][max_weight]" value="<?php echo esc_attr( $box['max_weight'] ); ?>" />
								<?php echo esc_html( $weight_unit ); ?>
							</td>
							<td>
								<input type="checkbox" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[<?php echo esc_attr( $key ); ?>][enabled]" <?php checked( ! empty( $box['enabled'] ) ); ?> />
								<input type="hidden" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[<?php echo esc_attr( $key ); ?>][id]" value="<?php echo esc_attr( $box['id'] ); ?>" />
							</td>
						</tr>
						<?php
					}
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<th colspan="9">
						<a href="#" class="button plus add-box"><?php esc_html_e( 'Add Box', 'woocommerce-shipping-dhl' ); ?></a>
						<a href="#" class="button minus remove-box"><?php esc_html_e( 'Remove Selected', 'woocommerce-shipping-dhl' ); ?></a>
					</th>
				</tr>
			</tfoot>
		</table>
		<script type="text/javascript">
			jQuery(function() {
				jQuery('.dhl-boxes').on( 'click', 'a.add-box', function() {
					var size = jQuery('.dhl-boxes tbody tr').length;
					jQuery('<tr>\
						<td class="check-column"><input type="checkbox" name="select" /></td>\
						<td><input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[' + size + '][name]" /></td>\
						<td><input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[' + size + '][length]" /> <?php echo esc_html( $dim_unit ); ?></td>\
						<td><input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[' + size + '][width]" /> <?php echo esc_html( $dim_unit ); ?></td>\
						<td><input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[' + size + '][height]" /> <?php echo esc_html( $dim_unit ); ?></td>\
						<td><input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[' + size + '][box_weight]" /> <?php echo esc_html( $weight_unit ); ?></td>\
						<td><input type="text" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[' + size + '][max_weight]" /> <?php echo esc_html( $weight_unit ); ?></td>\
						<td><input type="checkbox" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[' + size + '][enabled]" checked="checked" /><input type="hidden" name="<?php echo esc_attr( $this->get_field_key( 'boxes' ) ); ?>[' + size + '][id]" value="' + size + '" /></td>\
					</tr>').appendTo('.dhl-boxes tbody');
					return false;
				});
				jQuery('.dhl-boxes').on( 'click', 'a.remove-box', function() {
					jQuery('.dhl-boxes tbody tr').has('input:checkbox:checked[name="select"]').remove();
					return false;
				});
				// Checkbox select/deselect
				jQuery('.dhl-boxes thead input:checkbox').change(function() {
					jQuery(this).closest('table').find('tbody input:checkbox').prop('checked', jQuery(this).is(':checked'));
				});
			});
		</script>
	</td>
</tr>
</table>
<h3><?php esc_html_e( 'Packing Settings', 'woocommerce-shipping-dhl' ); ?></h3>
<p><?php esc_html_e( 'These are the packing options for the box packing method. These settings determine how items will be packed into boxes.', 'woocommerce-shipping-dhl' ); ?></p>
<table class="form-table"><?php