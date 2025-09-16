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
        button_home_override: 'walleeHomeLink',
        loader_id: 'walleeLoader',
        checkout_url: null,
        checkout_url_id: 'checkoutUrl',
        cart_recreate_url: null,
        cart_recreate_url_id: 'cartRecreateUrl',
        handler: null,

        /**
         * Initialize plugin
         */
        init: function () {
            WalleeCheckout.activateLoader(true);
            this.checkout_url = document.getElementById(this.checkout_url_id).value;
            this.cart_recreate_url = document.getElementById(this.cart_recreate_url_id).value;

            document.getElementById(this.button_cancel_id).addEventListener('click', this.recreateCart, false);
            document.getElementById(this.button_home_override).addEventListener('click', this.recreateCart, false);
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

        hideLoader: function () {
            const loader = document.getElementById(WalleeCheckout.loader_id);
            if (loader !== null && loader.parentNode !== null) {
                loader.parentNode.removeChild(loader);
            }
            WalleeCheckout.activateLoader(false);
        },

        recreateCart: function (e) {
            window.location.href = WalleeCheckout.cart_recreate_url;
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
            const iframeContainer = document.getElementById(WalleeCheckout.payment_method_iframe_id);

            if (!WalleeCheckout.handler) { // iframe has not been loaded yet
                // noinspection JSUnresolvedFunction
                WalleeCheckout.handler = window.IframeCheckoutHandler(paymentMethodConfigurationId);
                // noinspection JSUnresolvedFunction
                WalleeCheckout.handler.setValidationCallback(function(validationResult){
                    WalleeCheckout.hideErrors();
                    WalleeCheckout.validationCallBack(validationResult);
                });
                WalleeCheckout.handler.setInitializeCallback(WalleeCheckout.hideLoader());
                WalleeCheckout.handler.setHeightChangeCallback(function(height){
                    if(height < 1){ // iframe has no fields
                        WalleeCheckout.handler.submit();
                    }
                });
                WalleeCheckout.handler.create(iframeContainer);
                setTimeout(WalleeCheckout.hideLoader(), 10000);

            }
        },

        /**
         * validation callback
         * @param validationResult
         */
        validationCallBack: function (validationResult) {
            if (validationResult.success) {
                document.querySelector(this.payment_method_handler_status).value = true;
                WalleeCheckout.handler.submit();
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
            let alert = document.createElement('div');
            alert.setAttribute('class', 'alert alert-danger');
            alert.setAttribute('role', 'alert');
            alert.setAttribute('id', 'wallee-errors');
            document.getElementsByClassName('flashbags')[0].appendChild(alert);

            let alertContentContainer = document.createElement('div');
            alertContentContainer.setAttribute('class', 'alert-content-container');
            alert.appendChild(alertContentContainer);

            let alertContent = document.createElement('div');
            alertContent.setAttribute('class', 'alert-content');
            alertContentContainer.appendChild(alertContent);

            if (errors.length > 1) {
                let alertList = document.createElement('ul');
                alertList.setAttribute('class', 'alert-list');
                alertContent.appendChild(alertList);
                for (let index = 0; index < errors.length; index++) {
                    let alertListItem = document.createElement('li');
                    alertListItem.innerHTML = errors[index];
                    alertList.appendChild(alertListItem);
                }
            } else {
                alertContent.innerHTML = errors[0];
            }
        },

        hideErrors: function() {
            let errorElement = document.getElementById('wallee-errors');
            if (errorElement) {
                errorElement.parentNode.removeChild(errorElement);
            }
        }
    };

    window.WalleeCheckout = WalleeCheckout;

}(typeof window !== "undefined" ? window : this));

/**
 * Vanilla JS over JQuery
 */
window.addEventListener('load', function (e) {
    WalleeCheckout.init();
    window.history.pushState({}, document.title, WalleeCheckout.cart_recreate_url);
    window.history.pushState({}, document.title, WalleeCheckout.checkout_url);
}, false);

/**
 * This only works if the user has interacted with the page
 * @link https://stackoverflow.com/questions/57339098/chrome-popstate-not-firing-on-back-button-if-no-user-interaction
 */
window.addEventListener('popstate', function (e) {
    if (window.history.state == null) { // This means it's page load
        return;
    }
    window.location.href = WalleeCheckout.cart_recreate_url;
}, false);
