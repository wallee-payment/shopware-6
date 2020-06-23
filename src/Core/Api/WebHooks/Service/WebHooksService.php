<?php declare(strict_types=1);

namespace WalleePayment\Core\Api\WebHooks\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\{
	Routing\Generator\UrlGeneratorInterface,
	Routing\RouterInterface,};
use Wallee\Sdk\{
	ApiClient,
	Model\CreationEntityState,
	Model\CriteriaOperator,
	Model\EntityQuery,
	Model\EntityQueryFilter,
	Model\EntityQueryFilterType,
	Model\RefundState,
	Model\TransactionInvoiceState,
	Model\TransactionState,
	Model\WebhookListener,
	Model\WebhookListenerCreate,
	Model\WebhookUrl,
	Model\WebhookUrlCreate,};
use WalleePayment\Core\{
	Api\WebHooks\Struct\Entity,
	Settings\Service\SettingsService};

/**
 * Class WebHooksService
 *
 * @package WalleePayment\Core\Api\WebHooks\Service
 */
class WebHooksService {

	/**
	 * @var \WalleePayment\Core\Settings\Service\SettingsService
	 */
	protected $settingsService;

	/**
	 * @var \Symfony\Component\Routing\RouterInterface
	 */
	protected $router;

	/**
	 * @var \Wallee\Sdk\ApiClient
	 */
	protected $apiClient;

	/**
	 * Space Id
	 *
	 * @var int
	 */
	protected $spaceId;

	/**
	 * WebHook configs
	 */
	protected $webHookEntitiesConfig = [];

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * @var ?string $salesChannelId
	 */
	private $salesChannelId;

	/**
	 * WebHooksService constructor.
	 *
	 * @param \WalleePayment\Core\Settings\Service\SettingsService $settingsService
	 * @param \Symfony\Component\Routing\RouterInterface                          $router
	 * @param \Psr\Log\LoggerInterface                                            $logger
	 */
	public function __construct(SettingsService $settingsService, RouterInterface $router, LoggerInterface $logger)
	{
		$this->router          = $router;
		$this->settingsService = $settingsService;
		$this->logger          = $logger;
		$this->setWebHookEntitiesConfig();
	}

	protected function setWebHookEntitiesConfig(): void
	{
		/**
		 * Transaction WebHook Entity Id
		 * @link https://www.wallee.com/doc/api/webhook-entity/view/1472041829003
		 */
		$this->webHookEntitiesConfig[] = (new Entity())->assign([
			'id'                => 1472041829003,
			'name'              => 'Shopware6::WebHook::Transaction',
			'url'               => null,
			'states'            => [
				TransactionState::AUTHORIZED,
				TransactionState::COMPLETED,
				TransactionState::CONFIRMED,
				TransactionState::DECLINE,
				TransactionState::FAILED,
				TransactionState::FULFILL,
				TransactionState::PROCESSING,
				TransactionState::VOIDED,
			],
			'notifyEveryChange' => false,
		]);

		/**
		 * Transaction Invoice WebHook Entity Id
		 * @link https://www.wallee.com/doc/api/webhook-entity/view/1472041816898
		 */
		$this->webHookEntitiesConfig[] = (new Entity())->assign([
			'id'                => 1472041816898,
			'name'              => 'Shopware6::WebHook::Transaction Invoice',
			'url'               => null,
			'states'            => [
				TransactionInvoiceState::NOT_APPLICABLE,
				TransactionInvoiceState::PAID,
				TransactionInvoiceState::DERECOGNIZED,
			],
			'notifyEveryChange' => false,
		]);

		/**
		 * Refund WebHook Entity Id
		 * @link https://www.wallee.com/doc/api/webhook-entity/view/1472041839405
		 */
		$this->webHookEntitiesConfig[] = (new Entity())->assign([
			'id'                => 1472041839405,
			'name'              => 'Shopware6::WebHook::Refund',
			'url'               => null,
			'states'            => [
				RefundState::FAILED,
				RefundState::SUCCESSFUL,
			],
			'notifyEveryChange' => false,
		]);

		/**
		 * Payment Method Configuration Id
		 * @link https://www.wallee.com/doc/api/webhook-entity/view/1472041857405
		 */
		$this->webHookEntitiesConfig[] = (new Entity())->assign([
			'id'                => 1472041857405,
			'name'              => 'Shopware6::WebHook::Payment Method Configuration',
			'url'               => null,
			'states'            => [],
			'notifyEveryChange' => true,
		]);
	}

	/**
	 * @return \Wallee\Sdk\ApiClient
	 */
	public function getApiClient(): ApiClient
	{
		return $this->apiClient;
	}

