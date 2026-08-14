<?php

namespace App\Filament\Concerns;

use Ariaieboy\Jalali\Jalali;
use Carbon\Carbon;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToCheckFileExistence;
use Throwable;

/**
 * Value-formatting helpers shared by the custom record layouts.
 *
 * NOTE: HasCustomRecordView (the older shared-blade layout still used by the
 * other six resources) carries its own private copies of these methods. Do not
 * use both traits on the same page class until that one is retired, or the
 * duplicate method names will collide.
 */
trait RecordValueHelpers
{
    /**
     * Gregorian in the DB, Jalali on screen — same conversion the
     * ariaieboy/filament-jalali `jalaliDate()` / `jalaliDateTime()` macros use.
     */
    protected function toJalali(mixed $value, bool $withTime = false): ?string
    {
        if (blank($value)) {
            return null;
        }

        $format = $withTime
            ? config('filament-jalali.date_time_format')
            : config('filament-jalali.date_format');

        return Jalali::fromCarbon(
            Carbon::parse($value)->setTimezone(FilamentTimezone::get()),
        )->format($format);
    }

    /**
     * Signed temporary URL for a file on the private disk — the same path
     * Filament's ImageEntry takes for `->disk('local')->visibility('private')`.
     */
    protected function privateFileUrl(?string $path, string $disk = 'local'): ?string
    {
        if (blank($path)) {
            return null;
        }

        $storage = Storage::disk($disk);

        try {
            if (! $storage->exists($path)) {
                return null;
            }
        } catch (UnableToCheckFileExistence) {
            return null;
        }

        try {
            return $storage->temporaryUrl(
                $path,
                now()->addMinutes(config('filament.temporary_file_url_expiry_minutes', 30))->endOfHour(),
            );
        } catch (Throwable) {
            return $storage->url($path);
        }
    }

    /**
     * Thousands-separated number, matching the infolists' existing
     * `->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ',')`.
     */
    protected function formatNumber(mixed $value, int $decimals = 0, bool $trimZeros = false): ?string
    {
        if (blank($value) || ! is_numeric($value)) {
            return null;
        }

        $formatted = number_format((float) $value, $decimals, '.', ',');

        if ($trimZeros && str_contains($formatted, '.')) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }

        return $formatted;
    }

    /**
     * Joins only the parts that are present, so a missing city/country does not
     * leave a dangling separator in a subtitle.
     *
     * @param  array<int, mixed>  $parts
     */
    protected function joinFilled(array $parts, string $glue = '، '): ?string
    {
        $parts = array_values(array_filter($parts, static fn ($part): bool => filled($part)));

        return $parts === [] ? null : implode($glue, $parts);
    }

    /**
     * Stored URLs are often scheme-less ("example.com"), which a bare href
     * would resolve as a relative panel path.
     */
    protected function externalUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $url = trim($url);

        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://')
            ? $url
            : 'https://' . $url;
    }
}
