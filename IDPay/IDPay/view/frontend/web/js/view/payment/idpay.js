define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';
        rendererList.push(
            {
                type: 'idpay',
                component: 'IDPay_IDPay/js/view/payment/method-renderer/idpay-method'
            }
        );
        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: true
            },
            afterPlaceOrder: function (data, event) {
                window.location.replace('idpay/redirect/index');
            }
        });
    }
);
