<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\ApiPlatform\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use Furgonetka\SyliusFurgonetkaPlugin\ApiPlatform\Resource\FurgonetkaSettingResource;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaSettingsService;

/**
 * Sylius 1.14 (API Platform 2.7) adapter for the settings write — legacy mode persists through
 * DataPersisters, not State processors. Thin shell over FurgonetkaSettingsService::save() (shared
 * with the v2 Processor). The legacy ApiPlatform\Core interface exists only in 2.7, so this is
 * registered ONLY on Sylius < 2 (services_v1.yaml); loading it on 2.x would fatal.
 */
final class FurgonetkaSettingDataPersister implements ContextAwareDataPersisterInterface
{
    public function __construct(
        private readonly FurgonetkaSettingsService $settingsService,
    ) {
    }

    public function supports(mixed $data, array $context = []): bool
    {
        return $data instanceof FurgonetkaSettingResource;
    }

    /**
     * @param FurgonetkaSettingResource $data
     */
    public function persist(mixed $data, array $context = []): FurgonetkaSettingResource
    {
        $this->settingsService->save($data->name, (string) $data->value);

        return $data;
    }

    public function remove(mixed $data, array $context = []): void
    {
        // Required by the interface; no DELETE operation is exposed.
    }
}
