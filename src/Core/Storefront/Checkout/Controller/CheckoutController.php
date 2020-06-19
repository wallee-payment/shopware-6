<?php declare(strict_types=1);

namespace WalleePayment\Core\Storefront\Checkout\Controller;

use Psr\Log\LoggerInterface;
use Shopware\Core\{
	Checkout\Cart\Cart,
	Checkout\Cart\Exception\OrderNotFoundException,
	Checkout\Cart\LineItem\LineItem,
	Checkout\Cart\SalesChannel\CartService,
	Checkout\Order\OrderEntity,
	Content\Product\Exception\ProductNotFoundException,
	Framework\Context,
	Framework\DataAbstractionLayer\Search\Criteria,
	Framework\Routing\Annotation\RouteScope,
	Framework\Routing\Exception\MissingRequestParameterException,
	System\SalesChannel\SalesChannelContext};
use Shopware\Storefront\{
	Controller\StorefrontController,
	Page\Checkout\Finish\CheckoutFinishPage,
	Page\GenericPageLoader,};
use Symfony\Component\{
	HttpFoundation\JsonResponse,
	HttpFoundation\Request,
	HttpFoundation\Response,
	Routing\Annotation\Route};
use Wallee\Sdk\{
	Model\Transaction,
	Model\TransactionPending,
	Model\TransactionState};
use WalleePayment\Core\{
	Api\Transaction\Service\TransactionService,
	Settings\Options\Integration,
	Settings\Service\SettingsService,
	Storefront\Checkout\Struct\CheckoutPageData};

/**
 * Class CheckoutController
 *
 * @package WalleePayment\Core\Storefront\Checkout\Controller
 *
 * @RouteScope(scopes={"storefront"})
 */
class CheckoutController extends StorefrontController {

	/**
	 * @var \Shopware\Storefront\Page\GenericPageLoader
	 */
	protected $genericLoader;

	/**
	 * @var \Shopware\Core\Checkout\Cart\SalesChannel\CartService
	 */
	protected $cartService;

	/**
	 * @var \WalleePayment\Core\Settings\Service\SettingsService
	 */
	protected $settingsService;

	/**
	 * @var \WalleePayment\Core\Settings\Struct\Settings
	 */
	protected $settings;

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
	 * @param \Shopware\Core\Checkout\Cart\SalesChannel\CartService                         $cartService
	 * @param \WalleePayment\Core\Settings\Service\SettingsService           $settingsService
	 * @param \WalleePayment\Core\Api\Transaction\Service\TransactionService $transactionService
	 * @param \Shopware\Storefront\Page\GenericPageLoader                                   $genericLoader
	 * @param \Psr\Log\LoggerInterface                                                      $logger
	 */
	public function __construct(
		CartService $cartService,
		SettingsService $settingsService,
		TransactionService $transactionService,
		GenericPageLoader $genericLoader,
		LoggerInterface $logger
	)
	{
		$this->logger             = $logger;
		$this->cartService        = $cartService;
		$this->genericLoader      = $genericLoader;
		$this->settingsService    = $settingsService;
		$this->transactionService = $transactionService;
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
	 *     "/wallee/checkout/pay",
	 *     name="frontend.wallee.checkout.pay",
	 *     options={"seo": "false"},
	 *     methods={"GET"}
	 *     )
	 */
	public function pay(SalesChannelContext $salesChannelContext, Request $request): Response
	{
		$orderId = $request->query->get('orderId');

		if (empty($orderId)) {
			throw new MissingRequestParameterException('orderId');
		}

		// Configuration
		$this->settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());

		$transaction = $this->getTransaction($orderId, $salesChannelContext->getContext());

		if (in_array(
			$transaction->getState(),
			[
				TransactionState::AUTHORIZED,
				TransactionState::COMPLETED,
				TransactionState::FULFILL,
				TransactionState::PROCESSING,
			]
		)) {
			return $this->redirect($transaction->getSuccessUrl(), Response::HTTP_MOVED_PERMANENTLY);
		} else {
			if (in_array(
				$transaction->getState(),
				[
					TransactionState::DECLINE,
					TransactionState::FAILED,
					TransactionState::VOIDED,
				]
			)) {
				return $this->redirect($transaction->getFailedUrl(), Response::HTTP_MOVED_PERMANENTLY);
			}
		}

		$page          = $this->load($request, $salesChannelContext);
		$javascriptUrl = '';

		$possiblePaymentMethods = $this->settings->getApiClient()
												 ->getTransactionService()
												 ->fetchPaymentMethods(
													 $this->settings->getSpaceId(),
													 $transaction->getId(),
													 $this->settings->getIntegration()
												 );

		switch ($this->settings->getIntegration()) {
			case Integration::IFRAME:
				$javascriptUrl = $this->settings->getApiClient()->getTransactionIframeService()
												->javascriptUrl($this->settings->getSpaceId(), $transaction->getId());
				break;
			case Integration::LIGHTBOX:
				$javascriptUrl = $this->settings->getApiClient()->getTransactionLightboxService()
												->javascriptUrl($this->settings->getSpaceId(), $transaction->getId());
				break;
			default:
				$this->logger->critical(strtr('invalid integration : :integration', [':integration' => $this->settings->getIntegration()]));

		}

