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
	HttpFoundation\Response,
	Routing\Annotation\Route,};
use WalleePayment\Core\{
	Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService,
	Api\WebHooks\Service\WebHooksService};
use WalleePayment\Util\PaymentMethodUtil;

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
	 * @var \WalleePayment\Util\PaymentMethodUtil
	 */
	private $paymentMethodUtil;

	/**
	 * @var \WalleePayment\Core\Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService
	 */
	private $paymentMethodConfigurationService;

	/**
	 * ConfigurationController constructor.
	 *
	 * @param \WalleePayment\Util\PaymentMethodUtil                                                        $paymentMethodUtil
	 * @param \WalleePayment\Core\Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService $paymentMethodConfigurationService
	 * @param \WalleePayment\Core\Api\WebHooks\Service\WebHooksService                                     $webHooksService
	 * @param \Psr\Log\LoggerInterface                                                                                    $logger
	 */
	public function __construct(
		PaymentMethodUtil $paymentMethodUtil,
		PaymentMethodConfigurationService $paymentMethodConfigurationService,
		WebHooksService $webHooksService,
		LoggerInterface $logger
	)
	{
		$this->paymentMethodUtil                 = $paymentMethodUtil;
		$this->webHooksService                   = $webHooksService;
		$this->paymentMethodConfigurationService = $paymentMethodConfigurationService;
		$this->logger                            = $logger;
	}

	/**
	 * Set WalleePayment as the default payment for a give sales channel
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Shopware\Core\Framework\Context          $context
	 * @return \Symfony\Component\HttpFoundation\Response
	 *
	 * @Route(
	 *     "/api/v{version}/_action/wallee/configuration/set-wallee-as-sales-channel-payment-default",
	 *     name="api.action.wallee.configuration.set-wallee-as-sales-channel-payment-default",
	 *     methods={"POST"}
	 *     )
	 */
	public function setWalleeAsSalesChannelPaymentDefault(Request $request, Context $context): Response
	{
		$salesChannelId = $request->request->get('salesChannelId');
		$salesChannelId = ($salesChannelId == 'null') ? null : $salesChannelId;

		$this->paymentMethodUtil->setWalleeAsDefaultPaymentMethod($context, $salesChannelId);
		return new Response(null, Response::HTTP_NO_CONTENT);
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
}