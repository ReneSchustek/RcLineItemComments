<?php declare(strict_types=1);

namespace LineItemComments\Core\Content\LineItemComment;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class LineItemCommentEntity extends Entity
{
    use EntityIdTrait;

    protected string $lineItemId;
    protected string $comment;

    public function getLineItemId(): string
    {
        return $this->lineItemId;
    }

    public function setLineItemId(string $lineItemId): void
    {
        $this->lineItemId = $lineItemId;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }
}