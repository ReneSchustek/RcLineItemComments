<?php declare(strict_types=1);

namespace LineItemComments\Subscriber;

use LineItemComments\Service\LineItemCommentService;
use Shopware\Core\Checkout\Cart\Event\CartDeletedEvent;
use Shopware\Core\Checkout\Cart\Event\LineItemRemovedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartSubscriber implements EventSubscriberInterface
{
    private LineItemCommentService $commentService;

    public function __construct(LineItemCommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LineItemRemovedEvent::class => 'onLineItemRemoved',
            CartDeletedEvent::class => 'onCartDeleted',
        ];
    }

    public function onLineItemRemoved(LineItemRemovedEvent $event): void
    {
        // Kommentar löschen wenn Line Item entfernt wird
        $this->commentService->removeComment(
            $event->getLineItemId(),
            $event->getContext()
        );
    }

    public function onCartDeleted(CartDeletedEvent $event): void
    {
        // Hier könnten alle Kommentare des Warenkorbs gelöscht werden
        // Falls gewünscht, ansonsten bleiben sie für späteren Abruf erhalten
    }
}