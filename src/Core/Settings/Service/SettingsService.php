<?php declare(strict_types=1);

namespace WalleePayment\Core\Settings\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use WalleePayment\Core\Settings\Struct\Settings;


/**
 * Class SettingsService
 *
 * @package WalleePayment\Core\Settings\Service
 */
class SettingsService {

	/**
	 * Prefix to Wallee configs
	 */
	public const SYSTEM_CONFIG_DOMAIN = 'WalleePayment.config.';

	/**
	 * @var \Shopware\Core\System\SystemConfig\SystemConfigService
	 */
	private $systemConfigService;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $logger;

	/**
	 * SettingsService constructor.
	 *
	 * @param \Shopware\Core\System\SystemConfig\SystemConfigService $systemConfigService
	 * @param \Psr\Log\LoggerInterface                               $logger
	 */
	public function __construct(SystemConfigService $systemConfigService, LoggerInterface $logger)
	{
		$this->systemConfigService = $systemConfigService;
		$this->logger              = $logger;

	}

	/**
	 * Update setting
	 *
	 * @param array       $settings
	 * @param string|null $salesChannelId
	 */
	public function updateSettings(array $settings, ?string $salesChannelId = null): void
	{
		foreach ($settings as $key => $value) {
			$this->systemConfigService->set(
				self::SYSTEM_CONFIG_DOMAIN . $key,
				$value,
				$salesChannelId
			);
		}
	}

	/**
	 * Get settings
	 *
	 * @param string|null $salesChannelId
	 * @return \WalleePayment\Core\Settings\Struct\Settings
	 */
	public function getSettings(?string $salesChannelId = null): Settings
	{
		$values = $this->systemConfigService->getDomain(
			self::SYSTEM_CONFIG_DOMAIN,
			$salesChannelId,
			true
		);

		$propertyValuePairs = [];

		/** @var string $key */
		foreach ($values as $key => $value) {
			$property = (string) \mb_substr($key, \mb_strlen(self::SYSTEM_CONFIG_DOMAIN));
			if ($property === '') {
				continue;
			}
			if (!is_numeric($value) && empty($value)) {
				$this->logger->warning(strtr('Empty value :value for settings :property.', [':property' => $property, ':value' => $value]));
			}
			$propertyValuePairs[$property] = $value;
		}

		return (new Settings())->assign($propertyValuePairs);
	}

	/**
	 * Get valid settings
	 *
	 * @param string|null $salesChannelId
	 * @return \WalleePayment\Core\Settings\Struct\Settings|null
	 */
	public function getValidSettings(?string $salesChannelId = null): ?Settings
	{
		$settings = $this->getSettings($salesChannelId);

		if (empty($settings->getSpaceId())) {
			$this->logger->critical('Empty spaceId setting');
			return null;
		}

		if (empty($settings->getUserId())) {
			$this->logger->critical('Empty userId setting');
			return null;
		}

		if (empty($settings->getIntegration())) {
			$this->logger->critical('Empty integration setting');
			return null;
		}

		if (empty($settings->getApplicationKey())) {
			$this->logger->critical('Empty applicationKey setting');
			return null;
		}

		return $settings;
	}
}