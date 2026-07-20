<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Structure;

class FurgonetkaOAuthAuthenticationStructure
{
    public function __construct(
        public readonly string $clientId,
        public readonly string $clientSecret,
    ) {
    }
}
