<?php

namespace Tests\Feature\Sourcing;

use App\Services\Sourcing\Providers\OpenAiProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('sourcing.llm.openai', [
            'api_key'       => 'sk-test-key',
            'base_url'      => 'https://api.openai.com/v1',
            'timeout'       => 60,
            'model'         => 'gpt-4.1',
            'planner_model' => 'gpt-4o-mini',
        ]);
    }

    public function test_it_maps_request_and_response_including_json_mode_and_usage(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'model'   => 'gpt-4.1-2025-01-01',
                'choices' => [
                    ['message' => ['content' => '{"suppliers": [], "summary": "ok"}']],
                ],
                'usage' => [
                    'prompt_tokens'     => 123,
                    'completion_tokens' => 45,
                ],
            ], 200),
        ]);

        $response = (new OpenAiProvider())->chat('Find suppliers. Respond in JSON.', [
            'system'      => 'You are an analyst.',
            'temperature' => 0.2,
            'max_tokens'  => 2500,
            'json_mode'   => true,
            'model'       => 'gpt-4o-mini',
        ]);

        // Response mapping.
        $this->assertSame('{"suppliers": [], "summary": "ok"}', $response->content);
        $this->assertSame('gpt-4.1-2025-01-01', $response->model);
        $this->assertSame(123, $response->inputTokens);
        $this->assertSame(45, $response->outputTokens);
        $this->assertSame(['suppliers' => [], 'summary' => 'ok'], $response->json());

        // Request mapping.
        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->hasHeader('Authorization', 'Bearer sk-test-key')
                && $body['model'] === 'gpt-4o-mini'                          // per-call override wins
                && $body['temperature'] === 0.2
                && $body['max_tokens'] === 2500
                && $body['response_format'] === ['type' => 'json_object']    // json_mode
                && $body['messages'][0] === ['role' => 'system', 'content' => 'You are an analyst.']
                && $body['messages'][1]['role'] === 'user';
        });
    }

    public function test_it_omits_response_format_when_json_mode_off_and_uses_default_model(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'plain text']]],
                'usage'   => ['prompt_tokens' => 1, 'completion_tokens' => 2],
            ], 200),
        ]);

        $response = (new OpenAiProvider())->chat('hello');

        $this->assertSame('plain text', $response->content);
        // Falls back to config model when no override given.
        $this->assertSame('gpt-4.1', $response->model);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['model'] === 'gpt-4.1'
                && ! array_key_exists('response_format', $body)
                && ! array_key_exists('temperature', $body)
                && ! array_key_exists('max_tokens', $body);
        });
    }

    public function test_it_fails_loud_with_body_on_non_2xx(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response(
                ['error' => ['message' => 'bad request']],
                400
            ),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/OpenAI request failed \[400\].*bad request/');

        (new OpenAiProvider())->chat('hello');
    }
}
