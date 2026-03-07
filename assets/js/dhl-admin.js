/**
 * DHL Shipping Admin JavaScript
 */
jQuery( function( $ ) {
	'use strict';

	// Toggle service settings
	$( '.wc-dhl-service-toggle' ).on( 'change', function() {
		var serviceRow = $( this ).closest( 'tr' ).next( 'tr' );
		if ( $( this ).is( ':checked' ) ) {
			serviceRow.show();
		} else {
			serviceRow.hide();
		}
	} );

	// Initialize service settings visibility
	$( '.wc-dhl-service-toggle' ).each( function() {
		var serviceRow = $( this ).closest( 'tr' ).next( 'tr' );
		if ( $( this ).is( ':checked' ) ) {
			serviceRow.show();
		} else {
			serviceRow.hide();
		}
	} );

	// Add box
	$( document ).on( 'click', '.wc-dhl-add-box', function( e ) {
		e.preventDefault();

		var container = $( this ).closest( '.wc-dhl-boxes' );
		var boxRow = container.find( '.wc-dhl-box-row.template' ).first().clone();
		boxRow.removeClass( 'template' );
		boxRow.find( 'input, select' ).prop( 'disabled', false );

		var timestamp = new Date().getTime();
		boxRow.find( 'input, select' ).each( function() {
			var name = $( this ).attr( 'name' );
			if ( name ) {
				$( this ).attr( 'name', name.replace( 'template', timestamp ) );
			}
		} );

		boxRow.insertBefore( container.find( '.wc-dhl-add-box-row' ).first() );
		boxRow.show();
	} );

	// Remove box
	$( document ).on( 'click', '.wc-dhl-remove-box', function( e ) {
		e.preventDefault();
		$( this ).closest( 'tr' ).remove();
	} );

	function getPackingMethodFields() {
		return $( 'select[id$="_packing_method"], select#woocommerce_dhl_packing_method' );
	}

	// Show/hide box-packing-specific settings in both standalone settings and zone modals.
	function syncBoxPackingVisibility( selectEl ) {
		var method = $( selectEl ).val();
		var scope = $( selectEl ).closest( '.wc-modal-shipping-method-settings, form, table' );
		var rows = scope.find( '.dhl-box-packing' );

		if ( ! rows.length ) {
			rows = $( '.dhl-box-packing' );
		}

		if ( 'box_packing' === method ) {
			rows.show();
		} else {
			rows.hide();
		}
	}

	$( document ).on( 'change', 'select[id$="_packing_method"], select#woocommerce_dhl_packing_method', function() {
		syncBoxPackingVisibility( this );
	} );

	// Initialize
	getPackingMethodFields().each( function() {
		syncBoxPackingVisibility( this );
	} );

	// Validate API credentials from settings.
	$( document ).on( 'click', '#wc-shipping-dhl-oauth-button', function( e ) {
		e.preventDefault();

		if ( 'undefined' === typeof dhl_admin_params ) {
			return;
		}

		var container = $( this ).closest( '.wc-modal-shipping-method-settings, form, table' );
		var data = {
			action: 'wc_shipping_dhl_validate_credentials',
			api_user: container.find( 'input[id$="_api_user"]' ).val(),
			api_key: container.find( 'input[id$="_api_key"]' ).val(),
			shipper_number: container.find( 'input[id$="_shipper_number"]' ).val(),
			environment: container.find( 'select[id$="_environment"]' ).val(),
			nonce: dhl_admin_params.nonce
		};

		container.find( '#wc-shipping-dhl-oauth-status' ).html( '<p>' + dhl_admin_params.i18n_connecting + '</p>' );

		$.post( dhl_admin_params.ajax_url, data, function( response ) {
			if ( response.success ) {
				container.find( '#wc-shipping-dhl-oauth-status' ).html( '<p>' + response.data.message + '</p>' ).removeClass( 'error' ).addClass( 'success' );
			} else {
				container.find( '#wc-shipping-dhl-oauth-status' ).html( '<p>' + response.data.message + '</p>' ).removeClass( 'success' ).addClass( 'error' );
			}
		} );
	});
} );
