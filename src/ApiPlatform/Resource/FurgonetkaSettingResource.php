<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\ApiPlatform\Resource;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * API resource for a single plugin setting exchanged with Furgonetka ({name, value}, keyed on name).
 * A plain DTO, not the entity. Writes go through the Processor/DataPersister; reads through the
 * Provider/DataProvider, which hides sensitive settings. Wired in YAML per version so the metadata
 * format can differ between API Platform 2.7 and 4.x.
 */
final class FurgonetkaSettingResource
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name = '',
        // Validated against the LONGTEXT column limit (the map JSON exceeds the old 1024 cap).
        #[Assert\Length(max: 4294967295)]
        public ?string $value = null,
    ) {
    }
}
