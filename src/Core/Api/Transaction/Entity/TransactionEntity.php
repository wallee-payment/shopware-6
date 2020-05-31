<?php declare(strict_types=1);

namespace WalleePayment\Core\Api\Transaction\Entity;

use Shopware\Core\{
	Framework\DataAbstractionLayer\Entity,
	Framework\DataAbstractionLayer\EntityIdTrait};

/**
 * Class TransactionEntity
 *
 * @package WalleePayment\Core\Api\Transaction\Entity
 */
class TransactionEntity extends Entity {

	use EntityIdTrait;

	/**
	 * @var array
	 */
	protected $data;

	/**
	 * @var string
	 */
	protected $paymentMethodId;

	/**
	 * @var string
	 */
	protected $orderId;

	/**
	 * @var string
	 */
	protected $orderTransactionId;

	/**
	 * @var int
	 */
	protected $spaceId;

	/**
	 * @var string
	 */
	protected $state;

	/**
	 * @var string
	 */
	protected $salesChannelId;

	/**
	 * @var int
	 */
	protected $transactionId;

	/**
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}

	/**
	 * @param array $data
	 */
	public function setData(array $data): void
	{
		$this->data = $data;
	}

	/**
	 * @return string
	 */
	public function getPaymentMethodId(): string
	{
		return $this->paymentMethodId;
	}

	/**
	 * @param string $paymentMethodId
	 */
	public function setPaymentMethodId(string $paymentMethodId): void
	{
		$this->paymentMethodId = $paymentMethodId;
	}

	/**
	 * @return string
	 */
	public function getOrderId(): string
	{
		return $this->orderId;
	}

	/**
	 * @param string $orderId
	 */
	public function setOrderId(string $orderId): void
	{
		$this->orderId = $orderId;
	}

	/**
	 * @return string
	 */
	public function getOrderTransactionId(): string
	{
		return $this->orderTransactionId;
	}

	/**
	 * @param string $orderTransactionId
	 */
	public function setOrderTransactionId(string $orderTransactionId): void
	{
		$this->orderTransactionId = $orderTransactionId;
	}

	/**
	 * @return int
	 */
	public function getSpaceId(): int
	{
		return $this->spaceId;
	}

	/**
	 * @param int $spaceId
	 */
	public function setSpaceId(int $spaceId): void
	{
		$this->spaceId = $spaceId;
	}

	/**
	 * @return string
	 */
	public function getState(): string
	{
		return $this->state;
	}

	/**
	 * @param string $state
	 */
	public function setState(string $state): void
	{
		$this->state = $state;
	}

	/**
	 * @return string
	 */
	public function getSalesChannelId(): string
	{
		return $this->salesChannelId;
	}

	/**
	 * @param string $salesChannelId
	 */
	public function setSalesChannelId(string $salesChannelId): void
	{
		$this->salesChannelId = $salesChannelId;
	}

	/**
	 * @return int
	 */
	public function getTransactionId(): int
	{
		return $this->transactionId;
	}

	/**
	 * @param int $transactionId
	 */
	public function setTransactionId(int $transactionId): void
	{
		$this->transactionId = $transactionId;
	}


}