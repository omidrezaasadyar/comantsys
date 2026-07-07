<?php

namespace App\Services\Sourcing\Providers;

use App\Services\Sourcing\Contracts\SearchProviderInterface;
use App\Services\Sourcing\DTOs\SearchResult;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TavilySearchProvider implements SearchProviderInterface
{
    public function search(string $query, array $options = []): array
    {
        $config = config('sourcing.search.tavily');

        if (blank($config['api_key'])) {
            throw new RuntimeException('TAVILY_API_KEY is not set in .env');
        }

        $response = Http::withToken($config['api_key'])
            ->timeout($config['timeout'])
            ->post($config['base_url'] . '/search', [
                'query'        => $query,
                'search_depth' => $options['search_depth'] ?? $config['search_depth'],
                'max_results'  => $options['max_results'] ?? $config['max_results'],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Tavily search failed [' . $response->status() . ']: ' . $response->body()
            );
        }

        return collect($response->json('results', []))
            ->map(fn (array $item) => new SearchResult(
                title:   $item['title'] ?? '',
                url:     $item['url'] ?? '',
                snippet: $item['content'] ?? '',
                raw:     $item,
            ))
            ->all();
    }

    public function name(): string
    {
        return 'tavily';
    }
}
