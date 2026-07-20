<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Structure;

use Furgonetka\SyliusFurgonetkaPlugin\Enum\FurgonetkaAccountType;

class FurgonetkaSettingsStructure
{
    public function __construct(
        public ?string $integrationUuid = null,
        public ?string $environment = null,
        public ?string $accessToken = null,
        public ?string $clientId = null,
        public ?string $clientSecret = null,
        public ?string $refreshToken = null,
        public ?string $accessTokenExpires = null,
        public ?string $accountType = null,
        public ?string $mapApiKey = null,
        public ?string $mapConfiguration = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $self = new self();

        foreach ($data as $key => $value) {
            if (property_exists($self, $key)) {
                $self->{$key} = $value;
            }
        }

        return $self;
    }

    public function isPluginIntegrated(): bool
    {
        return null !== $this->integrationUuid;
    }

    public function isCompanyAccount(): bool
    {
        return FurgonetkaAccountType::tryFrom($this->accountType ?? '') === FurgonetkaAccountType::Company;
    }
}
