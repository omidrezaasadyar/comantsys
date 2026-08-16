<?php

namespace App\Filament\Portal\Auth;

use App\Filament\Auth\BrandedLogin;

/**
 * PORTAL login (customer-facing).
 *
 * Same class, same Blade, same CSS as the admin console — only the seams differ.
 * Nothing is duplicated here.
 *
 * Note the namespace: this sits OUTSIDE app/Filament/Portal/Resources, which is
 * the only tree PortalPanelProvider discovers, so it cannot be picked up as a
 * resource. It is wired explicitly via ->login(self::class).
 */
class Login extends BrandedLogin
{
    /**
     * Customer wording lives in lang/{en,fa}/portal-login.php. Keys absent there
     * fall back to lang/{en,fa}/login.php, so brand, clocks, contact and footer
     * copy stay single-sourced.
     */
    public function copyNamespace(): string
    {
        return 'portal-login';
    }

    /**
     * Customers are the European/Omani audience, so the portal opens in English
     * and LTR every time — unlike the admin console, which follows the app
     * locale (fa). Pinned rather than derived: the app locale describes the
     * staff UI and says nothing about who is standing at this door.
     *
     * The EN/FA toggle still works from here; this only sets the starting point.
     */
    public function defaultLocale(): string
    {
        return 'en';
    }

    /**
     * Matches the customer's three steps from the portal-login copy files
     * (درخواست / ثبت / پیگیری — Request / Submit / Track), replacing the
     * console's finance-flavoured defaults. Same order as 'divisions'.
     *
     * @return list<string>
     */
    public function divisionIcons(): array
    {
        return ['document-text', 'paper-airplane', 'magnifying-glass'];
    }

    /**
     * Explicit null, not an omission: the admin → portal link is ONE-WAY by
     * design. An externally reachable page must not advertise the internal
     * console, so this override is kept (rather than inherited) to make the
     * security decision visible at the point someone would try to add it.
     */
    public function alternatePanelLink(): ?array
    {
        return null;
    }
}
