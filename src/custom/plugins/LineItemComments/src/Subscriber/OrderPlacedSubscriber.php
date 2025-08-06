<?php declare(strict_types=1);

namespace LineItemComments\Subscriber;

use LineItemComments\Service\LineItemCommentService;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderPlacedSubscriber implements EventSubscriberInterface
{
    private LineItemCommentService $commentService;
    private EntityRepository $orderLineItemRepository;

    public function __construct(
        LineItemCommentService $commentService,
        EntityRepository $orderLineItemRepository
    ) {
        $this->commentService = $commentService;
        $this->orderLineItemRepository = $orderLineItemRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => 'onOrderPlaced',
        ];
    }

    public function onOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        $order = $event->getOrder();
        $context = $event->getContext();

        $updateData = [];

        foreach ($order->getLineItems() as $orderLineItem) {
            if ($orderLineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }

            // Kommentar aus der temporären Tabelle holen
            $comment = $this->commentService->getCommentByLineItemId(
                $orderLineItem->getIdentifier(),
                $context
            );

            if ($comment) {
                // Kommentar in das Order Line Item Payload übertragen
                $payload = $orderLineItem->getPayload() ?? [];
                $payload['comment'] = $comment->getComment();

                $updateData[] = [
                    'id' => $orderLineItem->getId(),
                    'payload' => $payload
                ];

                // Temporären Kommentar löschen (optional)
                $this->commentService->removeComment(
                    $orderLineItem->getIdentifier(),
                    $context
                );
            }
        }

        if (!empty($updateData)) {
            $this->orderLineItemRepository->update($updateData, $context);
        }
    }
}