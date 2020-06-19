/* global Shopware */

const ApiService = Shopware.Classes.ApiService;

/**
 * @class WalleePayment\Core\Api\Config\Controller\ConfigurationController
 */
class WalleeConfigurationService extends ApiService {

	/**
	 * WhitelabelmachinenameConfigurationService constructor
	 *
	 * @param httpClient
	 * @param loginService
	 * @param apiEndpoint
	 */
	constructor(httpClient, loginService, apiEndpoint = 'wallee') {
		super(httpClient, loginService, apiEndpoint);
	}

	/**
	 * Register web hooks
	 *
	 * @param {String|null} salesChannelId
	 * @return {*}
	 */
	registerWebHooks(salesChannelId = null) {

		const headers = this.getBasicHeaders();
		const apiRoute = `_action/${this.getApiBasePath()}/configuration/register-web-hooks`;

		return this.httpClient.post(
			apiRoute,
			{
				salesChannelId: salesChannelId
			},
			{
				headers: headers
			}
		).then((response) => {
			return ApiService.handleResponse(response);
		});
	}

	/**
	 * Set's the default payment method to Wallee for the given salesChannel id.
	 *
	 * @param {String|null} salesChannelId
	 *
	 * @returns {Promise}
	 */
	setWalleeAsSalesChannelPaymentDefault(salesChannelId = null) {

		const headers = this.getBasicHeaders();
		const apiRoute = `_action/${this.getApiBasePath()}/configuration/set-wallee-as-sales-channel-payment-default`;

		return this.httpClient.post(
			apiRoute,
			{
				salesChannelId: salesChannelId
			},
			{
				headers: headers
			}
		).then((response) => {
			return ApiService.handleResponse(response);
		});
	}

	/**
	 *
	 * @param salesChannelId
	 * @return {Promise}
	 */
	synchronizePaymentMethodConfiguration(salesChannelId = null) {
		const headers = this.getBasicHeaders();
		const apiRoute = `_action/${this.getApiBasePath()}/configuration/synchronize-payment-method-configuration`;

		return this.httpClient.post(
			apiRoute,
			{
				salesChannelId: salesChannelId
			},
			{
				headers: headers
			}
		).then((response) => {
			return ApiService.handleResponse(response);
		});
	}

	/**
	 *
	 * @return {*}
	 */
	installOrderDeliveryStates() {
		const headers = this.getBasicHeaders();
		const apiRoute = `_action/${this.getApiBasePath()}/configuration/install-order-delivery-states`;

		return this.httpClient.post(
			apiRoute,
			{
			},
			{
				headers: headers
			}
		).then((response) => {
			return ApiService.handleResponse(response);
		});
	}
}

export default WalleeConfigurationService;
