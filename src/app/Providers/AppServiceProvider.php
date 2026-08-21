<?php

namespace App\Providers;

use App\Contracts\RateProviderInterface;
use App\Services\Rates\TgjuRateProvider;
use Illuminate\Support\ServiceProvider;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Swappable rates source — bound to tgju for now, like the sourcing
        // provider pattern. getRates() resolves this behind a cache layer.
        $this->app->bind(RateProviderInterface::class, TgjuRateProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch->locales(['fa', 'en']);
        });
    }
}
