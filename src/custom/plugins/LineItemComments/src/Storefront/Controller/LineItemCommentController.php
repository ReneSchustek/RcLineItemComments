<?php declare(strict_types=1);

namespace LineItemComments\Storefront\Controller;

use LineItemComments\Service\LineItemCommentService;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class LineItemCommentController extends StorefrontController
{
    private LineItemCommentService $commentService;
    private CartService $cartService;

    public function __construct(
        LineItemCommentService $commentService,
        CartService $cartService
    ) {
        $this->commentService = $commentService;
        $this->cartService = $cartService;
    }

    public function saveComment(Request $request, SalesChannelContext $context): JsonResponse
    {
        $lineItemId = $request->get('lineItemId');
        $comment = $request->get('comment');

        if (!$lineItemId || !$comment) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Line Item ID und Kommentar sind erforderlich'
            ], 400);
        }

        try {
            // Kommentar speichern
            $this->commentService->saveComment($lineItemId, $comment, $context->getContext());

            // Cart aktualisieren
            $cart = $this->cartService->getCart($context->getToken(), $context);
            $lineItem = $cart->get($lineItemId);

            if ($lineItem) {
                $payload = $lineItem->getPayload();
                $payload['comment'] = $comment;
                $lineItem->setPayload($payload);

                $this->cartService->recalculate($cart, $context);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Kommentar gespeichert'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Fehler beim Speichern des Kommentars: ' . $e->getMessage()
            ], 500);
        }
    }

    public function validateComments(Request $request, SalesChannelContext $context): JsonResponse
    {
        $comments = $request->get('comments', []);
        $cart = $this->cartService->getCart($context->getToken(), $context);

        // Alle Kommentare speichern
        if (!empty($comments)) {
            $this->commentService->addCommentsToCart($cart, $comments, $context->getContext());
        }

        // Validierung durchführen (berücksichtigt Kategoriekonfiguration)
        $errors = $this->commentService->validateCartComments($cart, $context);

        return new JsonResponse([
            'success' => empty($errors),
            'errors' => $errors,
            'message' => empty($errors) ? 'Alle Kommentare sind gültig' : 'Einige Kommentare fehlen',
            'requiredProductIds' => $this->commentService->getRequiredCommentProductIds($cart, $context)
        ]);
    }

    public function getComment(string $lineItemId, SalesChannelContext $context): JsonResponse
    {
        try {
            $comment = $this->commentService->getCommentByLineItemId($lineItemId, $context->getContext());

            return new JsonResponse([
                'success' => true,
                'comment' => $comment ? $comment->getComment() : ''
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Fehler beim Laden des Kommentars: ' . $e->getMessage()
            ], 500);
        }
    }
}