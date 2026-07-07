<?php

namespace App\Services\Sourcing\Contracts;

interface OcrProviderInterface
{
    /**
     * Extract plain text from an image or PDF file.
     *
     * @param string $absolutePath  Absolute path inside the container.
     */
    public function extractText(string $absolutePath): string;

    public function name(): string;
}
