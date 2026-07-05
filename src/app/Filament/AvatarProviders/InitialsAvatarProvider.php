<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Offline fallback avatar: a self-contained SVG (data URI) with the user's
 * initials on a colour derived from their name. No external service.
 */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model | Authenticatable $record): string
    {
        $name = trim(Filament::getNameForDefaultAvatar($record));

        $initials = collect(preg_split('/\s+/', $name))
            ->filter()
            ->take(2)
            ->map(fn (string $segment): string => mb_strtoupper(mb_substr($segment, 0, 1)))
            ->implode('');

        if ($initials === '') {
            $initials = '؟';
        }

        $background = $this->colorFor($name);

        $svg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
                <rect width="100" height="100" fill="{$background}"/>
                <text x="50" y="50" dy="0.35em" fill="#FFFFFF" font-size="42"
                      font-family="Vazirmatn, Arial, sans-serif" font-weight="600"
                      text-anchor="middle">{$initials}</text>
            </svg>
            SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /** Deterministic, pleasant background colour from the name. */
    protected function colorFor(string $name): string
    {
        $palette = ['#0ea5e9', '#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#14b8a6', '#ef4444'];

        return $palette[crc32($name) % count($palette)];
    }
}
