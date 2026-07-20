<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Furgonetka\SyliusFurgonetkaPlugin\ApiPlatform\Resource\FurgonetkaSettingResource;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaSettingsService;
use Furgonetka\SyliusFurgonetkaPlugin\Structure\FurgonetkaSettingStructure;

/**
 * Sylius 2.x (API Platform 4.x) read adapter. The hidden-settings blacklist lives in the service
 * (shared with the v1 FurgonetkaSettingDataProvider) so both versions expose the same data.
 */
final class FurgonetkaSettingProvider implements ProviderInterface
{
    public function __construct(
        private readonly FurgonetkaSettingsService $settingsService,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable|object
    {
        if (isset($uriVariables['name'])) {
            $setting = $this->settingsService->getPublicSettingByName((string) $uriVariables['name']);

            // Unknown/hidden setting → empty array (HTTP 200 []), never 404.
            return $setting === null ? [] : self::toResource($setting);
        }

        return array_map(self::toResource(...), $this->settingsService->getPublicSettings());
    }

    private static function toResource(FurgonetkaSettingStructure $setting): FurgonetkaSettingResource
    {
        return new FurgonetkaSettingResource($setting->name, $setting->value);
    }
}
