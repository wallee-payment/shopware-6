<?php declare(strict_types=1);

namespace WalleePayment\Core\Api\Refund\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * Class RefundEntityCollection
 *
 * @package WalleePayment\Core\Api\Refund\Entity
 *
 * @method void              add(RefundEntity $entity)
 * @method void              set(string $key, RefundEntity $entity)
 * @method RefundEntity[]    getIterator()
 * @method RefundEntity[]    getElements()
 * @method RefundEntity|null get(string $key)
 * @method RefundEntity|null first()
 * @method RefundEntity|null last()
 */
class RefundEntityCollection extends EntityCollection {

	/**
	 * @param int $transactionId
	 * @return \WalleePayment\Core\Api\Refund\Entity\RefundEntityCollection
	 */
	public function filterByTransactionId(int $transactionId): RefundEntityCollection
	{
		return $this->filter(function (RefundEntity $refund) use ($transactionId) {
			return $refund->getTransactionId() === $transactionId;
		});
	}

	/**
	 * Get by refund id
	 *
	 * @param int $refundId
	 * @return \WalleePayment\Core\Api\Refund\Entity\RefundEntity|null
	 */
	public function getByRefundId(int $refundId): ?RefundEntity
	{
		foreach ($this->getIterator() as $element) {
			if ($element->getRefundId() === $refundId) {
				return $element;
			}
		}

		return null;
	}

	/**
	 * @return string
	 */
	protected function getExpectedClass(): string
	{
		return RefundEntity::class;
	}
}