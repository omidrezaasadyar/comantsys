<?php

namespace App\Console\Commands;

use App\Contracts\RateProviderInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RefreshRates extends Command
{
    protected $signature = 'app:refresh-rates';

    protected $description = 'Fetch USD/EUR rates from the provider and warm the dashboard cache';

    public function handle(RateProviderInterface $provider): int
    {
        $fresh = $provider->rates();

        if ($fresh === null) {
            // Provider failed; keep whatever good snapshot we already have.
            $this->warn('Rate fetch failed; kept existing cache (if any).');

            return self::FAILURE;
        }

        // Refresh both layers: the served value (30-min TTL) and the
        // forever "last known good" snapshot used for offline fallback.
        Cache::put('dashboard.rates', $fresh, now()->addMinutes(30));
        Cache::forever('dashboard.rates.last', $fresh);

        $this->info('Rates refreshed: USD=' . $fresh['usd']['value'] . ' EUR=' . $fresh['eur']['value']);

        return self::SUCCESS;
    }
}
