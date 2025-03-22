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
	$( '.wc-dhl-add-box' ).on( 'click', function( e ) {
		e.preventDefault();
		
		var boxRow = $( '.wc-dhl-box-row.template' ).clone();
		boxRow.removeClass( 'template' );
		boxRow.find( 'input, select' ).prop( 'disabled', false );
		
		var timestamp = new Date().getTime();
		boxRow.find( 'input, select' ).each( function() {
			var name = $( this ).attr( 'name' );
			if ( name ) {
				$( this ).attr( 'name', name.replace( 'template', timestamp ) );
			}
		} );
		
		boxRow.insertBefore( $( '.wc-dhl-boxes tbody .wc-dhl-add-box-row' ) );
		boxRow.show();
	} );

	// Remove box
	$( document ).on( 'click', '.wc-dhl-remove-box', function( e ) {
		e.preventDefault();
		$( this ).closest( 'tr' ).remove();
	} );

	// Toggle packing method settings
	$( '#woocommerce_dhl_packing_method' ).on( 'change', function() {
		var method = $( this ).val();
		
		if ( 'box_packing' === method ) {
			$( '.dhl-box-packing' ).show();
		} else {
			$( '.dhl-box-packing' ).hide();
		}
	} ).change();

	// Show/hide box packing options
	function showHideBoxPackingOptions() {
		if ($('select#woocommerce_dhl_packing_method').val() === 'box_packing') {
			$('#packing_options').show();
		} else {
			$('#packing_options').hide();
		}
	}

	$('select#woocommerce_dhl_packing_method').on('change', function() {
		showHideBoxPackingOptions();
	});

	// Initialize
	showHideBoxPackingOptions();

	// Handle the OAuth process
	$('#wc-shipping-dhl-oauth-button').on('click', function(e) {
		e.preventDefault();
		
		var data = {
			action: 'wc_shipping_dhl_oauth',
			api_user: $('#woocommerce_dhl_api_user').val(),
			api_key: $('#woocommerce_dhl_api_key').val(),
			environment: $('#woocommerce_dhl_environment').val(),
			security: $('#wc_shipping_dhl_oauth_nonce').val()
		};
		
		$('#wc-shipping-dhl-oauth-status').html('<p>' + dhl_admin_params.i18n_connecting + '</p>');
		
		$.post(dhl_admin_params.ajax_url, data, function(response) {
			if (response.success) {
				$('#wc-shipping-dhl-oauth-status').html('<p>' + response.data.message + '</p>').removeClass('error').addClass('success');
			} else {
				$('#wc-shipping-dhl-oauth-status').html('<p>' + response.data.message + '</p>').removeClass('success').addClass('error');
			}
		});
	});
} );