<?php declare(strict_types=1);

namespace WalleePayment\Core\Storefront\Account\Controller;

use Psr\Log\LoggerInterface;
use Shopware\Core\{
	Framework\Routing\Annotation\RouteScope,
	System\SalesChannel\SalesChannelContext};
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\{
	HttpFoundation\HeaderUtils,
	HttpFoundation\Response,
	Routing\Annotation\Route};
use WalleePayment\Core\{
	Api\Transaction\Service\TransactionService,
	Settings\Service\SettingsService};

/**
 * @RouteScope(scopes={"storefront"})
 */
class AccountOrderController extends StorefrontController {

	/**
	 * @var \WalleePayment\Core\Settings\Service\SettingsService
	 */
	protected $settingsService;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * @var \WalleePayment\Core\Api\Transaction\Service\TransactionService
	 */
	protected $transactionService;

	/**
	 * AccountOrderController constructor.
	 * @param \WalleePayment\Core\Settings\Service\SettingsService           $settingsService
	 * @param \WalleePayment\Core\Api\Transaction\Service\TransactionService $transactionService
	 */
	public function __construct(SettingsService $settingsService, TransactionService $transactionService)
	{
		$this->settingsService    = $settingsService;
		$this->transactionService = $transactionService;
	}

	/**
	 * @param \Psr\Log\LoggerInterface $logger
	 * @internal
	 * @required
	 *
	 */
	public function setLogger(LoggerInterface $logger): void
	{
		$this->logger = $logger;
	}


	/**
	 * Download invoice document
	 *
	 * @param string                                                 $orderId
	 * @param \Shopware\Core\System\SalesChannel\SalesChannelContext $salesChannelContext
	 * @return \Symfony\Component\HttpFoundation\Response
	 *
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 * @Route(
	 *     "/wallee/account/order/download/invoice/document/{orderId}",
	 *     name="frontend.wallee.account.order.download.invoice.document",
	 *     methods={"GET"}
	 *     )
	 */
	public function downloadInvoiceDocument(string $orderId, SalesChannelContext $salesChannelContext): Response
	{
		$this->denyAccessUnlessLoggedIn();
		$settings          = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
		$transactionEntity = $this->transactionService->getByOrderId($orderId, $salesChannelContext->getContext());
		$invoiceDocument   = $settings->getApiClient()->getTransactionService()->getInvoiceDocument($settings->getSpaceId(), $transactionEntity->getTransactionId());
		$forceDownload     = true;
		$filename          = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '_', $invoiceDocument->getTitle()) . '.pdf';
		$disposition       = HeaderUtils::makeDisposition(
			$forceDownload ? HeaderUtils::DISPOSITION_ATTACHMENT : HeaderUtils::DISPOSITION_INLINE,
			$filename,
			$filename
		);
		$response          = new Response(base64_decode($invoiceDocument->getData()));
		$response->headers->set('Content-Type', $invoiceDocument->getMimeType());
		$response->headers->set('Content-Disposition', $disposition);

		return $response;
	}
}
