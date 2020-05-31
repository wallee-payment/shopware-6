<?php declare(strict_types=1);

namespace WalleePayment\Core\Api\WebHooks\Controller;

use Psr\Log\LoggerInterface;
use Shopware\Core\{
	Checkout\Cart\Exception\OrderNotFoundException,
	Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity,
	Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler,
	Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates,
	Framework\Context,
	Framework\DataAbstractionLayer\Search\Criteria,
	Framework\Routing\Annotation\RouteScope};
use Shopware\Storefront\Page\GenericPageLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\{
	HttpFoundation\JsonResponse,
	HttpFoundation\Request,
	HttpFoundation\Response,
	Routing\Annotation\Route,};
use Wallee\Sdk\{
	Model\RefundState,
	Model\TransactionInvoiceState,
	Model\TransactionState,};
use WalleePayment\Core\{
	Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService,
	Api\Transaction\Service\TransactionService,
	Api\WebHooks\Struct\WebHookRequest,
	Settings\Service\SettingsService};

/**
 * Class WebHookController
 *
 * @package WalleePayment\Core\Api\WebHooks\Controller
 *
 * @RouteScope(scopes={"api"})
 */
class WebHookController extends AbstractController {

	/**
	 * @var \Shopware\Storefront\Page\GenericPageLoader
	 */
	protected $genericLoader;

	/**
	 * @var \Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler
	 */
	protected $orderTransactionStateHandler;

	/**
	 * @var \WalleePayment\Core\Settings\Service\SettingsService
	 */
	protected $settingsService;

	/**
	 * @var \Wallee\Sdk\ApiClient
	 */
	protected $apiClient;

	/**
	 * Transaction Final States
	 *
	 * @var array
	 */
	protected $transactionFinalStates = [
		OrderTransactionStates::STATE_CANCELLED,
		OrderTransactionStates::STATE_PAID,
		OrderTransactionStates::STATE_REFUNDED,
	];

	/**
	 * Transaction Failed States
	 *
	 * @var array
	 */
	protected $transactionFailedStates = [
		TransactionState::DECLINE,
		TransactionState::FAILED,
		TransactionState::VOIDED,
	];

	/**
	 * @var \WalleePayment\Core\Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService
	 */
	protected $paymentMethodConfigurationService;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * @var \WalleePayment\Core\Api\Transaction\Service\TransactionService
	 */
	protected $transactionService;

	/**
	 * WebHookController constructor.
	 *
	 * @param \Shopware\Storefront\Page\GenericPageLoader                                                                 $genericLoader
	 * @param \Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler                       $orderTransactionStateHandler
	 * @param \WalleePayment\Core\Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService $paymentMethodConfigurationService
	 * @param \WalleePayment\Core\Settings\Service\SettingsService                                         $settingsService
	 * @param \WalleePayment\Core\Api\Transaction\Service\TransactionService                               $transactionService
	 * @param \Psr\Log\LoggerInterface                                                                                    $logger
	 */
	public function __construct(
		GenericPageLoader $genericLoader,
		OrderTransactionStateHandler $orderTransactionStateHandler,
		PaymentMethodConfigurationService $paymentMethodConfigurationService,
		SettingsService $settingsService,
		TransactionService $transactionService,
		LoggerInterface $logger
	)
	{
		$this->genericLoader                     = $genericLoader;
		$this->orderTransactionStateHandler      = $orderTransactionStateHandler;
		$this->paymentMethodConfigurationService = $paymentMethodConfigurationService;
		$this->settingsService                   = $settingsService;
		$this->transactionService                = $transactionService;
		$this->logger                            = $logger;

	}

