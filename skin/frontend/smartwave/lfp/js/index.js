"use strict";

var vipAppComponent = {};

(function($){

    var app = {
        'bannerUpdateHeight' : function($elemParent,$elemChild) {

            var elemParent = $($elemParent);
            var elemChild = $($elemChild);

            if(elemParent.length){
                elemChild.height(elemParent.height())
            }

        },
        'featuredProduct' : function($elemParent) {

            var $parentElem  = $($elemParent);
            if($parentElem.length) {
                var activeClass = 'current';
                var  tabHandle = $parentElem.find('ul.control-products li');
                var tabContent = $parentElem.find('.tab-content');
                var mobileSelector  = $parentElem.find('.mobile-featured-selector select');

                tabHandle.find('span').on('click', function() {
                    var $this = $(this);
                    var parentLi = $this.parent();
                    var selectedTab = parentLi.data('tab');

                    tabContent.removeClass(activeClass);
                    tabHandle.removeClass(activeClass);
                    if(tabContent.hasClass(selectedTab)) {
                        parentLi.addClass(activeClass);
                        $('.' + selectedTab).addClass(activeClass).fadeIn(1000);
                    }

                });

                mobileSelector.on('change', function () {
                    let $this = $(this);
                    let selectedTab = $this.val();

                    tabContent.removeClass(activeClass);
                    if(tabContent.hasClass(selectedTab)) {
                        $('.' + selectedTab).addClass(activeClass).fadeIn(1000);
                    }
                })
            }

        },
        'newsletterSubscribe' : function ($elem) {

            var executeOnce = true;
            var $newsElemForm = $($elem);
            var email = $('#email');
            var $messageElem = $('.newsletter-message');
            var button = $('.button');
            var isEnableCaptcha = $newsElemForm.data('enable-captcha');
            var captchaSiteKey = $newsElemForm.data('site-key');

            var showLoader = function() {
                button.find('.fa').removeClass('hide');
                button.find('.label').addClass('hide');
            };

            var showSubscribeButton = function() {
                button.find('.fa').addClass('hide');
                button.find('.label').removeClass('hide');
            };

            $newsElemForm.on('submit', function(event){
                event.preventDefault();
                if(executeOnce && email.val().length > 0) {
                    let $this = $(this);

                    var ajaxNewsletter = function() {

                        $.ajax({
                            type: $this.attr('method'),
                            url: $this.attr('action'),
                            data: $this.serialize(),
                            beforeSend: function () {
                                showLoader();
                                executeOnce = false;
                                $messageElem.empty();
                            },
                            success: function (result) {
                                $messageElem.html(result.message);
                                $messageElem.removeClass('error success');
                                $messageElem.addClass(result.class);
                            },
                            error: function (xhr) {
                                $messageElem.html('There was a problem with the subscription.');
                                $messageElem.removeClass('error success');
                                $messageElem.addClass('error');
                                showSubscribeButton();
                            },
                            complete: function () {
                                executeOnce = true;
                                showSubscribeButton();
                            },
                        });
                    }

                    if(isEnableCaptcha) {
                        grecaptcha.ready(function() {
                            grecaptcha.execute(captchaSiteKey, {action: 'submit_newsletter'}).then(function(token) {
                                $newsElemForm.prepend('<input type="hidden" name="token" value="' + token + '">');
                                ajaxNewsletter();
                            });
                        });
                    }else {
                        ajaxNewsletter();
                    }
                }
            });
        },
        'tabletMenuToggle' : function (tabButton, tabContent) {

            let $tabButton = $(tabButton);
            let $tabContent = $(tabContent);

            if($tabButton.length && $tabContent.length) {

                $tabButton.find('.main-link li').on('click', function(e) {
                    $tabContent.toggle();
                    e.stopPropagation();
                });

                $(document).on('click', function (e) {
                    if($tabContent.is(":visible")) {
                        if ($(e.target).closest(tabContent).length === 0) {
                            $(tabContent).hide();
                            e.stopPropagation();
                        }
                    }
                });
            }
        },
        'googleAnalytics' : {
            'dataLayerVariable': 'layer-variable',
            'currencyCode': 'USD',
            'init' : function() {

                /** YMM Selector */
                this.__eventYmmSelector('#finderForm');

                /** Compatibility checker */
                this.__eventCompatibilityCheckerInvoke('#compatibility-checker');
                this.__eventCompatibilityChecker('.form-vehicle-checker');

                /** Search */
                this.__eventSearchTerms();

                /** Product Listing and product click */
                this._impression('.ga-impressions');
                this._productClick('.ga-productClick');

                /** Product details and view */
                this._productDetails();
                this._productView();

                /** Add to Cart */
                this._eventAddToCart();
                this.__eventAddedToCart();

                /** Remove from Cart */
                this._eventRemoveCartItem('.btn-remove'); //mini cart
                this._eventUpdateCartItem('#shopping-cart-table') //shopping cart
                this._emptyCheckoutCart('#shopping-cart-table');

                this._attemptedCheckout.init();

                /** checkout page */
                this._eventCheckoutPage.init('#checkoutSteps');

                /** Success Page */
                //this.__eventCheckoutSuccessPage('purchase');
                this.__eventCheckoutSuccessPage('Purchased');


            },
            '__eventYmmSelector' : function (finderForm) {

                let $finderForm = $(finderForm);
                if($finderForm.length) {
                    let finderYear = $finderForm.find('#finder-year');
                    let finderMake = $finderForm.find('#finder-make');
                    let finderModel = $finderForm.find('#finder-model');

                    finderModel.on('change', function () {
                        dataLayer.push({
                            'event': 'YMM Selected',
                            'Year' : finderYear.find('option:selected').text(),
                            'Make' : finderMake.find('option:selected').text(),
                            'Model' : finderModel.find('option:selected').text()
                        });
                    })
                }

            },
            '__eventCompatibilityCheckerInvoke' : function (checkerElem) {

                let $checkerElem = $(checkerElem);
                if($checkerElem.length){

                    let $openHandle = $checkerElem.find('.open-form');
                    $openHandle.on('click', function () {
                        dataLayer.push({
                            'event': 'Compatibility Check Invocation'
                        });
                    });
                }
            },
            '__eventCompatibilityChecker' : function (finderForm) {

                let $finderForm = $(finderForm);
                if($finderForm.length) {
                    let finderYear = $finderForm.find('#vyear');
                    let finderMake = $finderForm.find('#vmake');
                    let finderModel = $finderForm.find('#vmodel');

                    $finderForm.find('form').on('submit', function () {
                        dataLayer.push({
                            'event': 'Compatibility Submission',
                            'Year' : finderYear.find('option:selected').text(),
                            'Make' : finderMake.find('option:selected').text(),
                            'Model' : finderModel.find('option:selected').text()
                        });
                    });
                }
            },
            '_impression': function(impWrapper){
                var $parent = app.googleAnalytics;
                var $impWarapper = $(impWrapper);
                if($impWarapper.length){

                    var $impressionObj = [];
                    $impWarapper.each(function(index){
                        let $this = $(this);
                        let data = {};
                        let productObject = $this.data(app.googleAnalytics.dataLayerVariable);

                        data.name = productObject.name;
                        data.category = productObject.category;
                        data.brand = productObject.brand;
                        data.id = productObject.id;
                        data.price = productObject.price.replace(/\,/g,'');
                        data.list = productObject.list;
                        data.position = productObject.position;

                        $impressionObj.push(data);
                    });
                    $parent.__eventImpression($impressionObj);
                }
            },
            '_productClick': function (elem) {
                let $parent = app.googleAnalytics;
                let elemWrapper = $(elem);
                if(elemWrapper.length){
                    elemWrapper.find('a').on('click', function(e){
                        e.preventDefault();
                        let $this = $(this);
                        let obj = $this.closest(elem).data(app.googleAnalytics.dataLayerVariable);
                        $parent.__eventProductClick(obj);
                    })
                }

            },
            '_productDetails' : function (){
                if(typeof productDetails !== 'undefined'){
                    let $parent = app.googleAnalytics;
                    let pDetails = productDetails;
                    pDetails.make = productMake;
                    $parent.__eventProductDetails(pDetails);
                }

            },
            '_productView' : function (){
                if(typeof productDetails !== 'undefined'){
                    let $parent = app.googleAnalytics;
                    let pView = productDetails;
                    pView.make = productMake;
                    $parent.__eventProductView(pView);
                }

            },
            '_eventAddToCart' : function () {
                var addToCartButton = $('.btn-cart');
                if(typeof productDetails !== 'undefined' && addToCartButton.length) {
                    addToCartButton.attr('onclick',null);
                    let qty = $('#qty');
                    let cartFormAction = addToCartButton.closest('#product_addtocart_form');
                    addToCartButton.on('click', function (e) {
                        e.preventDefault();

                        if(window.ga && ga.loaded) {
                            dataLayer.push({
                                'event': 'addToCart',
                                'currencyCode': app.googleAnalytics.currencyCode,
                                'ecommerce': {
                                    'add': {
                                        'products': [{
                                            'name': productDetails.name,
                                            'category': productDetails.category,
                                            'brand': productDetails.brand,
                                            'id': productDetails.id,
                                            'price': productDetails.price.replace(/\,/g, ''),
                                            'quantity': qty.val(),
                                            'list': productDetails.list
                                        }]
                                    }
                                },
                                'eventCallback': function () {
                                    cartFormAction.submit();
                                }
                            });
                        }else {
                            cartFormAction.submit();
                        }

                    });
                }
            },
            '_eventRemoveCartItem' : function(deleteButton) {
                var deletebuttonLink = $(deleteButton);
                if(deletebuttonLink.length){
                    deletebuttonLink.attr('onclick',null);
                    deletebuttonLink.on('click', function (e){
                        e.preventDefault();
                        let $this = $(this);
                        let cartItem = $this.closest('li.item').data('cart-item');
                        let deleteAction = $this.attr('href');
                        var confirmAction = confirm('Are you sure you would like to remove this item from the shopping cart?');
                        if(confirmAction == true) {

                            if(window.ga && ga.loaded) {
                                dataLayer.push({
                                    'event': 'removeFromCart',
                                    'ecommerce': {
                                        'remove': {
                                            'products': [{
                                                'name': cartItem.name,
                                                'id': cartItem.id,
                                                'price': cartItem.price.replace(/\,/g, ''),
                                                'brand': cartItem.brand,
                                                'category': cartItem.category,
                                                'quantity': cartItem.quantity,
                                                'list': cartItem.list
                                            }]
                                        }
                                    },
                                    'eventCallback': function () {
                                        document.location = deleteAction;
                                    }
                                });
                            }else {
                                document.location = deleteAction;
                            }
                        }
                    });
                }
            },
            '_eventUpdateCartItem' : function(cartWrapper) {
                var cartWrapper = $(cartWrapper);
                if(cartWrapper.length) {
                    let buttonUpdate = cartWrapper.find('.btn-update');
                    let cartForm = cartWrapper.closest('form');
                    buttonUpdate.on('click', function(e){
                        e.preventDefault();
                        let cartItemElem = cartWrapper.find('.ga-cart-page');
                        let gaCartItems = [];
                        cartItemElem.each(function () {
                            let $this = $(this);
                            let qty = $this.find('.input-text.qty').val();
                            if(qty < 1){
                                let dataCartItem = $this.data('cart-item');
                                let gaCartItem = {};

                                gaCartItem.name = dataCartItem.name;
                                gaCartItem.id = dataCartItem.id;
                                gaCartItem.price = dataCartItem.price.replace(/\,/g,'');
                                gaCartItem.brand = dataCartItem.brand;
                                gaCartItem.category = dataCartItem.category;
                                gaCartItem.quantity = qty;
                                gaCartItem.list = dataCartItem.list;

                                gaCartItems.push(gaCartItem);
                            }
                        });

                        if(gaCartItems.length){

                            if(window.ga && ga.loaded) {
                                dataLayer.push({
                                    'event': 'removeFromCart',
                                    'ecommerce': {
                                        'remove': {
                                            'products': gaCartItems
                                        }
                                    },
                                    'eventCallback': function () {
                                        cartForm.submit();
                                    }
                                });
                            }else {
                                cartForm.submit();
                            }

                        }else{
                            cartForm.submit();
                        }

                    });
                }
            },
            '_emptyCheckoutCart' : function (cartWrapper) {

                var cartWrapper = $(cartWrapper);
                var clearButton = $('#empty_cart_button');
                if(cartWrapper.length) {
                    let cartForm = cartWrapper.closest('form');
                    clearButton.on('click', function(e) {
                        e.preventDefault();

                        let cartItemElem = cartWrapper.find('.ga-cart-page');
                        let gaCartItems = [];
                        cartItemElem.each(function () {
                            let $this = $(this);
                            let qty = $this.find('.input-text.qty').val();
                            let dataCartItem = $this.data('cart-item');
                            let gaCartItem = {};

                            gaCartItem.name = dataCartItem.name;
                            gaCartItem.id = dataCartItem.id;
                            gaCartItem.price = dataCartItem.price.replace(/\,/g,'');
                            gaCartItem.brand = dataCartItem.brand;
                            gaCartItem.category = dataCartItem.category;
                            gaCartItem.quantity = 0;
                            gaCartItem.list = dataCartItem.list;

                            gaCartItems.push(gaCartItem);
                        });

                        if(window.ga && ga.loaded) {
                            dataLayer.push({
                                'event': 'removeFromCart',
                                'ecommerce': {
                                    'remove': {
                                        'products': gaCartItems
                                    }
                                },
                                'eventCallback': function () {
                                    cartForm.append('<input type="hidden" name="update_cart_action" value="empty_cart" /> ');
                                    cartForm.submit();
                                }
                            });
                        }else {
                            cartForm.append('<input type="hidden" name="update_cart_action" value="empty_cart" /> ');
                            cartForm.submit();
                        }
                    });
                }
            },
            '_eventCheckoutPage' : {
                'init' : function (checkoutWrapper) {
                    var $checkoutWrapper = $(checkoutWrapper);
                    if($checkoutWrapper.length) {

                        if(typeof checkoutItems !== 'undefined') {
                            this._eventCheckoutMethod();
                            this._eventBillingMethod('#co-billing-form');
                            this._shippingInformation.init('#co-shipping-form');
                            this._eventShippingMethod('#co-shipping-method-form');
                            this._eventPaymentMethod('#checkout-step-payment');
                        }
                    }
                },
                '_eventCheckoutMethod' : function() {
                    dataLayer.push({
                        'event': 'checkout',
                        'ecommerce': {
                            'checkout': {
                                'actionField': {'step': 1}, //checkout Method
                                'products': checkoutItems
                            }
                        }
                    });
                },
                '_eventBillingMethod' : function (billingForm) {
                    let $billingForm = $(billingForm);
                    let continueButton = $billingForm.find('button');
                    let root = this;
                    continueButton.on('click', function (){
                        dataLayer.push({
                            'event': 'checkout',
                            'ecommerce': {
                                'checkout': {
                                    'actionField': {'step': 2}, //Billing Method
                                    'products': checkoutItems
                                }
                            },
                            'eventCallback': function() {
                                var useBillingForShipping = $("input[name='billing[use_for_shipping]']:checked").val();
                                if(useBillingForShipping == 1){
                                    root._shippingInformation.__eventShippingMethod();
                                }
                            }
                        });
                    });
                },
                '_shippingInformation' : {
                    'init' : function (shippingForm) {
                        let $shippingForm = $(shippingForm);
                        let continueButton = $shippingForm.find('button');
                        let root = this;
                        continueButton.on('click', function () {
                            root.__eventShippingMethod();
                        })
                    },
                    '__eventShippingMethod' : function() {
                        dataLayer.push({
                            'event': 'checkout',
                            'ecommerce': {
                                'checkout': {
                                    'actionField': {'step': 3}, //Shipping Information
                                    'products': checkoutItems
                                }
                            }
                        });
                    }
                },
                '_eventShippingMethod' : function (shippingMethodForm) {
                    let $shippingMethodForm = $(shippingMethodForm);
                    let continueButton = $shippingMethodForm.find('button');
                    continueButton.on('click', function () {
                        let shippingMethodOption = $("input[name='shipping_method']:checked").val();
                        if(shippingMethodOption){
                            dataLayer.push({
                                'event': 'checkout',
                                'ecommerce': {
                                    'checkout': {
                                        'actionField': {'step': 4, 'option' : shippingMethodOption}, //Shipping Method
                                        'products': checkoutItems
                                    }
                                }
                            });
                        }
                    });
                },
                '_eventPaymentMethod' : function (paymentMethod) {
                    let $paymentMethod = $(paymentMethod);
                    let continueButton = $paymentMethod.find('button');
                    continueButton.on('click', function () {
                        let paymentMethodOption = $("input[name='payment[method]']:checked").val();
                        if(paymentMethodOption){
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
                    });
                },
                '_eventReviewMethod' : function (reviewMethodWrapper) {

                    dataLayer.push({
                        'event': 'checkout',
                        'ecommerce': {
                            'checkout': {
                                'actionField': {'step': 6}, //Review order
                                'products': checkoutItems
                            }
                        }
                    });
                }
            },
            '__eventSearchTerms' : function () {

                var searchForm = $('#search_mini_form');

                if(searchForm.length) {
                    searchForm.one('submit', function (e) {
                        e.preventDefault();
                        var $this = $(this);
                        var searchField = $this.find('#search');

                        if(searchField.val() !== '') {
                            dataLayer.push({
                                'event': 'Searched',
                                'search_term' : searchField.val()
                            });
                        }
                        $this.submit();
                    })
                }

                var searchAutocomplete = searchForm.find('#search_autocomplete');
                searchAutocomplete.on('click', function () {
                    var searchField = searchForm.find('#search');
                    dataLayer.push({
                        'event': 'Searched',
                        'search_term' : searchField.val()
                    });
                });


            },
            '__eventAddedToCart' : function () {
                if(typeof recentAddedItem !== 'undefined') {
                    dataLayer.push({
                        'event': 'addedToCart',
                        'ecommerce': {
                            'add': {
                                'products': recentAddedItem
                            }
                        }
                    });
                }
            },
            '_attemptedCheckout' :  {
                'init' : function () {
                    let rootGa = app.googleAnalytics;

                    /** Mini cart proceed checkout */
                    let miniContinueButton = $('.mini-proceed-checkout');
                    if(miniContinueButton.length) {
                        miniContinueButton.on('click', function (e) {
                            let $this = $(this);
                            e.preventDefault();
                            rootGa.__eventAttemptedCheckout($this.attr('href'));
                        });
                    }

                    /**  primary proceed checkout */
                    let btnProceedCheckout = $('.btn-proceed-checkout');
                    if(btnProceedCheckout.length) {
                        btnProceedCheckout.attr('onClick',null);
                        btnProceedCheckout.on('click', function (e) {
                            e.preventDefault();
                            rootGa.__eventAttemptedCheckout(btnProceedCheckout.data('checkout-url'));
                        })
                    }

                    /** Paypal checkout link */
                    let paypalCheckoutLink = $('.checkout-types');
                    if(paypalCheckoutLink.length) {
                        paypalCheckoutLink.find('a').on('click', function (e) {
                            e.preventDefault();
                            let $this = $(this);
                            rootGa.__eventAttemptedCheckout($this.attr('href'));
                        });

                    }
                }
            },
            '__eventAttemptedCheckout' : function ($redirectUrl) {

                if(typeof cartItems !== 'undefined') {

                    if(window.ga && ga.loaded) {
                        dataLayer.push({
                            'event': 'AttemptedCheckout',
                            'ecommerce': {
                                'products': cartItems
                            },
                            'eventCallback': function () {
                                window.location = $redirectUrl;
                            }
                        });
                    }else {
                        window.location = $redirectUrl;
                    }
                }
            },
            '__eventCheckoutSuccessPage' : function (eventType) {

                if(typeof purchaseOrder !== 'undefined') {
                    dataLayer.push({
                        'event': eventType,
                        'ecommerce': {
                            'purchase': {
                                'actionField': purchaseOrder.info,
                                'products': purchaseOrder.items
                            }
                        }
                    });
                }
            },
            '__eventImpression' :  function($objImpressions) {
                dataLayer.push({
                    'event': 'impressions',
                    'ecommerce': {
                        'currencyCode': app.googleAnalytics.currencyCode,                       // Local currency is optional.
                        'impressions': $objImpressions
                    }
                });
            },
            '__eventProductClick' : function($obj) {
                dataLayer.push({
                    'event': 'productClick',
                    'ecommerce': {
                        'click': {
                            'actionField': {'list': $obj.list},
                            'products': [{
                                'name': $obj.name,
                                'category': $obj.category,
                                'brand': $obj.brand,
                                'id': $obj.id,
                                'price': $obj.price.replace(/\,/g,''),
                                'position': $obj.position
                            }]
                        }
                    },
                    'eventCallback': function() {
                        document.location = $obj.url
                    }
                });
                document.location = $obj.url;
            },
            '__eventProductDetails' : function(pdpObject) {
                dataLayer.push({
                    'event': 'detail',
                    'ecommerce': {
                        'detail': {
                            'actionField': {'list': pdpObject.list},
                            'products': [{
                                'name': pdpObject.name,
                                'category': pdpObject.category,
                                'brand': pdpObject.brand,
                                'id': pdpObject.id,
                                'price': pdpObject.price.replace(/\,/g,'')
                            }]
                        }
                    }
                });
            },
            '__eventProductView' : function(pdpObject) {
                dataLayer.push({
                    'event': 'ViewedProduct',
                    'ecommerce': {
                        'products': [{
                            'name': pdpObject.name,
                            'category': pdpObject.category,
                            'brand': pdpObject.brand,
                            'id': pdpObject.id,
                            'price': pdpObject.price.replace(/\,/g,'')
                        }]
                    }
                });
            },
        },
        'adjustFooterContainer' : function (mainContainer) {

            let $mainContainer = $(mainContainer);
            $mainContainer.css('height', $(window).height() - 370 +'px');
        },
        'mobileHideMinicart' : function (minicart) {

            let $minicart = $(minicart);

            if($minicart.length) {

                $('.cart-info').on('click',function(e) {
                    $($minicart).toggle();
                    e.stopPropagation();
                });

                $(document).on('click', function (e) {
                    if($minicart.is(":visible")) {
                        if ($(e.target).closest($minicart).length === 0) {
                            $($minicart).hide();
                            e.stopPropagation();
                        }
                    }
                });
            }

        },
        'showMorePartNames' : function (partNameWrapper) {
           
            let $partNameWrapper = $(partNameWrapper);
            if($partNameWrapper.length) {
                let limit = $partNameWrapper.data('limit');
                let totalItems = $partNameWrapper.data('total-item');
                let $showPerPage = $partNameWrapper.data('show-per-page');
                let $items = $partNameWrapper.find('.item');
                if($items.length > limit) {
                    let showMore = $('.show-more');
                    let showLess = $('.show-less');
                    showLess.hide();
                    var updateItemListing = function(action) {
                        let totalVisible = $items.has(':visible').length;
                        if(action == 'more') {
                            $items.slice(0, totalVisible + $showPerPage).show();
                            showLess.show();
                        }else {
                            let lessResult = totalVisible - $showPerPage;
                            if((totalVisible - $showPerPage) < limit) {
                                lessResult =  limit;
                            }
                            $items.slice(lessResult, totalItems).hide();
                        }

                        if($items.has(':visible').length >= totalItems){
                            showMore.hide();
                        }else {
                            showMore.show();
                        }
                        if($items.has(':visible').length <= limit) {
                            showLess.hide();
                        }
                    };

                    showMore.on('click', function() {
                        updateItemListing('more');
                    });

                    showLess.on('click', function() {
                        updateItemListing('less');
                    });

                }
            }

        },
        'showMoreAttributeOptions' : function(actionElem){

            var $actionElem  = $(actionElem);
                if($actionElem.length) {
                    $actionElem.on('click', function(){

                        let $this = $(this);
                        let parentList = $this.closest('.custom-filter');
                        let customOptions = parentList.find('.more-items');

                        if(parentList.find('.more-items.hide').length) {
                            customOptions.removeClass('hide');
                            $this.text('Show Less');
                        }else {
                            customOptions.addClass('hide');
                            $this.text('Show More');
                        }

                    });
                }
        },
        'partnameAccordion' : function(pageClass) {

            var $pageClass = $(pageClass);
            var owl = $pageClass.find('.cat-list');

            owl.owlCarousel({
                margin: 10,
                pagination: false,
                itemsDesktop: [1499, 5],
                itemsDesktopSmall: [1199, 5],
                itemsTablet: [899, 2],
                itemsMobile: [599, 1],
                navigation: true,
                navigationText: ['<span class="fa-stack"><i class="fa fa-caret-left"></i></span>', '<span class="fa-stack"></i><i class="fa fa-caret-right"></i></span>'],

            });

            if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                // some code..
                var owl = $pageClass.find('.onerow');
                owl.owlCarousel({
                    margin: 10,
                    pagination: false,
                    itemsDesktop: [1499, 5],
                    itemsDesktopSmall: [1199, 5],
                    itemsTablet: [899, 2],
                    itemsMobile: [599, 1],
                    navigation: true,
                    navigationText: ['<span class="fa-stack"><i class="fa fa-caret-left"></i></span>', '<span class="fa-stack"></i><i class="fa fa-caret-right"></i></span>'],
                });
            }
        },
        'ymmInterlinking' : function (elem) {

            let elemWrapper = $(elem);
            let ymmInterlinkContainer = elemWrapper.find('.product-list-container');
            if(elemWrapper.length) {
                let ymm = elemWrapper.data('ymm');
                let requestUrl = elemWrapper.data('request-url');
                let formKey = elemWrapper.data('form-key');

                let request = $.ajax({
                    url: requestUrl,
                    type: 'GET',
                    data:{form_key:formKey, year: ymm.year, make: ymm.make, model: ymm.model},
                    success: function(result) {

                        if(result){
                            $.each(result.interlink, function (index, data) {
                                ymmInterlinkContainer.append("<div class=\"product-noimage-container col-sm-4 col-xs-12\">\n" +
                                    "                        <a href=\"" + data.link + "\">" + data.label + "<span class=\"arrow\"><i class=\"fa fa-angle-right\"></i></span>\n" +
                                    "                        </a>\n" +
                                    "                    </div>");
                            });

                            elemWrapper.find('.interlink-title').html('Other ' + result.title + ' Years');
                        }
                    }
                });

            }
        },
        'vehicleForm': function (handle, elemWrapper) {
            var $elemWrapper = $(elemWrapper);
            var $handle = $(handle);
            if($elemWrapper.length){
                $handle.on('click', function (e) {
                    e.preventDefault();
                    $.fancybox($elemWrapper,{
                        padding: 0,
                        wrapCSS : "vehicle-checker-box",
                        helpers : {
                            overlay : {
                                css : {
                                    'background' : 'rgba(15, 25, 25, 0.5)'
                                }
                            }
                        },
                        beforeShow   : function() {
                            app.vehicleChecker._checkCompatibilityButton();
                        }

                    });
                });
            }
        },
        'vehicleChecker': {
            'vehicleResult': {},
            '_checkCompatibilityButton': function () {
                let compatibilityElem = $('.form-vehicle-checker');
                let selector = compatibilityElem.find('select.vselector');

                selector.each(function () {
                    var $this = $(this);
                    if($this.find('option:selected').val() === ""){
                        compatibilityElem.find('button').prop('disabled', true);
                        return;
                    }
                })
            },
            'init' :  function (elemWrapper) {

                var $parent = app.vehicleChecker;
                var compatibilityElem = $('#compatibility-checker');
                let $elemWrapper = $(elemWrapper);
                if ($elemWrapper.length) {
                    $('[data-toggle="tooltip"]').tooltip();
                    let vehicleform = $elemWrapper.find("form");
                    let requestUrl = vehicleform.attr('action');
                    let formKey = vehicleform.find('#formKey').val();
                    let vehicleSelector = vehicleform.find('select.vselector');
                    let nextSelector = "";

                    var data = {};
                    data.form_key = formKey;
                    data.product_id = vehicleform.find('#productId').val();
                    vehicleSelector.on('change', function(){
                        let $this = $(this);
                        let selectedLabel = $this.find("option:selected").text();

                        vehicleSelector.each(function () {
                            let $this = $(this);
                            data[$this.attr('name')] = $this.find('option:selected').val();
                        });

                        switch($this.attr('id')) {
                            case 'vyear':
                                data.method  = 'make';
                                nextSelector = vehicleform.find('#vmake');
                                vehicleform.find('#vmodel').html(new Option('Select Model', ''));
                                $parent._sendRequest(requestUrl,data,nextSelector)
                                    .done(function (res) {
                                        nextSelector.html(new Option('Select Make', ''));
                                        $.each(res, function (i, data) {
                                            nextSelector.append(new Option(data.label, data.id));
                                        });
                                    });
                                break;
                            case 'vmake':
                                data.method  = 'model';
                                nextSelector = vehicleform.find('#vmodel');
                                $parent._sendRequest(requestUrl,data,nextSelector)
                                    .done(function (res) {
                                        nextSelector.html(new Option('Select Model', ''));
                                        $.each(res, function (i, data) {
                                            nextSelector.append(new Option(data.label, data.id));
                                        });
                                    });

                                break;
                            case 'vmodel':
                                break;
                        }

                        if(vehicleform.find('#vmodel option:selected').val()){
                            vehicleform.find('button').prop('disabled', false);
                        }else{
                            vehicleform.find('button').prop('disabled', true);
                        }
                    });

                    vehicleform.on('submit',function (e) {
                        e.preventDefault();
                        vehicleSelector.each(function () {
                            let $this = $(this);
                            data[$this.attr('name')] = $this.find('option:selected').val();
                        });
                        $parent._getFitmentResult(data,requestUrl,compatibilityElem);
                    });

                    let fitmentData = compatibilityElem.data('fitment');
                    if(fitmentData){
                        let combineData = $.extend(data, fitmentData);
                        $(window).on('load', function() {
                            $parent._getFitmentResult(combineData,requestUrl,compatibilityElem);
                        });
                    }

                }
            },
            '_sendRequest': function (requestUrl, data, nextSelector) {

                let labelLoading = 'Loading...';
                let request =  $.ajax({
                    url: requestUrl,
                    data:data,
                    method: 'GET',
                    dataType: 'json',
                    beforeSend: function() {
                        if(nextSelector){
                            nextSelector.empty();
                            nextSelector.append(new Option(labelLoading, ''));
                        }
                    }
                });

                return request;
            },
            '_getFitmentResult' : function (data, requestUrl, compatibilityElem) {
                var $this = this;
                var loader = compatibilityElem.find('.loader');
                var innerContent = compatibilityElem.find('.inner-content');

                var headerYmmContainer = $('.ymm-form-container');
                var hYear = headerYmmContainer.find('#finder-year');
                var hMake = headerYmmContainer.find('#finder-make');
                var hModel = headerYmmContainer.find('#finder-model');
                var CompatibilityChecker = $('.form-vehicle-checker');
                var ymmResult = headerYmmContainer.find('.ymm-result');
                var ymmWrapper = headerYmmContainer.find('.ymm-wrapper');

                var newObject = $.extend($this.vehicleResult, data);
                let request =  $.ajax({
                    url: requestUrl + '/index/result',
                    data:newObject,
                    method: 'GET',
                    dataType: 'json',
                    beforeSend: function() {
                        loader.show();
                        innerContent.hide();
                        compatibilityElem.removeClass();
                        $.fancybox.close();
                    },
                }).done(function (res) {
                    loader.hide();
                    innerContent.show();

                    let ymmLabel = "<b>" + res.result.ymm + "</b>";
                    if(res.result.total >= 1){
                        compatibilityElem.addClass('vehicle-fit');
                        innerContent.find('.info').find('span').html("This part fits your vehicle!");
                        innerContent.find('.alternative').html("<a href='" + res.result.alternative +"'>Show Other Compatible Parts</a>");
                    }else{
                        compatibilityElem.addClass('vehicle-unfit');
                        innerContent.find('.info').find('span').html("This part won't fit your vehicle");
                        innerContent.find('.alternative').html("<a href='" + res.result.alternative +"'>See applicable parts for your model</a>");
                    }
                    innerContent.find('.open-form').html("Change Vehicle")

                    let formYear = CompatibilityChecker.find('#vyear');
                    let formMake = CompatibilityChecker.find('#vmake');
                    let formModel = CompatibilityChecker.find('#vmodel');

                    ymmResult.removeClass('hide');
                    ymmResult.find('.ymm-selection').html(ymmLabel);
                    ymmWrapper.addClass('hide');


                    hYear.html(formYear.html());
                    hMake.html(formMake.html());
                    hModel.html(formModel.html());
                    hYear.find('option[value="' + data.year+ '"]').prop('selected', true);
                    hMake.find('option[value="' + data.make+ '"]').prop('selected', true);
                    hModel.find('option[value="' + data.model+ '"]').prop('selected', true);
                    hModel.find('option[value="' + data.model+ '"]').attr('data-label',formModel.find('option:selected').text());

                });
            }
        },
        'headerTopBanner': function (bannerElem) {
            let $bannerElem = $(bannerElem);
            if($bannerElem.length){
                if (window.sessionStorage.getItem('is_banner_hidden')) {
                    $bannerElem.hide();
                }else{
                    $bannerElem.fadeIn();
                }

                let closeAction = $bannerElem.find('a');
                closeAction.on('click',function () {
                    window.sessionStorage.setItem('is_banner_hidden', true);
                    $bannerElem.fadeOut(500);
                });

            }
        },
        'yotpoEmptyReview': function () {

            let yotpoHtml = ' <div class="yotpo-bottomline pull-left  star-clickable">\n' +
                '       <span class="yotpo-stars">\n' +
                '         <span class="yotpo-icon yotpo-icon-empty-star pull-left"></span>\n' +
                '         <span class="yotpo-icon yotpo-icon-empty-star pull-left"></span>\n' +
                '         <span class="yotpo-icon yotpo-icon-empty-star pull-left"></span>\n' +
                '         <span class="yotpo-icon yotpo-icon-empty-star pull-left"></span>\n' +
                '         <span class="yotpo-icon yotpo-icon-empty-star pull-left"></span> </span>\n' +
                '        <div class="yotpo-clr"></div> </div>';
            $(window).load(function(){
                var yotpoContainer = $('.yotpo-review, .small-review');
                if(yotpoContainer.length){
                    setTimeout(function() {
                        yotpoContainer.each(function (index) {
                            let $this = $(this);
                            let yotpoMain = $this.find('.yotpo');
                            let hasStar = yotpoMain.find('.yotpo-stars');
                            if(!hasStar.length){
                                yotpoMain.html(yotpoHtml);
                            }
                        })
                    }, 1500);
                }
            });


        },
        'init': function () {
            app.bannerUpdateHeight('#featured-product','.card-sidebar');
            app.newsletterSubscribe('#newsletterForm');
            app.featuredProduct('.featured-product-wrapper');
            app.googleAnalytics.init();
            app.tabletMenuToggle('.tablet-menu-tab','.tablet-menu-content');
            app.mobileHideMinicart('.topCartContent');
            app.adjustFooterContainer('.cms-index-noroute .main-container');
            app.showMorePartNames('.category-partname');
            app.showMoreAttributeOptions('.action-show-hide');
            app.partnameAccordion('.hautopart-model-ymm');
            app.ymmInterlinking('#ymm-interlink');

            app.vehicleForm('.open-form','.form-vehicle-checker');
            app.vehicleChecker.init('.form-vehicle-checker');
            app.headerTopBanner('.global-header-notice');
            app.yotpoEmptyReview();
        }
    };

    app.init();

    vipAppComponent = app;

})(jQuery);