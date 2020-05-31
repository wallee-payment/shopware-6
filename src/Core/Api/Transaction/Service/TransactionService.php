<?php declare(strict_types=1);

namespace WalleePayment\Core\Api\Transaction\Service;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\{
	Checkout\Payment\Cart\AsyncPaymentTransactionStruct,
	Framework\Context,
	Framework\DataAbstractionLayer\Search\Criteria,
	System\SalesChannel\SalesChannelContext};
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wallee\Sdk\{
	Model\Transaction,
	Model\TransactionPending};
use WalleePayment\Core\{
	Api\Transaction\Entity\TransactionEntity,
	Settings\Options\Integration,
	Settings\Service\SettingsService};
use WalleePayment\Util\{
	LocaleCodeProvider,
	Payload\TransactionPayload};

/**
 * Class TransactionService
 *
 * @package WalleePayment\Core\Api\Transaction\Service
 */
class TransactionService {
	/**
	 * @var \Psr\Container\ContainerInterface
	 */
	protected $container;

	/**
	 * @var \WalleePayment\Util\LocaleCodeProvider
	 */
	private $localeCodeProvider;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $logger;

	/**
	 * @var \WalleePayment\Core\Settings\Service\SettingsService
	 */
	private $settingsService;

	/**
	 * TransactionService constructor.
	 * @param \Psr\Container\ContainerInterface                                   $container
	 * @param \WalleePayment\Util\LocaleCodeProvider               $localeCodeProvider
	 * @param \WalleePayment\Core\Settings\Service\SettingsService $settingsService
	 * @param \Psr\Log\LoggerInterface                                            $logger
	 */
	public function __construct(
		ContainerInterface $container,
		LocaleCodeProvider $localeCodeProvider,
		SettingsService $settingsService,
		LoggerInterface $logger
	)
	{
		$this->container          = $container;
		$this->localeCodeProvider = $localeCodeProvider;
		$this->settingsService    = $settingsService;
		$this->logger             = $logger;
	}


	/**
	 * The pay function will be called after the customer completed the order.
	 * Allows to process the order and store additional information.
	 *
	 * A redirect to the url will be performed
	 *
	 * @param \Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct $transaction
	 * @param \Shopware\Core\System\SalesChannel\SalesChannelContext             $salesChannelContext
	 * @return string
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 */
	public function create(
		AsyncPaymentTransactionStruct $transaction,
		SalesChannelContext $salesChannelContext
	): String
	{
		$settings  = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
		$apiClient = $settings->getApiClient();

		$transactionPayload = (new TransactionPayload(
			$this->container,
			$this->localeCodeProvider,
			$salesChannelContext,
			$settings,
			$transaction,
			$this->logger
		))->get();

		$createdTransaction = $apiClient->getTransactionService()->create($settings->getSpaceId(), $transactionPayload);

		$this->addWalleeTransactionId(
			$transaction,
			$salesChannelContext->getContext(),
			$createdTransaction->getId(),
			$settings->getSpaceId()
		);

		$redirectUrl = $this->container->get('router')->generate(
			'wallee.order',
			['orderId' => $transaction->getOrder()->getId(),],
			UrlGeneratorInterface::ABSOLUTE_URL
		);

		if ($settings->getIntegration() == Integration::PAYMENT_PAGE) {

			$pendingTransaction = new TransactionPending();
			$pendingTransaction->setId($createdTransaction->getId());
			$pendingTransaction->setVersion($createdTransaction->getVersion());

			$createdTransaction = $apiClient->getTransactionService()
											->confirm($settings->getSpaceId(), $pendingTransaction);
			$redirectUrl        = $apiClient->getTransactionPaymentPageService()
											->paymentPageUrl($settings->getSpaceId(), $createdTransaction->getId());
		}

		$this->upsert(
			$salesChannelContext->getContext(),
			$createdTransaction,
			$transaction->getOrderTransaction()->getPaymentMethodId(),
			$transaction->getOrder()->getSalesChannelId()
		);

		return $redirectUrl;
	}

	/**
	 * @param \Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct $transaction
	 * @param \Shopware\Core\Framework\Context                                   $context
	 * @param int                                                                $walleeTransactionId
	 * @param int                                                                $spaceId
	 */
	protected function addWalleeTransactionId(
		AsyncPaymentTransactionStruct $transaction,
		Context $context,
		int $walleeTransactionId,
		int $spaceId
	): void
	{
		$data = [
			'id'           => $transaction->getOrderTransaction()->getId(),
			'customFields' => [
				TransactionPayload::ORDER_TRANSACTION_CUSTOM_FIELDS_WALLEE_TRANSACTION_ID => $walleeTransactionId,
				TransactionPayload::ORDER_TRANSACTION_CUSTOM_FIELDS_WALLEE_SPACE_ID       => $spaceId,
			],
		];
		$this->container->get('order_transaction.repository')->update([$data], $context);
	}

	/**
	 * Persist Wallee transaction
	 *
	 * @param \Shopware\Core\Framework\Context             $context
	 * @param \Wallee\Sdk\Model\Transaction $transaction
	 * @param string|null                                  $paymentMethodId
	 * @param string|null                                  $salesChannelId
	 */
	public function upsert(
		Context $context,
		Transaction $transaction,
		string $paymentMethodId = null,
		string $salesChannelId = null
	)
	{
		try {

			$transactionMetaData = $transaction->getMetaData();
			$orderId             = $transactionMetaData['orderId'];
			$orderTransactionId  = $transactionMetaData['orderTransactionId'];

			$data = [
				'id'                 => $orderId,
				'data'               => json_decode(strval($transaction), true),
				'paymentMethodId'    => $paymentMethodId,
				'orderId'            => $orderId,
				'orderTransactionId' => $orderTransactionId,
				'spaceId'            => $transaction->getLinkedSpaceId(),
				'state'              => $transaction->getState(),
				'salesChannelId'     => $salesChannelId,
				'transactionId'      => $transaction->getId(),
			];

			$data = array_filter($data);
			$this->container->get('wallee_transaction.repository')->upsert([$data], $context);

		} catch (\Exception $exception) {
			$this->logger->critical(__CLASS__ . ' : ' . __FUNCTION__ . ' : ' . $exception->getMessage());
		}
	}

	/**
	 * Get transaction entity by orderId
	 *
	 * @param string                           $orderId
	 * @param \Shopware\Core\Framework\Context $context
	 * @return \WalleePayment\Core\Api\Transaction\Entity\TransactionEntity
	 */
	public function getByOrderId(string $orderId, Context $context): TransactionEntity
	{
		$transactionEntity = $this->container->get('wallee_transaction.repository')
											 ->search(new Criteria([$orderId]), $context)->get($orderId);
		return $transactionEntity;
	}

	/**
	 * Get transaction entity by Wallee transaction id
	 *
	 * @param int                              $transactionId
	 * @param \Shopware\Core\Framework\Context $context
	 * @return \WalleePayment\Core\Api\Transaction\Entity\TransactionEntity
	 */
	public function getByTransactionId(int $transactionId, Context $context): TransactionEntity
	{
		$transactionEntity = $this->container->get('wallee_transaction.repository')
											 ->search(new Criteria(), $context)
											 ->getEntities()
											 ->getByTransactionId($transactionId);
		return $transactionEntity;
	}

}