/* eslint-disable import/no-unresolved */

import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';


class WalleeCheckoutPlugin extends Plugin {

    static options = {
        payment_method_tabs: 'ul.wallee-payment-panel li',
        payment_method_iframe_prefix: 'iframe_payment_method_',
        payment_method_iframe_class: '.wallee-payment-iframe',
        payment_method_handler_name: 'wallee_payment_handler',
        payment_method_handler_prefix: 'wallee_handler_',
        payment_method_handler_status: 'input[name="wallee_payment_handler_validation_status"]',
        payment_form: 'confirmOrderForm'
    };

    init() {
        // @TODO Move JS to Plugin
        this._client = new HttpClient(window.accessKey);
    }

}

export default WalleeCheckoutPlugin;