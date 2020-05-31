<?php declare(strict_types=1);

namespace WalleePayment\Storefront\Checkout\Controller;

use Psr\Log\LoggerInterface;
use Shopware\Core\{
	Checkout\Cart\Exception\OrderNotFoundException,
	Checkout\Order\OrderEntity,
	Framework\Context,
	Framework\DataAbstractionLayer\Search\Criteria,
	Framework\Routing\Annotation\RouteScope,
	Framework\Routing\Exception\MissingRequestParameterException,
	System\SalesChannel\SalesChannelContext,};
use Shopware\Storefront\{
	Controller\StorefrontController,
	Page\Checkout\Finish\CheckoutFinishPage,
	Page\GenericPageLoader,};
use Symfony\Component\{
	HttpFoundation\JsonResponse,
	HttpFoundation\Request,
	HttpFoundation\Response,
	Routing\Annotation\Route};
use Wallee\Sdk\Model\TransactionPending;
use Wallee\Sdk\Model\TransactionState;
use WalleePayment\Core\{
	Api\Transaction\Service\TransactionService,
	Settings\Options\Integration,
	Settings\Service\SettingsService};
use WalleePayment\Storefront\Checkout\Struct\CheckoutPageData;


/**
 * Class PaymentController
 *
 * @package WalleePayment\Storefront\Checkout\Controller
 * @RouteScope(scopes={"storefront"})
 */
class PaymentController extends StorefrontController {

	/**
	 * @var \Shopware\Storefront\Page\GenericPageLoader
	 */
	protected $genericLoader;

	/**
	 * @var \WalleePayment\Core\Settings\Service\SettingsService
	 */
	protected $settingsService;

	/**
	 * @var \WalleePayment\Core\Api\Transaction\Service\TransactionService
	 */
	protected $transactionService;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $logger;

	/**
	 * PaymentController constructor.
	 *
	 * @param \WalleePayment\Core\Settings\Service\SettingsService           $settingsService
	 * @param \WalleePayment\Core\Api\Transaction\Service\TransactionService $transactionService
	 * @param \Shopware\Storefront\Page\GenericPageLoader                                   $genericLoader
	 * @param \Psr\Log\LoggerInterface                                                      $logger
	 */
	public function __construct(
		SettingsService $settingsService,
		TransactionService $transactionService,
		GenericPageLoader $genericLoader,
		LoggerInterface $logger
	)
	{
		$this->genericLoader      = $genericLoader;
		$this->settingsService    = $settingsService;
		$this->transactionService = $transactionService;
		$this->logger             = $logger;
	}

	/**
	 * @param \Shopware\Core\System\SalesChannel\SalesChannelContext $salesChannelContext
	 * @param \Symfony\Component\HttpFoundation\Request              $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 *
	 * @Route(
	 *     "/wallee/order",
	 *     name="wallee.order",
	 *     options={"seo": "false"},
	 *     methods={"GET"}
	 *     )
	 */
	public function order(SalesChannelContext $salesChannelContext, Request $request): Response
	{
		$orderId = $request->query->get('orderId');

		$transactionEntity = $this->transactionService->getByOrderId($orderId, $salesChannelContext->getContext());

		// Configuration
		$settings  = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
		$apiClient = $settings->getApiClient();

		$transaction = $apiClient->getTransactionService()->read($settings->getSpaceId(), $transactionEntity->getTransactionId());
		$this->transactionService->upsert($salesChannelContext->getContext(), $transaction);

		switch ($transaction->getState()) {
			case TransactionState::AUTHORIZED:
			case TransactionState::COMPLETED:
			case TransactionState::CONFIRMED:
			case TransactionState::FULFILL:
			case TransactionState::PROCESSING:
				return $this->redirect($transaction->getSuccessUrl(), Response::HTTP_MOVED_PERMANENTLY);
				break;
			case TransactionState::DECLINE:
			case TransactionState::FAILED:
			case TransactionState::VOIDED:
				return $this->redirect($transaction->getFailedUrl(), Response::HTTP_MOVED_PERMANENTLY);
				break;
		}

		$page                              = $this->load($request, $salesChannelContext);
		$javascriptUrl                     = '';
		$transactionPossiblePaymentMethods = $apiClient->getTransactionService()
													   ->fetchPaymentMethods(
														   $settings->getSpaceId(),
														   $transactionEntity->getTransactionId(),
														   $settings->getIntegration()
													   );

		switch ($settings->getIntegration()) {
			case Integration::IFRAME:
				$javascriptUrl = $apiClient->getTransactionIframeService()
										   ->javascriptUrl($settings->getSpaceId(), $transactionEntity->getTransactionId());
				break;
			case Integration::LIGHTBOX:
				$javascriptUrl = $apiClient->getTransactionLightboxService()
										   ->javascriptUrl($settings->getSpaceId(), $transactionEntity->getTransactionId());
				break;
			default:
				$this->logger->critical(strtr('invalid integration : :integration', [':integration' => $settings->getIntegration()]));

		}

		// Set Checkout Page Data
		$checkoutPageData = (new CheckoutPageData())
			->setIntegration($settings->getIntegration())
			->setJavascriptUrl($javascriptUrl)
			->setDeviceJavascriptUrl($settings->getSpaceId(), $this->container->get('session')->getId())
			->setTransactionPossiblePaymentMethods($transactionPossiblePaymentMethods);

		$page->addExtension('walleeData', $checkoutPageData);

		return $this->renderStorefront(
			'@WalleePayment/storefront/page/checkout/order/wallee.html.twig',
			['page' => $page]
		);
	}


