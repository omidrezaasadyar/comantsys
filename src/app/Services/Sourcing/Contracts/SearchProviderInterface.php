<?php

namespace App\Services\Sourcing\Contracts;

use App\Services\Sourcing\DTOs\SearchResult;

interface SearchProviderInterface
{
    /**
     * Run a web search and return normalized results.
     *
     * @param string $query
     * @param array  $options   'search_depth' (string: basic|advanced),
     *                          'max_results' (int)
     *
     * @return SearchResult[]
     */
    public function search(string $query, array $options = []): array;

    public function name(): string;
}
