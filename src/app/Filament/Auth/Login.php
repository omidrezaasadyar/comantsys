<?php

namespace App\Filament\Auth;

use Filament\Facades\Filament;

/**
 * ADMIN login (EIS console).
 *
 * Everything visual lives in BrandedLogin + its shared Blade. The admin keeps
 * both defaults — copy namespace 'login', SSO block on — and overrides only the
 * cross-panel link.
 */
class Login extends BrandedLogin
{
    /**
     * The one-way door: admin → portal. Deliberately NOT mirrored on the portal
     * side (App\Filament\Portal\Auth\Login returns null) so the customer-facing
     * page never advertises the staff console.
     *
     * The URL comes from the panel itself — Panel::getLoginUrl() (HasAuth.php)
     * resolves route("filament.portal.auth.login"), so it follows any future
     * ->path() change and is never a hardcoded string. getPanel() returns null
     * for an unregistered id rather than throwing, and getLoginUrl() returns
     * null when that panel has no ->login(); either way the button simply is
     * not rendered.
     *
     * @return array{url: string, label_key: string}|null
     */
    public function alternatePanelLink(): ?array
    {
        $url = Filament::getPanel('portal')?->getLoginUrl();

        if (blank($url)) {
            return null;
        }

        return [
            'url' => $url,
            'label_key' => 'login.alternate_panel',
        ];
    }
}
