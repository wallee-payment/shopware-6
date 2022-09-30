<?php declare(strict_types=1);

namespace WalleePayment\Core\Util\Analytics;

use Composer\InstalledVersions;
use Wallee\Sdk\ApiClient;

/**
 * Class Analytics
 *
 * @package WalleePayment\Core\Util\Analytics
 */
class Analytics {

	public const SHOP_SYSTEM             = 'x-wallee-shop-system';
	public const SHOP_SYSTEM_VERSION     = 'x-wallee-shop-system-version';
	public const SHOP_SYSTEM_AND_VERSION = 'x-wallee-shop-system-and-version';
	public const PLUGIN_FEATURE          = 'x-wallee-shop-plugin-feature';

	/**
	 * @return array
	 */
	public static function getDefaultData()
	{
		return [
			self::SHOP_SYSTEM             => 'shopware',
			self::SHOP_SYSTEM_VERSION     => '6',
			self::SHOP_SYSTEM_AND_VERSION => 'shopware-6',
		];
	}

	/**
	 * @param \Wallee\Sdk\ApiClient $apiClient
	 */
	public static function addHeaders(ApiClient &$apiClient)
	{
		$data = self::getDefaultData();
		foreach ($data as $key => $value) {
			$apiClient->addDefaultHeader($key, $value);
		}
	}
}


