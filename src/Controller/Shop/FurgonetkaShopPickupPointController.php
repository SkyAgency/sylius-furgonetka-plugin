<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Controller\Shop;

use Furgonetka\SyliusFurgonetkaPlugin\Entity\FurgonetkaPickupPoint;
use Furgonetka\SyliusFurgonetkaPlugin\Repository\FurgonetkaPickupPointRepository;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaMapService;
use Furgonetka\SyliusFurgonetkaPlugin\Structure\FurgonetkaPickupPointPayloadStructure;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Persists the pickup point chosen on the checkout select-shipping step.
 *
 * Called over AJAX by js/furgonetka-shop.js after the map callback returns a point.
 * The point is stored against the current cart (one point per order).
 */
final class FurgonetkaShopPickupPointController extends AbstractController
{
    public const CSRF_TOKEN_ID = 'furgonetka_pickup_point';

    public function __construct(
        private readonly CartContextInterface $cartContext,
        private readonly FurgonetkaPickupPointRepository $pickupPointRepository,
        private readonly FurgonetkaMapService $mapService,
    ) {
    }

    public function saveAction(Request $request): Response
    {
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $this->extractCsrfToken($request))) {
            return $this->json(['error' => 'invalid_csrf_token'], Response::HTTP_FORBIDDEN);
        }

        $payload = FurgonetkaPickupPointPayloadStructure::fromRequest($request);
        if (!$payload->isComplete()) {
            return $this->json(['error' => 'invalid_point'], Response::HTTP_BAD_REQUEST);
        }

        // The chosen point must match the method's map configuration (its courier service); the map
        // SDK returns that service in point.type. Rejects a point posted for a method that does not
        // offer pickup points or a mismatched courier.
        if (!$this->mapService->validatePoint($payload->shippingMethodCode, $payload->type)) {
            return $this->json(['error' => 'invalid_pickup_point'], Response::HTTP_BAD_REQUEST);
        }

        $order = $this->resolveCart();
        if (null === $order) {
            return $this->json(['error' => 'cart_not_found'], Response::HTTP_BAD_REQUEST);
        }

        $pickupPoint = $this->pickupPointRepository->findOneByOrder($order) ?? new FurgonetkaPickupPoint();
        $pickupPoint
            ->setOrder($order)
            ->setShippingMethodCode($payload->shippingMethodCode)
            ->setPointCode($payload->code)
            ->setPointName($payload->name)
            ->setPointType($payload->type);

        $this->pickupPointRepository->save($pickupPoint);

        return $this->json($payload->toArray());
    }

    /**
     * Drops the point saved for the current cart. Called over AJAX when the customer switches
     * shipping method, so a point chosen for the previous method does not linger (one point per
     * order; changing the method forces a fresh selection).
     */
    public function clearAction(Request $request): Response
    {
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $this->extractCsrfToken($request))) {
            return $this->json(['error' => 'invalid_csrf_token'], Response::HTTP_FORBIDDEN);
        }

        $order = $this->resolveCart();
        if (null === $order) {
            return $this->json(['error' => 'cart_not_found'], Response::HTTP_BAD_REQUEST);
        }

        $pickupPoint = $this->pickupPointRepository->findOneByOrder($order);
        if ($pickupPoint !== null) {
            $this->pickupPointRepository->remove($pickupPoint);
        }

        return $this->json(['cleared' => true]);
    }

    /**
     * Resolves the current cart as a Sylius Core order, or null when there is no cart.
     *
     * CartContextInterface::getCart() is typed against the narrower Order component interface,
     * while the entity and repository work with the richer Core OrderInterface. The shop cart
     * is always a Core order at runtime; the instanceof guard makes that contract explicit.
     */
    private function resolveCart(): ?OrderInterface
    {
        try {
            $cart = $this->cartContext->getCart();
        } catch (CartNotFoundException) {
            return null;
        }

        return $cart instanceof OrderInterface ? $cart : null;
    }

    private function extractCsrfToken(Request $request): string
    {
        return (string) $request->headers->get('X-CSRF-Token', $request->request->get('_csrf_token', ''));
    }
}
