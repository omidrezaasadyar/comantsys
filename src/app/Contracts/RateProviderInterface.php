<?php

namespace App\Contracts;

interface RateProviderInterface
{
    /**
     * Return current USD/EUR free-market rates for display.
     *
     * Shape (per currency):
     *   'value' => string  // Toman, thousands-separated, Latin digits
     *   'delta' => string  // percent change magnitude, e.g. "0.2" ("" if none)
     *   'dir'   => string  // 'up' | 'down' | 'flat'
     *
     * @return array{
     *   usd: array{value:string, delta:string, dir:string},
     *   eur: array{value:string, delta:string, dir:string}
     * }|null  Null when no data is available at all (caller decides fallback).
     */
    public function rates(): ?array;
}
