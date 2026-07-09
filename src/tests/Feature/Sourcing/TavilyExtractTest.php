<?php

namespace Tests\Feature\Sourcing;

use App\Services\Sourcing\Providers\TavilySearchProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TavilyExtractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('sourcing.search.tavily', [
            'api_key'      => 'tvly-test-key',
            'base_url'     => 'https://api.tavily.com',
            'timeout'      => 30,
            'search_depth' => 'basic',
            'max_results'  => 5,
        ]);
    }

    public function test_it_maps_extracted_pages_and_tolerates_per_url_failures(): void
    {
        Http::fake([
            'https://api.tavily.com/extract' => Http::response([
                'results' => [
                    ['url' => 'https://a.com', 'raw_content' => 'CONTENT A'],
                    ['url' => 'https://b.com', 'content' => 'CONTENT B'], // fallback key
                ],
                'failed_results' => [
                    ['url' => 'https://c.com', 'error' => 'timeout'],
                ],
            ], 200),
        ]);

        $out = (new TavilySearchProvider())->extract([
            'https://a.com',
            'https://b.com',
            'https://c.com', // failed → null
            'https://d.com', // omitted entirely by Tavily → null
        ]);

        $this->assertSame('CONTENT A', $out['https://a.com']);
        $this->assertSame('CONTENT B', $out['https://b.com']);
        $this->assertNull($out['https://c.com']);
        $this->assertNull($out['https://d.com']);

        // Batched into a single /extract call with basic depth.
        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->hasHeader('Authorization', 'Bearer tvly-test-key')
                && $body['extract_depth'] === 'basic'
                && count($body['urls']) === 4;
        });
    }

    public function test_it_dedupes_and_skips_call_for_empty_input(): void
    {
        Http::fake();

        $this->assertSame([], (new TavilySearchProvider())->extract([]));
        $this->assertSame([], (new TavilySearchProvider())->extract(['', null]));

        Http::assertNothingSent();
    }

    public function test_it_dedupes_repeated_urls_before_calling(): void
    {
        Http::fake([
            'https://api.tavily.com/extract' => Http::response(['results' => []], 200),
        ]);

        (new TavilySearchProvider())->extract([
            'https://a.com',
            'https://a.com',
            'https://b.com',
        ]);

        Http::assertSent(fn ($request) => count($request->data()['urls']) === 2);
    }

    public function test_it_throws_on_hard_failure(): void
    {
        Http::fake([
            'https://api.tavily.com/extract' => Http::response('unauthorized', 401),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Tavily extract failed \[401\]/');

        (new TavilySearchProvider())->extract(['https://a.com']);
    }
}
