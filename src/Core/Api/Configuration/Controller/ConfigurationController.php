<?php declare(strict_types=1);

namespace WalleePayment\Core\Api\Configuration\Controller;

use Psr\Log\LoggerInterface;
use Shopware\Core\{
	Framework\Context,
	Framework\Routing\Annotation\RouteScope,};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\{
	HttpFoundation\JsonResponse,
	HttpFoundation\Request,
	Routing\Annotation\Route};
use WalleePayment\Core\{
	Api\OrderDeliveryState\Service\OrderDeliveryStateService,
	Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService,
	Api\WebHooks\Service\WebHooksService,
	Util\PaymentMethodUtil};

/**
 * Class ConfigurationController
 *
 * This class handles web calls that are made via the WhiteLabelMachinePayment settings page.
 *
 * @package WalleePayment\Core\Api\Config\Controller
 * @RouteScope(scopes={"api"})
 */
class ConfigurationController extends AbstractController {

	/**
	 * @var \WalleePayment\Core\Api\WebHooks\Service\WebHooksService
	 */
	protected $webHooksService;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * @var \WalleePayment\Core\Util\PaymentMethodUtil
	 */
	private $paymentMethodUtil;

	/**
	 * @var \WalleePayment\Core\Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService
	 */
	private $paymentMethodConfigurationService;

	/**
	 * ConfigurationController constructor.
	 *
	 * @param \WalleePayment\Core\Util\PaymentMethodUtil                                                   $paymentMethodUtil
	 * @param \WalleePayment\Core\Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService $paymentMethodConfigurationService
	 * @param \WalleePayment\Core\Api\WebHooks\Service\WebHooksService                                     $webHooksService
	 */
	public function __construct(
		PaymentMethodUtil $paymentMethodUtil,
		PaymentMethodConfigurationService $paymentMethodConfigurationService,
		WebHooksService $webHooksService
	)
	{
		$this->webHooksService   = $webHooksService;
		$this->paymentMethodUtil = $paymentMethodUtil;

		$this->paymentMethodConfigurationService = $paymentMethodConfigurationService;
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
	 * Set WalleePayment as the default payment for a give sales channel
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Shopware\Core\Framework\Context          $context
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 *
	 * @Route(
	 *     "/api/v{version}/_action/wallee/configuration/set-wallee-as-sales-channel-payment-default",
	 *     name="api.action.wallee.configuration.set-wallee-as-sales-channel-payment-default",
	 *     methods={"POST"}
	 *     )
	 */
	public function setWalleeAsSalesChannelPaymentDefault(Request $request, Context $context): JsonResponse
	{
		$salesChannelId = $request->request->get('salesChannelId');
		$salesChannelId = ($salesChannelId == 'null') ? null : $salesChannelId;

		$this->paymentMethodUtil->setWalleeAsDefaultPaymentMethod($context, $salesChannelId);
		return new JsonResponse([]);
	}

	/**
	 * Register web hooks
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 *
	 * @Route(
	 *     "/api/v{version}/_action/wallee/configuration/register-web-hooks",
	 *     name="api.action.wallee.configuration.register-web-hooks",
	 *     methods={"POST"}
	 *   )
	 */
	public function registerWebHooks(Request $request): JsonResponse
	{
		$salesChannelId = $request->request->get('salesChannelId');
		$salesChannelId = ($salesChannelId == 'null') ? null : $salesChannelId;

		$result = $this->webHooksService->setSalesChannelId($salesChannelId)->install();

		return new JsonResponse(['result' => $result]);
	}

	/**
	 * Synchronize payment method configurations
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Shopware\Core\Framework\Context          $context
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 *
	 * @Route(
	 *     "/api/v{version}/_action/wallee/configuration/synchronize-payment-method-configuration",
	 *     name="api.action.wallee.configuration.synchronize-payment-method-configuration",
	 *     methods={"POST"}
	 *   )
	 */
	public function synchronizePaymentMethodConfiguration(Request $request, Context $context): JsonResponse
	{
		$salesChannelId = $request->request->get('salesChannelId');
		$salesChannelId = ($salesChannelId == 'null') ? null : $salesChannelId;

		$result = $this->paymentMethodConfigurationService->setSalesChannelId($salesChannelId)->synchronize($context);

		return new JsonResponse(['result' => $result]);
	}

	/**
	 * Install OrderDeliveryStates
	 *
	 * @param \Shopware\Core\Framework\Context $context
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 *
	 * @Route(
	 *     "/api/v{version}/_action/wallee/configuration/install-order-delivery-states",
	 *     name="api.action.wallee.configuration.install-order-delivery-states",
	 *     methods={"POST"}
	 *   )
	 */
	public function installOrderDeliveryStates(Context $context): JsonResponse
	{
		/**
		 * @var \WalleePayment\Core\Api\OrderDeliveryState\Service\OrderDeliveryStateService $orderDeliveryStateService
		 */
		$orderDeliveryStateService = $this->container->get(OrderDeliveryStateService::class);
		$orderDeliveryStateService->install($context);

		return new JsonResponse([]);
	}
}