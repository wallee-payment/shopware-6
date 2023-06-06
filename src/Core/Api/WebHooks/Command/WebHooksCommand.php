<?php declare(strict_types=1);


namespace WalleePayment\Core\Api\WebHooks\Command;

use Symfony\Component\{
	Console\Command\Command,
	Console\Input\InputInterface,
	Console\Output\OutputInterface};
use WalleePayment\Core\Api\WebHooks\Service\WebHooksService;

/**
 * Class WebHooksCommand
 *
 * @package WalleePayment\Core\Api\WebHooks\Command
 */
class WebHooksCommand extends Command {

	/**
	 * @var string
	 */
	protected static $defaultName = 'wallee:webhooks:install';

	/**
	 * @var \WalleePayment\Core\Api\WebHooks\Service\WebHooksService
	 */
	protected $webHooksService;

	/**
	 * WebHooksCommand constructor.
	 *
	 * @param \WalleePayment\Core\Api\WebHooks\Service\WebHooksService $webHooksService
	 */
	public function __construct(WebHooksService $webHooksService)
	{
		parent::__construct(self::$defaultName);
		$this->webHooksService = $webHooksService;
	}

	/**
	 * @param \Symfony\Component\Console\Input\InputInterface   $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 *
	 * @return int
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$output->writeln('Install WalleePayment webhooks...');
		$this->webHooksService->install();
		return 0;
	}

	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this->setDescription('Install WalleePayment webhooks.')
			 ->setHelp('This command installs WalleePayment webhooks.');
	}

}