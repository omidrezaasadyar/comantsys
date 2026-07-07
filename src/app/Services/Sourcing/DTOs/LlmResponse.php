<?php

namespace App\Services\Sourcing\DTOs;

final readonly class LlmResponse
{
    public function __construct(
        public string $content,
        public string $model,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
        public array $raw = [],
    ) {}

    /**
     * Parse content as JSON (for json_mode responses).
     * Throws JsonException on invalid JSON — fail loudly, not silently.
     */
    public function json(): array
    {
        return json_decode($this->content, true, 512, JSON_THROW_ON_ERROR);
    }
}
