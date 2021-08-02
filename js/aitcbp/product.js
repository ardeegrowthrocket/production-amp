
/**
 * Cost Based Pricing
 *
 * @category:    Aitoc
 * @package:     Aitoc_Aitcbp
 * @version      1.2.5
 * @license:     n/a
 * @copyright:   Copyright (c) 2015 AITOC, Inc. (http://www.aitoc.com)
 */
Event.observe(window, 'load', function() {
	aitcbpHandleEnabling();
	Event.observe('cbp_group', 'change', aitcbpHandleEnabling);
	Event.observe('cost', 'change', aitcbpHandleEnabling);
});

function aitcbpHandleEnabling()
{
	var group = $('cbp_group').value;
	var showPrice = $('show_price_cbp_group');
	if (group == '0') {
		showPrice.value = '';
		showPrice.hide();
	}
	else {
		type = cbpTypes[group];
		amount = cbpAmounts[group];
		cost = $('cost').value * 1;
		if (!cost) {
			showPrice.value = '';
			showPrice.hide();
			return;
		}
		showPrice.show();
		tierPriceControl.itemsCount;
		
		var newPrice = 0.0;
		switch (type)
		{
			// fixed
			case 1:
				newPrice = (cost + amount).toFixed(2); 
				break;
			
			// percent
			case 2:
				newPrice = (cost * (100 + amount) / 100).toFixed(2); 
				break;
		}

		var tierPrices = '';
		var originalPrice = $('price').value;

		if (originalPrice) {
            var isBundle = false;
        }

        if ($('price_type') != null) {
            isBundle = $('price_type').value;
        }

        for (i=0; i<tierPriceControl.itemsCount; i++) {
            var qty = $('tier_price_row_' + i + '_qty').value;

            if (!qty) {
                continue;
            }

            var price = $('tier_price_row_' + i + '_price').value;

            if (!price) {
                continue;
            }

            var percent = price / originalPrice;

            if (isBundle) {
                percent = (100 - price) / 100;
            }

            if (cpbRound) {
                tierPrices += formatTierPrice(qty, Math.round(newPrice * percent).toFixed(2));
            } else {
                tierPrices += formatTierPrice(qty, (newPrice * percent).toFixed(2));
            }

        }

        if (cpbRound) {
            newPrice = Math.round(newPrice);
        }
		showPrice.update(formatCbpPrice(newPrice, tierPrices));
	}
}

function formatCbpPrice(price, tierPrices)
{
	comment = '' + cbpLabel + ': ' + price + '<br />';
	if (tierPrices) comment += '' + cbpTierLabel + ':<br />' + tierPrices;
	comment += '<b>' + currencyCode + '</b><br />';
	return comment; 
}

function formatTierPrice(qty, price)
{
	return qty + ' - ' + price + '<br />';
}