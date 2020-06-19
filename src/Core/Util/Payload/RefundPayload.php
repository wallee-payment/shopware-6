<?php declare(strict_types=1);

namespace WalleePayment\Core\Util\Payload;


use Wallee\Sdk\{
	Model\RefundCreate,
	Model\RefundType,
	Model\Transaction,
	Model\TransactionState};

/**
 * Class RefundPayload
 *
 * @package WalleePayment\Core\Util\Payload
 */
class RefundPayload extends AbstractPayload {

	/**
	 * @param \Wallee\Sdk\Model\Transaction $transaction
	 * @param float                                        $amount
	 * @return \Wallee\Sdk\Model\RefundCreate|null
	 * @throws \Exception
	 */
	public function get(Transaction $transaction, float $amount): ?RefundCreate
	{
		if (
			($transaction->getState() == TransactionState::FULFILL) &&
			($amount <= floatval($transaction->getAuthorizationAmount()))
		) {
			$refund = new RefundCreate();
			$refund->setAmount($amount);
			$refund->setTransaction($transaction->getId());
			$refund->setMerchantReference($this->fixLength($transaction->getMerchantReference(), 100));
			$refund->setExternalId($this->fixLength(uniqid('refund_', true), 100));
			/** @noinspection PhpParamsInspection */
			$refund->setType(RefundType::MERCHANT_INITIATED_ONLINE);
			if (!$refund->valid()) {
				$this->logger->critical('Refund payload invalid:', $refund->listInvalidProperties());
				throw new \Exception('Refund payload invalid:' . json_encode($refund->listInvalidProperties()));
			}
			return $refund;
		}
		return null;
	}
}