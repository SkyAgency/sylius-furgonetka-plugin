<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Structure;

use Furgonetka\SyliusFurgonetkaPlugin\Enum\PointsType;

/**
 * One `mapConfiguration` entry: a courier service mapped to the shipping methods offering its
 * pickup points, plus the allowed point types. Mapped by shipping-method `code`.
 */
final class MapConfigurationStructure
{
    /**
     * @param string[] $shippingMethodCodes
     * @param string[] $pointsTypes
     */
    public function __construct(
        public readonly string $courierService,
        public readonly array $shippingMethodCodes,
        public readonly array $pointsTypes,
    ) {
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            courierService: (string) ($data['courierService'] ?? ''),
            shippingMethodCodes: self::toStringList($data['shippingMethodCodes'] ?? []),
            // Absent point types = no restriction → every known type.
            pointsTypes: isset($data['pointsTypes'])
                ? self::toStringList($data['pointsTypes'])
                : PointsType::values(),
        );
    }

    public function hasShippingMethodCode(string $code): bool
    {
        return in_array($code, $this->shippingMethodCodes, true);
    }

    /**
     * @return string[]
     */
    private static function toStringList(mixed $value): array
    {
        return array_values(array_map(
            static fn (mixed $item): string => (string) $item,
            (array) $value,
        ));
    }
}
