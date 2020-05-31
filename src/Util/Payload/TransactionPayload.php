<?php declare(strict_types=1);

namespace WalleePayment\Util\Payload;


use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\{
	Checkout\Cart\Tax\Struct\CalculatedTaxCollection,
	Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity,
	Checkout\Payment\Cart\AsyncPaymentTransactionStruct,
	Framework\DataAbstractionLayer\Search\Criteria,
	System\SalesChannel\SalesChannelContext};
use Wallee\Sdk\{
	Model\AddressCreate,
	Model\LineItemCreate,
	Model\LineItemType,
	Model\TaxCreate,
	Model\TransactionCreate};
use WalleePayment\Core\{
	Api\PaymentMethodConfiguration\Entity\PaymentMethodConfigurationEntity,
	Settings\Struct\Settings};
use WalleePayment\Util\LocaleCodeProvider;

/**
 * Class TransactionPayload
 *
 * @package WalleePayment\Util\Payload
 */
class TransactionPayload {

	public const ORDER_TRANSACTION_CUSTOM_FIELDS_WALLEE_TRANSACTION_ID = 'wallee_transaction_id';
	public const ORDER_TRANSACTION_CUSTOM_FIELDS_WALLEE_SPACE_ID       = 'wallee_space_id';

	/**
	 * @var \Shopware\Core\System\SalesChannel\SalesChannelContext
	 */
	protected $salesChannelContext;

	/**
	 * @var \Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct
	 */
	protected $transaction;

	/**
	 * @var \WalleePayment\Core\Settings\Struct\Settings
	 */
	protected $settings;

	/**
	 * @var \Psr\Container\ContainerInterface
	 */
	protected $container;
	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * @var \WalleePayment\Util\LocaleCodeProvider
	 */
	private $localeCodeProvider;

	/**
	 * TransactionPayload constructor.
	 *
	 * @param \Psr\Container\ContainerInterface                                  $container
	 * @param \WalleePayment\Util\LocaleCodeProvider              $localeCodeProvider
	 * @param \Shopware\Core\System\SalesChannel\SalesChannelContext             $salesChannelContext
	 * @param \WalleePayment\Core\Settings\Struct\Settings        $settings
	 * @param \Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct $transaction
	 * @param \Psr\Log\LoggerInterface                                           $logger
	 */
	public function __construct(
		ContainerInterface $container,
		LocaleCodeProvider $localeCodeProvider,
		SalesChannelContext $salesChannelContext,
		Settings $settings,
		AsyncPaymentTransactionStruct $transaction,
		LoggerInterface $logger
	)
	{
		$this->localeCodeProvider  = $localeCodeProvider;
		$this->salesChannelContext = $salesChannelContext;
		$this->settings            = $settings;
		$this->transaction         = $transaction;
		$this->container           = $container;
		$this->logger              = $logger;
	}

