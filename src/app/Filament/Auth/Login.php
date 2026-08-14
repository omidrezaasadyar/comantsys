<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

/**
 * Custom-branded admin login (EIS console).
 *
 * Only the presentation is replaced. authenticate(), the rate limiter,
 * the Attempting/Failed events and session regeneration all stay on the
 * Filament base class — the Blade posts into them via wire:submit="authenticate"
 * and binds to the inherited form state path `data`.
 */
class Login extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login';

    /**
     * SimplePage's own layout boxes the content in .fi-simple-main with a width
     * cap; this design is full-bleed, so render straight into the base document
     * layout (<html>/<body> + @filamentStyles/@filamentScripts + @stack('styles')).
     */
    protected static string $layout = 'filament-panels::components.layout.base';

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