	/**
	 * This is the method Wallee calls
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Shopware\Core\Framework\Context          $context
	 * @param string                                    $salesChannelId
	 * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
	 * @Route(
	 *     "/api/v{version}/_action/wallee/webHook/callback/{salesChannelId}",
	 *     name="api.action.wallee.webhook.update",
	 *     options={"seo": "false"},
	 *     defaults={"csrf_protected"=false, "XmlHttpRequest"=true, "auth_required"=false},
	 *     methods={"POST"}
	 *     )
	 */
	public function callback(Request $request, Context $context, string $salesChannelId)
	{
		$status       = Response::HTTP_INTERNAL_SERVER_ERROR;
		$callBackData = new WebHookRequest();
		try {
			// Configuration
			$salesChannelId  = $salesChannelId == 'null' ? null : $salesChannelId;
			$settings        = $this->settingsService->getSettings($salesChannelId);
			$this->apiClient = $settings->getApiClient();

			$callBackData->assign(json_decode($request->getContent(), true));

			switch ($callBackData->getListenerEntityTechnicalName()) {
				case WebHookRequest::PAYMENT_METHOD_CONFIGURATION:
					return $this->updatePaymentMethodConfiguration($context, $salesChannelId);
				case WebHookRequest::REFUND:
					return $this->updateRefund($callBackData, $context);
				case WebHookRequest::TRANSACTION:
					return $this->updateTransaction($callBackData, $context);
				case WebHookRequest::TRANSACTION_INVOICE:
					return $this->updateTransactionInvoice($callBackData, $context);
				default:
					$this->logger->critical(__CLASS__ . ' : ' . __FUNCTION__ . ' : Listener not implemented : ', $callBackData->jsonSerialize());
			}
		} catch (\Exception $exception) {
			$this->logger->critical(__CLASS__ . ' : ' . __FUNCTION__ . ' : ' . $exception->getMessage(), $callBackData->jsonSerialize());
		}
		return new JsonResponse(['data' => $callBackData], $status);
	}

	/**
	 * Handle Wallee Payment Method Configuration callback
	 *
	 * @param \Shopware\Core\Framework\Context $context
	 * @param string                           $salesChannelId
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 */
	private function updatePaymentMethodConfiguration(Context $context, string $salesChannelId): Response
	{
		$result = $this->paymentMethodConfigurationService->setSalesChannelId($salesChannelId)->synchronize($context);

		return new JsonResponse(['result' => $result]);
	}

	/**
	 * Handle Wallee Refund callback
	 *
	 * @param \WalleePayment\Core\Api\WebHooks\Struct\WebHookRequest $callBackData
	 * @param \Shopware\Core\Framework\Context                                      $context
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function updateRefund(WebHookRequest $callBackData, Context $context): Response
	{
		$status = Response::HTTP_INTERNAL_SERVER_ERROR;

		try {

			/**
			 * @var \Wallee\Sdk\Model\Transaction $transaction
			 */
			$refund              = $this->apiClient->getRefundService()
												   ->read($callBackData->getSpaceId(), $callBackData->getEntityId());
			$transaction         = $refund->getTransaction();
			$transactionMetaData = $transaction->getMetaData();
			$orderID             = $transactionMetaData['orderId'];
			$orderTransactionId  = $transactionMetaData['orderTransactionId'];
			$orderTransaction    = $this->getOrderTransaction($orderID, $context);
			if (
				in_array(
					$orderTransaction->getStateMachineState()->getTechnicalName(),
					[
						OrderTransactionStates::STATE_PAID,
						OrderTransactionStates::STATE_PARTIALLY_PAID,
					]
				) &&
				($refund->getState() == RefundState::SUCCESSFUL)
			) {
				if ($refund->getAmount() == $orderTransaction->getAmount()->getTotalPrice()) {
					$this->orderTransactionStateHandler->refund($orderTransactionId, $context);
				} else {
					if ($refund->getAmount() < $orderTransaction->getAmount()->getTotalPrice()) {
						$this->orderTransactionStateHandler->refundPartially($orderTransactionId, $context);
					}
				}
			}

