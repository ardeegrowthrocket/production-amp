document.observe('firecheckout:paymentMethod:addObserversAfter', function () {
    $$('#nps_cc_type, #nps_installment').each(function (el) {
        el.observe('change', function () {
            checkout.updateSections('review');
        });
    });
});

document.observe('firecheckout:setResponseAfter', function(e) {
    if (payment.getCurrentMethod() === 'nps' &&
        e.memo.url === checkout.urls.shopping_cart) {

        checkout.updateSections('review');
    }
});
