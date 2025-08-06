<?php declare(strict_types=1);

namespace LineItemComments\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1734567890LineItemComments extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1734567890;
    }

    public function update(Connection $connection): void
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS `line_item_comment` (
                `id` BINARY(16) NOT NULL,
                `line_item_id` VARCHAR(255) NOT NULL,
                `comment` TEXT NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.line_item_comment.line_item_id` (`line_item_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ';

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->executeStatement('DROP TABLE IF EXISTS `line_item_comment`');
    }
}