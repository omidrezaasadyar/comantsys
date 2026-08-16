<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

/**
 * Shared branded login (EIS console + customer portal).
 *
 * ONE class and ONE Blade serve every panel. Only the presentation is replaced:
 * authenticate(), the rate limiter, the Attempting/Failed events, session
 * regeneration and the post-login redirect all stay on the Filament base class.
 * The Blade posts into them via wire:submit="authenticate" and binds to the
 * inherited form state path `data`.
 *
 * The post-login redirect is panel-aware for free: the base class resolves it
 * through Filament::getUrl() / the panel's LoginResponse, both of which read the
 * CURRENT panel. Nothing here hardcodes a destination.
 *
 * Per-panel differences go through the SEAMS below, never through a second copy
 * of the Blade. Defaults are the admin behaviour, so App\Filament\Auth\Login
 * only has to override what actually differs.
 */
abstract class BrandedLogin extends BaseLogin
{
    /**
     * Shared by both panels. The path still says "pages/auth" for historical
     * reasons — it is NOT admin-specific; App\Filament\Portal\Auth\Login renders
     * the very same file.
     */
    protected string $view = 'filament.pages.auth.login';

    /**
     * SimplePage's own layout boxes the content in .fi-simple-main with a width
     * cap; this design is full-bleed, so render straight into the base document
     * layout (<html>/<body> + @filamentStyles/@filamentScripts + @stack('styles')).
     */
    protected static string $layout = 'filament-panels::components.layout.base';

    /* ───────────────────────────── Seams ───────────────────────────── */

    /**
     * Translation namespace for the page copy, e.g. 'login' or 'portal-login'.
     *
     * The Blade resolves every string as "{namespace}.{key}" and falls back to
     * "login.{key}" when the panel's own file does not define it — so shared
     * wording (brand, clocks, footer, control labels) lives in exactly one file.
     */
    public function copyNamespace(): string
    {
        return 'login';
    }

    /**
     * Which locale the page FIRST PAINTS in — 'en' or 'fa'.
     *
     * This decides only the initial render: what the server puts in the markup
     * and what Alpine's `lang` starts as. The client-side EN/FA toggle can still
     * switch away from it, and direction follows from it (fa => RTL, en => LTR),
     * so this single value also settles first-paint text direction.
     *
     * It does NOT touch the application locale, the session, or anything
     * server-side — the page renders both locales at once either way.
     *
     * Default is the admin behaviour: follow the app locale, which is fa.
     */
    public function defaultLocale(): string
    {
        return app()->getLocale() === 'fa' ? 'fa' : 'en';
    }

    /**
     * Icon name per division card, in card order.
     *
     * Names only — the shared Blade owns a name => inline-SVG-path map and draws
     * them, because the page has no icon library to hand (every glyph on it is
     * hand-written inline SVG, not a Heroicon/blade-icons component). A name the
     * map does not know renders nothing rather than breaking the card, so the
     * map doubles as the whitelist.
     *
     * Defaults are the admin console's divisions: Finance / Administration /
     * Procurement & Sales.
     *
     * @return list<string>
     */
    public function divisionIcons(): array
    {
        return ['credit-card', 'clipboard', 'truck'];
    }

    /**
     * One-way cross-panel link, or null for none.
     *
     * @return array{url: string, label_key: string}|null
     *   `label_key` is a FULL translation key (namespace included) — it is
     *   resolved outside copyNamespace() so the link can be labelled
     *   independently of which panel's copy file is in play.
     */
    public function alternatePanelLink(): ?array
    {
        return null;
    }

    /* ──────────────────────────── Plumbing ─────────────────────────── */

    /**
     * BasePage::render() always passes `maxContentWidth` (a Width enum) into the
     * layout. filament-panels::components.layout.base only declares a `livewire`
     * prop, so every other key falls through to $attributes and gets merged onto
     * <body> — stringifying the enum there is a fatal. Pass just `livewire`.
     */
    public function render(): View
    {
        return view($this->getView(), $this->getViewData())
            ->layout($this->getLayout(), ['livewire' => $this]);
    }

    /**
     * The custom Blade renders its own inputs, so these prefix icons are not
     * drawn today. They are kept so the field definitions stay complete if the
     * page is ever reverted to Filament's default view.
     */
    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->prefixIcon(Heroicon::OutlinedUser);
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->prefixIcon(Heroicon::OutlinedLockClosed);
    }
}
