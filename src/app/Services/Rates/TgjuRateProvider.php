<?php

namespace App\Services\Rates;

use App\Contracts\RateProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TgjuRateProvider implements RateProviderInterface
{
    /**
     * Public tgju snapshot endpoint (JSON). Free-market keys:
     *   price_dollar_rl  → USD (Rial)
     *   price_eur        → EUR (Rial)
     * Each item carries p (price), dp (percent change), dt (direction word).
     */
    private const ENDPOINT = 'https://call1.tgju.org/ajax.json';

    public function rates(): ?array
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get(self::ENDPOINT);

            if (! $response->ok()) {
                Log::warning('TgjuRateProvider: non-OK response', ['status' => $response->status()]);
                return null;
            }

            $current = $response->json('current');

            if (! is_array($current)) {
                Log::warning('TgjuRateProvider: unexpected payload shape');
                return null;
            }

            $usd = $this->extract($current, 'price_dollar_rl');
            $eur = $this->extract($current, 'price_eur');

            if ($usd === null || $eur === null) {
                Log::warning('TgjuRateProvider: missing USD/EUR keys');
                return null;
            }

            return ['usd' => $usd, 'eur' => $eur];
        } catch (\Throwable $e) {
            Log::warning('TgjuRateProvider: fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Normalize one tgju entry into the display shape.
     * - price is Rial ("1,894,000") → strip commas → Toman (÷10) → re-format
     * - dp is the percent magnitude; dt is a direction word that may be empty
     *   (market closed / no change) → normalized to 'flat'
     *
     * @return array{value:string, delta:string, dir:string}|null
     */
    private function extract(array $current, string $key): ?array
    {
        if (! isset($current[$key]['p'])) {
            return null;
        }

        $rial = (int) str_replace(',', '', (string) $current[$key]['p']);
        if ($rial <= 0) {
            return null;
        }

        $toman = intdiv($rial, 10);

        $delta = (string) ($current[$key]['dp'] ?? '');
        $delta = trim($delta);
        if ($delta === '0' || $delta === '0.0') {
            $delta = '';
        }

        $dtRaw = strtolower(trim((string) ($current[$key]['dt'] ?? '')));
        $dir = match ($dtRaw) {
            'high', 'up'   => 'up',
            'low', 'down'  => 'down',
            default        => 'flat',
        };

        // If direction is flat, there is effectively no delta to show.
        if ($dir === 'flat') {
            $delta = '';
        }

        return [
            'value' => number_format($toman), // Latin digits, thousands-separated
            'delta' => $delta,
            'dir'   => $dir,
        ];
    }
}
