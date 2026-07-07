<?php

namespace App\Services\Sourcing\Contracts;

use App\Services\Sourcing\DTOs\LlmResponse;

interface LlmProviderInterface
{
    /**
     * Send a prompt to the LLM and get a response.
     *
     * @param string $prompt        The user/task prompt.
     * @param array  $options       Provider-agnostic options:
     *                              'system' (string), 'temperature' (float),
     *                              'max_tokens' (int), 'json_mode' (bool).
     */
    public function chat(string $prompt, array $options = []): LlmResponse;

    /**
     * Provider identifier, e.g. 'gemini', 'openai', 'anthropic'.
     */
    public function name(): string;
}
