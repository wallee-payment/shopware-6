/* global window */
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
     *          payment_form: string,
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
        payment_method_tabs: 'ul.wallee-payment-panel li',
        payment_method_iframe_prefix: 'iframe_payment_method_',
        payment_method_iframe_class: '.wallee-payment-iframe',
        payment_method_handler_name: 'wallee_payment_handler',
        payment_method_handler_prefix: 'wallee_handler_',
        payment_method_handler_status: 'input[name="wallee_payment_handler_validation_status"]',
        payment_form: 'confirmOrderForm',

        /**
         * Initialize plugin
         */
        init: function () {
            const payment_method_tabs = document.querySelectorAll(this.payment_method_tabs);
            for (let i = 0; i < payment_method_tabs.length; i++) {
                payment_method_tabs[i].onclick = this.getIframe;
            }
            document.getElementById(this.payment_form).addEventListener('submit', this.submitPayment, false);
            payment_method_tabs[0].click();
        },

        /**
         * Submit form
         *
         * @param event
         * @return {boolean}
         */
        submitPayment: function (event) {
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
            document.getElementById(iframe_div).style.display = '';
            if (document.getElementById(iframe_div).children.length === 0) { // iframe has not been loaded yet
                const payment_handler_name = WalleeCheckout.payment_method_handler_prefix + value;
                // noinspection JSUnresolvedFunction
                window[payment_handler_name] = window.IframeCheckoutHandler(value);
                // noinspection JSUnresolvedFunction
                window[payment_handler_name].setValidationCallback((validationResult) => {
                    WalleeCheckout.validationCallBack(payment_handler_name, validationResult);
                });
                window[payment_handler_name].create(iframe_div);
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
                document.querySelector(WalleeCheckout.payment_method_handler_status).value = true;
                let orderId = this.getParameterByName('orderId');
                fetch('/wallee/confirm?orderId=' + orderId).then(() => {
                    window[payment_handler_name].submit();
                });
            } else {
                document.querySelector(WalleeCheckout.payment_method_handler_status).value = false;
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
window.onload = () => {
    WalleeCheckout.init();
};