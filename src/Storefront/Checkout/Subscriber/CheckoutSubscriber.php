<?php declare(strict_types=1);

namespace WalleePayment\Storefront\Checkout\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use WalleePayment\Core\Settings\Service\SettingsService;
use WalleePayment\Util\PaymentMethodUtil;

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
	 * @var \WalleePayment\Util\PaymentMethodUtil
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
	 * @param \WalleePayment\Util\PaymentMethodUtil                $paymentMethodUtil
	 * @param \Psr\Log\LoggerInterface                                            $logger
	 */
	public function __construct(
		SettingsService $settingsService,
		PaymentMethodUtil $paymentMethodUtil,
		LoggerInterface $logger
	)
	{
		$this->settingsService   = $settingsService;
		$this->paymentMethodUtil = $paymentMethodUtil;
		$this->logger            = $logger;
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			CheckoutConfirmPageLoadedEvent::class => ['onConfirmPageLoaded', 1],
		];
	}

	/**
	 * @param \Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent $event
	 */
	public function onConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
	{
		try {
			$settings = $this->settingsService->getSettings($event->getSalesChannelContext()->getSalesChannel()->getId());
			if (
				empty($settings->getSpaceId()) ||
				empty($settings->getApplicationKey()) ||
				empty($settings->getUserId())
			) {
				$this->removeWalleePaymentMethodFromConfirmPage($event);
			}
		} catch (\Exception $e) {
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