<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Structure;

class FurgonetkaSettingStructure
{
    public function __construct(
        public readonly string $name,
        public readonly string $value,
    ) {
    }
}
