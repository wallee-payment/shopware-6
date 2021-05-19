<?php declare(strict_types=1);

namespace WalleePayment;

use Shopware\Core\{
	Framework\Plugin,
	Framework\Plugin\Context\ActivateContext,
	Framework\Plugin\Context\DeactivateContext,
	Framework\Plugin\Context\UninstallContext,
	Framework\Plugin\Context\UpdateContext
};
use WalleePayment\Core\{
	Api\WebHooks\Service\WebHooksService,
	Util\Traits\WalleePaymentPluginTrait
};

putenv('WALLEE_API_BASE_PATH=host.docker.internal:8000/api');

// expect the vendor folder on Shopware store releases
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
	require_once dirname(__DIR__) . '/vendor/autoload.php';
}

/**
 * Class WalleePayment
 *
 * @package WalleePayment
 */
class WalleePayment extends Plugin {

	use WalleePaymentPluginTrait;

	/**
	 * @param \Shopware\Core\Framework\Plugin\Context\UninstallContext $uninstallContext
	 */
	public function uninstall(UninstallContext $uninstallContext): void
	{
		parent::uninstall($uninstallContext);
		$this->disablePaymentMethods($uninstallContext->getContext());
		$this->removeConfiguration($uninstallContext->getContext());
		$this->deleteUserData($uninstallContext);
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


	/**
	 * @param \Shopware\Core\Framework\Plugin\Context\UpdateContext $updateContext
	 *
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 */
	public function postUpdate(UpdateContext $updateContext): void
	{
		parent::postUpdate($updateContext);
		/**
		 * @var \WalleePayment\Core\Api\WebHooks\Service\WebHooksService $webHooksService
		 */
		$webHooksService = $this->container->get(WebHooksService::class);
		$webHooksService->install();
	}

}