	/**
	 * Get Transaction Payload
	 *
	 * @return \Wallee\Sdk\Model\TransactionCreate
	 */
	public function get(): TransactionCreate
	{

		$customer           = $this->salesChannelContext->getCustomer();
		$billingAddressData = $this->getAddressData($customer->getActiveBillingAddress());
		$billingAddress     = (new AddressCreate())
			->setCity($billingAddressData['city'])
			->setCountry($billingAddressData['country'])
			->setEmailAddress($customer->getEmail() ?? null)
			->setFamilyName($customer->getLastName() ?? null)
			->setGivenName($customer->getFirstName() ?? null)
			->setOrganizationName($customer->getCompany() ?? null)
			->setPhoneNumber($billingAddressData['phone_number'])
			->setPostCode($billingAddressData['postcode'])
			->setPostalState($billingAddressData['postal_state'])
			->setSalutation($customer->getSalutation() ? $customer->getSalutation()->getDisplayName() : null)
			->setStreet($billingAddressData['street']);

		$shippingAddressData = $this->getAddressData($customer->getActiveShippingAddress());
		$shippingAddress     = (new AddressCreate())
			->setCity($shippingAddressData['city'])
			->setCountry($shippingAddressData['country'])
			->setEmailAddress($customer->getEmail() ?? null)
			->setFamilyName($customer->getLastName() ?? null)
			->setGivenName($customer->getFirstName() ?? null)
			->setOrganizationName($customer->getCompany() ?? null)
			->setPostCode($shippingAddressData['postcode'])
			->setPostalState($shippingAddressData['postal_state'])
			->setPhoneNumber($shippingAddressData['phone_number'])
			->setSalutation($customer->getSalutation() ? $customer->getSalutation()->getDisplayName() : null)
			->setStreet($shippingAddressData['street']);

		$lineItems = $this->getLineItems();

		$transactionPayload = (new TransactionCreate())
			->setAutoConfirmationEnabled(false)
			->setBillingAddress($billingAddress)
			->setChargeRetryEnabled(false)
			->setCurrency($this->salesChannelContext->getCurrency()->getIsoCode())
			->setCustomerEmailAddress($customer->getEmail() ?? null)
			->setCustomerId($customer->getCustomerNumber() ?? null)
			->setLanguage($this->localeCodeProvider->getLocaleCodeFromContext($this->salesChannelContext->getContext()) ?? null)
			->setLineItems($lineItems)
			->setMerchantReference($this->transaction->getOrder()->getOrderNumber())
			->setMetaData([
				'orderId'            => $this->transaction->getOrder()->getId(),
				'orderTransactionId' => $this->transaction->getOrderTransaction()->getId(),
				'salesChannelId'     => $this->salesChannelContext->getSalesChannel()->getId(),
			])
			->setShippingAddress($shippingAddress)
			->setShippingMethod($this->salesChannelContext->getShippingMethod()->getName() ?? null)
			->setSpaceViewId($this->settings->getSpaceViewId() ?? null);


		$paymentConfiguration = $this->getPaymentConfiguration($this->salesChannelContext->getPaymentMethod()->getId());

		$transactionPayload->setAllowedPaymentMethodConfigurations([$paymentConfiguration->getPaymentMethodConfigurationId()]);

		$url = $this->transaction->getReturnUrl();

		if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
			$successUrl = $url . '&status=paid';
			$failedUrl  = $url . '&status=fail';
			$transactionPayload->setSuccessUrl($successUrl)
							   ->setFailedUrl($failedUrl);
		}
		return $transactionPayload;
	}

	/**
	 * @param \Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity $customerAddressEntity
	 * @return array
	 */
	protected function getAddressData(CustomerAddressEntity $customerAddressEntity): array
	{
		return [
			'city'         => $customerAddressEntity->getCity() ?? null,
			'country'      => $customerAddressEntity->getCountry() ? $customerAddressEntity->getCountry()->getIso() : null,
			'phone_number' => $customerAddressEntity->getPhoneNumber() ?? null,
			'postcode'     => $customerAddressEntity->getZipcode() ?? null,
			'postal_state' => $customerAddressEntity->getCountryState() ? $customerAddressEntity->getCountryState()->getShortCode() : null,
			'street'       => $customerAddressEntity->getStreet() ?? null,
		];
	}

	/**
	 * Get transaction line items
	 *
	 * @return \Wallee\Sdk\Model\LineItemCreate[]
	 */
	protected function getLineItems(): array
	{
		$lineItems          = [];
		$lineItemPriceTotal = 0;
		/**
		 * @var \Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity $shopLineItem
		 */
		foreach ($this->transaction->getOrder()->getLineItems() as $shopLineItem) {
			$taxes    = $this->getTaxes(
				$shopLineItem->getPrice()->getCalculatedTaxes(),
				''
			);
			$lineItem = (new LineItemCreate())
				->setName($shopLineItem->getLabel() ?? null)
				->setUniqueId($shopLineItem->getId() ?? null)
				->setSku($shopLineItem->getProductId() ?? null)
				->setQuantity($shopLineItem->getQuantity() ?? null)
				->setAmountIncludingTax($shopLineItem->getTotalPrice() ?? null)
				->setTaxes($taxes);

			$lineItemPriceTotal += $shopLineItem->getTotalPrice();
			/** @noinspection PhpParamsInspection */
			$lineItem->setType(LineItemType::PRODUCT);
			$lineItems[] = $lineItem;
		}

		/**
		 * @var \Wallee\Sdk\Model\LineItemCreate[] $lineItems
		 */
		$lineItems[] = $this->getShippingLineItem();

		/**
		 * @var \Wallee\Sdk\Model\LineItemCreate $lineItem
		 */
		$lineItemPriceTotal = array_sum(array_map(function ($lineItem) {
			return $lineItem->getAmountIncludingTax();
		}, $lineItems));

		$adjustmentPrice = round($this->transaction->getOrder()->getAmountTotal() - $lineItemPriceTotal, 2);

		if (
			(abs($adjustmentPrice) != 0) &&
			$this->settings->isLineItemConsistencyEnabled()
		) {

			$lineItem = (new LineItemCreate())
				->setName('Adjustment Line Item')
				->setUniqueId('Adjustment-Line-Item')
				->setSku('Adjustment-Line-Item')
				->setQuantity(1);
			/** @noinspection PhpParamsInspection */
			$lineItem->setAmountIncludingTax($adjustmentPrice)->setType(($adjustmentPrice > 0) ? LineItemType::FEE : LineItemType::DISCOUNT);
			$lineItems[] = $lineItem;

		}
		return $lineItems;

	}

	/**
	 * @param \Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection $calculatedTaxes
	 * @param String                                                          $title
	 * @return \Wallee\Sdk\Model\TaxCreate[]
	 */
	protected function getTaxes(CalculatedTaxCollection $calculatedTaxes, String $title): array
	{
		$taxes = [];
		foreach ($calculatedTaxes as $calculatedTax) {

			$taxes[] = (new TaxCreate())
				->setRate($calculatedTax->getTaxRate())
				->setTitle($title . ' : ' . $calculatedTax->getTaxRate());
		}

		return $taxes;
	}

	/**
	 * @return \Wallee\Sdk\Model\LineItemCreate
	 */
	protected function getShippingLineItem(): ?LineItemCreate
	{
		try {

			$amount = $this->transaction->getOrder()->getShippingTotal();
			if ($amount > 0) {

				$shippingName = $this->salesChannelContext->getShippingMethod()->getName() ?? 'Shipping';
				$taxes        = $this->getTaxes(
					$this->transaction->getOrder()->getShippingCosts()->getCalculatedTaxes(),
					$shippingName
				);

				$lineItem = (new LineItemCreate())
					->setAmountIncludingTax($amount)
					->setName($shippingName . ' Shipping Line Item')
					->setQuantity($this->transaction->getOrder()->getShippingCosts()->getQuantity())
					->setTaxes($taxes)
					->setSku($shippingName . '-Shipping-Line-Item')
					->setType(LineItemType::SHIPPING)
					->setUniqueId($shippingName . '-Shipping-Line-Item');

				return $lineItem;
			}
		} catch (\Exception $exception) {
			$this->logger->critical(__CLASS__ . ' : ' . __FUNCTION__ . ' : ' . $exception->getMessage());
		}
		return null;
	}

	/**
	 * @param string $id
	 * @return \WalleePayment\Core\Api\PaymentMethodConfiguration\Entity\PaymentMethodConfigurationEntity
	 */
	protected function getPaymentConfiguration(string $id): PaymentMethodConfigurationEntity
	{
		$criteria = (new Criteria([$id]));

		return $this->container->get('wallee_payment_method_configuration.repository')
							   ->search($criteria, $this->salesChannelContext->getContext())
							   ->getEntities()->first();
	}
}