function automaticRoundAmount(checkbox, totalUpdate) {
    var status = 0,
        params = {};

    if (checkbox && checkbox.checked) {
        status = 1;
    }

    if (totalUpdate) {
        $('removeDonationLink').show();
        donation = $('donation_value').value;
        if ($('donation_charity')) {
            charity = $('donation_charity').value;
        } else {
            charity = false;
        }
        params = {
            donation: donation,
            charity: charity,
            status: 1
        };
    } else {
        if (status > 0) {
            $('donation_value').value = checkbox.value;
            donations.changeCharity(donations.currentCharity);
        } else {
            $('donation_value').value = 0;
        }
        params = {
            donation: checkbox.value,
            charity: donations.currentCharity,
            status: status
        };
    }

    var url = checkout.urls.billing_address
        .replace(
            'firecheckout/index/saveBilling',
            'donations/cart/roundamount'
        );

    checkout.update(url, params, function () {
        checkout.updateSections('review');
    });
}
