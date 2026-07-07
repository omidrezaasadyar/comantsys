<?php

namespace App\Services\Sourcing\Providers;

use App\Services\Sourcing\Contracts\OcrProviderInterface;
use RuntimeException;
use Symfony\Component\Process\Process;

class TesseractOcrProvider implements OcrProviderInterface
{
    public function extractText(string $absolutePath): string
    {
        if (! is_file($absolutePath)) {
            throw new RuntimeException("OCR input file not found: {$absolutePath}");
        }

        $process = new Process([
            config('sourcing.ocr.tesseract.binary'),
            $absolutePath,
            'stdout',                                        // output to stdout, no temp file
            '-l', config('sourcing.ocr.tesseract.languages'),
        ]);

        $process->setTimeout(config('sourcing.ocr.tesseract.timeout'));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                'Tesseract failed: ' . trim($process->getErrorOutput())
            );
        }

        return trim($process->getOutput());
    }

    public function name(): string
    {
        return 'tesseract';
    }
}
