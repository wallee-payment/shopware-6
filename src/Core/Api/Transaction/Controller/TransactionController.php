<?php declare(strict_types=1);

namespace WalleePayment\Core\Api\Transaction\Controller;

use Psr\Log\LoggerInterface;
use Shopware\Core\{
	Framework\Context,
	Framework\Routing\Annotation\RouteScope};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\{
	HttpFoundation\HeaderUtils,
	HttpFoundation\JsonResponse,
	HttpFoundation\Request,
	HttpFoundation\Response,
	Routing\Annotation\Route};
use WalleePayment\{
	Core\Api\Transaction\Service\TransactionService,
	Core\Settings\Service\SettingsService};

/**
 * Class TransactionController
 *
 * @package WalleePayment\Core\Api\Transaction\Controller
 *
 * @Route(defaults={"_routeScope"={"api"}})
 */
class TransactionController extends AbstractController {

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
	 * TransactionController constructor.
	 *
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
	 *
	 * @internal
	 * @required
	 *
	 */
	public function setLogger(LoggerInterface $logger): void
	{
		$this->logger = $logger;
	}

	/**
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Shopware\Core\Framework\Context          $context
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 * @Route(
	 *     "/api/_action/wallee/transaction/get-transaction-data/",
	 *     name="api.action.wallee.transaction.get-transaction-data",
	 *     methods={"POST"}
	 *     )
	 */
	public function getTransactionData(Request $request, Context $context): JsonResponse
	{
		$transactionId = $request->request->get('transactionId');

		$transaction      = $this->transactionService->getByTransactionId(intval($transactionId), $context);
		$refundCollection = $this->transactionService->getRefundEntityCollectionByTransactionId(intval($transactionId), $context);

		$refunds = [];
		foreach ($refundCollection as $refundEntity) {
			$refunds[] = $refundEntity ? $refundEntity->getData() : [];
		}

		return new JsonResponse([
			'refunds'      => $refunds,
			'transactions' => [$transaction ? $transaction->getData() : []],
		]);
	}

	/**
	 * @param string                           $salesChannelId
	 * @param int                              $transactionId
	 * @param \Shopware\Core\Framework\Context $context
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 *
	 * @Route(
	 *     "/api/_action/wallee/transaction/get-invoice-document/{salesChannelId}/{transactionId}",
	 *     name="api.action.wallee.transaction.get-invoice-document",
	 *     methods={"GET"},
	 *     defaults={"_acl"={"order:read"}}
	 *     )
	 */
	public function getInvoiceDocument(string $salesChannelId, int $transactionId, Context $context): Response
	{
		if (!$this->isTransactionOfSalesChannel($salesChannelId, $transactionId, $context)) {
			return $this->documentAccessDenied();
		}

		$settings  = $this->settingsService->getSettings($salesChannelId);
		$apiClient = $settings->getApiClient();

		$invoiceDocument = $apiClient->getTransactionService()->getInvoiceDocument($settings->getSpaceId(), $transactionId);
		$forceDownload   = true;
		$filename        = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '_', $invoiceDocument->getTitle()) . '.pdf';
		$disposition     = HeaderUtils::makeDisposition(
			$forceDownload ? HeaderUtils::DISPOSITION_ATTACHMENT : HeaderUtils::DISPOSITION_INLINE,
			$filename,
			$filename
		);
		$response        = new Response(base64_decode($invoiceDocument->getData()));
		$response->headers->set('Content-Type', $invoiceDocument->getMimeType());
		$response->headers->set('Content-Disposition', $disposition);

		return $response;
	}

	/**
	 * @param string                           $salesChannelId
	 * @param int                              $transactionId
	 * @param \Shopware\Core\Framework\Context $context
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 *
	 * @Route(
	 *     "/api/_action/wallee/transaction/get-packing-slip/{salesChannelId}/{transactionId}",
	 *     name="api.action.wallee.transaction.get-packing-slip",
	 *     methods={"GET"},
	 *     defaults={"_acl"={"order:read"}}
	 *     )
	 */
	public function getPackingSlip(string $salesChannelId, int $transactionId, Context $context): Response
	{
		if (!$this->isTransactionOfSalesChannel($salesChannelId, $transactionId, $context)) {
			return $this->documentAccessDenied();
		}

		$settings  = $this->settingsService->getSettings($salesChannelId);
		$apiClient = $settings->getApiClient();

		$invoiceDocument = $apiClient->getTransactionService()->getPackingSlip($settings->getSpaceId(), $transactionId);
		$forceDownload   = true;
		$filename        = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '_', $invoiceDocument->getTitle()) . '.pdf';
		$disposition     = HeaderUtils::makeDisposition(
			$forceDownload ? HeaderUtils::DISPOSITION_ATTACHMENT : HeaderUtils::DISPOSITION_INLINE,
			$filename,
			$filename
		// only printable ascii

		);
		$response        = new Response(base64_decode($invoiceDocument->getData()));
		$response->headers->set('Content-Type', $invoiceDocument->getMimeType());
		$response->headers->set('Content-Disposition', $disposition);

		return $response;
	}

	/**
	 * Checks that the requested document really belongs to the given sales channel.
	 *
	 * The transaction id is sequential and the sales channel id is attacker controlled, so
	 * authentication alone is not enough: without this lookup any caller could iterate the
	 * ids and pull documents that are not covered by the sales channel they asked for.
	 *
	 * @param string                           $salesChannelId
	 * @param int                              $transactionId
	 * @param \Shopware\Core\Framework\Context $context
	 *
	 * @return bool
	 */
	private function isTransactionOfSalesChannel(string $salesChannelId, int $transactionId, Context $context): bool
	{
		try {
			$transaction = $this->transactionService->getByTransactionId($transactionId, $context);
		} catch (\Exception $exception) {
			$this->logger->error(__CLASS__ . ' : ' . __FUNCTION__ . ' : ' . $exception->getMessage());

			return false;
		}

		return $transaction !== null && $transaction->getSalesChannelId() === $salesChannelId;
	}

	/**
	 * Uniform rejection for documents the caller may not read.
	 *
	 * The same response is used for "unknown transaction" and "wrong sales channel" so the
	 * endpoint cannot be used to probe which transaction ids exist.
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	private function documentAccessDenied(): JsonResponse
	{
		return new JsonResponse(
			['error' => 'The requested document is not available for this sales channel.'],
			Response::HTTP_FORBIDDEN
		);
	}
}