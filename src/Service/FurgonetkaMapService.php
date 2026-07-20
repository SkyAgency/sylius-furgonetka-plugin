<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Service;

use Furgonetka\SyliusFurgonetkaPlugin\Entity\FurgonetkaPickupPoint;
use Furgonetka\SyliusFurgonetkaPlugin\Repository\FurgonetkaPickupPointRepository;
use Furgonetka\SyliusFurgonetkaPlugin\Structure\MapConfigurationStructure;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;

/**
 * Storefront map configuration and pickup-point lookups.
 * Version-agnostic across Sylius 1.14 and 2.x.
 */
final class FurgonetkaMapService
{
    public const MAP_SDK_URL_PRODUCTION = 'https://furgonetka.pl/js/dist/map/map.js';
    public const MAP_SDK_URL_SANDBOX = 'https://sandbox.furgonetka.pl/js/dist/map/map.js';

    private bool $cartResolved = false;

    private ?FurgonetkaPickupPoint $cachedCartPoint = null;

    /** @var array<int, FurgonetkaPickupPoint|null> request-scoped cache for explicit-order lookups */
    private array $orderPointCache = [];

    /** @var MapConfigurationStructure[]|null */
    private ?array $cachedConfiguration = null;

    public function __construct(
        private readonly CartContextInterface $cartContext,
        private readonly FurgonetkaPickupPointRepository $pickupPointRepository,
        private readonly FurgonetkaSettingsService $settingsService,
    ) {
    }

    // Sandbox SDK lives on a separate domain, so the URL follows the integration environment.
    public function getSdkUrl(): string
    {
        return $this->isSandbox()
            ? self::MAP_SDK_URL_SANDBOX
            : self::MAP_SDK_URL_PRODUCTION;
    }

    public function getEnvironment(): string
    {
        return $this->isSandbox()
            ? FurgonetkaIntegrationService::INTEGRATION_ENVIRONMENT_SANDBOX
            : FurgonetkaIntegrationService::INTEGRATION_ENVIRONMENT_PRODUCTION;
    }

    public function getApiKey(): string
    {
        return $this->settingsService->getSettings()->mapApiKey ?? '';
    }

    // Map configuration entry for a method, or null when it offers no pickup points.
    public function getConfigurationForMethod(string $shippingMethodCode): ?MapConfigurationStructure
    {
        foreach ($this->getConfiguration() as $entry) {
            if ($entry->hasShippingMethodCode($shippingMethodCode)) {
                return $entry;
            }
        }

        return null;
    }

    public function isPickupPointMethod(string $shippingMethodCode): bool
    {
        return $this->getConfigurationForMethod($shippingMethodCode) !== null;
    }

    // Single gate shared by the validator and the storefront notice.
    public function orderRequiresPickupPoint(OrderInterface $order): bool
    {
        $shipment = $order->getShipments()->first();
        $method = $shipment === false ? null : $shipment->getMethod();

        return $method !== null && $this->isPickupPointMethod((string) $method->getCode());
    }

    // The point's courier service (SDK returns it in `point.type`) must match the method's config.
    public function validatePoint(string $shippingMethodCode, string $pointCourierService): bool
    {
        $config = $this->getConfigurationForMethod($shippingMethodCode);

        return $config !== null && $config->courierService === $pointCourierService;
    }

    /**
     * @return MapConfigurationStructure[]
     */
    private function getConfiguration(): array
    {
        return $this->cachedConfiguration ??= $this->decodeConfiguration();
    }

    /**
     * Decodes the `mapConfiguration` JSON setting; empty list when unset or malformed.
     *
     * @return MapConfigurationStructure[]
     */
    private function decodeConfiguration(): array
    {
        $json = $this->settingsService->getSettings()->mapConfiguration;

        if ($json === null || $json === '') {
            return [];
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        $entries = [];
        foreach ($decoded as $entry) {
            if (is_array($entry)) {
                $entries[] = MapConfigurationStructure::fromArray($entry);
            }
        }

        return $entries;
    }

    // Saved point for the given order, or for the current cart when none is passed (pass the order
    // explicitly on pages rendering a specific order — the cart context would be wrong there).
    public function getSavedPickupPoint(?OrderInterface $order = null): ?FurgonetkaPickupPoint
    {
        if ($order !== null) {
            $id = $order->getId();
            if ($id !== null && array_key_exists($id, $this->orderPointCache)) {
                return $this->orderPointCache[$id];
            }

            $point = $this->pickupPointRepository->findOneByOrder($order);
            if ($id !== null) {
                $this->orderPointCache[$id] = $point;
            }

            return $point;
        }

        if ($this->cartResolved) {
            return $this->cachedCartPoint;
        }

        $this->cartResolved = true;

        try {
            $cart = $this->cartContext->getCart();
        } catch (CartNotFoundException) {
            return $this->cachedCartPoint = null;
        }

        // getCart() returns the narrower Order interface; the repository needs the Core one.
        if (!$cart instanceof OrderInterface) {
            return $this->cachedCartPoint = null;
        }

        return $this->cachedCartPoint = $this->pickupPointRepository->findOneByOrder($cart);
    }

    // Drops the saved point when it no longer matches the chosen method (one point per order).
    public function clearStalePickupPoint(OrderInterface $order): void
    {
        $point = $this->pickupPointRepository->findOneByOrder($order);
        if ($point === null) {
            return;
        }

        $shipment = $order->getShipments()->first();
        $method = $shipment === false ? null : $shipment->getMethod();

        if ($method === null || $point->getShippingMethodCode() !== $method->getCode()) {
            $this->pickupPointRepository->remove($point);
        }
    }

    private function isSandbox(): bool
    {
        return $this->settingsService->getSettings()->environment
            === FurgonetkaIntegrationService::INTEGRATION_ENVIRONMENT_SANDBOX;
    }
}