	/**
	 * @param \Wallee\Sdk\ApiClient $apiClient
	 * @return \WalleePayment\Core\Api\WebHooks\Service\WebHooksService
	 */
	public function setApiClient(ApiClient $apiClient): WebHooksService
	{
		$apiClientBasePath = getenv('WALLEE_API_BASE_PATH') ? getenv('WALLEE_API_BASE_PATH') : $apiClient->getBasePath();
		$apiClient->setBasePath($apiClientBasePath);
		$this->apiClient = $apiClient;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getSpaceId(): int
	{
		return $this->spaceId;
	}

	/**
	 * @param int $spaceId
	 * @return \WalleePayment\Core\Api\WebHooks\Service\WebHooksService
	 */
	public function setSpaceId(int $spaceId): WebHooksService
	{
		$this->spaceId = $spaceId;
		return $this;
	}

	/**
	 * Install WebHooks
	 *
	 * @return array
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 */
	public function install(): array
	{
		// Configuration
		$settings = $this->settingsService->getSettings($this->getSalesChannelId());
		$this->setSpaceId($settings->getSpaceId())->setApiClient($settings->getApiClient());

		return $this->installListeners();
	}

	/**
	 * Get sales channel id
	 *
	 * @return string|null
	 */
	public function getSalesChannelId(): ?string
	{
		return $this->salesChannelId;
	}

	/**
	 * Set sales channel id
	 *
	 * @param string|null $salesChannelId
	 * @return \WalleePayment\Core\Api\WebHooks\Service\WebHooksService
	 */
	public function setSalesChannelId(?string $salesChannelId = null): WebHooksService
	{
		$this->salesChannelId = $salesChannelId;
		return $this;
	}

	/**
	 * Install Listeners
	 *
	 * @return array
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 */
	protected function installListeners(): array
	{
		$webHookUrlId      = $this->getOrCreateWebHookUrl()->getId();
		$installedWebHooks = $this->getInstalledWebHookListeners($webHookUrlId);
		$webHookEntityIds  = array_map(function (WebhookListener $webHook) {
			return $webHook->getEntity();
		}, $installedWebHooks);
		$returnValue       = [];

		/**
		 * @var $data Entity
		 */
		foreach ($this->webHookEntitiesConfig as $data) {

			if (in_array($data->getId(), $webHookEntityIds)) {
				continue;
			}

			/** @noinspection PhpParamsInspection */
			$entity = (new WebhookListenerCreate())
				->setName($data->getName())
				->setEntity($data->getId())
				->setNotifyEveryChange($data->isNotifyEveryChange())
				->setState(CreationEntityState::CREATE)
				->setEntityStates($data->getStates())
				->setUrl($webHookUrlId);

			$returnValue[] = $this->apiClient->getWebhookListenerService()->create($this->spaceId, $entity);
		}

		return $returnValue;
	}

	/**
	 * Create WebHook URL
	 *
	 * @return WebhookUrl
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 */
	protected function getOrCreateWebHookUrl(): WebhookUrl
	{

		/** @noinspection PhpParamsInspection */
		$entityQueryFilter = (new EntityQueryFilter())
			->setType(EntityQueryFilterType::_AND)
			->setChildren([
				$this->getEntityFilter('state', CreationEntityState::ACTIVE),
				$this->getEntityFilter('url', $this->getWebHookCallBackUrl()),
			]);

		$query = (new EntityQuery())->setFilter($entityQueryFilter)->setNumberOfEntities(1);

		$webHookUrls = $this->apiClient->getWebhookUrlService()->search($this->spaceId, $query);

		if (!empty($webHookUrls[0])) {
			return $webHookUrls[0];
		}

		/** @noinspection PhpParamsInspection */
		$entity = (new WebhookUrlCreate())
			->setName('Shopware6::WebHookURL')
			->setUrl($this->getWebHookCallBackUrl())
			->setState(CreationEntityState::ACTIVE);

		return $this->apiClient->getWebhookUrlService()->create($this->spaceId, $entity);
	}

	/**
	 * Creates and returns a new entity filter.
	 *
	 * @param string $fieldName
	 * @param        $value
	 * @param string $operator
	 * @return \Wallee\Sdk\Model\EntityQueryFilter
	 */
	protected function getEntityFilter(string $fieldName, $value, string $operator = CriteriaOperator::EQUALS): EntityQueryFilter
	{
		/** @noinspection PhpParamsInspection */
		return (new EntityQueryFilter())
			->setType(EntityQueryFilterType::LEAF)
			->setOperator($operator)
			->setFieldName($fieldName)
			->setValue($value);
	}

	/**
	 * Get web hook callback url
	 *
	 * @return string
	 */
	protected function getWebHookCallBackUrl(): string
	{
		return $this->router->generate(
			'api.action.wallee.webhook.update',
			[
				'version'        => 1,
				'salesChannelId' => $this->getSalesChannelId() ?? 'null',
			],
			UrlGeneratorInterface::ABSOLUTE_URL
		);
	}

	/**
	 * @param int $webHookUrlId
	 * @return array
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 */
	protected function getInstalledWebHookListeners(int $webHookUrlId): array
	{
		/** @noinspection PhpParamsInspection */
		$entityQueryFilter = (new EntityQueryFilter())
			->setType(EntityQueryFilterType::_AND)
			->setChildren([
				$this->getEntityFilter('state', CreationEntityState::ACTIVE),
				$this->getEntityFilter('url.id', $webHookUrlId),
			]);

		$query = (new EntityQuery())->setFilter($entityQueryFilter);

		return $this->apiClient->getWebhookListenerService()->search($this->spaceId, $query);
	}

}