<?php declare(strict_types=1);

namespace WalleePayment\Core\Storefront\Checkout\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\{Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection,
    Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates,
    Checkout\Order\OrderEntity,
    Content\MailTemplate\Service\Event\MailBeforeValidateEvent};
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use WalleePayment\Core\{Api\Transaction\Service\OrderMailService,
    Api\Transaction\Service\TransactionService,
    Checkout\PaymentHandler\WalleePaymentHandler,
    Settings\Service\SettingsService,
    Settings\Struct\Settings,
    Util\PaymentMethodUtil};
use WalleePayment\Core\Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService;
use WalleePayment\Sdk\{Model\AddressCreate,
    Model\ChargeAttempt,
    Model\CreationEntityState,
    Model\CriteriaOperator,
    Model\EntityQuery,
    Model\EntityQueryFilter,
    Model\EntityQueryFilterType,
    Model\LineItemAttributeCreate,
    Model\LineItemCreate,
    Model\LineItemType,
    Model\TaxCreate,
    Model\Transaction,
    Model\TransactionCreate,
    Model\TransactionPending};

/**
 * Class CheckoutSubscriber
 *
 * @package WalleePayment\Storefront\Checkout\Subscriber
 */
class CheckoutSubscriber implements EventSubscriberInterface
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \WalleePayment\Core\Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService
     */
    private $paymentMethodConfigurationService;

    /**
     * @var \WalleePayment\Core\Api\Transaction\Service\TransactionService
     */
    private $transactionService;

    /**
     * @var \WalleePayment\Core\Settings\Service\SettingsService
     */
    private $settingsService;

    /**
     * @var \WalleePayment\Core\Util\PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * CheckoutSubscriber constructor.
     *
     * @param \WalleePayment\Core\Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService $paymentMethodConfigurationService
     * @param \WalleePayment\Core\Api\Transaction\Service\TransactionService $transactionService
     * @param \WalleePayment\Core\Settings\Service\SettingsService $settingsService
     * @param \WalleePayment\Core\Util\PaymentMethodUtil $paymentMethodUtil
     */
    public function __construct(PaymentMethodConfigurationService $paymentMethodConfigurationService, TransactionService $transactionService, SettingsService $settingsService, PaymentMethodUtil $paymentMethodUtil)
    {
        $this->paymentMethodConfigurationService = $paymentMethodConfigurationService;
        $this->transactionService = $transactionService;
        $this->settingsService = $settingsService;
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
            MailBeforeValidateEvent::class => ['onMailBeforeValidate', 1],
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

        if (!empty($order) && $order->getAmountTotal() > 0) {

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
        }
    }

    /**
     * @param \Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent $event
     */
    public function onConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        try {
            $salesChannelContext = $event->getSalesChannelContext();
            $settings = $this->settingsService->getValidSettings($salesChannelContext->getSalesChannel()->getId());
            if (is_null($settings)) {
                $this->logger->notice('Removing payment methods because settings are invalid');
                $this->removeWalleePaymentMethodFromConfirmPage($event);
            }

            $createdTransactionId = $this->transactionService->createPendingTransaction($salesChannelContext, $event);
            $this->updateTempTransactionIfNeeded($salesChannelContext, $createdTransactionId);

            $this->getAvailablePaymentMethods($settings, $createdTransactionId);
            $this->setPossiblePaymentMethods($settings->getSpaceId(), $event);
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
        $paymentMethodIds = $this->paymentMethodUtil->getWalleePaymentMethodIds($event->getContext());
        foreach ($paymentMethodIds as $paymentMethodId) {
            $paymentMethodCollection->remove($paymentMethodId);
        }
    }

    /**
     * @param Settings $settings
     * @param int $createdTransactionId
     * @return void
     */
    private function getAvailablePaymentMethods(Settings $settings, int $createdTransactionId): void
    {
        $transactionService = $settings->getApiClient()->getTransactionService();
        $possiblePaymentMethods = $transactionService->fetchPaymentMethods(
            $settings->getSpaceId(),
            $createdTransactionId,
            $settings->getIntegration()
        );
        $arrayOfPossibleMethods = [];
        foreach ($possiblePaymentMethods as $possiblePaymentMethod) {
            $arrayOfPossibleMethods[] = $possiblePaymentMethod->getid();
        }
        $_SESSION['arrayOfPossibleMethods'] = $arrayOfPossibleMethods;
    }

    /**
     * @param int $spaceId
     * @param CheckoutConfirmPageLoadedEvent $event
     * @return void
     */
    private function setPossiblePaymentMethods(int $spaceId, CheckoutConfirmPageLoadedEvent $event): void
    {
        $localPaymentMethods = [];
        $paymentMethodConfigurations = $this->paymentMethodConfigurationService->getAllPaymentMethodConfigurations($spaceId, $event->getSalesChannelContext()->getContext());
        foreach ($paymentMethodConfigurations as $paymentMethodConfiguration) {
            $localPaymentMethods[$paymentMethodConfiguration->getId()] = $paymentMethodConfiguration->getPaymentMethodConfigurationId();
        }

        $paymentMethodCollection = $event->getPage()->getPaymentMethods();
        foreach ($paymentMethodCollection as $paymentMethodCollectionItem) {
            $isWalleePM = WalleePaymentHandler::class == $paymentMethodCollectionItem->getHandlerIdentifier();
            if (!$isWalleePM) {
                continue;
            }

            $paymentMethodConfigurationId = $localPaymentMethods[$paymentMethodCollectionItem->getId()];
            if (!\in_array($paymentMethodConfigurationId, $_SESSION['arrayOfPossibleMethods'])) {
                $paymentMethodCollection->remove($paymentMethodCollectionItem->getId());
            }
        }
    }

    /**
     * @param SalesChannelContext $salesChannelContext
     * @param int $createdTransactionId
     * @return void
     */
    private function updateTempTransactionIfNeeded(SalesChannelContext $salesChannelContext, int $createdTransactionId): void
    {
        $addressCheck = $_SESSION['addressCheck'] ?? null;
        $currencyCheck = $_SESSION['currencyCheck'] ?? null;

        $customer = $salesChannelContext->getCustomer();
        $addressHash = md5(json_encode((array)$customer));
        $currency = $salesChannelContext->getCurrency()->getIsoCode();
        if (($addressCheck && $currencyCheck) && $addressCheck !== $addressHash || $currencyCheck !== $currency) {
            if ($createdTransactionId) {
                $this->transactionService->updateTempTransaction($salesChannelContext, $createdTransactionId);
            }
            $_SESSION['arrayOfPossibleMethods'] = null;
            $_SESSION['addressCheck'] = $addressHash;
            $_SESSION['currencyCheck'] = $currency;
        }
    }
}
