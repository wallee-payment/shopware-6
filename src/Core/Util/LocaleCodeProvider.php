<?php declare(strict_types=1);

namespace WalleePayment\Core\Util;

use Psr\Log\LoggerInterface;
use Shopware\Core\{
	Framework\Context,
	Framework\DataAbstractionLayer\EntityRepositoryInterface,
	Framework\DataAbstractionLayer\Search\Criteria};

/**
 * Class LocaleCodeProvider
 * 
 * @package WalleePayment\Core\Util
 */
class LocaleCodeProvider {

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * @var EntityRepositoryInterface
	 */
	private $languageRepository;

	/**
	 * LocaleCodeProvider constructor.
	 *
	 * @param \Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface $languageRepository
	 * @param \Psr\Log\LoggerInterface                                                $logger
	 */
	public function __construct(EntityRepositoryInterface $languageRepository, LoggerInterface $logger)
	{
		$this->languageRepository = $languageRepository;
		$this->logger = $logger;
	}

	/**
	 * @param \Shopware\Core\Framework\Context $context
	 * @return string
	 */
	public function getLocaleCodeFromContext(Context $context): string
	{
		$defaultLocale = 'en-GB';
		$languageId    = $context->getLanguageId();
		$criteria      = (new Criteria([$languageId]))->addAssociation('locale');
		/** @var \Shopware\Core\System\Language\LanguageCollection $languageCollection */
		$languageCollection = $this->languageRepository->search($criteria, $context)->getEntities();

		$language = $languageCollection->get($languageId);
		if (is_null($language)) {
			return $defaultLocale;
		}

		return $language->getLocale() ? $language->getLocale()->getCode() : $defaultLocale;
	}
}