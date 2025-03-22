/**
 * DHL Admin Scripts
 */
jQuery(function($) {
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
});