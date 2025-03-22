<?php
/**
 * Utility trait file.
 *
 * @package WC_Shipping_DHL
 */

namespace WooCommerce\DHL;

/**
 * Trait Util
 */
trait Util {

	/**
	 * Format a weight for DHL based on the chosen weight unit.
	 *
	 * @param float  $weight Weight to format.
	 * @param string $unit   Weight unit.
	 * @return float
	 */
	public function format_weight( $weight, $unit = '' ) {
		if ( empty( $unit ) && isset( $this->weight_unit ) ) {
			$unit = $this->weight_unit;
		}

		// Default to kg if no unit is provided.
		if ( empty( $unit ) ) {
			$unit = 'KG';
		}

		// Round to DHL precision based on unit.
		if ( 'KG' === $unit ) {
			// KG supports 3 decimal places.
			return round( $weight, 3 );
		} else {
			// LBS/OZS support 1 decimal place.
			return round( $weight, 1 );
		}
	}

	/**
	 * Format dimensions for DHL.
	 *
	 * @param float  $dimension Dimension to format.
	 * @param string $unit      Dimension unit.
	 * @return float
	 */
	public function format_dimension( $dimension, $unit = '' ) {
		if ( empty( $unit ) && isset( $this->dim_unit ) ) {
			$unit = $this->dim_unit;
		}

		// Default to cm if no unit is provided.
		if ( empty( $unit ) ) {
			$unit = 'cm';
		}

		// Round to DHL precision - always integers for dimensions.
		return round( $dimension );
	}

	/**
	 * Check if a destination is domestic.
	 *
	 * @param string $destination_country Destination country code.
	 * @return bool
	 */
	public function is_domestic( $destination_country ) {
		$origin_country = isset( $this->origin_country ) ? $this->origin_country : '';
		
		if ( empty( $origin_country ) ) {
			$origin_country = WC()->countries->get_base_country();
		}

		return $origin_country === $destination_country;
	}

	/**
	 * Clean string - removes special characters and replaces non-ASCII chars with ASCII equivalent.
	 *
	 * @param string $string String to clean.
	 * @return string
	 */
	public function clean_string( $string ) {
		// Replace special characters.
		$string = str_replace( array( "'", '"', '&', '/', '#', '\\' ), ' ', $string );
		$string = str_replace( array( 'á', 'à', 'â', 'ã', 'ä' ), 'a', $string );
		$string = str_replace( array( 'é', 'è', 'ê', 'ë' ), 'e', $string );
		$string = str_replace( array( 'í', 'ì', 'î', 'ï' ), 'i', $string );
		$string = str_replace( array( 'ó', 'ò', 'ô', 'õ', 'ö' ), 'o', $string );
		$string = str_replace( array( 'ú', 'ù', 'û', 'ü' ), 'u', $string );
		$string = str_replace( array( 'ç' ), 'c', $string );
		$string = str_replace( array( 'ñ' ), 'n', $string );

		// Strip all other non-ASCII characters.
		$string = preg_replace( '/[^(\x20-\x7F)]*/', '', $string );

		// Trim.
		$string = trim( $string );

		return $string;
	}

	/**
	 * Get countries that are supported by DHL Express.
	 *
	 * @return array
	 */
	public function get_supported_countries() {
		return apply_filters(
			'woocommerce_dhl_supported_countries',
			array_keys( WC()->countries->get_countries() )
		);
	}

	/**
	 * Check if a country is supported by DHL Express.
	 *
	 * @param string $country_code Country code.
	 * @return bool
	 */
	public function is_supported_country( $country_code ) {
		return in_array( $country_code, $this->get_supported_countries(), true );
	}

	/**
	 * Get DHL services from the data file.
	 *
	 * @return array
	 */
	public function get_dhl_services_from_data() {
		$services_file = WC_SHIPPING_DHL_PLUGIN_DIR . '/includes/data/data-services.php';
		
		if ( file_exists( $services_file ) ) {
			return include $services_file;
		}

		// Return an empty array if the file doesn't exist.
		return array();
	}
}