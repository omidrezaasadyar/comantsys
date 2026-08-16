{{-- resources/views/filament/pages/auth/login.blade.php --}}
{{--
    SHARED branded login — rendered by BOTH panels (App\Filament\Auth\Login for
    the admin console, App\Filament\Portal\Auth\Login for the customer portal),
    via their common base App\Filament\Auth\BrandedLogin. The path still says
    "pages/auth" for historical reasons; it is not admin-specific.

    Per-panel differences arrive as SEAMS on the page class, never as a second
    copy of this file:
      $this->copyNamespace()      which lang/*/{namespace}.php supplies the copy
      $this->alternatePanelLink() the one-way cross-panel button, or null

    Only the presentation lives here. The form posts into Filament's inherited
    authenticate() via wire:submit, and the inputs bind to the base class's form
    state path `data` (data.email / data.password / data.remember), so the rate
    limiter, Attempting/Failed events, session regeneration and the panel-aware
    post-login redirect are untouched.

    The EN/FA segmented control is PRESENTATION ONLY: it flips a local `lang`
    value that swaps visible copy and text direction. It does NOT change the
    application locale, the session, or anything server-side. Both languages are
    rendered from lang/{en,fa}/login.php via __() with an explicit locale.
--}}

@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Lang;
    use Morilog\Jalali\Jalalian;

    // Latin -> Persian digits, for the Persian-language presentation only.
    $faDigits = fn (string $v): string => strtr($v, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);

    // SEAM: which copy file this panel uses ('login' | 'portal-login').
    $ns = $this->copyNamespace();

    // Panel copy first, admin copy as the fallback — so a panel file only has to
    // list what genuinely differs and shared wording stays single-sourced.
    // fallback: false on Lang::has() is deliberate: with the default `true` a key
    // present only in the app's fallback LOCALE would report as existing for the
    // other locale, and the page renders both locales at once.
    $tr = fn (string $key, string $locale): mixed => Lang::has("{$ns}.{$key}", $locale, false)
        ? __("{$ns}.{$key}", [], $locale)
        : __("login.{$key}", [], $locale);

    // SEAM: one-way cross-panel button. Null on the portal, by design.
    // 'label_key' is a FULL key, resolved outside $ns.
    $alternate = $this->alternatePanelLink();

    // SEAM: which icon sits on each division card, in card order. The page class
    // names them; the map below owns the drawing, because this page has no icon
    // library — every glyph on it is hand-written inline SVG.
    $divisionIcons = $this->divisionIcons();

    // name => inner SVG markup. All Lucide-style geometry on a 24x24 grid so they
    // sit consistently in the shared <svg> wrapper below. A name absent from this
    // map draws nothing, so the map IS the whitelist for the {!! !!} echo — the
    // values are literals in this file and never touch user input.
    $divisionIconPaths = [
        // admin: Finance / Administration / Procurement & Sales
        'credit-card' => '<path d="M2 8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2z"></path><path d="M14 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0"></path><path d="M6 12h.01"></path><path d="M18 12h.01"></path>',
        'clipboard' => '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><path d="M9 2h6a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1"></path><path d="M8 12h8"></path><path d="M8 16h5"></path>',
        'truck' => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"></path><path d="M15 18H9"></path><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"></path><path d="M7 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0"></path><path d="M21 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0"></path>',
        // portal: Request / Submit / Track
        'document-text' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path>',
        'paper-airplane' => '<path d="m22 2-7 20-4-9-9-4z"></path><path d="M22 2 11 13"></path>',
        'magnifying-glass' => '<circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>',
    ];

    // Both locales up front, so the client-side toggle never needs a round trip.
    $copy = [];

    foreach (['en', 'fa'] as $l) {
        $copy[$l] = [
            'brand' => $tr('brand', $l),
            'country' => $tr('country', $l),
            'lede' => $tr('lede', $l),
            'divisions' => array_values((array) $tr('divisions', $l)),
            'nums' => array_values((array) $tr('nums', $l)),
            'status' => $tr('status', $l),
            'zones' => array_values((array) $tr('zones', $l)),
            'kicker' => $tr('kicker', $l),
            'signIn' => $tr('sign_in', $l),
            'subtitle' => $tr('subtitle', $l),
            'userLabel' => $tr('user_label', $l),
            'passLabel' => $tr('pass_label', $l),
            'userPlaceholder' => $tr('user_placeholder', $l),
            'show' => $tr('show', $l),
            'hide' => $tr('hide', $l),
            'caps' => $tr('caps', $l),
            'remember' => $tr('remember', $l),
            'submit' => $tr('submit', $l),
            'verifying' => $tr('verifying', $l),
            'lastUpdated' => $tr('last_updated', $l),
            'altPanel' => $alternate ? __($alternate['label_key'], [], $l) : null,
        ];
    }

    // SEAM: which locale this panel first-paints in. Admin follows the app locale
    // (fa); the portal pins 'en'. Everything below that reads $defaultLang —
    // copy, dir/RTL, clock digits, the x-cloak pairs, the radios, the footer
    // date — follows from this one value.
    $defaultLang = $this->defaultLocale();

    // The page only builds $copy for the locales it renders, so an unknown value
    // would fatal on $copy[$defaultLang] below. Clamp instead of blowing up.
    if (! isset($copy[$defaultLang])) {
        $defaultLang = 'en';
    }

    // Footer "version updated" date. Source: config('app.updated_at'), which is
    // env-driven (APP_UPDATED_AT) so a deploy step can stamp it. Jalali for the
    // Persian presentation, Gregorian for English.
    $updatedAt = Carbon::parse(config('app.updated_at'));

    $updatedLabels = [
        'en' => $updatedAt->format('Y-m-d'),
        'fa' => $faDigits(Jalalian::fromDateTime($updatedAt)->format('Y/m/d')),
    ];

    $version = config('app.version');

    // Server-rendered first paint for the clocks, so they never flash "--:--".
    // Alpine takes over on boot and keeps them ticking.
    $timezones = ['Asia/Muscat', 'Europe/London', 'Asia/Tehran'];

    $initialClocks = array_map(
        fn (string $tz): string => $defaultLang === 'fa'
            ? $faDigits(Carbon::now($tz)->format('H:i'))
            : Carbon::now($tz)->format('H:i'),
        $timezones,
    );

    $t = $copy[$defaultLang];
    $isRtlDefault = $defaultLang === 'fa';
@endphp

@push('styles')
    {{--
        Every face this page uses is served from public/fonts — no CDN, no
        build-time fetch. The woff2 files are committed static assets, so prod
        gets them from a plain `git pull`.

        Vazirmatn (public/fonts/vazirmatn) was already shipped for the PDF
        pipeline and is reused here.

        Barlow + Barlow Condensed (public/fonts/barlow) come from the npm
        packages @fontsource/barlow and @fontsource/barlow-condensed, both pinned
        to 5.3.0 in package.json — that pin is the provenance record and the way
        to regenerate the files; nothing at runtime or build time reads it.

        LATIN subset only, and only the weights the CSS below actually asks for:
          Barlow            400  (body text; .eis-latin runs)
          Barlow Condensed  500  (inherited by .eis-brand-name, .eis-division-num,
                                  .eis-division-label, .eis-clock-label, .eis-kicker
                                  — see the note on 500 below)
          Barlow Condensed  600  (.eis-title, .eis-signin, .eis-submit)
          Barlow Condensed  700  (.eis-logo)
        Adding a weight to the CSS means adding the file here too, or the browser
        will synthesise it.
    --}}

    <style>
        @font-face {
            font-family: 'Barlow';
            src: url('/fonts/barlow/Barlow-Regular.woff2') format('woff2');
            font-weight: 400;
            font-display: swap;
        }

        /*
           500 is deliberate, and it is NOT a weight any rule names explicitly.
           The five elements listed above set the Condensed family without a
           font-weight, so they inherit 400 from .eis-login. While this page was
           on the Google CDN the request was `Barlow+Condensed:wght@500;600;700` —
           400 was never delivered, so CSS font matching resolved those elements
           UP to 500. Shipping 400 here instead of 500 would silently make all
           five lighter than they have always rendered.
        */
        @font-face {
            font-family: 'Barlow Condensed';
            src: url('/fonts/barlow/BarlowCondensed-Medium.woff2') format('woff2');
            font-weight: 500;
            font-display: swap;
        }

        @font-face {
            font-family: 'Barlow Condensed';
            src: url('/fonts/barlow/BarlowCondensed-SemiBold.woff2') format('woff2');
            font-weight: 600;
            font-display: swap;
        }

        @font-face {
            font-family: 'Barlow Condensed';
            src: url('/fonts/barlow/BarlowCondensed-Bold.woff2') format('woff2');
            font-weight: 700;
            font-display: swap;
        }

        @font-face {
            font-family: 'VazirmatnLogin';
            src: url('/fonts/vazirmatn/Vazirmatn-Regular.woff2') format('woff2');
            font-weight: 400;
            font-display: swap;
        }

        @font-face {
            font-family: 'VazirmatnLogin';
            src: url('/fonts/vazirmatn/Vazirmatn-Medium.woff2') format('woff2');
            font-weight: 500;
            font-display: swap;
        }

        @font-face {
            font-family: 'VazirmatnLogin';
            src: url('/fonts/vazirmatn/Vazirmatn-Bold.woff2') format('woff2');
            font-weight: 700;
            font-display: swap;
        }

        /* The panel theme forces a light page background; this page is dark. */
        body.fi-body:has(.eis-login) {
            background: #141f2a !important;
        }

        /* ── Scoped design tokens (mirrored from storage/app/design/styles.css) ── */
        .eis-login {
            --eis-bg: #f2f2f3;
            --eis-accent: #5980a6;
            --eis-accent-200: #d6ebff;
            --eis-accent-300: #b5d9fd;
            --eis-accent-500: #749dc4;
            --eis-accent-600: #597ea3;
            --eis-accent-700: #416180;
            --eis-accent-900: #1d2d3d;
            --eis-orange: #e0762c;

            --eis-line: rgba(242, 242, 243, 0.28);
            --eis-line-strong: rgba(242, 242, 243, 0.34);

            --eis-space-1: 3.4px;
            --eis-space-2: 6.8px;
            --eis-space-3: 10.2px;
            --eis-space-4: 13.6px;
            --eis-space-6: 20.4px;
            --eis-space-8: 27.2px;

            --eis-radius-md: 4px;
            --eis-radius-lg: 7px;
            --eis-shadow-lg: 0 12px 32px rgba(43, 43, 45, 0.22);

            /* Both resolve to the @font-face blocks above (public/fonts/barlow).
               Plain family names are safe here: nothing else in the project
               declares 'Barlow' or 'Barlow Condensed', and no panel loads them —
               unlike Vazirmatn, which is why that one is aliased. */
            --eis-font-heading: 'Barlow Condensed', system-ui, sans-serif;
            --eis-font-body: 'Barlow', system-ui, sans-serif;
            /* 'VazirmatnLogin' is the @font-face above, served from the project's own
               public/fonts/vazirmatn/*.woff2. Plain 'Vazirmatn' is deliberately NOT in
               this stack: the panel's ->font('Vazirmatn') pulls that family name from
               fonts.bunny.net, and listing it here would let Persian silently fall back
               to the third-party copy. Tahoma is the local Persian-capable last resort. */
            --eis-font-fa: 'VazirmatnLogin', Tahoma, sans-serif;

            position: relative;
            min-height: 100vh;
            display: flex;
            overflow: hidden;
            background: var(--eis-accent-900);
            color: var(--eis-bg);
            font-family: var(--eis-font-body);
            font-size: 15px;
            line-height: 1.55;
        }

        .eis-login *,
        .eis-login *::before,
        .eis-login *::after {
            box-sizing: border-box;
        }

        .eis-login [x-cloak] {
            display: none !important;
        }

        /* Persian presentation: the self-hosted Vazirmatn, forced on every RTL
           descendant so nothing can inherit a Latin face by accident.
           NOTE: dir="rtl" sits on .eis-login itself, so this must be the compound
           selector .eis-login[dir='rtl'] — a descendant form (.eis-login [dir='rtl'])
           would match nothing on this page. */
        .eis-login[dir='rtl'],
        .eis-login[dir='rtl'] * {
            font-family: var(--eis-font-fa) !important;
        }

        .eis-login[dir='rtl'] .eis-tracked {
            letter-spacing: normal !important;
            text-transform: none !important;
        }

        .eis-login[dir='rtl'] .eis-title {
            font-size: clamp(30px, 3.3vw, 50px);
            line-height: 1.35;
        }

        /* Latin-only strings (EN toggle, EIS marks, URL/email, credentials) keep their
           Latin face. Descendants are included so the rule above cannot reach inside
           an .eis-latin element; equal specificity (0,2,0), and this block is declared
           later, so it wins. */
        .eis-login .eis-latin,
        .eis-login .eis-latin * {
            font-family: var(--eis-font-body) !important;
        }

        /* ── Background layers ── */
        .eis-bg {
            position: absolute;
            inset: 0;
        }

        .eis-bg-img {
            position: absolute;
            inset: 0;
            overflow: hidden;
        }

        .eis-bg-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* duotone: tint the photograph toward the steel accent */
        .eis-bg-img::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: var(--eis-accent);
            mix-blend-mode: color;
        }

        .eis-scrim,
        .eis-grid,
        .eis-sweep,
        .eis-vignette {
            position: absolute;
            pointer-events: none;
        }

        .eis-scrim {
            inset: 0;
        }

        .eis-grid {
            inset: 0;
            opacity: 0.14;
            background-image:
                repeating-linear-gradient(0deg, rgba(242, 242, 243, 0.55) 0 1px, transparent 1px 88px),
                repeating-linear-gradient(90deg, rgba(242, 242, 243, 0.55) 0 1px, transparent 1px 88px);
        }

        .eis-sweep {
            inset-inline: 0;
            top: 0;
            height: 34%;
            background: linear-gradient(180deg, transparent, rgba(89, 128, 166, 0.2), transparent);
            animation: eis-sweep 14s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        .eis-vignette {
            inset: 0;
            box-shadow: inset 0 0 220px 60px rgba(10, 17, 24, 0.7);
        }

        /* ── Shell ── */
        .eis-shell {
            position: relative;
            z-index: 1;
            flex: 1;
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
            gap: var(--eis-space-6);
            align-items: center;
            align-content: center;
            /* Outer breathing room. max() so a notched/rounded viewport can only
               ever push the columns further in, never pull them toward the edge. */
            padding: 80px 104px;
            padding-left: max(104px, env(safe-area-inset-left));
            padding-right: max(104px, env(safe-area-inset-right));
            max-width: 1560px;
            margin: 0 auto;
            width: 100%;
        }

        .eis-left {
            display: flex;
            flex-direction: column;
            gap: var(--eis-space-4);
            max-width: 640px;
        }

        /* thin blueprint frame — registration "+" corner marks intentionally omitted */
        .eis-blueprint {
            position: relative;
            border: 1px solid var(--eis-line);
        }

        .eis-logo {
            width: 48px;
            height: 48px;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            border-color: rgba(242, 242, 243, 0.5);
            font-family: var(--eis-font-heading) !important;
            font-weight: 700;
            font-size: 20px;
            letter-spacing: 0.04em;
        }

        .eis-brand-row {
            display: flex;
            align-items: center;
            gap: 16px;
            animation: eis-rise 0.7s cubic-bezier(0.2, 0.7, 0.2, 1) both;
        }

        .eis-brand-name {
            display: block;
            font-family: var(--eis-font-heading);
            font-size: 14px;
            line-height: 1.35;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            overflow-wrap: break-word;
        }

        .eis-brand-country {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            font-size: 11.5px;
            line-height: 1.45;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(242, 242, 243, 0.62);
        }

        .eis-title {
            margin: 0;
            font-family: var(--eis-font-heading);
            font-weight: 600;
            font-size: clamp(34px, 4.2vw, 62px);
            line-height: 1.06;
            letter-spacing: -0.01em;
            color: var(--eis-orange);
            display: flex;
            flex-direction: column;
            animation: eis-rise 0.7s cubic-bezier(0.2, 0.7, 0.2, 1) 0.08s both;
        }

        .eis-lede {
            margin: 0;
            max-width: 46ch;
            font-size: 15px;
            line-height: 1.65;
            color: rgba(242, 242, 243, 0.8);
            text-wrap: pretty;
            animation: eis-rise 0.7s cubic-bezier(0.2, 0.7, 0.2, 1) 0.16s both;
        }

        .eis-divisions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            border-top: 1px solid var(--eis-line);
            border-bottom: 1px solid var(--eis-line);
            animation: eis-rise 0.7s cubic-bezier(0.2, 0.7, 0.2, 1) 0.24s both;
        }

        .eis-division {
            padding: 13px 15px;
            display: flex;
            flex-direction: column;
            gap: 7px;
            min-width: 0;
            border-inline-start: 1px solid var(--eis-line);
            transition: background 0.25s ease;
        }

        .eis-division:hover {
            background: rgba(242, 242, 243, 0.06);
        }

        .eis-division-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            color: rgba(242, 242, 243, 0.6);
        }

        .eis-division-num {
            font-family: var(--eis-font-heading);
            font-size: 12px;
            letter-spacing: 0.18em;
        }

        .eis-division-label {
            font-family: var(--eis-font-heading);
            font-size: 16px;
            line-height: 1.25;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            overflow-wrap: break-word;
        }

        .eis-status-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px 16px;
            font-size: 12px;
            color: rgba(242, 242, 243, 0.6);
            animation: eis-rise 0.7s cubic-bezier(0.2, 0.7, 0.2, 1) 0.32s both;
        }

        .eis-dot {
            width: 7px;
            height: 7px;
            flex: 0 0 auto;
            border-radius: 50%;
            background: var(--eis-accent-300);
            animation: eis-pulse 2.4s ease-in-out infinite;
        }

        .eis-clock-label {
            font-family: var(--eis-font-heading);
            font-size: 11px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(242, 242, 243, 0.5);
        }

        .eis-clock-time {
            font-variant-numeric: tabular-nums;
            font-size: 13px;
            color: rgba(242, 242, 243, 0.85);
        }

        /* ── Public contact (website + email) ── */
        .eis-contact-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px 22px;
            margin-top: var(--eis-space-2);
            padding-top: var(--eis-space-4);
            /* thin rule separating it from the clocks above */
            border-top: 1px solid var(--eis-line);
            animation: eis-rise 0.7s cubic-bezier(0.2, 0.7, 0.2, 1) 0.4s both;
        }

        .eis-contact {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 12px;
            text-decoration: none;
            /* muted, matching the clock row */
            color: rgba(242, 242, 243, 0.6);
            transition: color 0.2s ease;
        }

        .eis-contact svg {
            flex: 0 0 auto;
            opacity: 0.85;
        }

        .eis-login .eis-contact:hover,
        .eis-login .eis-contact:focus-visible {
            color: var(--eis-accent-300) !important;
        }

        /* mobile mirror inside the card — revealed at <=980px */
        .eis-login .contact-mobile {
            display: none;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px 18px;
            margin-top: var(--eis-space-6);
            padding-top: var(--eis-space-4);
            border-top: 1px solid rgba(242, 242, 243, 0.24);
        }

        /* the card footer follows the mobile block, so drop its duplicate top gap */
        .eis-login .contact-mobile + .eis-foot {
            margin-top: var(--eis-space-3);
            padding-top: var(--eis-space-3);
        }

        /* ── Right plate ── */
        .eis-plate {
            position: relative;
            justify-self: end;
            width: 100%;
            max-width: 448px;
            padding: var(--eis-space-6);
            border-color: var(--eis-line-strong);
            border-radius: calc(var(--eis-radius-lg) * 1.7);
            background: linear-gradient(158deg, rgba(36, 54, 73, 0.94) 0%, rgba(19, 29, 39, 0.95) 52%, rgba(23, 35, 47, 0.96) 100%);
            backdrop-filter: blur(10px);
            box-shadow: var(--eis-shadow-lg);
            color: var(--eis-bg);
            animation: eis-plate 0.75s cubic-bezier(0.2, 0.7, 0.2, 1) 0.12s both;
        }

        .eis-plate-fx {
            position: absolute;
            inset: 0;
            z-index: 0;
            border-radius: inherit;
            overflow: hidden;
            pointer-events: none;
        }

        .eis-plate-fx > i {
            position: absolute;
            display: block;
        }

        .eis-plate-glow {
            inset: 0;
            background: radial-gradient(130% 90% at 100% 0%, rgba(89, 128, 166, 0.34), transparent 62%);
        }

        .eis-plate-mesh {
            inset: 0;
            opacity: 0.07;
            background-image:
                repeating-linear-gradient(0deg, rgba(242, 242, 243, 0.9) 0 1px, transparent 1px 24px),
                repeating-linear-gradient(90deg, rgba(242, 242, 243, 0.9) 0 1px, transparent 1px 24px);
        }

        .eis-plate-hair {
            top: 0;
            inset-inline: 12%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(242, 242, 243, 0.7), transparent);
        }

        .eis-plate-orb {
            bottom: -40%;
            inset-inline-start: -20%;
            width: 70%;
            height: 70%;
            background: radial-gradient(circle, rgba(89, 128, 166, 0.22), transparent 65%);
        }

        .eis-plate > *:not(.eis-plate-fx) {
            position: relative;
            z-index: 1;
        }

        .eis-plate-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .eis-kicker {
            display: flex;
            align-items: center;
            gap: 7px;
            font-family: var(--eis-font-heading);
            font-size: 12px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--eis-accent-300);
        }

        /* segmented EN / FA control */
        .eis-seg {
            display: inline-flex;
            overflow: hidden;
            border: 1px solid rgba(242, 242, 243, 0.3);
            border-radius: var(--eis-radius-lg);
        }

        .eis-seg-opt {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            font-size: 13px;
            cursor: pointer;
            color: inherit;
        }

        .eis-seg-opt + .eis-seg-opt {
            border-inline-start: 1px solid rgba(242, 242, 243, 0.3);
        }

        .eis-seg-opt input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
            pointer-events: none;
        }

        .eis-seg-opt:has(input:checked) {
            background: var(--eis-accent);
            color: var(--eis-bg);
        }

        .eis-seg-opt:not(:has(input:checked)):hover {
            background: rgba(242, 242, 243, 0.08);
        }

        .eis-seg-opt:has(input:focus-visible) {
            outline: 2px solid var(--eis-accent-300);
            outline-offset: -2px;
        }

        .eis-signin {
            margin: 0 0 8px;
            font-family: var(--eis-font-heading);
            font-weight: 600;
            font-size: 34px;
            line-height: 1.1;
            color: var(--eis-orange);
        }

        .eis-subtitle {
            margin: 0 0 var(--eis-space-6);
            font-size: 13.5px;
            line-height: 1.6;
            color: rgba(242, 242, 243, 0.62);
        }

        .eis-form {
            display: flex;
            flex-direction: column;
            gap: var(--eis-space-4);
        }

        .eis-field > label {
            display: block;
            font-size: 12px;
            margin-bottom: 5px;
            color: rgba(242, 242, 243, 0.7);
        }

        .eis-control {
            position: relative;
            display: flex;
            align-items: center;
        }

        .eis-control > .eis-lead {
            position: absolute;
            inset-inline-start: 13px;
            display: flex;
            pointer-events: none;
            color: rgba(242, 242, 243, 0.55);
        }

        .eis-input {
            width: 100%;
            min-height: 44px;
            font: inherit;
            font-size: 14px;
            padding: 6px 10px;
            padding-inline-start: 42px;
            color: var(--eis-bg);
            caret-color: var(--eis-accent-300);
            background: rgba(242, 242, 243, 0.06);
            border: 1px solid var(--eis-line-strong);
            border-radius: var(--eis-radius-lg);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .eis-input::placeholder {
            color: rgba(242, 242, 243, 0.38);
        }

        .eis-input:hover {
            border-color: rgba(242, 242, 243, 0.5);
        }

        .eis-input:focus,
        .eis-input:focus-visible {
            outline: none;
            border-color: var(--eis-accent-300);
            box-shadow: 0 0 0 3px rgba(89, 128, 166, 0.28);
            background: rgba(242, 242, 243, 0.1);
        }

        /* email + password stay LTR even in the Persian presentation */
        .eis-input[dir='ltr'] {
            text-align: left;
        }

        .eis-has-trailing .eis-input {
            padding-inline-end: 44px;
        }

        .eis-reveal {
            position: absolute;
            inset-inline-end: 6px;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            cursor: pointer;
            background: transparent;
            border: 1px solid transparent;
            border-radius: var(--eis-radius-md);
            color: rgba(242, 242, 243, 0.72);
            transition: background 0.2s ease, color 0.2s ease;
        }

        .eis-reveal:hover {
            background: rgba(242, 242, 243, 0.14);
            color: var(--eis-bg);
        }

        .eis-hint {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 7px;
            font-size: 11.5px;
            color: var(--eis-accent-200);
            animation: eis-alert 0.25s ease both;
        }

        .eis-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: -2px;
        }

        /* remember: round filled dot + inset ring */
        .eis-check {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        .eis-check input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
            pointer-events: none;
        }

        .eis-check .eis-check-dot {
            width: 16px;
            height: 16px;
            flex: none;
            border-radius: 50%;
            border: 1.5px solid rgba(242, 242, 243, 0.45);
            transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .eis-check:hover .eis-check-dot {
            border-color: var(--eis-accent-300);
        }

        .eis-check input:checked + .eis-check-dot {
            border-color: var(--eis-accent);
            background: var(--eis-accent);
            box-shadow: inset 0 0 0 4px var(--eis-accent-900);
        }

        .eis-check input:focus-visible + .eis-check-dot {
            outline: 2px solid var(--eis-accent-300);
            outline-offset: 2px;
        }

        .eis-check-text {
            font-size: 13px;
            color: rgba(242, 242, 243, 0.85);
        }

        .eis-alert {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            border: 1px solid var(--eis-accent-500);
            border-radius: var(--eis-radius-lg);
            background: rgba(89, 128, 166, 0.18);
            padding: 11px 13px;
            font-size: 13px;
            line-height: 1.55;
            color: var(--eis-bg);
            animation: eis-alert 0.3s cubic-bezier(0.2, 0.7, 0.2, 1) both;
        }

        .eis-alert svg {
            flex: 0 0 auto;
            margin-top: 2px;
        }

        /* primary: solid steel-blue fill, dark accent-900 text */
        .eis-submit {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            min-height: 48px;
            margin-top: var(--eis-space-2);
            cursor: pointer;
            font-family: var(--eis-font-heading);
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--eis-accent-900);
            background: var(--eis-accent);
            border: 1px solid var(--eis-accent);
            border-radius: var(--eis-radius-lg);
            transition: background 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
        }

        /*
           Idle / verifying groups inside the submit button. Only alignment lives
           here — `display` is Livewire's to set (wire:loading.flex /
           wire:loading.remove.flex). The `display: flex` below is just the
           pre-JS default for the idle group; Livewire's own
           [wire\:loading\.flex][wire\:loading\.flex] rule is a doubled attribute
           selector (0,2,0) and so still out-specifies this class (0,1,0) when it
           needs to keep the verifying group hidden at rest.
        */
        .eis-submit-state {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .eis-login[dir='rtl'] .eis-submit {
            letter-spacing: normal;
            text-transform: none;
            font-weight: 700;
        }

        .eis-submit:hover:not(:disabled) {
            background: var(--eis-accent-600);
            box-shadow: 0 8px 26px rgba(89, 128, 166, 0.4);
        }

        .eis-submit:active:not(:disabled) {
            background: var(--eis-accent-700);
            transform: translateY(1px);
        }

        .eis-submit:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .eis-spin {
            display: flex;
            animation: eis-spin 0.8s linear infinite;
        }

        /* One-way cross-panel link (admin -> portal). Deliberately quieter than
           .eis-submit: it is a wrong-door escape hatch, not an action on this
           form. Dashed border marks it as "leaves this page". */
        .eis-altlink {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            width: 100%;
            min-height: 42px;
            /* space-3, not space-2: the "or" divider that used to sit between
               this and the submit button went away with the SSO block, so the
               gap has to carry the separation on its own. Renders on the admin
               login only — the portal has no alternatePanelLink(). */
            margin-top: var(--eis-space-3);
            padding: 10px 14px;
            font-size: 13px;
            line-height: 1.4;
            text-align: center;
            text-decoration: none;
            color: var(--eis-accent-200);
            background: transparent;
            border: 1px dashed var(--eis-line-strong);
            border-radius: var(--eis-radius-lg);
            transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .eis-altlink svg {
            flex: 0 0 auto;
            opacity: 0.9;
        }

        /* The icon's arrow points at the destination, so it mirrors with direction. */
        .eis-login[dir='rtl'] .eis-altlink svg {
            transform: scaleX(-1);
        }

        /* Same !important as .eis-contact: the panel stylesheet sets its own
           anchor colours, and a bare class (0,1,0) loses to them. */
        .eis-login .eis-altlink:hover,
        .eis-login .eis-altlink:focus-visible {
            background: rgba(242, 242, 243, 0.1);
            border-color: var(--eis-accent-300);
            color: var(--eis-bg) !important;
        }

        .eis-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: var(--eis-space-6);
            padding-top: var(--eis-space-4);
            border-top: 1px solid rgba(242, 242, 243, 0.24);
            font-size: 12px;
            color: rgba(242, 242, 243, 0.55);
        }

        .eis-foot span {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .eis-sr {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* ── Motion ── */
        @keyframes eis-rise {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: none; }
        }

        @keyframes eis-plate {
            from { opacity: 0; transform: translateY(22px) scale(0.985); }
            to { opacity: 1; transform: none; }
        }

        @keyframes eis-sweep {
            0% { transform: translateY(-60%); opacity: 0; }
            12% { opacity: 0.5; }
            88% { opacity: 0.5; }
            100% { transform: translateY(160%); opacity: 0; }
        }

        @keyframes eis-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.35; transform: scale(0.82); }
        }

        @keyframes eis-spin {
            to { transform: rotate(360deg); }
        }

        @keyframes eis-alert {
            0% { opacity: 0; transform: translateY(-6px); }
            100% { opacity: 1; transform: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            .eis-login *,
            .eis-login *::before,
            .eis-login *::after {
                animation: none !important;
                transition: none !important;
            }
        }

        /* ── Responsive ── */
        @media (max-width: 1100px) {
            /* Stacks the columns. Padding is deliberately NOT reset here — the
               desktop 80/104 inset carries down to the 980px rule below, so there
               is exactly one padding source per width range. */
            .eis-shell {
                grid-template-columns: minmax(0, 1fr);
                gap: var(--eis-space-8);
                align-content: start;
            }

            .eis-left { max-width: none; }
            .eis-plate { justify-self: stretch; max-width: 520px; }
            .eis-login { min-height: 100%; }
        }

        @media (max-width: 980px) {
            .eis-shell {
                padding: 36px 28px;
            }

            /* Below this width the contact pair moves into the card footer, so the
               left-column copy is suppressed to avoid showing it twice. */
            .eis-contact-row { display: none; }
            .eis-login .contact-mobile { display: flex; }
        }

        @media (max-width: 640px) {
            .eis-divisions { grid-template-columns: minmax(0, 1fr); }
            .eis-signin { font-size: 28px; }
            .eis-plate { padding: var(--eis-space-4); }
        }
    </style>
@endpush

<div
    class="eis-login"
    dir="{{ $isRtlDefault ? 'rtl' : 'ltr' }}"
    x-data="{
        show: false,
        caps: false,
        lang: @js($defaultLang),
        now: new Date(),
        copy: @js($copy),
        dates: @js($updatedLabels),
        tz: @js($timezones),
        get t() { return this.copy[this.lang] },
        get rtl() { return this.lang === 'fa' },
        clock(i) {
            try {
                return new Intl.DateTimeFormat(this.rtl ? 'fa-IR' : 'en-GB', {
                    hour: '2-digit', minute: '2-digit', hour12: false, timeZone: this.tz[i],
                }).format(this.now)
            } catch (e) {
                return '--:--'
            }
        },
        checkCaps(e) {
            this.caps = !!(e.getModifierState && e.getModifierState('CapsLock'))
        },
    }"
    x-init="setInterval(() => now = new Date(), 15000)"
    :dir="rtl ? 'rtl' : 'ltr'"
