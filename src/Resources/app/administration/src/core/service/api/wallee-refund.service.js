/* global Shopware */

const ApiService = Shopware.Classes.ApiService;

/**
 * @class WalleePayment\Core\Api\Transaction\Controller\RefundController
 */
class WalleeRefundService extends ApiService {

	/**
	 * WalleeRefundService constructor
	 *
	 * @param httpClient
	 * @param loginService
	 * @param apiEndpoint
	 */
	constructor(httpClient, loginService, apiEndpoint = 'wallee') {
		super(httpClient, loginService, apiEndpoint);
	}

	/**
	 * Refund a transaction
	 *
	 * @param {String} salesChannelId
	 * @param {int} transactionId
	 * @param {float} refundableAmount
	 * @return {*}
	 */
	createRefund(salesChannelId, transactionId, refundableAmount) {

		const headers = this.getBasicHeaders();
		const apiRoute = `${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/refund/create-refund/`;

		return this.httpClient.post(
			apiRoute,
			{
				salesChannelId: salesChannelId,
				transactionId: transactionId,
				refundableAmount: refundableAmount
			},
			{
				headers: headers
			}
		).then((response) => {
			return ApiService.handleResponse(response);
		});
	}
}

export default WalleeRefundService;