		// Set Checkout Page Data
		$checkoutPageData = (new CheckoutPageData())
			->setIntegration($this->settings->getIntegration())
			->setJavascriptUrl($javascriptUrl)
			->setDeviceJavascriptUrl($this->settings->getSpaceId(), $this->container->get('session')->getId())
			->setTransactionPossiblePaymentMethods($possiblePaymentMethods);

		$page->addExtension('walleeData', $checkoutPageData);

		return $this->renderStorefront(
			'@WalleePayment/storefront/page/checkout/order/wallee.html.twig',
			['page' => $page]
		);
	}

	/**
	 * @param \Symfony\Component\HttpFoundation\Request              $request
	 * @param \Shopware\Core\System\SalesChannel\SalesChannelContext $salesChannelContext
	 * @return \Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPage
	 */
	protected function load(Request $request, SalesChannelContext $salesChannelContext): CheckoutFinishPage
	{
		$page = CheckoutFinishPage::createFrom($this->genericLoader->load($request, $salesChannelContext));
		$page->setOrder($this->getOrder($request->get('orderId'), $salesChannelContext->getContext()));

		return $page;
	}

	/**
	 * @param string                           $orderId
	 * @param \Shopware\Core\Framework\Context $context
	 * @return \Shopware\Core\Checkout\Order\OrderEntity
	 */
	private function getOrder(string $orderId, Context $context): OrderEntity
	{
		$criteria = (new Criteria([$orderId]))->addAssociations([
			'lineItems.cover',
			'transactions.paymentMethod',
			'deliveries.shippingMethod',
		]);

		try {
			$order = $this->container->get('order.repository')->search(
				$criteria,
				$context
			)->first();
		} catch (\Exception $exception) {
			$this->logger->notice($exception->getMessage());
			throw new OrderNotFoundException($orderId);
		}

		if (is_null($order)) {
			throw new OrderNotFoundException($orderId);
		}

		return $order;
	}

	/**
	 * Recreate Cart
	 *
	 * @param \Shopware\Core\Checkout\Cart\Cart                      $cart
	 * @param \Symfony\Component\HttpFoundation\Request              $request
	 * @param \Shopware\Core\System\SalesChannel\SalesChannelContext $salesChannelContext
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 *
	 * @Route(
	 *     "/wallee/checkout/recreate-cart",
	 *     name="frontend.wallee.checkout.recreate-cart",
	 *     options={"seo": "false"},
	 *     methods={"GET"}
	 *     )
	 */
	public function recreateCart(Cart $cart, Request $request, SalesChannelContext $salesChannelContext)
	{
		$orderId = $request->query->get('orderId');

		if (empty($orderId)) {
			throw new MissingRequestParameterException('orderId');
		}

		// Configuration
		$this->settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());

		$orderEntity = $this->getOrder($orderId, $salesChannelContext->getContext());

		try {
			foreach ($orderEntity->getLineItems() as $orderLineItemEntity) {
				$lineItem = (new LineItem($orderLineItemEntity->getProductId(), $orderLineItemEntity->getType()))
					->setStackable($orderLineItemEntity->getStackable())
					->setReferencedId($orderLineItemEntity->getReferencedId())
					->setQuantity($orderLineItemEntity->getQuantity())
					->setRemovable($orderLineItemEntity->getRemovable());

				$cart = $this->cartService->add($cart, $lineItem, $salesChannelContext);
			}
			$transaction = $this->getTransaction($orderId, $salesChannelContext->getContext());
			if (!empty($transaction->getUserFailureMessage())) {
				$this->addFlash('danger', $transaction->getUserFailureMessage());
			}

		} catch (ProductNotFoundException $exception) {
			$this->addFlash('danger', $this->trans('error.addToCartError'));
		}

		return $this->redirectToRoute('frontend.checkout.confirm.page');
	}

	/**
	 * @param                                  $orderId
	 * @param \Shopware\Core\Framework\Context $context
	 * @return \Wallee\Sdk\Model\Transaction
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 */
	private function getTransaction($orderId, Context $context): Transaction
	{
		$transactionEntity = $this->transactionService->getByOrderId($orderId, $context);
		return $this->settings->getApiClient()->getTransactionService()->read($this->settings->getSpaceId(), $transactionEntity->getTransactionId());
	}

	/**
	 * Confirm Transaction
	 *
	 * @param \Shopware\Core\System\SalesChannel\SalesChannelContext $salesChannelContext
	 * @param \Symfony\Component\HttpFoundation\Request              $request
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 *
	 * @Route(
	 *     "/wallee/checkout/confirm",
	 *     name="frontend.wallee.confirm",
	 *     options={"seo": "false"},
	 *     methods={"GET"}
	 *     )
	 */
	public function confirm(Request $request, SalesChannelContext $salesChannelContext): Response
	{
		$orderId = $request->query->get('orderId');

		if (empty($orderId)) {
			throw new MissingRequestParameterException('orderId');
		}

		// Configuration
		$this->settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());

		$transaction = $this->getTransaction($orderId, $salesChannelContext->getContext());

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

		$this->settings->getApiClient()->getTransactionService()->confirm($this->settings->getSpaceId(), $pendingTransaction);

		return new JsonResponse([]);
	}
}