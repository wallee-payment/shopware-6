<?php declare(strict_types=1);

namespace WalleePayment\Core\Storefront\Checkout\Controller;

use Psr\Log\LoggerInterface;
use Shopware\Core\{
	Checkout\Cart\Cart,
	Checkout\Cart\Exception\OrderNotFoundException,
	Checkout\Cart\LineItemFactoryRegistry,
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
	HttpFoundation\Request,
	HttpFoundation\Response,
	Routing\Annotation\Route};
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wallee\Sdk\{
	Model\Transaction,
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
	 * @var \Shopware\Core\Checkout\Cart\LineItemFactoryRegistry
	 */
	private $lineItemFactoryRegistry;

	/**
	 * PaymentController constructor.
	 *
	 * @param \Shopware\Core\Checkout\Cart\LineItemFactoryRegistry                          $lineItemFactoryRegistry
	 * @param \Shopware\Core\Checkout\Cart\SalesChannel\CartService                         $cartService
	 * @param \WalleePayment\Core\Settings\Service\SettingsService           $settingsService
	 * @param \WalleePayment\Core\Api\Transaction\Service\TransactionService $transactionService
	 * @param \Shopware\Storefront\Page\GenericPageLoader                                   $genericLoader
	 */
	public function __construct(
		LineItemFactoryRegistry $lineItemFactoryRegistry,
		CartService $cartService,
		SettingsService $settingsService,
		TransactionService $transactionService,
		GenericPageLoader $genericLoader
	)
	{
		$this->cartService             = $cartService;
		$this->genericLoader           = $genericLoader;
		$this->settingsService         = $settingsService;
		$this->transactionService      = $transactionService;
		$this->lineItemFactoryRegistry = $lineItemFactoryRegistry;
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
	 * @param \Shopware\Core\System\SalesChannel\SalesChannelContext $salesChannelContext
	 * @param \Symfony\Component\HttpFoundation\Request              $request
	 *
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

		$transaction     = $this->getTransaction($orderId, $salesChannelContext->getContext());
		$recreateCartUrl = $this->generateUrl(
			'frontend.wallee.checkout.recreate-cart',
			['orderId' => $orderId,],
			UrlGeneratorInterface::ABSOLUTE_URL
		);

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

		$possiblePaymentMethods = $this->settings->getApiClient()
												 ->getTransactionService()
												 ->fetchPaymentMethods(
													 $this->settings->getSpaceId(),
													 $transaction->getId(),
													 $this->settings->getIntegration()
												 );

		if (empty($possiblePaymentMethods)) {
			$this->addFlash('danger', $this->trans('wallee.paymentMethod.notAvailable'));
			return $this->redirect($recreateCartUrl, Response::HTTP_MOVED_PERMANENTLY);
		}

		$javascriptUrl = $this->getTransactionJavaScriptUrl($transaction->getId());

		// Set Checkout Page Data
		$checkoutPageData = (new CheckoutPageData())
			->setIntegration($this->settings->getIntegration())
			->setJavascriptUrl($javascriptUrl)
			->setDeviceJavascriptUrl($this->settings->getSpaceId(), $this->container->get('session')->getId())
			->setTransactionPossiblePaymentMethods($possiblePaymentMethods)
			->setCheckoutUrl($this->generateUrl(
				'frontend.wallee.checkout.pay',
				['orderId' => $orderId,],
				UrlGeneratorInterface::ABSOLUTE_URL
			))
			->setCartRecreateUrl($recreateCartUrl);
		$page             = $this->load($request, $salesChannelContext);
		$page->addExtension('walleeData', $checkoutPageData);

		return $this->renderStorefront(
			'@WalleePayment/storefront/page/checkout/order/wallee.html.twig',
			['page' => $page]
		);
	}

	/**
	 * Get transaction Javascript URL
	 *
	 * @param int $transactionId
	 *
	 * @return string
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 */
	private function getTransactionJavaScriptUrl(int $transactionId): string
	{
		$javascriptUrl = '';
		switch ($this->settings->getIntegration()) {
			case Integration::IFRAME:
				$javascriptUrl = $this->settings->getApiClient()->getTransactionIframeService()
												->javascriptUrl($this->settings->getSpaceId(), $transactionId);
				break;
			case Integration::LIGHTBOX:
				$javascriptUrl = $this->settings->getApiClient()->getTransactionLightboxService()
												->javascriptUrl($this->settings->getSpaceId(), $transactionId);
				break;
			default:
				$this->logger->critical(strtr('invalid integration : :integration', [':integration' => $this->settings->getIntegration()]));

		}
		return $javascriptUrl;
	}

	/**
	 * @param                                  $orderId
	 * @param \Shopware\Core\Framework\Context $context
	 *
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
	 * @param \Symfony\Component\HttpFoundation\Request              $request
	 * @param \Shopware\Core\System\SalesChannel\SalesChannelContext $salesChannelContext
	 *
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
	 *
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
	 *
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
				$lineItem = $this->lineItemFactoryRegistry->create([
					'id'           => $orderLineItemEntity->getId(),
					'quantity'     => $orderLineItemEntity->getQuantity(),
					'referencedId' => $orderLineItemEntity->getReferencedId(),
					'type'         => $orderLineItemEntity->getType(),
				], $salesChannelContext);
				$cart     = $this->cartService->add($cart, $lineItem, $salesChannelContext);
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
}