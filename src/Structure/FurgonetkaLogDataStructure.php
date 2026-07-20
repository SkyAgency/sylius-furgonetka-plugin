<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Structure;

class FurgonetkaLogDataStructure
{
    public function __construct(public readonly string $message, public readonly array $context = [])
    {
    }

    public function toJson(): string
    {
        return json_encode([
            'message' => $this->message,
            'context' => $this->context,
        ]);
    }
}
