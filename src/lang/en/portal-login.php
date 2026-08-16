<?php

// lang/en/portal-login.php
// Copy for the customer-facing portal login (App\Filament\Portal\Auth\Login).
//
// Deliberately PARTIAL: only the keys that must read differently for a customer
// live here. The shared Blade resolves any missing key from lang/en/login.php
// (Lang::has -> fallback), so shared wording (brand, country, status, clocks,
// contact, footer, control labels) is single-sourced.
//
// As with login.php, the Blade renders both locales at once, so every key here
// must have its Persian counterpart in lang/fa/portal-login.php.

return [

    // Rendered as stacked lines inside the H1.
    'title' => ['Request', 'Submission', 'Portal'],

    'lede' => 'Submit your requests and follow their progress in one place.',

    // The three left-column cards. Where the staff console lists company
    // divisions, the portal shows the customer's three steps. 'nums' is
    // deliberately NOT overridden — 01/02/03 from login.php reads as the step
    // order here.
    'divisions' => ['Request', 'Submit', 'Track'],

    'kicker'   => 'Secure access',
    'subtitle' => 'Log in with the credentials provided by administrator.',

    // "Work email" belongs to the staff console; a customer signs in with theirs.
    'user_label'       => 'Email',
    'user_placeholder' => 'name@company.com',

    // Intentionally absent, inherited from login.php:
    // brand, country, nums, status, zones, sign_in, pass_label, show, hide,
    // caps, remember, submit, verifying, last_updated, lang_en, lang_fa,
    // lang_group

];
