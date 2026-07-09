<?php

namespace App\Services\Sourcing;

use App\Models\SourcingRequest;
use App\Models\SourcingRequestAttachment;
use App\Models\SourcingRun;
use App\Services\Sourcing\Contracts\ExtractsPagesInterface;
use App\Services\Sourcing\Contracts\LlmProviderInterface;
use App\Services\Sourcing\Contracts\OcrProviderInterface;
use App\Services\Sourcing\Contracts\SearchProviderInterface;
use App\Services\Sourcing\DTOs\SearchResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
    /** Max combined OCR text injected into the prompt (chars) — keeps it bounded/cheap. */
    private const OCR_TEXT_CAP = 1500;

    /** Per-attachment OCR text (filename => text) from the most recent extraContext() call. */
    private array $ocrContext = [];

    public function __construct(
        private readonly LlmProviderInterface $llm,
        private readonly SearchProviderInterface $search,
        private readonly OcrProviderInterface $ocr,
        private readonly ExtractsPagesInterface $extractor,
    ) {}

    public function run(SourcingRequest $request, SourcingRun $run, array $options = []): SourcingRun
    {
        $language = $options['language'] ?? config('sourcing.agent.output_language', 'fa');
        $instructions = trim((string) $request->search_instructions);

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

            // Record exactly what OCR fed the query builder (evaluation data).
            // partText() → extraContext() populated $this->ocrContext above.
            $run->ocr_context = $this->ocrContext !== [] ? $this->ocrContext : null;

            // 2. LLM call #1 — build a SEARCH PLAN (json): always a general query,
            //    plus targeted/domain-restricted queries when the free-text
            //    instructions call for them, capped at max_search_queries.
            $maxQueries = (int) config('sourcing.agent.max_search_queries', 4);

            // Cost rationale: the plan is a cheap, mechanical transform — run it on
            // the cheap planner model; the strong default model is saved for analysis.
            $plannerModel = config("sourcing.llm.{$providerKey}.planner_model");

            $planResponse = $this->llm->chat(
                $this->searchPlanPrompt($partText, $instructions, $maxQueries),
                array_filter([
                    'system'      => 'You are a procurement search strategist. Respond with strict JSON only.',
                    'temperature' => 0.2,
                    'json_mode'   => true,
                    // Output JSON is small, but thinking-token models share the output
                    // budget — budget generously so it is never truncated.
                    'max_tokens'  => 800,
                    'model'       => $plannerModel,  // null for providers without a planner role → default
                ], static fn ($v) => $v !== null)
            );

            // Normalize + guard + cap; throws if nothing usable (failure path handles it).
            $plan = $this->buildSearchPlan($planResponse->json(), $maxQueries);
            $run->query = implode(' | ', array_column($plan, 'q'));
            $run->save();

            // 3. Execute each planned query. COST: 1 Tavily credit per query, so
            //    credits/run = count($plan) (≤ max_search_queries). Merge results,
            //    de-duplicate by URL for analysis, keep full provenance for raw_search.
            $baseOptions = array_filter([
                'search_depth' => $options['search_depth'] ?? null,
                'max_results'  => $options['max_results'] ?? null,
            ], static fn ($v) => $v !== null);

            $seenUrls = [];
            /** @var SearchResult[] $mergedResults */
            $mergedResults = [];
            $rawResults = [];

            foreach ($plan as $entry) {
                $entryOptions = $entry['include_domains'] !== []
                    ? $baseOptions + ['include_domains' => $entry['include_domains']]
                    : $baseOptions;

                foreach ($this->search->search($entry['q'], $entryOptions) as $result) {
                    // Provenance: which planned query (+ domain filter) produced this hit.
                    $rawResults[] = $result->raw + [
                        '_query'           => $entry['q'],
                        '_include_domains' => $entry['include_domains'],
                    ];

                    if ($result->url !== '' && isset($seenUrls[$result->url])) {
                        continue;
                    }
                    if ($result->url !== '') {
                        $seenUrls[$result->url] = true;
                    }
                    $mergedResults[] = $result;
                }
            }

            // 4. Persist the plan + raw results IMMEDIATELY — the paid search data
            //    must survive even if the extraction/analysis calls below fail.
            //    'plan_model' records which model served the (cheap) planning call
            //    — evaluation proof of the per-role model split.
            $run->raw_search = [
                'search_plan' => $plan,
                'plan_model'  => $planResponse->model,
                'results'     => $rawResults,
            ];
            $run->save();

            // 4b. EXTRACTION STAGE — pull full page content for the top-N merged
            //     results so analysis reads real contact/price data, not snippets.
            //     Tolerant: any failure leaves us snippet-only; NEVER fails the run.
            $extractedText = $this->extractTopPages($mergedResults);

            // Persist only the char counts per URL (not the full text) — keeps the
            // row size sane while proving what fed analysis.
            $run->raw_search = $run->raw_search + [
                'extracted' => array_map('mb_strlen', $extractedText),
            ];
            $run->save();

            // 5. LLM call #2 — analyze snippets (all results) + extracted text
            //    (top-N) into strict, actionable JSON, applying the user's
            //    free-text instructions to selection/ordering/wording.
            $analysisResponse = $this->llm->chat(
                $this->analysisPrompt($partText, $mergedResults, $extractedText, $language, $instructions),
                [
                    'system'     => 'You are a procurement analyst. Respond with strict JSON only.',
                    'json_mode'  => true,
                    // Larger input (full pages) + the thinking-token lesson generalizes:
                    // budget generously so structured output is never truncated.
                    'max_tokens' => 2500,
                ]
            );

            $parsed = $analysisResponse->json(); // throws on invalid JSON — fail loudly

            // Record the analysis model too (evaluation proof of the per-role split).
            $run->raw_search = $run->raw_search + ['analysis_model' => $analysisResponse->model];

            // 6. Success: store results and the summed token usage across both calls.
            $run->fill([
                'status'        => 'completed',
                'results'       => $parsed,
                'input_tokens'  => $planResponse->inputTokens + $analysisResponse->inputTokens,
                'output_tokens' => $planResponse->outputTokens + $analysisResponse->outputTokens,
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
     * Extra query context: OCR text extracted from the request's image
     * attachments, capped and labeled. Also records the full per-attachment
     * map in $this->ocrContext so run() can persist what the builder saw.
     * Returns '' when there are no images or no text — the prompt is then
     * exactly as it was before OCR.
     */
    protected function extraContext(SourcingRequest $request): string
    {
        $this->ocrContext = $this->ocrImageAttachments($request);

        $combined = trim(implode("\n", array_values($this->ocrContext)));

        if ($combined === '') {
            return '';
        }

        // Cap the combined text — OCR output can be long and noisy.
        $combined = mb_substr($combined, 0, self::OCR_TEXT_CAP);

        return "Text extracted from attached part images (may contain noise):\n" . $combined;
    }

    /**
     * OCR every IMAGE attachment of the request. PDFs are skipped for now —
     * pdf→image rasterization is a separate concern (a later step). Each OCR
     * call is isolated: a corrupt/unreadable image is logged and skipped, and
     * never fails the run.
     *
     * @return array<string, string>  filename => extracted text (non-empty only)
     */
    private function ocrImageAttachments(SourcingRequest $request): array
    {
        $extracted = [];

        foreach ($request->attachments as $attachment) {
            if (! $this->isImage($attachment)) {
                continue;
            }

            $absolutePath = Storage::disk('local')->path($attachment->file_path);

            try {
                $text = trim($this->ocr->extractText($absolutePath));
            } catch (Throwable $e) {
                Log::warning('Sourcing OCR failed for attachment; skipping.', [
                    'attachment_id' => $attachment->id,
                    'file'          => $attachment->file_path,
                    'error'         => $e->getMessage(),
                ]);

                continue;
            }

            if ($text !== '') {
                $extracted[basename((string) $attachment->file_path)] = $text;
            }
        }

        return $extracted;
    }

    /**
     * Image if the stored mime says so, else fall back to the file extension.
     */
    private function isImage(SourcingRequestAttachment $attachment): bool
    {
        if (str_starts_with((string) $attachment->file_type, 'image/')) {
            return true;
        }

        $extension = strtolower(pathinfo((string) $attachment->file_path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'tif', 'tiff'], true);
    }

    private function searchPlanPrompt(string $partText, string $instructions, int $maxQueries): string
    {
        return implode("\n", [
            'You are planning web searches to find SUPPLIERS for a single part/product.',
            'The details may be written in Persian, English, or a mix.',
            '',
            'Part details:',
            $partText,
            '',
            'User search instructions (free text — follow them; may name sites, price preferences, target markets, etc.):',
            $instructions !== '' ? $instructions : '(none provided)',
            '',
            'Produce a search plan as STRICT JSON in exactly this shape:',
            '{',
            '  "queries": [',
            '    { "q": "<english search query>", "include_domains": ["example.com"] }',
            '  ]',
            '}',
            '',
            'Rules:',
            '- The FIRST query is ALWAYS one general supplier query with "include_domains": [] (no restriction).',
            '- Add ADDITIONAL queries ONLY when the user instructions call for them — e.g. a named website becomes a query with that domain in "include_domains"; a target market becomes a market-focused query. If the instructions ask for nothing extra, return ONLY the general query.',
            "- HARD CAP: at most {$maxQueries} queries total. Never exceed {$maxQueries}.",
            '- Every "q" is in ENGLISH. If the part details include a part number, that exact part number MUST appear verbatim (unchanged) in EVERY "q".',
            '- "include_domains" is an array of bare domains (e.g. "alibaba.com"); use [] when unrestricted.',
            '- Return valid JSON only: no markdown code fences, no commentary.',
        ]);
    }

    /**
     * Normalize + validate the LLM search plan: drop entries whose query is
     * too short (per-query guard), clamp to the cap, and throw if nothing
     * usable remains (the run's failure path records the message).
     *
     * @param  array<string, mixed>  $planJson
     * @return array<int, array{q: string, include_domains: array<int, string>}>
     */
    private function buildSearchPlan(array $planJson, int $maxQueries): array
    {
        $queries = is_array($planJson['queries'] ?? null) ? $planJson['queries'] : [];
        $plan = [];

        foreach ($queries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $q = trim((string) ($entry['q'] ?? ''));

            if (mb_strlen($q) < 5) {
                continue; // per-query short-query guard
            }

            $domains = $entry['include_domains'] ?? [];
            $domains = is_array($domains)
                ? array_values(array_filter(
                    array_map(static fn ($d): string => trim((string) $d), $domains),
                    static fn (string $d): bool => $d !== '',
                ))
                : [];

            $plan[] = ['q' => $q, 'include_domains' => $domains];
        }

        $plan = array_slice($plan, 0, max(1, $maxQueries)); // enforce the cap in code too

        if ($plan === []) {
            throw new RuntimeException('Search planning produced no usable queries (all dropped by the short-query guard).');
        }

        return $plan;
    }

    /**
     * Extract full page content for the top-N merged results and cap each page.
     * Returns url => capped-text for pages that extracted successfully.
     *
     * Failure-tolerant by contract: a wholesale extraction failure is logged
     * and yields an empty map — analysis then proceeds snippet-only. This
     * NEVER throws out of the run.
     *
     * @param  SearchResult[]  $mergedResults
     * @return array<string, string>  url => extracted text (capped, non-empty only)
     */
    private function extractTopPages(array $mergedResults): array
    {
        $topN     = (int) config('sourcing.agent.extract_top', 5);
        $charCap  = (int) config('sourcing.agent.extract_chars_per_page', 4000);

        $urls = [];
        foreach ($mergedResults as $result) {
            if ($result->url !== '') {
                $urls[] = $result->url;
            }
            if (count($urls) >= $topN) {
                break;
            }
        }

        if ($urls === []) {
            return [];
        }

        try {
            $pages = $this->extractor->extract($urls);
        } catch (Throwable $e) {
            // Extraction is best-effort — a hard failure must not fail the run.
            Log::warning('Sourcing page extraction failed wholesale; analysis proceeds snippet-only.', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        $extracted = [];
        foreach ($pages as $url => $content) {
            if (! filled($content)) {
                continue; // per-URL failure (null) — snippet-only for this page
            }

            $extracted[$url] = mb_substr((string) $content, 0, $charCap);
        }

        return $extracted;
    }

    /**
     * @param SearchResult[]                $searchResults  ALL merged results (snippets)
     * @param array<string, string>         $extractedText  url => full page text (top-N)
     */
    private function analysisPrompt(string $partText, array $searchResults, array $extractedText, string $language, string $instructions): string
    {
        $languageName = $language === 'en' ? 'English' : 'Persian (Farsi)';

        $resultsText = '';
        foreach ($searchResults as $i => $r) {
            $n = $i + 1;
            $resultsText .= "[{$n}] {$r->title}\n    URL: {$r->url}\n    Snippet: {$r->snippet}\n";

            if (isset($extractedText[$r->url])) {
                $resultsText .= "    Extracted page text (authoritative — read contact/price ONLY from here or the snippet above):\n";
                $resultsText .= '    """' . "\n" . $extractedText[$r->url] . "\n" . '    """' . "\n";
            }

            $resultsText .= "\n";
        }
        $resultsText = trim($resultsText);

        if ($resultsText === '') {
            $resultsText = '(no search results returned)';
        }

        return implode("\n", [
            'You are analyzing web search results (snippets, plus full extracted page text for some) to identify suppliers for a part-sourcing request.',
            '',
            'Part to source:',
            $partText,
            '',
            'User search instructions (free text — apply any stated preferences):',
            $instructions !== '' ? $instructions : '(none provided)',
            '',
            'Search results:',
            $resultsText,
            '',
            'From the results, identify the most relevant suppliers for this part. Respond with STRICT JSON in exactly this shape:',
            '',
            '{',
            '  "suppliers": [',
            '    {',
            '      "name": "...",',
            '      "url": "...",',
            '      "relevance": "...",',
            '      "contact": { "email": null, "phone": null, "whatsapp": null },',
            '      "price":   { "value": null, "currency": null, "note": null }',
            '    }',
            '  ],',
            '  "summary": "..."',
            '}',
            '',
            'Rules:',
            '- Apply the user instructions above when SELECTING and ORDERING suppliers and when writing "relevance" and "summary" — e.g. if they ask for the lowest price, prefer/rank cheaper options; if they name a market or site, favor matching suppliers.',
            '- "name" and "url" must be copied exactly as found in the results (do NOT translate them).',
            '- ANTI-HALLUCINATION — this is critical: fill "email", "phone", "whatsapp", "price.value" and "price.currency" ONLY with values that appear LITERALLY in the provided snippet or extracted page text for that supplier. If a value is not literally present, it MUST be null. NEVER infer, guess, complete, or fabricate a contact detail or a price. When in doubt, use null.',
            '- "price.value" is a plain number (no currency symbol, no thousands separators); "price.currency" is the currency code/symbol as written (e.g. "USD", "EUR", "€"). "price.note" carries qualifiers found in the text (e.g. "per 10 pcs", "MOQ 100", "ex-works").',
            '- "email" is a bare address; "phone" and "whatsapp" are phone numbers as written. Use null for any that are absent.',
            "- \"relevance\", \"summary\", and \"price.note\" MUST be written in {$languageName}. Contact values, emails, phone numbers, prices, currencies, names and URLs are copied verbatim and never translated.",
            '- Return valid JSON only: no markdown code fences, no commentary.',
        ]);
    }
}
