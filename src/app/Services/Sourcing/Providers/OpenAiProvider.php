<?php

namespace App\Services\Sourcing\Providers;

use App\Services\Sourcing\Contracts\LlmProviderInterface;
use App\Services\Sourcing\DTOs\LlmResponse;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OpenAI Chat Completions provider (POST /v1/chat/completions).
 *
 * Mirrors GeminiProvider's fail-loud contract: any non-2xx throws with the
 * response body attached. Per-call model override via $options['model']
 * enables per-role model selection (cheap planner vs. strong analyst).
 */
class OpenAiProvider implements LlmProviderInterface
{
    public function chat(string $prompt, array $options = []): LlmResponse
    {
        $config = config('sourcing.llm.openai');

        if (blank($config['api_key'])) {
            throw new RuntimeException('OPENAI_API_KEY is not set in .env');
        }

        $model = $options['model'] ?? $config['model'];

        $messages = [];

        if (! empty($options['system'])) {
            $messages[] = ['role' => 'system', 'content' => $options['system']];
        }

        $messages[] = ['role' => 'user', 'content' => $prompt];

        $payload = array_filter([
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => $options['temperature'] ?? null,
            'max_tokens'  => $options['max_tokens'] ?? null,
        ], static fn ($v) => $v !== null);

        // OpenAI requires the literal word "json" somewhere in the messages when
        // response_format is json_object. Our system/user prompts already say
        // "JSON" (see SourcingAgentService), so the constraint is satisfied.
        if ($options['json_mode'] ?? false) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = Http::withToken($config['api_key'])
            ->timeout($config['timeout'])
            ->post($config['base_url'] . '/chat/completions', $payload);

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenAI request failed [' . $response->status() . ']: ' . $response->body()
            );
        }

        $content = $response->json('choices.0.message.content');

        if ($content === null) {
            throw new RuntimeException(
                'OpenAI returned no message content: ' . $response->body()
            );
        }

        return new LlmResponse(
            content:      $content,
            model:        $response->json('model', $model),
            inputTokens:  (int) $response->json('usage.prompt_tokens', 0),
            outputTokens: (int) $response->json('usage.completion_tokens', 0),
            raw:          $response->json(),
        );
    }

    public function name(): string
    {
        return 'openai';
    }
}
