<?php declare(strict_types=1);

namespace WalleePayment\Util\Traits;

use Doctrine\DBAL\Connection;
use Shopware\Core\{
	Framework\Context,
	Framework\DataAbstractionLayer\EntityRepositoryInterface,
	Framework\DataAbstractionLayer\Search\Criteria,
	Framework\DataAbstractionLayer\Search\Filter\EqualsFilter,
	Framework\Plugin\Context\UninstallContext};
use WalleePayment\Core\{
	Api\PaymentMethodConfiguration\Entity\PaymentMethodConfigurationEntityDefinition,
	Checkout\PaymentHandler\WalleePaymentHandler};

/**
 * Trait WalleePaymentPluginTrait
 *
 * We use a trait keep the plugin class clean and free of auxiliary functions.
 *
 * @package WalleePayment\Util\Traits
 */
trait WalleePaymentPluginTrait {

	/**
	 * @param \Shopware\Core\Framework\Context $context
	 */
	protected function enablePaymentMethods(Context $context)
	{
		$paymentMethodIds = $this->getPaymentMethodIds();
		foreach ($paymentMethodIds as $paymentMethodId) {
			$this->setPaymentMethodIsActive($paymentMethodId, true, $context);
		}
	}

	/**
	 * @return string[]
	 */
	protected function getPaymentMethodIds(): array
	{
		/** @var EntityRepositoryInterface $paymentRepository */
		$paymentRepository = $this->container->get('payment_method.repository');
		$criteria          = (new Criteria())
			->addFilter(new EqualsFilter('handlerIdentifier', WalleePaymentHandler::class));

		return $paymentRepository->searchIds($criteria, Context::createDefaultContext())->getIds();
	}

	/**
	 * @param string                           $paymentMethodId
	 * @param bool                             $active
	 * @param \Shopware\Core\Framework\Context $context
	 */
	protected function setPaymentMethodIsActive(string $paymentMethodId, bool $active, Context $context): void
	{
		$paymentMethod = [
			'id'     => $paymentMethodId,
			'active' => $active,
		];

		/** @var EntityRepositoryInterface $paymentRepository */
		$paymentRepository = $this->container->get('payment_method.repository');
		$paymentRepository->update([$paymentMethod], $context);
	}

	/**
	 * @param \Shopware\Core\Framework\Context $context
	 */
	protected function disablePaymentMethods(Context $context)
	{
		$paymentMethodIds = $this->getPaymentMethodIds();
		foreach ($paymentMethodIds as $paymentMethodId) {
			$this->setPaymentMethodIsActive($paymentMethodId, false, $context);
		}
	}

	/**
	 * Delete user data when plugin is uninstalled
	 *
	 * @internal
	 * @param \Shopware\Core\Framework\Plugin\Context\UninstallContext $uninstallContext
	 */
	protected function deleteUserData(UninstallContext $uninstallContext)
	{
		if (!$uninstallContext->keepUserData()) {

			$connection = $this->container->get(Connection::class);

			$connection->executeQuery(strtr(
				'DROP TABLE IF EXISTS `{db_table}`',
				['{db_table}' => PaymentMethodConfigurationEntityDefinition::ENTITY_NAME]
			));
		}
	}
}