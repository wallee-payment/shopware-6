<?php declare(strict_types=1);

namespace WalleePayment\Core\Util\Payload;

use Psr\Log\LoggerInterface;

/**
 * Class AbstractPayload
 * 
 * @package WalleePayment\Core\Util\Payload
 */
abstract class AbstractPayload {

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * RefundPayload constructor.
	 * @param \Psr\Log\LoggerInterface $logger
	 */
	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * Fix string length string to specific length.
	 *
	 * @param string $string
	 * @param int    $maxLength
	 * @return string
	 */
	protected function fixLength(string $string, int $maxLength): string
	{
		return mb_substr($string, 0, $maxLength, 'UTF-8');
	}

}