			$status = Response::HTTP_OK;
		} catch (\Exception $exception) {
			$this->logger->critical(__CLASS__ . ' : ' . __FUNCTION__ . ' : ' . $exception->getMessage(), $callBackData->jsonSerialize());
		}

		return new JsonResponse(['data' => $callBackData->jsonSerialize()], $status);
	}

	/**
	 * @param String                           $orderId
	 * @param \Shopware\Core\Framework\Context $context
	 * @return \Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity
	 */
	private function getOrderTransaction(String $orderId, Context $context): OrderTransactionEntity
	{
		$criteria = (new Criteria([$orderId]))->addAssociations(['transactions',]);

		try {
			/** @var OrderTransactionEntity|null $transaction */
			$transaction = $this->container->get('order.repository')->search(
				$criteria,
				$context
			)->first()->getTransactions()->first();
		} catch (\Exception $e) {
			throw new OrderNotFoundException($orderId);
		}

		return $transaction;
	}

	/**
	 * Handle Wallee Transaction callback
	 *
	 * @param \WalleePayment\Core\Api\WebHooks\Struct\WebHookRequest $callBackData
	 * @param \Shopware\Core\Framework\Context                                      $context
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	private function updateTransaction(WebHookRequest $callBackData, Context $context): Response
	{
		$status = Response::HTTP_INTERNAL_SERVER_ERROR;

		try {

			/**
			 * @var \Wallee\Sdk\Model\Transaction $transaction
			 * @var \Shopware\Core\Checkout\Order\OrderEntity    $order
			 */
			$transaction = $this->apiClient->getTransactionService()
										   ->read(
											   $callBackData->getSpaceId(),
											   $callBackData->getEntityId()
										   );
			$this->transactionService->upsert($context, $transaction);
			$transactionMetaData = $transaction->getMetaData();
			$orderID             = $transactionMetaData['orderId'];
			$orderTransactionId  = $transactionMetaData['orderTransactionId'];
			$orderTransaction    = $this->getOrderTransaction($orderID, $context);
			if (
				!in_array(
					$orderTransaction->getStateMachineState()->getTechnicalName(),
					$this->transactionFinalStates,
					true
				) &&
				in_array($transaction->getState(), $this->transactionFailedStates)
			) {
				$this->orderTransactionStateHandler->cancel($orderTransactionId, $context);
			}
			$status = Response::HTTP_OK;
		} catch (\Exception $exception) {
			$this->logger->critical(__CLASS__ . ' : ' . __FUNCTION__ . ' : ' . $exception->getMessage(), $callBackData->jsonSerialize());
		}
		return new JsonResponse(['data' => $callBackData->jsonSerialize()], $status);
	}

	/**
	 * Handle Wallee TransactionInvoice callback
	 *
	 * @param \WalleePayment\Core\Api\WebHooks\Struct\WebHookRequest $callBackData
	 * @param \Shopware\Core\Framework\Context                                      $context
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function updateTransactionInvoice(WebHookRequest $callBackData, Context $context): Response
	{
		$status = Response::HTTP_INTERNAL_SERVER_ERROR;

		try {
			/**
			 * @var \Wallee\Sdk\Model\Transaction        $transaction
			 * @var \Wallee\Sdk\Model\TransactionInvoice $transactionInvoice
			 */
			$transactionInvoice  = $this->apiClient->getTransactionInvoiceService()
												   ->read($callBackData->getSpaceId(), $callBackData->getEntityId());
			$transaction         = $this->apiClient->getTransactionService()
												   ->read($callBackData->getSpaceId(), $transactionInvoice->getLinkedTransaction());
			$transactionMetaData = $transaction->getMetaData();
			$orderID             = $transactionMetaData['orderId'];
			$orderTransactionId  = $transactionMetaData['orderTransactionId'];
			$orderTransaction    = $this->getOrderTransaction($orderID, $context);
			if (!in_array(
				$orderTransaction->getStateMachineState()->getTechnicalName(),
				$this->transactionFinalStates,
				true
			)) {
				switch ($transactionInvoice->getState()) {
					case TransactionInvoiceState::DERECOGNIZED:
						$this->orderTransactionStateHandler->cancel($orderTransactionId, $context);
						break;
					case TransactionInvoiceState::NOT_APPLICABLE:
					case TransactionInvoiceState::PAID:
						$this->orderTransactionStateHandler->process($orderTransactionId, $context);
						$this->orderTransactionStateHandler->paid($orderTransactionId, $context);
						break;
					default:
						break;
				}
			}
			$status = Response::HTTP_OK;
		} catch (\Exception $exception) {
			$this->logger->critical(__CLASS__ . ' : ' . __FUNCTION__ . ' : ' . $exception->getMessage(), $callBackData->jsonSerialize());
		}

		return new JsonResponse(['data' => $callBackData->jsonSerialize()], $status);
	}
}