>
    {{-- ── Background: photo + duotone, scrim, grid, sweep, vignette ── --}}
    <div class="eis-bg" aria-hidden="true">
        <div class="eis-bg-img">
            <img src="/images/bg-tech.png" alt="">
        </div>

        {{-- scrim mirrors with direction so the plate always sits on the light end --}}
        <div
            class="eis-scrim"
            style="background-image: linear-gradient(to {{ $isRtlDefault ? 'left' : 'right' }}, rgba(20,31,42,0.94) 0%, rgba(20,31,42,0.78) 46%, rgba(20,31,42,0.42) 100%);"
            :style="`background-image: linear-gradient(to ${rtl ? 'left' : 'right'}, rgba(20,31,42,0.94) 0%, rgba(20,31,42,0.78) 46%, rgba(20,31,42,0.42) 100%)`"
        ></div>

        <div class="eis-grid"></div>
        <div class="eis-sweep"></div>
        <div class="eis-vignette"></div>
    </div>

    <div class="eis-shell">
        {{-- ═══════════ Left column ═══════════ --}}
        <div class="eis-left">
            <div class="eis-brand-row">
                {{-- logo tile: thin frame only, no registration marks --}}
                <div class="eis-blueprint eis-logo eis-latin">EIS</div>

                <div style="display: flex; flex-direction: column; gap: 5px; min-width: 0; flex: 1 1 auto;">
                    <span class="eis-brand-name eis-tracked" x-text="t.brand">{{ $t['brand'] }}</span>

                    <span class="eis-brand-country eis-tracked">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="flex: 0 0 auto; margin-top: 2px;" aria-hidden="true"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <span style="display: block; line-height: 1.45;" x-text="t.country">{{ $t['country'] }}</span>
                    </span>
                </div>
            </div>

            {{--
                Title line-count differs per language (EN wraps to 3 lines, FA is
                one), so the two variants are rendered as separate blocks rather
                than swapped word-by-word. x-cloak hides the non-default one
                until Alpine boots, so neither flashes.
            --}}
            <h1 class="eis-title">
                <span
                    x-show="lang === 'en'"
                    @if ($defaultLang !== 'en') x-cloak @endif
                    style="display: contents;"
                >
                    @foreach ((array) $tr('title', 'en') as $line)
                        <span>{{ $line }}</span>
                    @endforeach
                </span>

                <span
                    x-show="lang === 'fa'"
                    @if ($defaultLang !== 'fa') x-cloak @endif
                    style="display: contents;"
                >
                    @foreach ((array) $tr('title', 'fa') as $line)
                        <span>{{ $line }}</span>
                    @endforeach
                </span>
            </h1>

            <p class="eis-lede" x-text="t.lede">{{ $t['lede'] }}</p>

            <div class="eis-divisions">
                @foreach ($t['divisions'] as $i => $division)
                    <div class="eis-division">
                        <div class="eis-division-top">
                            <span class="eis-division-num" x-text="t.nums[{{ $i }}]">{{ $t['nums'][$i] ?? '' }}</span>
                            {{-- SEAM: icon named by the page class, drawn from $divisionIconPaths. --}}
                            <span style="display: flex;" aria-hidden="true">
                                @if ($path = $divisionIconPaths[$divisionIcons[$i] ?? ''] ?? null)
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $path !!}</svg>
                                @endif
                            </span>
                        </div>
                        <span class="eis-division-label eis-tracked" x-text="t.divisions[{{ $i }}]">{{ $division }}</span>
                    </div>
                @endforeach
            </div>

            <div class="eis-status-row">
                <span style="display: flex; align-items: center; gap: 8px;">
                    <span class="eis-dot" aria-hidden="true"></span>
                    <span style="display: block; line-height: 1.45;" x-text="t.status">{{ $t['status'] }}</span>
                </span>

                <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                    <span style="display: flex; align-items: center; gap: 7px; color: rgba(242,242,243,0.5);" aria-hidden="true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </span>

                    @foreach ($t['zones'] as $i => $zone)
                        <span style="display: flex; align-items: baseline; gap: 7px;">
                            <span class="eis-clock-label eis-tracked" x-text="t.zones[{{ $i }}]">{{ $zone }}</span>
                            <span class="eis-clock-time" x-text="clock({{ $i }})">{{ $initialClocks[$i] }}</span>
                        </span>
                    @endforeach
                </div>
            </div>

            {{--
                Public company contact. Real data — deliberately NOT routed through
                __(): a URL and a mailbox are the same in every language. dir="ltr"
                + .eis-latin so they stay Latin-shaped inside the RTL column.
                Mirrored in .contact-mobile below; this copy hides under 980px.
            --}}
            <div class="eis-contact-row">
                <a href="https://www.eisindustry.com" target="_blank" rel="noopener noreferrer" dir="ltr" class="eis-contact eis-latin">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path><path d="M2 12h20"></path></svg>
                    www.eisindustry.com
                </a>

                <a href="mailto:info@eisindustry.com" dir="ltr" class="eis-contact eis-latin">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                    info@eisindustry.com
                </a>
            </div>
        </div>

        {{-- ═══════════ Right plate ═══════════ --}}
        <div class="eis-blueprint eis-plate">
            <div class="eis-plate-fx" aria-hidden="true">
                <i class="eis-plate-glow"></i>
                <i class="eis-plate-mesh"></i>
                <i class="eis-plate-hair"></i>
                <i class="eis-plate-orb"></i>
            </div>

            <div class="eis-plate-head">
                <span class="eis-kicker eis-tracked">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path></svg>
                    <span x-text="t.kicker">{{ $t['kicker'] }}</span>
                </span>

                {{--
                    Presentation-only language switch. Swaps copy + direction in
                    the browser; the app locale, session and server stay on fa.
                --}}
                <div class="eis-seg" role="group" aria-label="{{ $tr('lang_group', $defaultLang) }}">
                    <label class="eis-seg-opt eis-latin">
                        <input type="radio" name="eis-lang" value="en" x-model="lang" @checked($defaultLang === 'en')>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 6 6"></path><path d="m4 14 6-6 2-3"></path><path d="M2 5h12"></path><path d="M7 2h1"></path><path d="m22 22-5-10-5 10"></path><path d="M14 18h6"></path></svg>
                        <span>{{ $tr('lang_en', $defaultLang) }}</span>
                    </label>

                    <label class="eis-seg-opt">
                        <input type="radio" name="eis-lang" value="fa" x-model="lang" @checked($defaultLang === 'fa')>
                        <span>{{ $tr('lang_fa', $defaultLang) }}</span>
                    </label>
                </div>
            </div>

            <h2 class="eis-signin" x-text="t.signIn">{{ $t['signIn'] }}</h2>
            <p class="eis-subtitle" x-text="t.subtitle">{{ $t['subtitle'] }}</p>

            {{-- The only functional part: posts to Filament's inherited authenticate(). --}}
            <form class="eis-form" wire:submit="authenticate">
                <div class="eis-field">
                    <label for="eis-email" x-text="t.userLabel">{{ $t['userLabel'] }}</label>

                    <div class="eis-control">
                        <span class="eis-lead" aria-hidden="true">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                        </span>

                        <input
                            id="eis-email"
                            class="eis-input eis-latin"
                            type="text"
                            dir="ltr"
                            autocomplete="username"
                            wire:model="data.email"
                            placeholder="{{ $t['userPlaceholder'] }}"
                            :placeholder="t.userPlaceholder"
                            required
                        >
                    </div>

                    @error('data.email')
                        <div class="eis-alert" style="margin-top: 9px;" role="alert">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <div class="eis-field">
                    <label for="eis-password" x-text="t.passLabel">{{ $t['passLabel'] }}</label>

                    <div class="eis-control eis-has-trailing">
                        <span class="eis-lead" aria-hidden="true">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </span>

                        <input
                            id="eis-password"
                            class="eis-input eis-latin"
                            type="password"
                            :type="show ? 'text' : 'password'"
                            dir="ltr"
                            autocomplete="current-password"
                            wire:model="data.password"
                            placeholder="••••••••"
                            x-on:keyup="checkCaps($event)"
                            x-on:keydown="checkCaps($event)"
                            required
                        >

                        <button
                            type="button"
                            class="eis-reveal"
                            x-on:click="show = !show"
                            :aria-label="show ? t.hide : t.show"
                            :title="show ? t.hide : t.show"
                            aria-label="{{ $t['show'] }}"
                        >
                            <svg x-show="!show" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.06 12.35a1 1 0 0 1 0-.7 10.75 10.75 0 0 1 19.88 0 1 1 0 0 1 0 .7 10.75 10.75 0 0 1-19.88 0"></path><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0"></path></svg>
                            <svg x-show="show" x-cloak width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.73 5.08a10.74 10.74 0 0 1 11.21 6.57 1 1 0 0 1 0 .7 10.75 10.75 0 0 1-1.45 2.49"></path><path d="M14.08 14.16a3 3 0 0 1-4.24-4.24"></path><path d="M17.48 17.5a10.75 10.75 0 0 1-15.42-5.15 1 1 0 0 1 0-.7 10.75 10.75 0 0 1 4.45-5.14"></path><path d="m2 2 20 20"></path></svg>
                        </button>
                    </div>

                    <span class="eis-hint" x-show="caps" x-cloak role="status">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>
                        <span x-text="t.caps"></span>
                    </span>

                    @error('data.password')
                        <div class="eis-alert" style="margin-top: 9px;" role="alert">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                {{--
                    "Forgot password?" is intentionally absent: this panel does not
                    enable ->passwordReset(), so filament()->hasPasswordReset() is
                    false and there is no reset route to link to. If password reset
                    is enabled later, restore the link here using
                    filament()->getRequestPasswordResetUrl().
                --}}
                <div class="eis-row">
                    <label class="eis-check">
                        <input type="checkbox" wire:model="data.remember">
                        <span class="eis-check-dot"></span>
                        <span class="eis-check-text" x-text="t.remember">{{ $t['remember'] }}</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="eis-submit"
                    wire:loading.attr="disabled"
                    wire:target="authenticate"
                >
                    {{--
                        Livewire owns the visibility of these two spans — do NOT put an
                        inline `style="display:..."` on them. It hides the loading span
                        with a stylesheet rule ([wire\:loading][wire\:loading]{display:none})
                        and any inline display beats that rule, which is what made
                        "verifying" show permanently. The .flex modifier also tells
                        Livewire to restore `flex` instead of its `inline-block` default
                        when it shows an element again after a request.
                    --}}
                    <span class="eis-submit-state" wire:loading.remove.flex wire:target="authenticate">
                        <span x-text="t.submit">{{ $t['submit'] }}</span>

                        {{-- arrow mirrors with direction --}}
                        <svg x-show="!rtl" @if ($isRtlDefault) x-cloak @endif width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                        <svg x-show="rtl" @if (! $isRtlDefault) x-cloak @endif width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"></path><path d="m12 19-7-7 7-7"></path></svg>
                    </span>

                    <span class="eis-submit-state" wire:loading.flex wire:target="authenticate">
                        <span x-text="t.verifying">{{ $t['verifying'] }}</span>
                        <span class="eis-spin" aria-hidden="true">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.22-8.56"></path></svg>
                        </span>
                    </span>
                </button>

                {{--
                    SEAM — one-way cross-panel link. Rendered only where
                    alternatePanelLink() returns a target: today that is the admin
                    console pointing at the portal, and nothing points back.
                    The href comes from Panel::getLoginUrl() on the page class, so
                    it tracks the target panel's ->path() instead of being frozen
                    into this markup.
                --}}
                @if ($alternate)
                    <a href="{{ $alternate['url'] }}" class="eis-altlink">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 11h-6"></path><path d="m19 8 3 3-3 3"></path></svg>
                        <span x-text="t.altPanel">{{ $t['altPanel'] }}</span>
                    </a>
                @endif
            </form>

            {{--
                Mobile mirror of .eis-contact-row. Hidden by default; shown only at
                <=980px, the same breakpoint that hides the left-column copy — so
                exactly one of the two is on screen at any width.
            --}}
            <div class="contact-mobile">
                <a href="https://www.eisindustry.com" target="_blank" rel="noopener noreferrer" dir="ltr" class="eis-contact eis-latin">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path><path d="M2 12h20"></path></svg>
                    www.eisindustry.com
                </a>

                <a href="mailto:info@eisindustry.com" dir="ltr" class="eis-contact eis-latin">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                    info@eisindustry.com
                </a>
            </div>

            <div class="eis-foot">
                {{-- version-updated date — config('app.updated_at') via APP_UPDATED_AT --}}
                <span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path></svg>
                    {{-- label + date kept in ONE text node so bidi orders the colon correctly in RTL --}}
                    <span x-text="`${t.lastUpdated}: ${dates[lang]}`">{{ $t['lastUpdated'] }}: {{ $updatedLabels[$defaultLang] }}</span>
                </span>

                <span class="eis-latin">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2"></rect><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    EIS · {{ $version }}
                </span>
            </div>
        </div>
    </div>
</div>
