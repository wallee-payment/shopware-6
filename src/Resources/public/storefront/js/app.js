/* global window */
// noinspection ThisExpressionReferencesGlobalObjectJS
(function (window) {
    /**
     * WalleeCheckout
     * @type {
     *      {
     *          payment_method_handler_name: string,
     *          payment_method_iframe_class: string,
     *          init: init,
     *          validationCallBack: validationCallBack,
     *          payment_method_handler_status: string,
     *          submitPayment: (function(*): boolean),
     *          payment_method_iframe_prefix: string,
     *          payment_form_id: string,
     *          payment_method_handler_prefix: string,
     *          payment_method_tabs: string,
     *          getIframe: (function(): boolean
     *      }
     * }
     */
    const WalleeCheckout = {
        /**
         * Variables
         */
        payment_panel_id: 'wallee-payment-panel',
        payment_method_iframe_id: 'wallee-payment-iframe',
        payment_method_handler_name: 'wallee_payment_handler',
        payment_method_handler_status: 'input[name="wallee_payment_handler_validation_status"]',
        payment_form_id: 'confirmOrderForm',
        button_cancel_id: 'walleeOrderCancel',
        order_id: '',
        loader_id: 'walleeLoader',
        confirm_url: '/wallee/checkout/confirm?orderId=',
        pay_url: '/wallee/checkout/pay?orderId=',
        recreate_cart_url: '/wallee/checkout/recreate-cart?orderId=',
        handler: null,

        /**
         * Initialize plugin
         */
        init: function () {
            WalleeCheckout.activateLoader(true);
            this.order_id = this.getParameterByName('orderId');
            this.confirm_url += this.order_id;
            this.pay_url += this.order_id;
            this.recreate_cart_url += this.order_id;

            document.getElementById(this.button_cancel_id).addEventListener('click', this.recreateCart, false);
            document.getElementById(this.payment_form_id).addEventListener('submit', this.submitPayment, false);

            WalleeCheckout.getIframe();
        },

        activateLoader: function (activate) {
            const buttons = document.querySelectorAll('button');
            if (activate) {
                for (let i = 0; i < buttons.length; i++) {
                    buttons[i].disabled = true;
                }
            } else {
                for (let i = 0; i < buttons.length; i++) {
                    buttons[i].disabled = false;
                }
            }
        },

        recreateCart: function (e) {
            window.location.href = WalleeCheckout.recreate_cart_url;
            e.preventDefault();
        },

        /**
         * Submit form
         *
         * @param event
         * @return {boolean}
         */
        submitPayment: function (event) {
            WalleeCheckout.activateLoader(true);
            WalleeCheckout.handler.validate();
            event.preventDefault();
            return false;
        },

        /**
         * Get iframe
         */
        getIframe: function () {
            const paymentPanel = document.getElementById(WalleeCheckout.payment_panel_id);
            const paymentMethodConfigurationId = paymentPanel.dataset.id;
            if (!WalleeCheckout.handler) { // iframe has not been loaded yet
                // noinspection JSUnresolvedFunction
                WalleeCheckout.handler = window.IframeCheckoutHandler(paymentMethodConfigurationId);
                // noinspection JSUnresolvedFunction
                WalleeCheckout.handler.setValidationCallback((validationResult) => {
                    WalleeCheckout.hideErrors();
                    WalleeCheckout.validationCallBack(validationResult);
                });
                WalleeCheckout.handler.setInitializeCallback(() => {
                    var loader = document.getElementById(WalleeCheckout.loader_id);
                    loader.parentNode.removeChild(loader);
                    WalleeCheckout.activateLoader(false);
                });
                const iframeContainer = document.getElementById(WalleeCheckout.payment_method_iframe_id);
                WalleeCheckout.handler.create(iframeContainer);
            }
        },

        /**
         * validation callback
         * @param validationResult
         */
        validationCallBack: function (validationResult) {
            if (validationResult.success) {
                document.querySelector(this.payment_method_handler_status).value = true;
                fetch(this.confirm_url).then(() => {
                    WalleeCheckout.handler.submit();
                }).catch(() => {
                    WalleeCheckout.activateLoader(false);
                });
            } else {
                document.body.scrollTop = 0;
                document.documentElement.scrollTop = 0;

                if (validationResult.errors) {
                    WalleeCheckout.showErrors(validationResult.errors);
                }
                document.querySelector(this.payment_method_handler_status).value = false;
                WalleeCheckout.activateLoader(false);
            }
        },

        showErrors: function(errors) {
            var alert = document.createElement('div');
            alert.setAttribute('class', 'alert alert-danger');
            alert.setAttribute('role', 'alert');
            alert.setAttribute('id', 'wallee-errors');
            document.getElementsByClassName('flashbags')[0].appendChild(alert);

            var alertContentContainer = document.createElement('div');
            alertContentContainer.setAttribute('class', 'alert-content-container');
            alert.appendChild(alertContentContainer);

            var alertContent = document.createElement('div');
            alertContent.setAttribute('class', 'alert-content');
            alertContentContainer.appendChild(alertContent);

            if (errors.length > 1) {
                var alertList = document.createElement('ul');
                alertList.setAttribute('class', 'alert-list');
                alertContent.appendChild(alertList);
                for (var index = 0; index < errors.length; index++) {
                    var alertListItem = document.createElement('li');
                    alertListItem.textContent = errors[index];
                    alertList.appendChild(alertListItem);
                }
            } else {
                alertContent.textContent = errors[0];
            }
        },

        hideErrors: function() {
            var errorElement = document.getElementById('wallee-errors');
            if (errorElement) {
                errorElement.parentNode.removeChild(errorElement);
            }
        },

        /**
         * Get query name value
         *
         * @param name
         * @param url
         * @link https://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
         * @return {*}
         */
        getParameterByName: function (name, url) {
            if (!url) url = window.location.href;
            name = name.replace(/[\[\]]/g, '\\$&');
            const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
                results = regex.exec(url);
            if (!results) return null;
            if (!results[2]) return '';
            return decodeURIComponent(results[2].replace(/\+/g, ' '));
        }
    };

    window.WalleeCheckout = WalleeCheckout;

}(typeof window !== "undefined" ? window : this));

/**
 * Vanilla JS over JQuery
 */
window.addEventListener('load', function (e) {
    WalleeCheckout.init();
    history.pushState({}, document.title, WalleeCheckout.recreate_cart_url);
    history.pushState({}, document.title, WalleeCheckout.pay_url);
}, false);

window.addEventListener('popstate', function (e) {
    if (window.history.state == null) { // This means it's page load
        return;
    }
    window.location.href = WalleeCheckout.recreate_cart_url;
}, false);