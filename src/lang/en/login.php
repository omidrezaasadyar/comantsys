<?php

// lang/en/login.php
// Copy for the custom Filament admin login page (App\Filament\Auth\Login).
// NOTE: the login page renders BOTH locales at once so its EN/FA segmented
// toggle can swap visible copy client-side without changing the app locale.
// The Blade therefore reads these with an explicit locale: __('login.x', [], 'en').

return [

    'brand'   => 'Effective & Innovative Solutions',
    'country' => 'LLC · Sultanate of Oman',

    // Rendered as stacked lines inside the H1.
    'title' => ['Company', 'Management', 'Console'],

    'lede' => 'A unified platform for intelligent management of corporate operations.',

    'divisions' => ['Finance', 'Administration', 'Procurement & Sales'],
    'nums'      => ['01', '02', '03'],

    'status' => 'Systems operational',
    'zones'  => ['Muscat', 'London', 'Tehran'],

    'kicker'   => 'Secure access',
    'sign_in'  => 'Sign in',
    'subtitle' => 'Use your company account. Authorized personnel only.',

    // Labelled "Work email" (not "employee ID"): the underlying Filament
    // component carries an ->email() validation rule.
    'user_label' => 'Work email',
    'pass_label' => 'Password',
    'user_placeholder' => 'name@eis.om',

    'show' => 'Show password',
    'hide' => 'Hide password',
    'caps' => 'Caps Lock is on.',

    'remember'  => 'Keep me signed in',
    'submit'    => 'Sign in',
    'verifying' => 'Verifying…',

    // Label for the one-way admin -> portal button, returned as a full key by
    // App\Filament\Auth\Login::alternatePanelLink(). It has no portal-side
    // counterpart: portal -> admin does not exist, by design.
    'alternate_panel' => 'Request Portal',

    'last_updated' => 'Last updated',
    'lang_en'   => 'EN',
    'lang_fa'   => 'فارسی',
    'lang_group' => 'Display language',

];
