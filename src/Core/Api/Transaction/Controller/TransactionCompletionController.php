<?php declare(strict_types=1);

namespace WalleePayment\Core\Api\Transaction\Controller;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\{
	HttpFoundation\JsonResponse,
	HttpFoundation\Request,
	HttpFoundation\Response,
	Routing\Annotation\Route};
use Wallee\Sdk\{
	Model\TransactionState};
use WalleePayment\Core\Settings\Service\SettingsService;


/**
 * Class TransactionCompletionController
 *
 * @package WalleePayment\Core\Api\Transaction\Controller
 *
 * @RouteScope(scopes={"api"})
 */
class TransactionCompletionController extends AbstractController {

	/**
	 * @var \WalleePayment\Core\Settings\Service\SettingsService
	 */
	protected $settingsService;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * TransactionCompletionController constructor.
	 *
	 * @param \WalleePayment\Core\Settings\Service\SettingsService $settingsService
	 */
	public function __construct(SettingsService $settingsService)
	{
		$this->settingsService = $settingsService;
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
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 *
	 * @Route(
	 *     "/api/_action/wallee/transaction-completion/create-transaction-completion/",
	 *     name="api.action.wallee.transaction-completion.create-transaction-completion",
	 *     methods={"POST"}
	 *     )
	 */
	public function createTransactionCompletion(Request $request): JsonResponse
	{
		$salesChannelId = $request->request->get('salesChannelId');
		$transactionId  = $request->request->get('transactionId');

		$settings  = $this->settingsService->getSettings($salesChannelId);
		$apiClient = $settings->getApiClient();


		$transaction = $apiClient->getTransactionService()->read($settings->getSpaceId(), $transactionId);
		if ($transaction->getState() == TransactionState::AUTHORIZED) {
			$transactionCompletion = $apiClient->getTransactionCompletionService()->completeOnline($settings->getSpaceId(), $transaction->getId());
			return new JsonResponse(strval($transactionCompletion), Response::HTTP_OK, [], true);
		}

		return new JsonResponse(
			[
				'message' => strtr('Transaction is in state {state}, it can not be completed at this time', ['{state}' => $transaction->getState()]),
			],
			Response::HTTP_NOT_ACCEPTABLE
		);
	}
}