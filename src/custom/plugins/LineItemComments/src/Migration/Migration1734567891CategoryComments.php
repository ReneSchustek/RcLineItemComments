<?php
declare(strict_types=1);

namespace LineItemComments\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1734567891CategoryComments extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1734567891;
    }

    public function update(Connection $connection): void
    {
        // Custom Field Set erstellen
        $customFieldSetId = Uuid::randomBytes();
        $customFieldId = Uuid::randomBytes();

        $connection->executeStatement('
            INSERT IGNORE INTO `custom_field_set` (`id`, `name`, `config`, `active`, `created_at`)
            VALUES (?, ?, ?, 1, NOW())
        ', [
            $customFieldSetId,
            'line_item_comments_category_config',
            json_encode([
                'label' => [
                    'de-DE' => 'Artikel-Bemerkungen',
                    'en-GB' => 'Line Item Comments'
                ]
            ])
        ]);

        // Custom Field für require_comments erstellen
        $connection->executeStatement('
            INSERT IGNORE INTO `custom_field` (`id`, `name`, `type`, `config`, `active`, `set_id`, `created_at`)
            VALUES (?, ?, ?, ?, 1, ?, NOW())
        ', [
            $customFieldId,
            'line_item_comments_required',
            'bool',
            json_encode([
                'type' => 'checkbox',
                'label' => [
                    'de-DE' => 'Bemerkungen für Artikel erforderlich',
                    'en-GB' => 'Require comments for line items'
                ],
                'helpText' => [
                    'de-DE' => 'Wenn aktiviert, müssen Kunden für alle Artikel dieser Kategorie eine Bemerkung hinterlassen',
                    'en-GB' => 'When enabled, customers must leave a comment for all items in this category'
                ],
                'customFieldType' => 'checkbox'
            ]),
            $customFieldSetId
        ]);

        // Custom Field Set zu Category Entity verknüpfen
        $connection->executeStatement('
            INSERT IGNORE INTO `custom_field_set_relation` (`id`, `set_id`, `entity_name`, `created_at`)
            VALUES (?, ?, "category", NOW())
        ', [
            Uuid::randomBytes(),
            $customFieldSetId
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Custom Field Set löschen (entfernt automatisch auch Fields und Relations)
        $connection->executeStatement('
            DELETE FROM `custom_field_set` WHERE `name` = "line_item_comments_category_config"
        ');
    }
}