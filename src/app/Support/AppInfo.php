<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Carbon;

/**
 * Single source of truth for the app version and "last updated" date.
 *
 * Values live in the app_settings table and fall back to config. This is the
 * ONE place the setting key names and their config fallbacks are written.
 */
final class AppInfo
{
    public static function version(): string
    {
        return (string) AppSetting::get('app_version', config('app.version'));
    }

    /**
     * Raw stored value: a Gregorian 'Y-m-d' string, or null if unset.
     */
    public static function updatedAtRaw(): ?string
    {
        return AppSetting::get('app_updated_at', config('app.updated_at'));
    }

    /**
     * Parsed "last updated" date, or null when unset or malformed.
     * A bad stored date must never fatal a page, so parsing is guarded.
     */
    public static function updatedAt(): ?Carbon
    {
        $raw = self::updatedAtRaw();

        if (blank($raw)) {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }
}
