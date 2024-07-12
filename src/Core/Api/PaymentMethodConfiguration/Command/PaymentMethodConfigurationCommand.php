<?php declare(strict_types=1);


namespace WalleePayment\Core\Api\PaymentMethodConfiguration\Command;

use Shopware\Core\Framework\Context;
use Symfony\Component\{
	Console\Command\Command,
	Console\Input\InputInterface,
	Console\Output\OutputInterface};
use WalleePayment\Core\Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService;

/**
 * Class PaymentMethodConfigurationCommand
 *
 * @package WalleePayment\Core\Api\PaymentMethodConfiguration\Command
 */
class PaymentMethodConfigurationCommand extends Command {

	/**
	 * @var string
	 */
	protected static $defaultName = 'wallee:payment-method:configuration';

	/**
	 * @var \WalleePayment\Core\Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService
	 */
	protected $paymentMethodConfigurationService;

	/**
	 * PaymentMethodConfigurationCommand constructor.
	 *
	 * @param \WalleePayment\Core\Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService $paymentMethodConfigurationService
	 */
	public function __construct(PaymentMethodConfigurationService $paymentMethodConfigurationService)
	{
		parent::__construct(self::$defaultName);
		$this->paymentMethodConfigurationService = $paymentMethodConfigurationService;
	}

	/**
	 * @param \Symfony\Component\Console\Input\InputInterface   $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @return int
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$output->writeln('Fetch WalleePayment space available payment methods...');
		$this->paymentMethodConfigurationService->synchronize(Context::createDefaultContext());
		return 0;
	}

	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this->setDescription('Fetches WalleePayment space available payment methods.')
			 ->setHelp('This command fetches WalleePayment space available payment methods.');
	}

}