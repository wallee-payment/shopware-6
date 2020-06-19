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
        payment_panel_class: 'wallee-payment-panel',
        payment_method_tabs: 'ul.wallee-payment-panel li',
        payment_method_iframe_prefix: 'iframe_payment_method_',
        payment_method_iframe_class: '.wallee-payment-iframe',
        payment_method_handler_name: 'wallee_payment_handler',
        payment_method_handler_prefix: 'wallee_handler_',
        payment_method_handler_status: 'input[name="wallee_payment_handler_validation_status"]',
        payment_form_id: 'confirmOrderForm',
        button_cancel_id: 'walleeOrderCancel',
        order_id: '',
        loader_id: 'walleeLoader',
        confirm_url: '/wallee/checkout/confirm?orderId=',
        pay_url: '/wallee/checkout/pay?orderId=',
        recreate_cart_url: '/wallee/checkout/recreate-cart?orderId=',

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

            const payment_method_tabs = document.querySelectorAll(this.payment_method_tabs);
            for (let i = 0; i < payment_method_tabs.length; i++) {
                payment_method_tabs[i].addEventListener('click', this.getIframe, false);
            }
            document.getElementById(this.payment_form_id).addEventListener('submit', this.submitPayment, false);
            payment_method_tabs[0].click();
        },

        activateLoader: function (activate) {
            var panel = document.getElementsByClassName(WalleeCheckout.payment_panel_class)[0];
            var loader = document.getElementById(WalleeCheckout.loader_id);
            const buttons = document.querySelectorAll('button');
            if (activate) {
                panel.style.display = 'none';
                panel.style.transition = 'opacity 2s ease-out 2s';
                panel.style.opacity = '0';

                loader.style.display = 'block';
                loader.style.transition = 'opacity 2s ease-in 2s';
                loader.style.opacity = '1';
                for (let i = 0; i < buttons.length; i++) {
                    buttons[i].disabled = true;
                }

            } else {
                loader.style.display = 'none';
                loader.style.transition = 'opacity 2s ease-out 2s';
                loader.style.opacity = '0';

                panel.style.display = 'block';
                panel.style.transition = 'opacity 2s ease-in 2s';
                panel.style.opacity = '1';

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
            const handler = document.querySelector('input[name="' + WalleeCheckout.payment_method_handler_name + '"]:checked').value;
            window[handler].validate();
            event.preventDefault();
            return false;
        },

        /**
         * Get iframe
         */
        getIframe: function () {
            const payment_method_iframe_class = document.querySelectorAll(WalleeCheckout.payment_method_iframe_class);
            for (let i = 0; i < payment_method_iframe_class.length; i++) {
                payment_method_iframe_class[i].style.display = 'none';
            }
            this.getElementsByTagName('input')[0].checked = true;
            const value = this.dataset.id;
            const iframe_div = WalleeCheckout.payment_method_iframe_prefix + value;
            if (document.getElementById(iframe_div).children.length === 0) { // iframe has not been loaded yet
                const payment_handler_name = WalleeCheckout.payment_method_handler_prefix + value;
                // noinspection JSUnresolvedFunction
                window[payment_handler_name] = window.IframeCheckoutHandler(value);
                // noinspection JSUnresolvedFunction
                window[payment_handler_name].setValidationCallback((validationResult) => {
                    WalleeCheckout.validationCallBack(payment_handler_name, validationResult);
                });
                window[payment_handler_name].create(iframe_div);
                document.getElementById(iframe_div).style.display = 'block';
                setTimeout(function () {
                    WalleeCheckout.activateLoader(false);
                }, 1000);

            }

            return false;
        },

        /**
         * validation callback
         * @param payment_handler_name
         * @param validationResult
         */
        validationCallBack: function (payment_handler_name, validationResult) {
            if (validationResult.success) {
                document.querySelector(this.payment_method_handler_status).value = true;
                fetch(this.confirm_url).then(() => {
                    window[payment_handler_name].submit();
                }).catch(() => {
                    WalleeCheckout.activateLoader(false);
                });
            } else {
                document.querySelector(this.payment_method_handler_status).value = false;
                WalleeCheckout.activateLoader(false);
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