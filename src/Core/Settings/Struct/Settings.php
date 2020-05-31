<?php declare(strict_types=1);

namespace WalleePayment\Core\Settings\Struct;

use Shopware\Core\Framework\Struct\Struct;
use Wallee\Sdk\ApiClient;

/**
 * Class Settings
 *
 * @package WalleePayment\Core\Settings\Struct
 */
class Settings extends Struct {

	/**
	 * @var \Wallee\Sdk\ApiClient
	 */
	protected $apiClient;

	/**
	 * Application Key
	 *
	 * @var string
	 */
	protected $applicationKey;

	/**
	 * Preferred integration
	 *
	 * @var string
	 */
	protected $integration;

	/**
	 * Enforce line item consistency
	 *
	 * @var bool
	 */
	protected $lineItemConsistencyEnabled;

	/**
	 * Space Id
	 *
	 * @var int
	 */
	protected $spaceId;

	/**
	 * Space View Id
	 *
	 * @var ?int
	 */
	protected $spaceViewId;

	/**
	 * User id
	 *
	 * @var int
	 */
	protected $userId;

	/**
	 * @return string
	 */
	public function getIntegration(): string
	{
		return (string) $this->integration;
	}

	/**
	 * @param string $integration
	 */
	public function setIntegration(string $integration): void
	{
		$this->integration = $integration;
	}

	/**
	 * @return bool
	 */
	public function isLineItemConsistencyEnabled(): bool
	{
		return (bool) $this->lineItemConsistencyEnabled;
	}

	/**
	 * @param bool $lineItemConsistencyEnabled
	 * @return Settings
	 */
	public function setLineItemConsistencyEnabled(bool $lineItemConsistencyEnabled): Settings
	{
		$this->lineItemConsistencyEnabled = $lineItemConsistencyEnabled;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getSpaceId(): int
	{
		return intval($this->spaceId);
	}

	/**
	 * @param int $spaceId
	 */
	public function setSpaceId(int $spaceId): void
	{
		$this->spaceId = $spaceId;
	}

	/**
	 * @return int|null
	 */
	public function getSpaceViewId(): ?int
	{
		return !empty($this->spaceViewId) & is_numeric($this->spaceViewId) ? intval($this->spaceViewId) : null;
	}

	/**
	 * @param int $spaceViewId
	 */
	public function setSpaceViewId(int $spaceViewId): void
	{
		$this->spaceViewId = $spaceViewId;
	}

	/**
	 * Get SDK ApiClient
	 *
	 * @return \Wallee\Sdk\ApiClient
	 */
	public function getApiClient(): ApiClient
	{
		if (is_null($this->apiClient)) {
			$this->apiClient   = new ApiClient($this->getUserId(), $this->getApplicationKey());
			$apiClientBasePath = getenv('WALLEE_API_BASE_PATH') ? getenv('WALLEE_API_BASE_PATH') : $this->apiClient->getBasePath();
			$this->apiClient->setBasePath($apiClientBasePath);
		}
		return $this->apiClient;
	}

	/**
	 * @return int
	 */
	public function getUserId(): int
	{
		return intval($this->userId);
	}

	/**
	 * @param int $userId
	 */
	public function setUserId(int $userId): void
	{
		$this->userId = $userId;
	}

	/**
	 * @return string
	 */
	public function getApplicationKey(): string
	{
		return (string) $this->applicationKey;
	}

	/**
	 * @param string $applicationKey
	 */
	public function setApplicationKey(string $applicationKey): void
	{
		$this->applicationKey = $applicationKey;
	}
}