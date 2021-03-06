var FC = FC || {};
FC.OrderReview = Class.create();
FC.OrderReview.prototype = {
    skip: {
        'billing-address': {
            'crafty_postcode_lookup_result_option1': 1,
            'billing[is_business_address]': 1
        },
        'shipping-address': {
            'billing[email]': 1,
            'crafty_postcode_lookup_result_option1': 1,
            'crafty_postcode_lookup_result_option2': 1,
            'billing[is_business_address]': 1
        }
    },

    initialize: function(options) {
        var self = this;

        this.options   = options;
        this.container = new Element('div');
        this.container.writeAttribute('id', 'addresses-review');
        this.container.writeAttribute('class', 'col2-set');

        var i = 0,
            html = '';
        ['billing-address', 'shipping-address', 'payment-method'].each(function (id) {
            if (!$(id)) {
                return;
            }

            html += "<div id='" + id + "-review' class='col-" + ((i++ % 2) + 1) + "'></div>";
        });
        html += '<div class="clearer"></div>';
        this.container.insert(html);

        $$('#agreements-wrapper, #checkout-review-load').each(function(el) {
            $(el).insert({
                before: self.container
            });
            throw $break;
        });

        this.update('billing-address');
        this.update('shipping-address');
        this.update('payment-method');

        var selector = 'input:text, input:checkbox, input:radio, input:tel, select, textarea';

        $('billing-address') && $('billing-address').select(selector).each(function(el) {
            el.observe('change', function() {
                self.update('billing-address');
                if ($('shipping:same_as_billing') && $('shipping:same_as_billing').checked) {
                    self.update('billing-address', 'shipping-address');
                }
            });
        });

        $('shipping-address') && $('shipping-address').select(selector).each(function(el) {
            el.observe('change', function() {
                self.update('shipping-address');
            });
        });

        $$('[name="billing[use_for_shipping]"]').each(function(el) {
            el.observe('click', function() {
                if (this.value > 0) {
                    self.update('billing-address', 'shipping-address');
                } else {
                    self.update('shipping-address');
                }
            });

            if (el.value > 0) {
                self.update('billing-address', 'shipping-address');
            }
        });

        // craftyclicks postcode lookup
        $(document).observe('dom:loaded', function() {
            if (typeof _cp_instances !== 'undefined') {
                _cp_instances.each(function(instance) {
                    instance.populate_form_fields = instance.populate_form_fields.wrap(function(original, j) {
                        original(j);
                        self.update('billing-address');
                        if ($('shipping-address')) {
                            if ($('shipping:same_as_billing') && $('shipping:same_as_billing').checked) {
                                self.update('billing-address', 'shipping-address');
                            } else {
                                self.update('shipping-address');
                            }
                        }
                    });
                });
            }
        });
        // craftyclicks postcode lookup

        $(document).observe('change', function(e) {
            var el = e.element();
            if (el.name === 'payment[method]') {
                self.update.bind(self).defer('payment-method');
            }
        });

        $('payment-method').select('input:radio').each(function(el) {
            el.observe('click', function() {
                self.update.bind(self).defer('payment-method');
            });
        });
    },

    update: function(from, to) {
        to = to || from;
        var review = $(to + '-review');
        review && review.update(this.getContent(from, to));
    },

    getContent: function(from, to) {
        to = to || from;

        var html = '',
            self = this;

        if ('payment-method' === from) {
            if (payment.currentMethod) {
                var radio = $('p_method_' + payment.currentMethod);
                if (radio) {
                    var title = radio.readAttribute('title');
                    if (!title) {
                        var label = radio.up('dt').down('label');
                        title = label.innerHTML;
                    }
                    html += title;
                }
            }
        } else {
            var from = $(from);
            if (!from) {
                return '';
            }
            var addressSelect = from.down('.address-select');
            if (addressSelect) {
                var option = addressSelect.options[addressSelect.selectedIndex];
                if (option && option.value) {
                    return self.getTitle(to) + option.innerHTML.replace(/, /g, '<br/>');
                }
            }

            var processedFields = [];
            from.down('fieldset').select('li').each(function(li) {
                var br = '';
                li.select('input:text, input:tel, select, textarea').each(function(el) {
                    if (self.skip[to] && (self.skip[to][el.name] || self.skip[to][el.id])) {
                        return;
                    }
                    if (!el.visible() ||
                        !el.up().visible() ||
                        !el.getHeight() ||
                        ['hidden', 'checkbox', 'password'].indexOf(el.type) > -1) {

                        return;
                    }
                    if (!el.value.length || processedFields.indexOf(el.name) > -1) {
                        return;
                    }

                    if (el.tagName.toLowerCase() == 'select') {
                        var option = el.options[el.selectedIndex];
                        if (!option.value) {
                            return;
                        }
                        html += option.innerHTML + ' ';
                    } else {
                        html += el.value + ' ';
                    }

                    // skip if array, e.g. street
                    if (el.name.indexOf('[]') === -1) {
                        processedFields.push(el.name);
                    }

                    br = '</br>';
                });
                html += br;
            });
        }
        if (html.length) {
            html = self.getTitle(to) + html;
        }
        return html;
    },

    getTitle: function(block) {
        var title = $(block).select('> .block-title span'),
            i     = ('payment-method' === block ? 2 : 1);

        return '<strong>' + title[title.length - i].innerHTML + '</strong>'
            + ' <a href="javascript:void(0)" onclick="reviewInfo.editBlock(\'' + block + '\')">' + this.options.changeText + '</a>'
            + '<br/>';
    },

    editBlock: function(id) {
        var block = $(id),
            sameAsBilling = $('shipping:same_as_billing');

        if (-1 !== id.indexOf('address') && (!block || (sameAsBilling && sameAsBilling.checked))) {
            block = $('billing-address');
        }

        if (block) {
            FC.Utils.scrollTo(block, {
                afterFinish: function() {
                    block.highlight();
                }
            }, true);
        }
    }
};
