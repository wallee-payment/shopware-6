/* global Shopware */

const ApiService = Shopware.Classes.ApiService;

/**
 * @class WalleePayment\Core\Api\Transaction\Controller\TransactionController
 */
class WalleeTransactionService extends ApiService {

	/**
	 * WalleeTransactionService constructor
	 *
	 * @param httpClient
	 * @param loginService
	 * @param apiEndpoint
	 */
	constructor(httpClient, loginService, apiEndpoint = 'wallee') {
		super(httpClient, loginService, apiEndpoint);
	}

	/**
	 * Get transaction data
	 *
	 * @param {String} salesChannelId
	 * @param {int} transactionId
	 * @return {*}
	 */
	getTransactionData(salesChannelId, transactionId) {

		const headers = this.getBasicHeaders();
		const apiRoute = `_action/${this.getApiBasePath()}/transaction/get-transaction-data/`;

		return this.httpClient.post(
			apiRoute,
			{
				salesChannelId: salesChannelId,
				transactionId: transactionId
			},
			{
				headers: headers
			}
		).then((response) => {
			return ApiService.handleResponse(response);
		});
	}

	/**
	 * Download Invoice Document
	 *
	 * @param context
	 * @param salesChannelId
	 * @param transactionId
	 * @return {string}
	 */
	getInvoiceDocument(context, salesChannelId, transactionId) {
		return `${context.apiPath}/v${context.apiVersion}/_action/${this.getApiBasePath()}/transaction/get-invoice-document/${salesChannelId}/${transactionId}`;
	}

	/**
	 * Download Packing slip
	 *
	 * @param context
	 * @param salesChannelId
	 * @param transactionId
	 * @return {string}
	 */
	getPackingSlip(context, salesChannelId, transactionId) {
		return `${context.apiPath}/v${context.apiVersion}/_action/${this.getApiBasePath()}/transaction/get-packing-slip/${salesChannelId}/${transactionId}`;
	}
}

export default WalleeTransactionService;