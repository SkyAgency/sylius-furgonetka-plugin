<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Enum;

enum FurgonetkaLogsLevel: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';
}
