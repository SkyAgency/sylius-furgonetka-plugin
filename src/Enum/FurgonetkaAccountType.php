<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Enum;

enum FurgonetkaAccountType: string
{
    case Personal = 'personal';
    case Company = 'company';
}
