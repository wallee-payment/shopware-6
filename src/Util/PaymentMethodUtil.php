<?php declare(strict_types=1);

namespace WalleePayment\Util;

use Psr\Log\LoggerInterface;
use Shopware\Core\{
	Framework\Context,
	Framework\DataAbstractionLayer\EntityCollection,
	Framework\DataAbstractionLayer\EntityRepositoryInterface,
	Framework\DataAbstractionLayer\Search\Criteria,
	Framework\DataAbstractionLayer\Search\Filter\EqualsFilter,};
use WalleePayment\Core\Checkout\PaymentHandler\WalleePaymentHandler;

/**
 * Class PaymentMethodUtil
 *
 * @package WalleePayment\Util
 */
class PaymentMethodUtil {

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * @var \Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface
	 */
	private $paymentRepository;

	/**
	 * @var \Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface
	 */
	private $salesChannelRepository;


	/**
	 * PaymentMethodUtil constructor.
	 *
	 * @param \Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface $paymentRepository
	 * @param \Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface $salesChannelRepository
	 * @param \Psr\Log\LoggerInterface                                                $logger
	 */
	public function __construct(
		EntityRepositoryInterface $paymentRepository,
		EntityRepositoryInterface $salesChannelRepository,
		LoggerInterface $logger)
	{
		$this->paymentRepository      = $paymentRepository;
		$this->salesChannelRepository = $salesChannelRepository;
		$this->logger                 = $logger;
	}

	/**
	 * @param \Shopware\Core\Framework\Context $context
	 * @param string|null                      $salesChannelId
	 */
	public function setWalleeAsDefaultPaymentMethod(Context $context, ?string $salesChannelId): void
	{
		$paymentMethodIds = $this->getWalleePaymentMethodIds($context);
		if (empty($paymentMethodIds)) {
			return;
		}

		$salesChannelsToChange = $this->getSalesChannelsToChange($context, $salesChannelId);
		$updateData            = [];

		foreach ($salesChannelsToChange as $salesChannel) {
			foreach ($paymentMethodIds as $paymentMethodId) {
				$salesChannelUpdateData = [
					'id'              => $salesChannel->getId(),
					'paymentMethodId' => $paymentMethodId,
				];

				$paymentMethodCollection = $salesChannel->getPaymentMethods();
				if (is_null($paymentMethodCollection) || is_null($paymentMethodCollection->get($paymentMethodId))) {
					$salesChannelUpdateData['paymentMethods'][] = [
						'id' => $paymentMethodId,
					];
				}

				$updateData[] = $salesChannelUpdateData;
			}
		}

		$this->salesChannelRepository->update($updateData, $context);
	}

	/**
	 * @param \Shopware\Core\Framework\Context $context
	 * @return array
	 */
	public function getWalleePaymentMethodIds(Context $context): array
	{
		$criteria = (new Criteria())
			->addFilter(new EqualsFilter('handlerIdentifier', WalleePaymentHandler::class));

		return $this->paymentRepository->searchIds($criteria, $context)->getIds();
	}

	/**
	 * @param \Shopware\Core\Framework\Context $context
	 * @param string|null                      $salesChannelId
	 * @return \Shopware\Core\Framework\DataAbstractionLayer\EntityCollection
	 */
	private function getSalesChannelsToChange(Context $context, ?string $salesChannelId): EntityCollection
	{
		$criteria = is_null($salesChannelId) ? new Criteria() : new Criteria([$salesChannelId]);
		$criteria->addAssociation('paymentMethods');

		return $this->salesChannelRepository->search($criteria, $context)->getEntities();
	}
}