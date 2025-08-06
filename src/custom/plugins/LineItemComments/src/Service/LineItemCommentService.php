<?php declare(strict_types=1);

namespace LineItemComments\Service;

use LineItemComments\Core\Content\LineItemComment\LineItemCommentEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;

class LineItemCommentService
{
    private EntityRepository $lineItemCommentRepository;
    private SalesChannelContextService $salesChannelContextService;

    public function __construct(
        EntityRepository $lineItemCommentRepository,
        SalesChannelContextService $salesChannelContextService
    ) {
        $this->lineItemCommentRepository = $lineItemCommentRepository;
        $this->salesChannelContextService = $salesChannelContextService;
    }

    public function saveComment(string $lineItemId, string $comment, Context $context): void
    {
        $existing = $this->getCommentByLineItemId($lineItemId, $context);

        if ($existing) {
            $this->lineItemCommentRepository->update([
                [
                    'id' => $existing->getId(),
                    'comment' => $comment,
                ]
            ], $context);
        } else {
            $this->lineItemCommentRepository->create([
                [
                    'id' => Uuid::randomHex(),
                    'lineItemId' => $lineItemId,
                    'comment' => $comment,
                ]
            ], $context);
        }
    }

    public function getCommentByLineItemId(string $lineItemId, Context $context): ?LineItemCommentEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('lineItemId', $lineItemId));

        return $this->lineItemCommentRepository->search($criteria, $context)->first();
    }

    public function validateCartComments(Cart $cart, ?SalesChannelContext $salesChannelContext = null): array
    {
        $errors = [];

        foreach ($cart->getLineItems() as $lineItem) {
            if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }

            // Prüfen ob für dieses Produkt Kommentare erforderlich sind
            if (!$this->isCommentRequiredForLineItem($lineItem, $salesChannelContext)) {
                continue;
            }

            $comment = $lineItem->getPayload()['comment'] ?? '';

            if (empty(trim($comment))) {
                $errors[$lineItem->getId()] = 'Bemerkung ist erforderlich';
            }
        }

        return $errors;
    }

    public function isCommentRequiredForLineItem(LineItem $lineItem, ?SalesChannelContext $salesChannelContext = null): bool
    {
        if (!$salesChannelContext) {
            return true; // Fallback: Kommentar erforderlich wenn Context nicht verfügbar
        }

        $productId = $lineItem->getReferencedId();

        if (!$productId) {
            return false;
        }

        // Produkt mit Kategorien laden
        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('categories');

        $productRepository = $this->container->get('product.repository');
        $product = $productRepository->search($criteria, $salesChannelContext->getContext())->first();

        if (!$product || !$product->getCategories()) {
            return false;
        }

        // Prüfen ob eine der Kategorien Kommentare erfordert
        foreach ($product->getCategories() as $category) {
            $customFields = $category->getCustomFields() ?? [];
            if (isset($customFields['line_item_comments_required']) && $customFields['line_item_comments_required'] === true) {
                return true;
            }
        }

        return false;
    }

    public function getRequiredCommentProductIds(Cart $cart, ?SalesChannelContext $salesChannelContext = null): array
    {
        $requiredProductIds = [];

        foreach ($cart->getLineItems() as $lineItem) {
            if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }

            if ($this->isCommentRequiredForLineItem($lineItem, $salesChannelContext)) {
                $requiredProductIds[] = $lineItem->getReferencedId();
            }
        }

        return $requiredProductIds;
    }

    public function addCommentsToCart(Cart $cart, array $comments, Context $context): void
    {
        foreach ($comments as $lineItemId => $comment) {
            $lineItem = $cart->get($lineItemId);
            if ($lineItem) {
                $payload = $lineItem->getPayload();
                $payload['comment'] = $comment;
                $lineItem->setPayload($payload);

                // Auch in der Datenbank speichern
                $this->saveComment($lineItemId, $comment, $context);
            }
        }
    }

    public function removeComment(string $lineItemId, Context $context): void
    {
        $existing = $this->getCommentByLineItemId($lineItemId, $context);

        if ($existing) {
            $this->lineItemCommentRepository->delete([
                ['id' => $existing->getId()]
            ], $context);
        }
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}