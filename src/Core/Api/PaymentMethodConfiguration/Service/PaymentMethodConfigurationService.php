<?php declare(strict_types=1);

namespace WalleePayment\Core\Api\PaymentMethodConfiguration\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\{
	Content\ImportExport\DataAbstractionLayer\Serializer\Entity\MediaSerializer,
	Content\ImportExport\DataAbstractionLayer\Serializer\SerializerRegistry,
	Content\ImportExport\Struct\Config,
	Content\Media\MediaDefinition,
	Framework\Context,
	Framework\DataAbstractionLayer\Search\Criteria,
	Framework\DataAbstractionLayer\Search\Filter\EqualsFilter,
	Framework\Plugin\Util\PluginIdProvider,
	Framework\Uuid\Uuid};
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wallee\Sdk\{
	ApiClient,
	Model\CreationEntityState,
	Model\EntityQuery,
	Model\PaymentMethodConfiguration};
use WalleePayment\Core\{
	Api\PaymentMethodConfiguration\Entity\PaymentMethodConfigurationEntity,
	Checkout\PaymentHandler\WalleePaymentHandler,
	Settings\Service\SettingsService};
use WalleePayment\WalleePayment;


/**
 * Class PaymentMethodConfigurationService
 *
 * @package WalleePayment\Core\Api\PaymentMethodConfiguration\Service
 */
class PaymentMethodConfigurationService {

	/**
	 * @var \WalleePayment\Core\Settings\Service\SettingsService
	 */
	protected $settingsService;

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
	 * @var \Symfony\Component\DependencyInjection\ContainerInterface
	 */
	protected $container;
	/**
	 * @var \Shopware\Core\Content\ImportExport\DataAbstractionLayer\Serializer\SerializerRegistry
	 */
	protected $serializerRegistry;
	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * @var ?string $salesChannelId
	 */
	private $salesChannelId;

	/**
	 * @var \Shopware\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity\MediaSerializer
	 */
	private $mediaSerializer;