	/**
	 * @param \Shopware\Core\System\SalesChannel\SalesChannelContext $salesChannelContext
	 * @param \Symfony\Component\HttpFoundation\Request              $request
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 *
	 * @Route(
	 *     "/wallee/confirm",
	 *     name="wallee.confirm",
	 *     options={"seo": "false"},
	 *     methods={"GET"}
	 *     )
	 */
	public function confirm(SalesChannelContext $salesChannelContext, Request $request): Response
	{
		$orderId = $request->query->get('orderId');

		$transactionEntity = $this->transactionService->getByOrderId($orderId, $salesChannelContext->getContext());

		// Configuration
		$settings  = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
		$apiClient = $settings->getApiClient();

		$transaction = $apiClient->getTransactionService()->read($settings->getSpaceId(), $transactionEntity->getTransactionId());

		switch ($transaction->getState()) {
			case TransactionState::AUTHORIZED:
			case TransactionState::COMPLETED:
			case TransactionState::CONFIRMED:
			case TransactionState::FULFILL:
			case TransactionState::PROCESSING:
				return $this->redirect($transaction->getSuccessUrl(), Response::HTTP_MOVED_PERMANENTLY);
				break;
			case TransactionState::DECLINE:
			case TransactionState::FAILED:
			case TransactionState::VOIDED:
				return $this->redirect($transaction->getFailedUrl(), Response::HTTP_MOVED_PERMANENTLY);
				break;
		}

		$pendingTransaction = new TransactionPending();
		$pendingTransaction->setId($transaction->getId());
		$pendingTransaction->setVersion($transaction->getVersion());

		$apiClient->getTransactionService()->confirm($settings->getSpaceId(), $pendingTransaction);

		return new JsonResponse([]);
	}


	/**
	 * @param \Symfony\Component\HttpFoundation\Request              $request
	 * @param \Shopware\Core\System\SalesChannel\SalesChannelContext $salesChannelContext
	 * @return \Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPage
	 */
	public function load(Request $request, SalesChannelContext $salesChannelContext): CheckoutFinishPage
	{
		$page = CheckoutFinishPage::createFrom($this->genericLoader->load($request, $salesChannelContext));
		$page->setOrder($this->getOrder($request->get('orderId'), $salesChannelContext->getContext()));

		return $page;
	}

	/**
	 * @param String                           $orderId
	 * @param \Shopware\Core\Framework\Context $context
	 * @return \Shopware\Core\Checkout\Order\OrderEntity
	 */
	private function getOrder(String $orderId, Context $context): OrderEntity
	{
		if (empty($orderId)) {
			throw new MissingRequestParameterException('orderId', '/orderId');
		}

		$criteria = (new Criteria([$orderId]))->addAssociations([
			'lineItems.cover',
			'transactions.paymentMethod',
			'deliveries.shippingMethod',
		]);

		try {
			$searchResult = $this->container->get('order.repository')->search(
				$criteria,
				$context
			);
		} catch (\Exception $e) {
			throw new OrderNotFoundException($orderId);
		}

		/** @var OrderEntity|null $order */
		$order = $searchResult->get($orderId);

		if (!$order) {
			throw new OrderNotFoundException($orderId);
		}

		return $order;
	}
}