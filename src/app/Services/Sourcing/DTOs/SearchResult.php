<?php

namespace App\Services\Sourcing\DTOs;

final readonly class SearchResult
{
    public function __construct(
        public string $title,
        public string $url,
        public string $snippet,
        public array $raw = [],
    ) {}
}
