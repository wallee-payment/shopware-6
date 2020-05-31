<?php declare(strict_types=1);

namespace WalleePayment\Core\Api\Transaction\Controller;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\{
	HttpFoundation\JsonResponse,
	HttpFoundation\Request,
	HttpFoundation\Response,
	Routing\Annotation\Route,};
use WalleePayment\Core\Settings\Service\SettingsService;
use WalleePayment\Util\Payload\RefundPayload;


/**
 * Class RefundController
 *
 * @package WalleePayment\Core\Api\Transaction\Controller
 *
 * @RouteScope(scopes={"api"})
 */
class RefundController extends AbstractController {

	/**
	 * @var \WalleePayment\Core\Settings\Service\SettingsService
	 */
	protected $settingsService;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * RefundController constructor.
	 *
	 * @param \WalleePayment\Core\Settings\Service\SettingsService $settingsService
	 * @param \Psr\Log\LoggerInterface                                            $logger
	 */
	public function __construct(SettingsService $settingsService, LoggerInterface $logger)
	{
		$this->settingsService = $settingsService;
		$this->logger          = $logger;
	}

	/**
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 *
	 * @Route(
	 *     "/api/v{version}/_action/wallee/refund/create-refund/",
	 *     name="api.action.wallee.refund.create-refund",
	 *     methods={"POST"}
	 *     )
	 */
	public function createRefund(Request $request): JsonResponse
	{
		$salesChannelId   = $request->request->get('salesChannelId');
		$transactionId    = $request->request->get('transactionId');
		$refundableAmount = $request->request->get('refundableAmount');

		$settings  = $this->settingsService->getSettings($salesChannelId);
		$apiClient = $settings->getApiClient();

		$transaction   = $apiClient->getTransactionService()->read($settings->getSpaceId(), $transactionId);
		$refundPayload = (new RefundPayload())->get($transaction, $refundableAmount);

		if (!is_null($refundPayload)) {
			$refund = $apiClient->getRefundService()->refund($settings->getSpaceId(), $refundPayload);
			return new JsonResponse(strval($refund), Response::HTTP_OK, [], true);
		}

		return new JsonResponse(
			['message' => 'Refund amount is greater than transaction amount.'],
			Response::HTTP_INTERNAL_SERVER_ERROR
		);
	}
}