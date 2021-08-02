"use strict";

var vipFireCheckout = {};

(function($) {
    var app = {
        'init':function () {
            /** FireCheckout  */

            document.observe('dom:loaded', function() {
                var fireCheckoutPage = $('.firecheckout-index-index');
                if(fireCheckoutPage.length){
                    if(typeof checkoutItems !== 'undefined') {
                        app.consoleDisplay('step 1');
                        dataLayer.push({
                            'event': 'checkout',
                            'ecommerce': {
                                'checkout': {
                                    'actionField': {'step': 1},
                                    'products': checkoutItems
                                }
                            },
                        });
                    }
                }
            });

            document.observe('firecheckout:updateBefore', function(param) {

                if(param && param.memo.url !== undefined){
                    let url = param.memo.url;
                    /** FireCheckout billingDetails */
                    if(url.indexOf("saveBilling") > 0){
                        app.consoleDisplay('step 2');
                        if(typeof checkoutItems !== 'undefined') {
                            dataLayer.push({
                                'event': 'checkout',
                                'ecommerce': {
                                    'checkout': {
                                        'actionField': {'step': 2},
                                        'products': checkoutItems
                                    }
                                },
                            });
                        }

                    }

                    /** FireCheckout shippingDetails */
                    if(url.indexOf("saveShipping/") > 0){
                        if(typeof checkoutItems !== 'undefined') {
                            app.consoleDisplay('step 3');
                            dataLayer.push({
                                'event': 'checkout',
                                'ecommerce': {
                                    'checkout': {
                                        'actionField': {'step': 3},
                                        'products': checkoutItems
                                    }
                                },
                            });
                        }
                    }

                    /** FireCheckout ShippingMethod */
                    if(url.indexOf("saveShippingMethod") > 0){
                        let shippingMethodOption = $("input[name='shipping_method']:checked").val();
                        if(shippingMethodOption){
                            app.consoleDisplay('step 4');
                            dataLayer.push({
                                'event': 'checkout',
                                'ecommerce': {
                                    'checkout': {
                                        'actionField': {'step': 4, 'option' : shippingMethodOption},
                                        'products': checkoutItems
                                    }
                                }
                            });
                        }
                    }

                    /** FireCheckout paymentMethod */
                    if(url.indexOf("savePayment") > 0){
                        let paymentMethodOption = $("input[name='payment[method]']:checked").val();
                        if(paymentMethodOption){
                            app.consoleDisplay('step 5');
                            dataLayer.push({
                                'event': 'checkout',
                                'ecommerce': {
                                    'checkout': {
                                        'actionField': {'step': 5, 'option' : paymentMethodOption}, //Payment Information
                                        'products': checkoutItems
                                    }
                                }
                            });
                        }
                    }

                    /** FireCheckout saveCart */
                    if(url.indexOf("saveCart") > 0){


                    }

                }

            });
        },
        'consoleDisplay': function (message) {
            console.log(message)
        }
    };

    app.init();
    vipFireCheckout.app;
})(jQuery);