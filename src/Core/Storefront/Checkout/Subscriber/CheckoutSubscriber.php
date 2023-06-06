<?php declare(strict_types=1);

namespace WalleePayment\Core\Storefront\Checkout\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\{
	Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates,
	Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection,
	Checkout\Order\OrderEntity,
	Content\MailTemplate\Service\Event\MailBeforeValidateEvent};
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use WalleePayment\Core\{
	Api\Transaction\Service\OrderMailService,
	Checkout\PaymentHandler\WalleePaymentHandler,
	Settings\Service\SettingsService,
	Util\PaymentMethodUtil};

/**
 * Class CheckoutSubscriber
 *
 * @package WalleePayment\Storefront\Checkout\Subscriber
 */
class CheckoutSubscriber implements EventSubscriberInterface {

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * @var \WalleePayment\Core\Util\PaymentMethodUtil
	 */
	private $paymentMethodUtil;

	/**
	 * @var \WalleePayment\Core\Settings\Service\SettingsService
	 */
	private $settingsService;

	/**
	 * CheckoutSubscriber constructor.
	 *
	 * @param \WalleePayment\Core\Settings\Service\SettingsService $settingsService
	 * @param \WalleePayment\Core\Util\PaymentMethodUtil           $paymentMethodUtil
	 */
	public function __construct(SettingsService $settingsService, PaymentMethodUtil $paymentMethodUtil)
	{
		$this->settingsService   = $settingsService;
		$this->paymentMethodUtil = $paymentMethodUtil;
	}

	/**
	 * @param \Psr\Log\LoggerInterface $logger
	 *
	 * @internal
	 * @required
	 *
	 */
	public function setLogger(LoggerInterface $logger): void
	{
		$this->logger = $logger;
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			CheckoutConfirmPageLoadedEvent::class => ['onConfirmPageLoaded', 1],
			MailBeforeValidateEvent::class        => ['onMailBeforeValidate', 1],
		];
	}

	/**
	 * Stop order emails being sent out
	 *
	 * @param \Shopware\Core\Content\MailTemplate\Service\Event\MailBeforeValidateEvent $event
	 */
	public function onMailBeforeValidate(MailBeforeValidateEvent $event): void
	{
		$templateData = $event->getTemplateData();
		
		/**
		 * @var $order \Shopware\Core\Checkout\Order\OrderEntity
		 */
		$order = !empty($templateData['order']) && $templateData['order'] instanceof OrderEntity ? $templateData['order'] : null;

		if (!empty($order) && $order->getAmountTotal() > 0){

			$isWalleeEmailSettingEnabled = $this->settingsService->getSettings($order->getSalesChannelId())->isEmailEnabled();

			if (!$isWalleeEmailSettingEnabled) { //setting is disabled
				return;
			}

			$orderTransactions = $order->getTransactions();
			if (!($orderTransactions instanceof OrderTransactionCollection)) {
				return;
			}
			$orderTransactionLast = $orderTransactions->last();
			if (empty($orderTransactionLast) || empty($orderTransactionLast->getPaymentMethod())) { // no payment method available
				return;
			}

			$isWalleePM = WalleePaymentHandler::class == $orderTransactionLast->getPaymentMethod()->getHandlerIdentifier();
			if (!$isWalleePM) { // not our payment method
				return;
			}

			$isOrderTransactionStateOpen = in_array(
				$orderTransactionLast->getStateMachineState()->getTechnicalName(), [
				OrderTransactionStates::STATE_OPEN,
				OrderTransactionStates::STATE_IN_PROGRESS,
			]);

			if (!$isOrderTransactionStateOpen) { // order payment status is open or in progress
				return;
			}

			$isWalleeEmail = isset($templateData[OrderMailService::EMAIL_ORIGIN_IS_WALLEE]);

			if (!$isWalleeEmail) {
				$this->logger->info('Email disabled for ', ['orderId' => $order->getId()]);
				$event->stopPropagation();
			}
		}
	}

	/**
	 * @param \Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent $event
	 */
	public function onConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
	{
		try {
			$settings = $this->settingsService->getValidSettings($event->getSalesChannelContext()->getSalesChannel()->getId());
			if (is_null($settings)) {
				$this->logger->notice('Removing payment methods because settings are invalid');
				$this->removeWalleePaymentMethodFromConfirmPage($event);
			}

		} catch (\Exception $e) {
			$this->logger->error($e->getMessage());
			$this->removeWalleePaymentMethodFromConfirmPage($event);
		}
	}

	/**
	 * @param \Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent $event
	 */
	private function removeWalleePaymentMethodFromConfirmPage(CheckoutConfirmPageLoadedEvent $event): void
	{
		$paymentMethodCollection = $event->getPage()->getPaymentMethods();
		$paymentMethodIds        = $this->paymentMethodUtil->getWalleePaymentMethodIds($event->getContext());
		foreach ($paymentMethodIds as $paymentMethodId) {
			$paymentMethodCollection->remove($paymentMethodId);
		}
	}
}