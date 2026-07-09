<?php

namespace App\Services\Sourcing\Contracts;

/**
 * A search/content provider that can also fetch the full text of pages.
 *
 * Kept separate from SearchProviderInterface so search stays a clean,
 * minimal contract; a provider (e.g. Tavily) may implement both.
 */
interface ExtractsPagesInterface
{
    /**
     * Fetch page content for a batch of URLs. Per-URL failures are tolerated:
     * a URL that cannot be extracted maps to null (never throws for one bad
     * page). The whole call may still throw on a hard transport/auth error.
     *
     * @param  array<int, string>  $urls
     * @return array<string, string|null>  url => extracted text, or null
     */
    public function extract(array $urls): array;
}
