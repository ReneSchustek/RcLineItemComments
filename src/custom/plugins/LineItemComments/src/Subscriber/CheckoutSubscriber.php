<?php declare(strict_types=1);

namespace LineItemComments\Subscriber;

use LineItemComments\Service\LineItemCommentService;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Cart\Exception\CartException;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class CheckoutSubscriber implements EventSubscriberInterface
{
    private LineItemCommentService $commentService;
    private RequestStack $requestStack;
    private TranslatorInterface $translator;

    public function __construct(
        LineItemCommentService $commentService,
        RequestStack $requestStack,
        TranslatorInterface $translator
    ) {
        $this->commentService = $commentService;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutCartPageLoadedEvent::class => 'onCartPageLoaded',
            CheckoutConfirmPageLoadedEvent::class => 'onConfirmPageLoaded',
            CheckoutOrderPlacedEvent::class => 'onOrderPlaced',
        ];
    }

    public function onCartPageLoaded(CheckoutCartPageLoadedEvent $event): void
    {
        $cart = $event->getPage()->getCart();
        $context = $event->getContext();
        $salesChannelContext = $event->getSalesChannelContext();

        // Bestehende Kommentare laden und Produktdaten für Template verfügbar machen
        foreach ($cart->getLineItems() as $lineItem) {
            if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }

            // Produktdaten mit Kategorien laden für Template-Zugriff
            $this->loadProductDataForLineItem($lineItem, $salesChannelContext);

            $existingComment = $this->commentService->getCommentByLineItemId(
                $lineItem->getId(),
                $context
            );

            if ($existingComment) {
                $payload = $lineItem->getPayload();
                $payload['comment'] = $existingComment->getComment();
                $lineItem->setPayload($payload);
            }
        }
    }

    public function onConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $cart = $event->getPage()->getCart();
        $salesChannelContext = $event->getSalesChannelContext();

        // Validierung der Kommentare nur für Produkte die Kommentare erfordern
        $errors = $this->commentService->validateCartComments($cart, $salesChannelContext);

        if (!empty($errors)) {
            $request = $this->requestStack->getCurrentRequest();
            if ($request) {
                $request->getSession()->getFlashBag()->add('danger',
                    $this->translator->trans('lineitemcomments.checkout.missing_comments')
                );
            }
        }
    }

    private function loadProductDataForLineItem(LineItem $lineItem, SalesChannelContext $salesChannelContext): void
    {
        $productId = $lineItem->getReferencedId();
        if (!$productId) {
            return;
        }

        // Produkt mit Kategorien laden
        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('categories');

        // Repository-Service aus Container holen
        $productRepository = $this->getProductRepository();
        $product = $productRepository->search($criteria, $salesChannelContext->getContext())->first();

        if ($product) {
            $payload = $lineItem->getPayload();
            $payload['productEntity'] = $product;
            $lineItem->setPayload($payload);
        }
    }

    private function getProductRepository()
    {
        // Da wir keinen direkten Zugriff auf den Container haben,
        // nutzen wir eine Alternative über den Service
        return $this->commentService->getContainer()->get('product.repository');
    }

    public function onOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        // Hier könnten die Kommentare in die Bestellung übertragen werden
        // Das würde eine weitere Migration für order_line_item Erweiterung erfordern
    }
}