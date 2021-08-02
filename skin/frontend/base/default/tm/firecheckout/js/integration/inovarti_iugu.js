document.observe('dom:loaded', function () {
    if (review._save) {
        review._save = function () {
            payment.save();
        };
    }
});

document.observe('firecheckout:saveBefore', function (e) {
    if (e.memo.forceSave) {
        return;
    }

    if (payment.currentMethod.indexOf('iugu_') === -1) {
        return;
    }

    e.memo.stopFurtherProcessing = true;

    review.save(); // @see skin/frontend/base/default/iugu/js/checkout.js
});
