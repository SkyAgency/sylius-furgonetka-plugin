<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\ApiPlatform\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ArrayObject;
use Furgonetka\SyliusFurgonetkaPlugin\ApiPlatform\Resource\FurgonetkaSettingResource;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaSettingsService;
use Furgonetka\SyliusFurgonetkaPlugin\Structure\FurgonetkaSettingStructure;

/**
 * Sylius 1.14 (API Platform 2.7) read adapter — legacy mode resolves GET through DataProviders,
 * not State providers. The hidden-settings blacklist lives in the service (shared with the v2
 * FurgonetkaSettingProvider). Registered ONLY on Sylius < 2; the ApiPlatform\Core interfaces it
 * implements do not exist on 2.x.
 */
final class FurgonetkaSettingDataProvider implements
    CollectionDataProviderInterface,
    ItemDataProviderInterface,
    RestrictedDataProviderInterface
{
    public function __construct(
        private readonly FurgonetkaSettingsService $settingsService,
    ) {
    }

    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        return $resourceClass === FurgonetkaSettingResource::class;
    }

    /**
     * @return FurgonetkaSettingResource[]
     */
    public function getCollection(string $resourceClass, ?string $operationName = null): array
    {
        return array_map(self::toResource(...), $this->settingsService->getPublicSettings());
    }

    public function getItem(
        string $resourceClass,
        mixed $id,
        ?string $operationName = null,
        array $context = [],
    ): ?object {
        $setting = $this->settingsService->getPublicSettingByName((string) $id);

        // ArrayObject, not []: Sylius' ChannelAwareItemDataProvider decorator types getItem() as
        // ?object and TypeErrors on an array. Empty ArrayObject serializes like the v2 provider's [].
        return $setting === null ? new ArrayObject() : self::toResource($setting);
    }

    private static function toResource(FurgonetkaSettingStructure $setting): FurgonetkaSettingResource
    {
        return new FurgonetkaSettingResource($setting->name, $setting->value);
    }
}
