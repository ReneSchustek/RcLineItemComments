<?php declare(strict_types=1);

namespace LineItemComments\Core\Checkout\Order\Aggregate\OrderLineItem;

use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class OrderLineItemExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new StringField('customer_comment', 'customerComment'))->addFlags(new Runtime())
        );
    }

    public function getDefinitionClass(): string
    {
        return OrderLineItemDefinition::class;
    }

    public function getEntityName(): string
    {
        return 'order_line_item';
    }
}