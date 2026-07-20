<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Enum;

/**
 * Pickup-point types the map widget can be restricted to (Furgonetka.Map `availableServiceTypes`).
 */
enum PointsType: string
{
    case ParcelMachine = 'parcel_machine';
    case ServicePoint = 'service_point';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
