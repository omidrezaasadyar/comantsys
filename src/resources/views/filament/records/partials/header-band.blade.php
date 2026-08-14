{{--
    resources/views/filament/records/partials/header-band.blade.php

    Reusable hero band, shared by the custom record pages (via
    App\Filament\Concerns\HasRecordPageLayout / page.blade.php) and by list
    pages that render it from ListRecords::getHeader().

    Expects $header:
      ['icon', 'image', 'title', 'badge', 'subtitle', 'breadcrumbs', 'edit_url']
    plus, for list pages, an optional:
      'actions'  => array of already-booted Filament actions to render in the
                    action corner (e.g. $this->getCachedHeaderActions()).

    Every key is optional here rather than assumed, because callers outside the
    trait build the array by hand.

    Under RTL the first flex child sits on the RIGHT, so identity comes first in
    the DOM and the breadcrumb + actions are pushed to the LEFT.

    The band carries its own <style> so both the record pages and the list pages
    style it from one source. It renders once per page.
--}}

@php
    $bandIcon = $header['icon'] ?? null;
    $bandImage = $header['image'] ?? null;
    $bandTitle = $header['title'] ?? '';
    $bandBadge = $header['badge'] ?? null;
    $bandSubtitle = $header['subtitle'] ?? null;
    $bandCrumbs = $header['breadcrumbs'] ?? [];
    $bandEditUrl = $header['edit_url'] ?? null;
    $bandActions = $header['actions'] ?? [];
@endphp

<div class="fi-section cs-rp-card cs-rp-head">
    <div class="cs-rp-head-id">
        <div class="cs-rp-avatar">
            @if (filled($bandImage))
                <img src="{{ $bandImage }}" alt="{{ $bandTitle }}" />
            @elseif (filled($bandIcon))
                <x-filament::icon :icon="$bandIcon" class="cs-rp-avatar-icon" />
            @endif
        </div>

        <div class="cs-rp-head-text">
            <div class="cs-rp-head-title-line">
                <h1 class="cs-rp-head-title">{{ $bandTitle }}</h1>

                @if (filled($bandBadge))
                    <x-filament::badge :color="$bandBadge['color'] ?? 'gray'">
                        {{ $bandBadge['label'] ?? '' }}
                    </x-filament::badge>
                @endif
            </div>

            @if (filled($bandSubtitle))
                <p class="cs-rp-head-subtitle">{{ $bandSubtitle }}</p>
            @endif
        </div>
    </div>

    <div class="cs-rp-head-actions">
        @if (filled($bandCrumbs))
            <nav class="cs-rp-crumbs">
                @foreach ($bandCrumbs as $index => $crumb)
                    @if ($index > 0)
                        <span class="cs-rp-crumb-sep">/</span>
                    @endif

                    @if (filled($crumb['url'] ?? null))
                        <a class="cs-rp-crumb" href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                    @else
                        <span class="cs-rp-crumb cs-rp-crumb-current">{{ $crumb['label'] }}</span>
                    @endif
                @endforeach
            </nav>
        @endif

        @if (filled($bandEditUrl) || filled($bandActions))
            <div class="cs-rp-head-buttons">
                @if (filled($bandEditUrl))
                    <x-filament::button
                        tag="a"
                        :href="$bandEditUrl"
                        icon="heroicon-m-pencil-square"
                        size="sm"
                    >
                        {{ __('filament-actions::edit.single.label') }}
                    </x-filament::button>
                @endif

                {{-- Already-booted Filament actions: rendering them keeps their
                     own label, icon, colour and authorization instead of
                     re-implementing the button here. --}}
                @foreach ($bandActions as $bandAction)
                    {{ $bandAction }}
                @endforeach
            </div>
        @endif
    </div>
</div>

<style>
    /* Colours are panel theme tokens only (--primary-*, --gray-*); the card
       surface itself comes from Filament's `fi-section`. */

    .cs-rp-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        min-width: 0;
        padding: 1.15rem 1.25rem;
    }

    .cs-rp-head-id {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        min-width: 0;
    }

    .cs-rp-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 3.25rem;
        height: 3.25rem;
        flex-shrink: 0;
        border-radius: 0.7rem;
        overflow: hidden;
        background: color-mix(in oklab, var(--primary-500) 14%, transparent);
        color: var(--primary-600);
    }
    .dark .cs-rp-avatar { color: var(--primary-400); }
    .cs-rp-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .cs-rp-avatar .cs-rp-avatar-icon { width: 1.7rem; height: 1.7rem; }

    .cs-rp-head-text { min-width: 0; display: flex; flex-direction: column; gap: 0.3rem; }
    .cs-rp-head-title-line {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.6rem;
    }
    .cs-rp-head-title {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 700;
        line-height: 1.4;
        color: var(--gray-950);
        overflow-wrap: anywhere;
    }
    .dark .cs-rp-head-title { color: var(--gray-50); }
    .cs-rp-head-subtitle {
        margin: 0;
        font-size: 0.8rem;
        line-height: 1.6;
        color: var(--gray-500);
        overflow-wrap: anywhere;
    }
    .dark .cs-rp-head-subtitle { color: var(--gray-400); }

    .cs-rp-head-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.6rem;
        margin-inline-start: auto;
    }
    .cs-rp-head-buttons { display: flex; align-items: center; gap: 0.5rem; }

    .cs-rp-crumbs {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.75rem;
        color: var(--gray-500);
    }
    .dark .cs-rp-crumbs { color: var(--gray-400); }
    .cs-rp-crumb { color: inherit; text-decoration: none; }
    .cs-rp-crumb:hover { text-decoration: underline; }
    .cs-rp-crumb-current { color: var(--gray-700); }
    .dark .cs-rp-crumb-current { color: var(--gray-200); }
    .cs-rp-crumb-sep { color: var(--gray-400); }
    .dark .cs-rp-crumb-sep { color: var(--gray-600); }

    @media (max-width: 640px) {
        .cs-rp-head-actions { align-items: flex-start; margin-inline-start: 0; }
    }
</style>
