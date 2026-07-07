<?php

namespace App\Services\Sourcing;

use App\Models\Inquiry;
use App\Models\SourcingResult;
use App\Services\Sourcing\Contracts\LlmProviderInterface;
use App\Services\Sourcing\Contracts\SearchProviderInterface;
use App\Services\Sourcing\DTOs\SearchResult;
use Throwable;

/**
 * Orchestrates one supplier-sourcing run for an inquiry:
 * LLM query-building → web search → LLM analysis → persisted SourcingResult.
 *
 * The whole run is wrapped in try/catch: the row never ends up stuck in
 * 'running', paid search data is persisted before the (fallible) analysis
 * call, and any throwable is rethrown so queue retry semantics stay intact.
 */
class SourcingAgentService
{
    public function __construct(
        private readonly LlmProviderInterface $llm,
        private readonly SearchProviderInterface $search,
    ) {}

    public function run(Inquiry $inquiry, SourcingResult $result, array $options = []): SourcingResult
    {
        $language = $options['language'] ?? config('sourcing.agent.output_language', 'fa');

        try {
            // 1. Mark the run as started and stamp provider metadata.
            $providerKey = config('sourcing.llm.provider');

            $result->fill([
                'status'          => 'running',
                'started_at'      => now(),
                'llm_provider'    => $this->llm->name(),
                'search_provider' => $this->search->name(),
                'llm_model'       => config("sourcing.llm.{$providerKey}.model"),
            ])->save();

            $items = $this->itemsText($inquiry);

            // 2. LLM call #1 — collapse the (Persian/mixed) items into one
            //    effective English supplier-search query.
            $queryResponse = $this->llm->chat($this->queryPrompt($items), [
                'system'      => 'You are a procurement research assistant. Reply with the search query text only — no quotes, no explanation.',
                'temperature' => 0.2,
                'max_tokens'  => 100,
            ]);

            $query = trim($queryResponse->content);
            $result->query = $query;
            $result->save();

            // 3. Web search.
            $searchOptions = array_filter([
                'search_depth' => $options['search_depth'] ?? null,
                'max_results'  => $options['max_results'] ?? null,
            ], static fn ($v) => $v !== null);

            /** @var SearchResult[] $searchResults */
            $searchResults = $this->search->search($query, $searchOptions);

            // 4. Persist the raw search payload IMMEDIATELY — the paid search
            //    data must survive even if the analysis call below fails.
            $result->raw_search = array_map(static fn (SearchResult $r): array => $r->raw, $searchResults);
            $result->save();

            // 5. LLM call #2 — analyze the results into strict JSON.
            $analysisResponse = $this->llm->chat($this->analysisPrompt($items, $searchResults, $language), [
                'system'    => 'You are a procurement analyst. Respond with strict JSON only.',
                'json_mode' => true,
            ]);

            $parsed = $analysisResponse->json(); // throws on invalid JSON — fail loudly

            // 6. Success: store results and the summed token usage across both calls.
            $result->fill([
                'status'        => 'completed',
                'results'       => $parsed,
                'input_tokens'  => $queryResponse->inputTokens + $analysisResponse->inputTokens,
                'output_tokens' => $queryResponse->outputTokens + $analysisResponse->outputTokens,
                'finished_at'   => now(),
            ])->save();

            return $result;
        } catch (Throwable $e) {
            // 7. Any failure: record it, stamp finish time, save, then rethrow
            //    so the queue can apply its retry policy. Never leave 'running'.
            $result->fill([
                'status'      => 'failed',
                'error'       => $e->getMessage(),
                'finished_at' => now(),
            ])->save();

            throw $e;
        }
    }

    /**
     * Render the inquiry's line items as a readable bullet list.
     * Uses the `description` column plus quantity and the `unit_label`
     * accessor (free text when the unit is «سایر»).
     */
    private function itemsText(Inquiry $inquiry): string
    {
        $lines = $inquiry->items
            ->map(function ($item): ?string {
                $description = trim((string) $item->description);

                if ($description === '') {
                    return null;
                }

                $parts = [$description];

                if (filled($item->quantity)) {
                    $quantity = rtrim(rtrim((string) $item->quantity, '0'), '.');
                    $unit     = trim((string) $item->unit_label);
                    $parts[]  = 'qty: ' . $quantity . ($unit !== '' ? ' ' . $unit : '');
                }

                return '- ' . implode(' | ', $parts);
            })
            ->filter()
            ->implode("\n");

        return $lines !== '' ? $lines : '- (no items listed)';
    }

    private function queryPrompt(string $items): string
    {
        return implode("\n", [
            'The following are line items from a procurement inquiry. They may be written in Persian, English, or a mix.',
            '',
            'Items:',
            $items,
            '',
            'Task: Produce ONE concise, effective ENGLISH web-search query to find suppliers, manufacturers, or distributors that sell these parts/products. Emphasize product names and specifications, and include words like "supplier" or "manufacturer" where helpful.',
            '',
            'Output ONLY the search query text on a single line — no quotes, no explanation.',
        ]);
    }

    /**
     * @param SearchResult[] $searchResults
     */
    private function analysisPrompt(string $items, array $searchResults, string $language): string
    {
        $languageName = $language === 'en' ? 'English' : 'Persian (Farsi)';

        $resultsText = '';
        foreach ($searchResults as $i => $r) {
            $n = $i + 1;
            $resultsText .= "{$n}. {$r->title}\n   URL: {$r->url}\n   {$r->snippet}\n\n";
        }
        $resultsText = trim($resultsText);

        if ($resultsText === '') {
            $resultsText = '(no search results returned)';
        }

        return implode("\n", [
            'You are analyzing web search results to identify suppliers for a procurement inquiry.',
            '',
            'Inquiry items:',
            $items,
            '',
            'Search results:',
            $resultsText,
            '',
            'From the search results, identify the most relevant suppliers for the inquiry items. Respond with STRICT JSON in exactly this shape:',
            '',
            '{',
            '  "suppliers": [',
            '    {"name": "...", "url": "...", "relevance": "...", "price_hint": "... or null"}',
            '  ],',
            '  "summary": "..."',
            '}',
            '',
            'Rules:',
            '- "name" and "url" must be copied exactly as found in the search results (do NOT translate them).',
            "- \"relevance\", \"price_hint\", and \"summary\" MUST be written in {$languageName}.",
            '- Use null (not a string) for "price_hint" when no price information is available.',
            '- Return valid JSON only: no markdown code fences, no commentary.',
        ]);
    }
}
