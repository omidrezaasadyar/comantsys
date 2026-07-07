<?php

namespace App\Services\Sourcing\Providers;

use App\Services\Sourcing\Contracts\LlmProviderInterface;
use App\Services\Sourcing\DTOs\LlmResponse;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiProvider implements LlmProviderInterface
{
    public function chat(string $prompt, array $options = []): LlmResponse
    {
        $config = config('sourcing.llm.gemini');

        if (blank($config['api_key'])) {
            throw new RuntimeException('GEMINI_API_KEY is not set in .env');
        }

        $model = $options['model'] ?? $config['model'];

        $payload = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => array_filter([
                'temperature'      => $options['temperature'] ?? null,
                'maxOutputTokens'  => $options['max_tokens'] ?? null,
                'responseMimeType' => ($options['json_mode'] ?? false)
                                        ? 'application/json' : null,
            ], fn ($v) => $v !== null),
        ];

        if (! empty($options['system'])) {
            $payload['system_instruction'] = [
                'parts' => [['text' => $options['system']]],
            ];
        }

        $response = Http::withHeaders(['x-goog-api-key' => $config['api_key']])
            ->timeout($config['timeout'])
            ->post("{$config['base_url']}/models/{$model}:generateContent", $payload);

        if ($response->failed()) {
            throw new RuntimeException(
                'Gemini request failed [' . $response->status() . ']: ' . $response->body()
            );
        }

        $content = $response->json('candidates.0.content.parts.0.text');

        if ($content === null) {
            throw new RuntimeException(
                'Gemini returned no text content: ' . $response->body()
            );
        }

        return new LlmResponse(
            content:      $content,
            model:        $model,
            inputTokens:  (int) $response->json('usageMetadata.promptTokenCount', 0),
            outputTokens: (int) $response->json('usageMetadata.candidatesTokenCount', 0),
            raw:          $response->json(),
        );
    }

    public function name(): string
    {
        return 'gemini';
    }
}
