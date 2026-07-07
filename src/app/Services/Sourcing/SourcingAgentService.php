<?php

namespace App\Services\Sourcing;

use App\Models\SourcingRequest;
use App\Models\SourcingRun;
use App\Services\Sourcing\Contracts\LlmProviderInterface;
use App\Services\Sourcing\Contracts\SearchProviderInterface;
use App\Services\Sourcing\DTOs\SearchResult;
use RuntimeException;
use Throwable;

/**
 * Orchestrates one supplier-sourcing run for a standalone sourcing request:
 * LLM query-building → web search → LLM analysis → persisted SourcingRun.
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

    public function run(SourcingRequest $request, SourcingRun $run, array $options = []): SourcingRun
    {
        $language = $options['language'] ?? config('sourcing.agent.output_language', 'fa');

        try {
            // 1. Mark the run as started and stamp provider metadata.
            $providerKey = config('sourcing.llm.provider');

            $run->fill([
                'status'          => 'running',
                'started_at'      => now(),
                'llm_provider'    => $this->llm->name(),
                'search_provider' => $this->search->name(),
                'llm_model'       => config("sourcing.llm.{$providerKey}.model"),
            ])->save();

            $partText = $this->partText($request);

            // 2. LLM call #1 — collapse the (Persian/mixed) part details into
            //    one effective English supplier-search query.
            $queryResponse = $this->llm->chat($this->queryPrompt($partText), [
                'system'      => 'You are a procurement research assistant. Reply with the search query text only — no quotes, no explanation.',
                'temperature' => 0.2,
                // Gemini 2.5 thinking tokens share the output budget, so a low cap
                // truncates the visible query mid-string; the query itself stays ~1 line.
                'max_tokens'  => 500,
            ]);

            $query = trim($queryResponse->content);
            $run->query = $query;
            $run->save();

            // Fail fast on a garbage query (e.g. truncated to "S") before spending
            // a paid Tavily credit; the catch block below records the run as failed.
            if (mb_strlen($query) < 5) {
                throw new RuntimeException("Generated search query is too short to be usable: \"{$query}\"");
            }

            // 3. Web search.
            $searchOptions = array_filter([
                'search_depth' => $options['search_depth'] ?? null,
                'max_results'  => $options['max_results'] ?? null,
            ], static fn ($v) => $v !== null);

            /** @var SearchResult[] $searchResults */
            $searchResults = $this->search->search($query, $searchOptions);

            // 4. Persist the raw search payload IMMEDIATELY — the paid search
            //    data must survive even if the analysis call below fails.
            $run->raw_search = array_map(static fn (SearchResult $r): array => $r->raw, $searchResults);
            $run->save();

            // 5. LLM call #2 — analyze the results into strict JSON.
            $analysisResponse = $this->llm->chat($this->analysisPrompt($partText, $searchResults, $language), [
                'system'    => 'You are a procurement analyst. Respond with strict JSON only.',
                'json_mode' => true,
            ]);

            $parsed = $analysisResponse->json(); // throws on invalid JSON — fail loudly

            // 6. Success: store results and the summed token usage across both calls.
            $run->fill([
                'status'        => 'completed',
                'results'       => $parsed,
                'input_tokens'  => $queryResponse->inputTokens + $analysisResponse->inputTokens,
                'output_tokens' => $queryResponse->outputTokens + $analysisResponse->outputTokens,
                'finished_at'   => now(),
            ])->save();

            return $run;
        } catch (Throwable $e) {
            // 7. Any failure: record it, stamp finish time, save, then rethrow
            //    so the queue can apply its retry policy. Never leave 'running'.
            $run->fill([
                'status'      => 'failed',
                'error'       => $e->getMessage(),
                'finished_at' => now(),
            ])->save();

            throw $e;
        }
    }

    /**
     * Compose the part-to-source description from the request fields
     * (part_name + part_number + description) plus any extra context.
     * Any field may be Persian, English, or mixed.
     */
    private function partText(SourcingRequest $request): string
    {
        $lines = ['Part name: ' . trim((string) $request->part_name)];

        if (filled($request->part_number)) {
            $lines[] = 'Part number: ' . trim((string) $request->part_number);
        }

        if (filled($request->description)) {
            $lines[] = 'Description: ' . trim((string) $request->description);
        }

        $extra = trim($this->extraContext($request));

        if ($extra !== '') {
            $lines[] = 'Additional context: ' . $extra;
        }

        return implode("\n", $lines);
    }

    /**
     * Extension point for extra query/analysis context — e.g. OCR text
     * extracted from the request's attachments. Wired in a later step;
     * returns an empty string for now.
     */
    protected function extraContext(SourcingRequest $request): string
    {
        return '';
    }

    private function queryPrompt(string $partText): string
    {
        return implode("\n", [
            'The following describes a single part/product to source. The details may be written in Persian, English, or a mix.',
            '',
            'Part details:',
            $partText,
            '',
            'Task: Produce ONE concise, effective ENGLISH web-search query to find suppliers, manufacturers, or distributors that sell this part/product. Emphasize the product name, part number, and specifications, and include words like "supplier" or "manufacturer" where helpful.',
            '',
            'HARD REQUIREMENT: if the part details above include a part number, that exact part number MUST appear verbatim (unchanged, same characters) in the query.',
            '',
            'Output ONLY the search query text on a single line — no quotes, no explanation.',
        ]);
    }

    /**
     * @param SearchResult[] $searchResults
     */
    private function analysisPrompt(string $partText, array $searchResults, string $language): string
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
            'You are analyzing web search results to identify suppliers for a part-sourcing request.',
            '',
            'Part to source:',
            $partText,
            '',
            'Search results:',
            $resultsText,
            '',
            'From the search results, identify the most relevant suppliers for this part. Respond with STRICT JSON in exactly this shape:',
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
