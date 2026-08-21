<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key-value application settings.
 *
 * The logical key IS the primary key (string, non-incrementing), so there is
 * no surrogate id. Reads go through a forever cache keyed per setting; writes
 * invalidate that single key.
 */
class AppSetting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /**
     * Read a setting, cached forever until set() invalidates it.
     *
     * The cached payload is the RAW stored value or null — the default is
     * applied after the cache lookup on purpose. Caching the default would
     * mask a real value written later.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::rememberForever(
            self::cacheKey($key),
            fn (): ?string => self::query()->find($key)?->value,
        );

        return $value ?? $default;
    }

    /**
     * Write a setting and drop its cache entry so the next read is fresh.
     */
    public static function set(string $key, ?string $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget(self::cacheKey($key));
    }

    private static function cacheKey(string $key): string
    {
        return "app_setting:{$key}";
    }
}
