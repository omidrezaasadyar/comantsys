<?php

namespace App\Services\Sourcing\Providers;

use App\Services\Sourcing\Contracts\ExtractsPagesInterface;
use App\Services\Sourcing\Contracts\SearchProviderInterface;
use App\Services\Sourcing\DTOs\SearchResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TavilySearchProvider implements SearchProviderInterface, ExtractsPagesInterface
{
    public function search(string $query, array $options = []): array
    {
        $config = config('sourcing.search.tavily');

        if (blank($config['api_key'])) {
            throw new RuntimeException('TAVILY_API_KEY is not set in .env');
        }

        $payload = [
            'query'        => $query,
            'search_depth' => $options['search_depth'] ?? $config['search_depth'],
            'max_results'  => $options['max_results'] ?? $config['max_results'],
        ];

        // Tavily-native domain restriction — only sent when the caller asks for it.
        if (! empty($options['include_domains'])) {
            $payload['include_domains'] = array_values($options['include_domains']);
        }

        $response = Http::withToken($config['api_key'])
            ->timeout($config['timeout'])
            ->post($config['base_url'] . '/search', $payload);

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

    /**
     * Batch-extract full page content for a set of URLs via Tavily /extract
     * (basic depth: 5 URLs = 1 credit). Per-URL failures are tolerated — a URL
     * Tavily reports as failed (or simply omits) maps to null and is logged;
     * the run continues on snippets for that page.
     *
     * @param  array<int, string>  $urls
     * @return array<string, string|null>
     */
    public function extract(array $urls): array
    {
        $urls = array_values(array_unique(array_filter($urls, static fn ($u) => filled($u))));

        if ($urls === []) {
            return [];
        }

        $config = config('sourcing.search.tavily');

        if (blank($config['api_key'])) {
            throw new RuntimeException('TAVILY_API_KEY is not set in .env');
        }

        // Seed every requested URL as null; successful extractions overwrite it.
        $out = array_fill_keys($urls, null);

        $response = Http::withToken($config['api_key'])
            ->timeout($config['timeout'])
            ->post($config['base_url'] . '/extract', [
                'urls'          => $urls,
                'extract_depth' => 'basic',
            ]);

        if ($response->failed()) {
            // Hard failure (auth/transport) — surface it; caller decides whether
            // to fall back to snippet-only. We do NOT fabricate content.
            throw new RuntimeException(
                'Tavily extract failed [' . $response->status() . ']: ' . $response->body()
            );
        }

        foreach ($response->json('results', []) as $item) {
            $url = $item['url'] ?? null;
            $content = $item['raw_content'] ?? $item['content'] ?? null;

            if ($url !== null && filled($content)) {
                $out[$url] = $content;
            }
        }

        // Tavily returns per-URL failures in `failed_results`; log and leave null.
        foreach ($response->json('failed_results', []) as $failed) {
            Log::warning('Tavily extract could not fetch a page; continuing snippet-only.', [
                'url'   => $failed['url'] ?? null,
                'error' => $failed['error'] ?? null,
            ]);
        }

        return $out;
    }

    public function name(): string
    {
        return 'tavily';
    }
}
