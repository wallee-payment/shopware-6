<?php declare(strict_types=1);

namespace WalleePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1674114556PaymentMethodConfiguration extends MigrationStep
{
	public function getCreationTimestamp(): int
	{
		return 1674114556;
	}

	public function update(Connection $connection): void
	{
		try {
			$connection->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
			$connection->executeStatement('ALTER TABLE `wallee_payment_method_configuration` DROP FOREIGN KEY `fk.wle_payment_method_configuration.payment_method_id`;');
			$connection->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
		}catch (\Exception $exception){
			echo $exception->getMessage();
		}
	}

	public function updateDestructive(Connection $connection): void
	{
		// implement update destructive
	}
}



//SELECT TABLE_NAME,
//       COLUMN_NAME,
//       CONSTRAINT_NAME,
//       REFERENCED_TABLE_NAME,
//       REFERENCED_COLUMN_NAME
//FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
//WHERE TABLE_SCHEMA = "shopware"
//AND TABLE_NAME = "wallee_payment_method_configuration"
//AND REFERENCED_COLUMN_NAME IS NOT NULL;
//
//
//ALTER TABLE wallee_payment_method_configuration
//ADD CONSTRAINT `fk.wle_payment_method_configuration.payment_method_id` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`id`) ON DELETE CASCADE;