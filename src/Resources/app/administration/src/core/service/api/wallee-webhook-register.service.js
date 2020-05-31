/* global Shopware */

const ApiService = Shopware.Classes.ApiService;

/**
 * @class WalleePayment\Core\Api\WebHooks\Controller\WebHookController
 */
class WalleeWebHookRegisterService extends ApiService {

	/**
	 * WalleeWebHookRegisterService
	 *
	 * @param httpClient
	 * @param loginService
	 * @param apiEndpoint
	 */
	constructor(httpClient, loginService, apiEndpoint = 'wallee') {
		super(httpClient, loginService, apiEndpoint);
	}

	/**
	 * Register a webhook
	 *
	 * @param {String|null} salesChannelId
	 * @return {*}
	 */
	registerWebHook(salesChannelId) {

		const headers = this.getBasicHeaders();
		const apiRoute = `_action/${this.getApiBasePath()}/webHook/register/${salesChannelId}`;

		return this.httpClient.post(
			apiRoute,
			{},
			{
				headers: headers
			}
		).then((response) => {
			return ApiService.handleResponse(response);
		});
	}
}

export default WalleeWebHookRegisterService;
