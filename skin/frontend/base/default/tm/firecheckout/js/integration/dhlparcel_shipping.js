/*
 * Copied from skin/frontend/base/default/js/dhlparcel/shipping/quickcheckout.js
 */

jQuery(document).ready(function ($) {

    var dhlparcel_ready_checkout_timeout = null;
    var dhlparcel_ready_checkout_loaded= null;
    var dhlparcel_quickcheckout_observer = null;
    var dhlShippingOptionsSelected = [];

    $(document.body).on('dhlparcel:connect_checkout_shipping_service_options', function(e) {
        if (typeof FC !== 'undefined') {
            // Set quickcheckout observer
            $(document.body).trigger('dhlparcel:set_firecheckout_observer');
            // Trigger once
            $(document.body).trigger('dhlparcel:ready_checkout_buffer');
        }

    }).on('dhlparcel:set_firecheckout_observer', function() {
        fc_shipping_method_block = $$('#checkout-shipping-method-load')[0];
        if (fc_shipping_method_block !== null) {

            // Check this hasn't been called already
            if (dhlparcel_quickcheckout_observer === null) {
                // Setup observer
                dhlparcel_quickcheckout_observer = new MutationObserver(function (mutations) {
                    if (dhlparcel_ready_checkout_loaded === null) {
                        return;
                    }

                    if (mutations[0].target == fc_shipping_method_block) {
                        // Trigger reload
                        dhlparcel_ready_checkout_loaded = null;
                        $(document.body).trigger('dhlparcel:ready_checkout_buffer');

                    }
                });

                dhlparcel_quickcheckout_observer.observe(fc_shipping_method_block, {childList: true, subtree: true});
            } else {
                // Just reset observer
                dhlparcel_quickcheckout_observer.observe(fc_shipping_method_block, {childList: true, subtree: true});
            }
        }
    }).on('dhlparcel:changed_shipping_options', function(){
        DHLParcel_Save_Options($('form#firecheckout-form'), function() {
            FC.Utils.fireEvent($('input[name="shipping_method"]:checked').get(0), 'click');

            // Reset Selected options
            dhlShippingOptionsSelected = [];

            $('input.dhlparcel-shippingoption:checked').each(function(index, inputField) {
                dhlShippingOptionsSelected[$(inputField).attr('id')] = true;
            });

            $('select.dhlparcel-shippingoption option:selected').each(function(index, inputField) {
                dhlShippingOptionsSelected[$(inputField).closest('select').attr('id')] = $(inputField).val();
            });
        });
    }).on('dhlparcel:ready_checkout_buffer', function() {
        clearTimeout(dhlparcel_ready_checkout_timeout);

        if ($('#checkout-shipping-method-load').length > 0) {
            dhlparcel_ready_checkout_timeout = setTimeout(function () {
                $(document.body).trigger('dhlparcel:ready_checkout');
            }, 100);
        }

    }).on('dhlparcel:ready_checkout', function() {
        // Check if 'Homedelivery' is available
        if ($('input#s_method_dhlparcel_DOOR').length == 0) {
            dhlparcel_ready_checkout_loaded = true;

            $(document.body).trigger('dhlparcel:servicepoint_set_triggers');

            dhlparcel_ready_checkout_loaded = true;

            return;
        }

        DHLParcel_Refresh_Options(function(refreshData){
            // Check again for a loaded block
            if (dhlparcel_ready_checkout_loaded !== null) {
                return;
            }

            dhlparcel_ready_checkout_timeout = null;
            // $('label[for="s_method_dhlparcel_DOOR"]').append(refreshData.html);
            dhlparcel_ready_checkout_loaded = true;

            DHLParcel_Show_Active_Options();

            // Select options
            DHLParcel_Select_Selected_Options(dhlShippingOptionsSelected);

            // Post shipping options and recalculate totals
            $('.dhlparcel-shippingoption').change(function(){
                $(document.body).trigger('dhlparcel:changed_shipping_options');
            });

            // Bind onchange shipping methods
            $('input[name="shipping_method"]').change(function(){
                // Show the active options
                DHLParcel_Show_Active_Options();
            });

            $(document.body).trigger('dhlparcel:servicepoint_set_triggers');

            dhlparcel_ready_checkout_loaded = true;
        });
    })
    .on('dhlparcel:servicepoint_set_triggers', function() {
        // Add a hidden servicepoint input select
        if (!$('#dhlparcel-servicepoint-select').length) {
            $('<input>').attr({
                type: 'hidden',
                id: 'dhlparcel-servicepoint-select',
                name: 'dhlparcel-servicepoint-select',
                class: 'required-entry'
            }).appendTo('#firecheckout-form');
        }

        var hostElement = document.getElementById('dhl-servicepoint-locator-component');

        if ($('#dhlparcel_servicepoint_change_button').length == 0) {
            $("label[for='s_method_dhlparcel_PS']").append('' +
                '<div id="dhlparcel_servicepoint_change_button">' +
                '<button type="button" class="button dhlparcel_servicepoint_change"><span><span>' + DHLShipping_Texts_SelectPs + '</span></span</button>' +
                '</div>'
            );
            $("label[for='s_method_dhlparcel_PS'] #dhlparcel_servicepoint_change_button").before('<div id="dhlparcel_servicepoint_name_info">&nbsp;</div>');


            $(".dhlparcel_servicepoint_change").click(function() {
                $('#dhl-servicepoint-locator-component').attr('data-zip-code', $('input[name="billing[postcode]"]').val().replace(/\s/g, ''));
                $('#dhl-servicepoint-locator-component').attr('data-country-code', $('select[name="billing[country_id]"]').val());

                $(document.body).trigger("dhlparcel:show_parcelshop_selection_modal", [hostElement]);
                $('div.dhlparcel-modal').show();
            });
        }

        $("input[name='shipping_method']").change(function (e) {
            if ($(this).val() === 'dhlparcel_PS') {
                $('#dhlparcel-servicepoint-select').addClass('required-entry');
            } else {
                $('#dhlparcel-servicepoint-select').removeClass('required-entry');
            }
        });

        // Select Closest PSc
        $(document.body).trigger('dhlparcel:servicepoint_get_closest', [{
            'zipCode': $('input[name="billing[postcode]"]').val().replace(/\s/g, ''),
            'countryCode': $('select[name="billing[country_id]"]').val()
        }]);
    });

    $(document.body).trigger("dhlparcel:connect_checkout_shipping_service_options");
});
