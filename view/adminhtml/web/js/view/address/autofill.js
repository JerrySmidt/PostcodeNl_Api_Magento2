define([
    'uiCollection',
    'uiRegistry',
], function (Collection, Registry) {
    'use strict';

    return Collection.extend({

        defaults: {
            container: null,
            inputs: null,
            error: '',
            listens: {
                visible: 'onVisible',
                countryCode: 'onChangeCountry',
            },
        },

        initialize: function () {
            this._super();

            this.container = document.getElementById(`order-${this.addressType}_address_fields`);
            this.inputs = {
                country: document.getElementById(this.htmlIdPrefix + 'country_id'),
                street: this.container.querySelectorAll('.field-street .input-text'),
                postcode: document.getElementById(this.htmlIdPrefix + 'postcode'),
                city: document.getElementById(this.htmlIdPrefix + 'city'),
                region: document.getElementById(this.htmlIdPrefix + 'region'),
                regionId: document.getElementById(this.htmlIdPrefix + 'region_id'),
            };

            this.inputs.country.addEventListener('change', (e) => {
                this.countryCode(e.target.value);
            });

            return this;
        },

        initElement: function (elem) {
            if (this.addressType === 'shipping') {
                this.delegate('disabled', document.forms.edit_form.shipping_same_as_billing.checked);
            }
        },

        initObservable: function () {
            this._super();
            this.observe('countryCode error visible');
            return this;
        },

        onVisible: function (isVisible) {
            this.container.querySelector('.field-' + this.name).classList.toggle('hidden', !isVisible);
        },

        onChangeCountry: function (countryCode) {
            this.visible(this.settings.enabled_countries.includes(countryCode));

            // Update shipping component if same as billing.
            if (document.forms.edit_form.shipping_same_as_billing.checked && this.addressType === 'billing') {
                Registry.get('shipping_address_autofill').set('countryCode', countryCode);
            }
        },

    });
});