	/**
	 * PaymentMethodConfigurationService constructor.
	 *
	 * @param \WalleePayment\Core\Settings\Service\SettingsService                        $settingsService
	 * @param \Symfony\Component\DependencyInjection\ContainerInterface                                  $container
	 * @param \Shopware\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity\MediaSerializer $mediaSerializer
	 * @param \Shopware\Core\Content\ImportExport\DataAbstractionLayer\Serializer\SerializerRegistry     $serializerRegistry
	 * @param \Psr\Log\LoggerInterface                                                                   $logger
	 */
	public function __construct(
		SettingsService $settingsService,
		ContainerInterface $container,
		MediaSerializer $mediaSerializer,
		SerializerRegistry $serializerRegistry,
		LoggerInterface $logger
	)
	{
		$this->settingsService    = $settingsService;
		$this->container          = $container;
		$this->mediaSerializer    = $mediaSerializer;
		$this->serializerRegistry = $serializerRegistry;
		$this->logger             = $logger;
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
	 * @return \WalleePayment\Core\Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService
	 */
	public function setApiClient(ApiClient $apiClient): PaymentMethodConfigurationService
	{
		$this->apiClient = $apiClient;
		return $this;
	}

	/**
	 * @param \Shopware\Core\Framework\Context $context
	 * @return array
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 */
	public function synchronize(Context $context): array
	{
		// Configuration
		$settings = $this->settingsService->getSettings($this->getSalesChannelId());
		$this->setSpaceId($settings->getSpaceId())
			 ->setApiClient($settings->getApiClient());

		$this->disablePaymentMethodConfigurations($context);
		$this->enablePaymentMethodConfigurations($context);

		return [];
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
	 * @return \WalleePayment\Core\Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService
	 */
	public function setSalesChannelId(?string $salesChannelId = null): PaymentMethodConfigurationService
	{
		$this->salesChannelId = $salesChannelId;
		return $this;
	}

	/**
	 * @param \Shopware\Core\Framework\Context $context
	 */
	private function disablePaymentMethodConfigurations(Context $context)
	{
		$criteria = (new Criteria())
			->addFilter(new EqualsFilter('state', CreationEntityState::ACTIVE));

		$paymentMethodConfigurationEntities = $this->container->get('wallee_payment_method_configuration.repository')
															  ->search($criteria, $context)
															  ->getEntities();
		/**
		 * @var $paymentMethodConfigurationEntity \WalleePayment\Core\Api\PaymentMethodConfiguration\Entity\PaymentMethodConfigurationEntity
		 */
		foreach ($paymentMethodConfigurationEntities as $paymentMethodConfigurationEntity) {
			$this->setPaymentMethodIsActive($paymentMethodConfigurationEntity->getPaymentMethodId(), false, $context);
			$data = [
				'id'    => $paymentMethodConfigurationEntity->getId(),
				'state' => CreationEntityState::INACTIVE,
			];
			$this->container->get('wallee_payment_method_configuration.repository')
							->update([$data], $context);
		}
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

		/** @var \Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface $paymentRepository */
		$paymentRepository = $this->container->get('payment_method.repository');
		$paymentRepository->update([$paymentMethod], $context);
	}

	/**
	 * @param \Shopware\Core\Framework\Context $context
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 */
	private function enablePaymentMethodConfigurations(Context $context)
	{
		$paymentMethodConfigurations = $this->getPaymentMethodConfigurations();
		$this->logger->info('Updating payment methods', $paymentMethodConfigurations);

		/**
		 * @var $paymentMethodConfiguration \Wallee\Sdk\Model\PaymentMethodConfiguration
		 */
		foreach ($paymentMethodConfigurations as $paymentMethodConfiguration) {

			$paymentMethodConfigurationEntity = $this->getPaymentMethodConfigurationEntity(
				$paymentMethodConfiguration->getSpaceId(),
				$paymentMethodConfiguration->getId(),
				$context
			);

			if (!($paymentMethodConfiguration->getState() == CreationEntityState::ACTIVE)) {
				continue;
			}

			$id = is_null($paymentMethodConfigurationEntity) ? Uuid::randomHex() : $paymentMethodConfigurationEntity->getId();

			$data = [
				'id'                           => $id,
				'paymentMethodConfigurationId' => $paymentMethodConfiguration->getId(),
				'paymentMethodId'              => $id,
				'data'                         => json_decode(strval($paymentMethodConfiguration), true),
				'sortOrder'                    => $paymentMethodConfiguration->getSortOrder(),
				'spaceId'                      => $paymentMethodConfiguration->getSpaceId(),
				'state'                        => CreationEntityState::ACTIVE,
			];

			$this->upsertPaymentMethod($id, $paymentMethodConfiguration, $context);


			$this->container->get('wallee_payment_method_configuration.repository')
							->upsert([$data], $context);
		}
	}

	/**
	 * @return \Wallee\Sdk\Model\PaymentMethodConfiguration[]
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 */
	private function getPaymentMethodConfigurations(): array
	{
		return $this->apiClient->getPaymentMethodConfigurationService()
							   ->search($this->getSpaceId(), new EntityQuery());
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
	 * @return \WalleePayment\Core\Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService
	 */
	public function setSpaceId(int $spaceId): PaymentMethodConfigurationService
	{
		$this->spaceId = $spaceId;
		return $this;
	}

	/**
	 * @param int                              $spaceId
	 * @param int                              $paymentMethodConfigurationId
	 * @param \Shopware\Core\Framework\Context $context
	 * @return \WalleePayment\Core\Api\PaymentMethodConfiguration\Entity\PaymentMethodConfigurationEntity|null
	 */
	protected function getPaymentMethodConfigurationEntity(
		int $spaceId,
		int $paymentMethodConfigurationId,
		Context $context
	): ?PaymentMethodConfigurationEntity
	{
		$criteria = (new Criteria())->addFilter(
			new EqualsFilter('spaceId', $spaceId),
			new EqualsFilter('paymentMethodConfigurationId', $paymentMethodConfigurationId)
		);

		return $this->container->get('wallee_payment_method_configuration.repository')
							   ->search($criteria, $context)
							   ->getEntities()
							   ->first();
	}

	/**
	 * Update or insert Payment Method
	 *
	 * @param string                                                      $id
	 * @param \Wallee\Sdk\Model\PaymentMethodConfiguration $paymentMethodConfiguration
	 * @param \Shopware\Core\Framework\Context                            $context
	 */
	protected function upsertPaymentMethod(
		string $id,
		PaymentMethodConfiguration $paymentMethodConfiguration,
		Context $context
	): void
	{
		/** @var PluginIdProvider $pluginIdProvider */
		$pluginIdProvider = $this->container->get(PluginIdProvider::class);
		$pluginId         = $pluginIdProvider->getPluginIdByBaseClass(
			WalleePayment::class,
			$context
		);

		$data = [
			'id'                => $id,
			'handlerIdentifier' => WalleePaymentHandler::class,
			'name'              => $paymentMethodConfiguration->getName(),
			'description'       => empty($paymentMethodConfiguration->getDescription()->getDisplayName()) ?
				$paymentMethodConfiguration->getName() :
				$paymentMethodConfiguration->getDescription()->getDisplayName(),
			'pluginId'          => $pluginId,
			'position'          => -101,
			'active'            => true,
		];

		$data['mediaId'] = $this->upsertMedia($id, $paymentMethodConfiguration, $context);

		$data = array_filter($data);

		/** @var \Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface $paymentRepository */
		$paymentRepository = $this->container->get('payment_method.repository');
		$paymentRepository->upsert([$data], $context);
	}

	/**
	 * Upload Payment Method icons
	 *
	 * @param string                                                      $id
	 * @param \Wallee\Sdk\Model\PaymentMethodConfiguration $paymentMethodConfiguration
	 * @param \Shopware\Core\Framework\Context                            $context
	 * @return string|null
	 */
	protected function upsertMedia(string $id, PaymentMethodConfiguration $paymentMethodConfiguration, Context $context): ?string
	{
		try {
			$mediaDefaultFolderRepository = $this->container->get('media_default_folder.repository');
			$mediaDefaultFolderRepository->upsert([
				[
					'id'                => $id,
					'associationFields' => [],
					'entity'            => 'payment_method_' . $paymentMethodConfiguration->getId(),
				],
			], $context);

			$mediaFolderRepository = $this->container->get('media_folder.repository');
			$mediaFolderRepository->upsert([
				[
					'id'                     => $id,
					'defaultFolderId'        => $id,
					'name'                   => $paymentMethodConfiguration->getName(),
					'useParentConfiguration' => false,
					'configuration'          => [],
				],
			], $context);


			$mediaDefinition = $this->container->get(MediaDefinition::class);
			$this->mediaSerializer->setRegistry($this->serializerRegistry);
			$data            = [
				'id'            => $id,
				'title'         => $paymentMethodConfiguration->getName(),
				'url'           => $paymentMethodConfiguration->getResolvedImageUrl(),
				'mediaFolderId' => $id,
			];
			$data            = $this->mediaSerializer->deserialize(new Config([], []), $mediaDefinition, $data);
			$mediaRepository = $this->container->get('media.repository');
			$mediaRepository->upsert([$data], $context);
			return $id;
		} catch (\Exception $e) {
			$this->logger->critical($e->getMessage(), [$e->getTraceAsString()]);
			return null;
		}
	}


}