<?php

namespace App\Providers;

use App\Services\Sourcing\Contracts\LlmProviderInterface;
use App\Services\Sourcing\Contracts\OcrProviderInterface;
use App\Services\Sourcing\Contracts\SearchProviderInterface;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class SourcingServiceProvider extends ServiceProvider
{
    /**
     * Map of provider names (from config/sourcing.php) to concrete classes.
     * Adding a new provider = add the class + one line here. Consumers never change.
     */
    private const LLM_PROVIDERS = [
        'gemini' => \App\Services\Sourcing\Providers\GeminiProvider::class,
        // 'openai'    => \App\Services\Sourcing\Providers\OpenAiProvider::class,
        // 'anthropic' => \App\Services\Sourcing\Providers\AnthropicProvider::class,
    ];

    private const SEARCH_PROVIDERS = [
        'tavily' => \App\Services\Sourcing\Providers\TavilySearchProvider::class,
        // 'brave' => \App\Services\Sourcing\Providers\BraveSearchProvider::class,
    ];

    private const OCR_PROVIDERS = [
        'tesseract' => \App\Services\Sourcing\Providers\TesseractOcrProvider::class,
    ];

    public function register(): void
    {
        $this->app->singleton(LlmProviderInterface::class, function ($app) {
            return $app->make($this->resolveClass('llm', self::LLM_PROVIDERS));
        });

        $this->app->singleton(SearchProviderInterface::class, function ($app) {
            return $app->make($this->resolveClass('search', self::SEARCH_PROVIDERS));
        });

        $this->app->singleton(OcrProviderInterface::class, function ($app) {
            return $app->make($this->resolveClass('ocr', self::OCR_PROVIDERS));
        });
    }

    private function resolveClass(string $service, array $map): string
    {
        $name = config("sourcing.{$service}.provider");

        if (! isset($map[$name])) {
            throw new InvalidArgumentException(
                "Unknown sourcing {$service} provider [{$name}]. "
                . 'Valid options: ' . implode(', ', array_keys($map))
            );
        }

        return $map[$name];
    }
}
