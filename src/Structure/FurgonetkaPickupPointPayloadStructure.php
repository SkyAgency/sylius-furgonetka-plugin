<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Structure;

use Symfony\Component\HttpFoundation\Request;

/**
 * Pickup point chosen on the checkout select-shipping step, as posted over AJAX
 * by js/furgonetka-shop.js. Immutable; built straight from the request body.
 */
class FurgonetkaPickupPointPayloadStructure
{
    public function __construct(
        public readonly string $shippingMethodCode,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $request->getContent(), true) ?: [];

        return new self(
            shippingMethodCode: trim((string) ($data['shippingMethodCode'] ?? '')),
            code: trim((string) ($data['code'] ?? '')),
            name: trim((string) ($data['name'] ?? '')),
            type: trim((string) ($data['type'] ?? '')),
        );
    }

    /**
     * Type is optional; a point cannot be persisted without a shipping method, code and name.
     */
    public function isComplete(): bool
    {
        return $this->shippingMethodCode !== '' && $this->code !== '' && $this->name !== '';
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'shippingMethodCode' => $this->shippingMethodCode,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
        ];
    }
}
