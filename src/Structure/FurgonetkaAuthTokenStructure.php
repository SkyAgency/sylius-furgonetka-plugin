<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Structure;

class FurgonetkaAuthTokenStructure
{
    public function __construct(
        public readonly string $tokenType,
        public readonly int $expiresIn,
        public readonly string $refreshToken,
        public readonly string $accessToken
    ) {
    }
}
