<?php declare(strict_types=1);

namespace LineItemComments\Core\Content\LineItemComment;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<LineItemCommentEntity>
 */
class LineItemCommentCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'line_item_comment_collection';
    }

    protected function getExpectedClass(): string
    {
        return LineItemCommentEntity::class;
    }
}