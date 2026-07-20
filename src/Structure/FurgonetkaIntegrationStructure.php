<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Structure;

class FurgonetkaIntegrationStructure
{
    public function __construct(
        public readonly string $integrationUuid,
        public readonly ?string $mapApiKey = null,
    ) {
    }
}
