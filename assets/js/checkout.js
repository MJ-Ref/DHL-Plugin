/**
 * DHL Checkout Scripts
 */
jQuery(function($) {
    // Trigger update checkout when country, state, postcode, or city fields are updated
    $(document.body).on('change', 'select.country_to_state, input.input-text.state, input.input-text.postcode, input.input-text.city', function() {
        $(document.body).trigger('update_checkout');
    });
});