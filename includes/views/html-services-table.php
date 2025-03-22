<?php
/**
 * Services table template.
 *
 * @package WC_Shipping_DHL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style type="text/css">
    .dhl-services th.sort {
        width: 20px;
    }
    .dhl-services tbody tr {
        cursor: move;
    }
    .dhl-services tr.disabled td {
        opacity: 0.3;
    }
    .dhl-services tr.disabled {
        border-left: 3px solid #AA0000;
    }
    .dhl-services tr.enabled {
        border-left: 3px solid #73880A;
    }
</style>
<script type="text/javascript">
    jQuery(function($) {
        $('.dhl-services tbody').sortable({
            items: 'tr',
            cursor: 'move',
            axis: 'y',
            handle: '.sort',
            scrollSensitivity: 40,
            helper: function(e, ui) {
                ui.children().each(function() {
                    $(this).width($(this).width());
                });
                ui.css('left', '0');
                return ui;
            },
            start: function(event, ui) {
                ui.item.css('background-color', '#f6f6f6');
            },
            stop: function(event, ui) {
                ui.item.removeAttr('style');
                // Reset the service order values
                var i = 0;
                $('.dhl-services tbody tr').each(function() {
                    var service_code = $(this).find('.service_code').text();
                    $(this).find('input[name$="[order]"]').val(i);
                    i++;
                });
            }
        });

        $('.dhl-services input[type="checkbox"]').change(function() {
            var $this = $(this);
            var $tr = $this.closest('tr');
            if ($this.is(':checked')) {
                $tr.removeClass('disabled').addClass('enabled');
            } else {
                $tr.removeClass('enabled').addClass('disabled');
            }
        }).change();
    });
</script><?php