<?php declare(strict_types=1);

namespace WalleePayment;

use Shopware\Core\{
	Framework\Plugin,
	Framework\Plugin\Context\ActivateContext,
	Framework\Plugin\Context\DeactivateContext,
	Framework\Plugin\Context\InstallContext,
	Framework\Plugin\Context\UninstallContext};
use Symfony\Component\{
	Config\FileLocator,
	DependencyInjection\ContainerBuilder,
	DependencyInjection\Loader\XmlFileLoader,};
use WalleePayment\Util\Traits\WalleePaymentPluginTrait;

/**
 * Class WalleePayment
 *
 * @package WalleePayment
 */
class WalleePayment extends Plugin {

	use WalleePaymentPluginTrait;

	/**
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 * @throws \Exception
	 */
	public function build(ContainerBuilder $container): void
	{
		parent::build($container);
		$loader    = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection/'));
		$resources = [
			'core/checkout.xml',
			'core/configuration.xml',
			'core/payment_method_configuration.xml',
			'core/settings.xml',
			'core/transaction.xml',
			'core/webhook.xml',
			'storefront/checkout.xml',
			'util.xml',
		];
		foreach ($resources as $resource) {
			$loader->load($resource);
		}
	}

	/**
	 * @param \Shopware\Core\Framework\Plugin\Context\InstallContext $installContext
	 */
	public function install(InstallContext $installContext): void
	{
		parent::install($installContext);
	}

	/**
	 * @param \Shopware\Core\Framework\Plugin\Context\UninstallContext $uninstallContext
	 */
	public function uninstall(UninstallContext $uninstallContext): void
	{
		parent::uninstall($uninstallContext);
		$this->disablePaymentMethods($uninstallContext->getContext());
	}

	/**
	 * @param \Shopware\Core\Framework\Plugin\Context\ActivateContext $activateContext
	 */
	public function activate(ActivateContext $activateContext): void
	{
		parent::activate($activateContext);
		$this->enablePaymentMethods($activateContext->getContext());
	}

	/**
	 * @param \Shopware\Core\Framework\Plugin\Context\DeactivateContext $deactivateContext
	 */
	public function deactivate(DeactivateContext $deactivateContext): void
	{
		parent::deactivate($deactivateContext);
		$this->disablePaymentMethods($deactivateContext->getContext());
	}

}