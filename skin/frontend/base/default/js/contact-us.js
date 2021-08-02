"use strict";
(function($){
    var app = {
        'requestCallback' : function (formWrapper) {
            var $formWrapper = $(formWrapper);
            if($formWrapper.length){
                var contactUsHandle = $formWrapper.find('#submit-ticket');
                var formSubmitTicket = $('.form-submit-ticket');
                contactUsHandle.on('click', function (e) {
                    e.preventDefault();
                    $.fancybox(formSubmitTicket,{
                        helpers : {
                            overlay : {
                                css : {
                                    'background' : 'rgba(15, 25, 25, 0.5)'
                                }
                            }
                        },
                    });

                    var contactForm = formSubmitTicket.find('form');
                    var tokenField = contactForm.find('.google-token');
                    var siteKey = contactForm.data('captcha-sitekey');

                    grecaptcha.ready(function() {
                        grecaptcha.execute(siteKey, {action: 'contact_us'}).then(function(token) {
                            tokenField.val(token);
                        });
                    });
                });

                var requestCallback = $formWrapper.find('#request-callback');
                var formRequestCallback = $('.form-request-callback');
                requestCallback.on('click', function (e) {
                    e.preventDefault();
                    $.fancybox(formRequestCallback,{
                        helpers : {
                            overlay : {
                                css : {
                                    'background' : 'rgba(15, 25, 25, 0.5)'
                                }
                            }
                        },
                    });

                    var contactForm = formRequestCallback.find('form');
                    var tokenField = contactForm.find('.google-token');
                    var siteKey = contactForm.data('captcha-sitekey');

                    grecaptcha.ready(function() {
                        grecaptcha.execute(siteKey, {action: 'contact_us'}).then(function(token) {
                            tokenField.val(token);
                        });
                    });
                });
            }
        },
        'init': function () {
            if ('NodeList' in window) {
                if (!NodeList.prototype.each && NodeList.prototype.forEach) {
                    NodeList.prototype.each = NodeList.prototype.forEach;
                }
            }
            app.requestCallback('.callback-container');
        }
    };

    app.init();

})(jQuery);