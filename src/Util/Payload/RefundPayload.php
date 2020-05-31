<?php declare(strict_types=1);

namespace WalleePayment\Util\Payload;

use Wallee\Sdk\{
	Model\RefundCreate,
	Model\RefundType,
	Model\Transaction,
	Model\TransactionState};

/**
 * Class RefundPayload
 *
 * @package WalleePayment\Util\Payload
 */
class RefundPayload {

	/**
	 * @param \Wallee\Sdk\Model\Transaction $transaction
	 * @param float                                        $amount
	 * @return \Wallee\Sdk\Model\RefundCreate|null
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
			$refund->setMerchantReference($transaction->getMerchantReference());
			$refund->setExternalId(uniqid('refund_', true));
			/** @noinspection PhpParamsInspection */
			$refund->setType(RefundType::MERCHANT_INITIATED_ONLINE);
			return $refund;
		}
		return null;
	}
}