<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Furgonetka\SyliusFurgonetkaPlugin\ApiPlatform\Resource\FurgonetkaSettingResource;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaSettingsService;

/**
 * Sylius 2.x (API Platform 4.x) adapter for the settings write — thin shell over
 * FurgonetkaSettingsService::save(). On 1.14, FurgonetkaSettingDataPersister handles writes instead.
 */
final class FurgonetkaSettingProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly FurgonetkaSettingsService $settingsService,
    ) {
    }

    /**
     * @param FurgonetkaSettingResource $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): FurgonetkaSettingResource
    {
        $this->settingsService->save($data->name, (string) $data->value);

        return $data;
    }
}
