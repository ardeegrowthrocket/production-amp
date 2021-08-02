document.observe('firecheckout:saveBefore', function (e) {
    if (typeof OnecolumnCheckout !== 'undefined' &&
        !$$('.onecolumn')[0].hasClassName('expanded')
    ) {
        return;
    }

    if (payment.getCurrentMethod() !== 'upg_payments') {
        return;
    }

    if (e.memo.forceSave) {
        return;
    }

    e.memo.stopFurtherProcessing = true;

    payment.save();
});

document.observe('dom:loaded', function () {
    if (typeof OnecolumnCheckout === 'undefined') {
        return;
    }

    OnecolumnCheckout.saveStep = OnecolumnCheckout.saveStep.wrap(function (o, url, button, callback, params) {
        if ($$('.onecolumn')[0].hasClassName('expanded') ||
            url !== checkout.urls.payment_method ||
            payment.getCurrentMethod() !== 'upg_payments'
        ) {
            return o(url, button, callback, params);
        }

        // copied from upg/checkout/upg.phtml
        var arguments = {
            fancybox: {
                is_mobile: UpgMagentoJsIntegration.viewportWidth() <= 450,
                units: 'px',
                width: '0',
                height: '0',
                fit_to_width_desktop: true
            }
        };
        UpgMagentoJsIntegration.setPaymentCallback(function () {
            o(url, button, callback, params);
        });
        UpgMagentoJsIntegration.getPaymentIframe(arguments);
    });
});
