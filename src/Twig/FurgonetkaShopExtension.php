<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Twig;

use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaMapService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Thin Twig layer forwarding to FurgonetkaMapService. Version-agnostic (1.14 + 2.x).
 */
final class FurgonetkaShopExtension extends AbstractExtension
{
    public function __construct(private readonly FurgonetkaMapService $mapService)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('furgonetka_saved_pickup_point', [$this->mapService, 'getSavedPickupPoint']),
            new TwigFunction('furgonetka_map_sdk_url', [$this->mapService, 'getSdkUrl']),
            new TwigFunction('furgonetka_map_environment', [$this->mapService, 'getEnvironment']),
            new TwigFunction('furgonetka_map_api_key', [$this->mapService, 'getApiKey']),
            // Null for methods without pickup points; the template renders the selector only when set.
            new TwigFunction('furgonetka_map_configuration_for_method', [$this->mapService, 'getConfigurationForMethod']),
            new TwigFunction('furgonetka_order_requires_pickup_point', [$this->mapService, 'orderRequiresPickupPoint']),
        ];
    }
}
