# COMANTSYS — Project Guide (Source of Truth)

> This file reflects the **current state** of the project, not a log of how we got here. Anything no longer true must be **corrected or removed**, not appended to. History lives in `git log`; this file is a snapshot.
>
> Communication language with the developer is **Persian**; this file is kept in **English** for machine-comprehension fidelity, tooling compatibility (git / Claude Code), and consistency with the code identifiers.

---

## 1. Working Rules (apply every session)

- **Step by step:** one action per message; wait for explicit confirmation before the next step. This is a firm preference.
- **Full file path** at the top of every code block.
- **Language split:** chat in Persian; terminal commands, code, and file paths in English.
- **Code is written with Claude Code**, run as `appuser` **inside the container**. **Git is run from the host terminal.** Never paste PHP into a shell.
- **Verify before commit:** after any edit, `cat`/`grep` the file from the terminal — never assume an edit took effect.
- **Engineering-first answers:** professional, precise, with trade-off reasoning. The developer accepts engineering trade-offs when the reasoning is laid out.
- **Filament v5 API:** never trust internet docs; always `grep` the vendor source before writing an API call.
- The developer never shares passwords in chat; GUI guidance is given screen-by-screen from screenshots.

---

## 2. Purpose & Context

A Persian-language internal corporate management system for running operations across multiple companies (Iranian and European/Omani), with visibility into sales, costs, invoices, and procurement. Private, production-critical project; the developer is the sole developer and decision-maker. The system now supports multiple human users with per-role access control (see §4/§5).

---

## 3. Stack & Environment

- **Backend:** Laravel 13 + Filament v5.6.7 + PHP 8.4
- **Database:** PostgreSQL 17 (bind to `127.0.0.1` only, never a public port)
- **Frontend:** Tailwind v4 + Vite
- **Access management:** `bezhansalleh/filament-shield ^4.3` on `spatie/laravel-permission`
- **Hosting:** Netcup RS 4000 G12 (Debian, hostname `Inducxel-G12`, Tailscale `100.67.165.84`). Two Docker stacks under `/opt/stacks/`: `comantsys-dev` and `comantsys-prod`. Caddy reverse proxy at `/opt/stacks/proxy`. Co-hosted ERPNext, n8n, xray must not be disrupted.
- **URLs:** dev → `http://100.67.165.84:8096` (Tailscale-only); prod → `https://portal.eisindustry.com`
- **Git:** `git@github.com:omidrezaasadyar/comantsys.git` — SSH, ed25519, no passphrase. Repo root is the **stack** dir (`/opt/stacks/comantsys-dev`); app code lives under `src/`.
- **Container user:** `appuser` (UID/GID = 1000).

### Environment workflow (important — this is how the two layers split)

- **VS Code** connects via **Remote-SSH to the host** (not a Dev Containers attach).
- **Git** lives on the host at `/opt/stacks/comantsys-dev/src`. Run **all git commands there** — the container does **not** see `.git`.
- **App commands** (artisan / composer / claude) run **inside the container**: `docker compose exec -u appuser app <cmd>`. Launch Claude Code with `docker compose exec -u appuser app bash` then `claude`.
- **Claude Code is installed in the dev image** (before `USER appuser`), so files it creates are owned by `appuser`. Always run it **as appuser**; after launch, `pwd` must be under `/var/www/html`. Do **not** use the VS Code Claude Code *extension* to create files — on this setup it runs on the host as root and produces root-owned files.
- **CLAUDE.md lives at `src/CLAUDE.md`** (the Laravel app root = `/var/www/html`) so Claude Code auto-loads it every session from inside the container.
- **Deploy flow:** edit + test in dev → commit/push from host → on prod: `git pull` + `php artisan optimize:clear` (+ run migrations when the schema changed).

---

## 4. Completed Modules

- **Suppliers** — with Relation Managers for contacts, parts, attachments.
- **Sales** — cost Repeater, currency handling, attachments.
- **Invoices / Proforma** — full PDF generation (spec below).
- **Inquiries (استعلام)** — items Repeater, Jalali datepicker, Persian i18n; per-record `direction` and `calendar` (see §5).
- **Sourcing (تأمین‌یابی)**, **Customers**, **Companies**.
- **Access Control (RBAC)** — Filament Shield: roles + permissions + policies enforcing **per-operation** access; `super_admin` role bypasses all checks. **User management** via `UserResource` — create/edit users and assign roles entirely in-panel (no terminal).
- **Read-only View pages** — dedicated Infolist + `ViewRecord` page + table `ViewAction`, done on: **Suppliers, Customers, Sourcing, Companies, Inquiries**. *(Sales & Invoices pending — see §7.)*

### Invoices/Proforma PDF spec
- **Render engine:** Browsershot/Chromium (replaced mPDF). Chromium/Chrome-for-Testing installed permanently in the Dockerfile before `USER appuser`.
- **Font:** Vazirmatn embedded as base64 in the HTML.
- **Persian numerals:** full rendering, with `$fa` / `$faStr` / `$jdate` separation.
- **Barcode:** milon/barcode, C128. **QR:** simplesoftwareio, SVG (no imagick).
- **Watermark:** faint, diagonal. **Template:** rounded corners.
- **Logo/stamp:** files live on the private disk (`storage/app/private`), not `public`.
- **Currency label:** dynamic Persian map (EUR/USD/GBP/IRR), not hardcoded to "ریال".

---

## 5. Locked Architectural Decisions

- **Multilingual (i18n):** UI labels are translatable and must come through `lang/` via `__()`, never hardcoded. **User-entered data is NOT translated** — stored as written, outside the i18n layer.
- **Access control model:** **operation-level** (view / create / update / delete), **role-based**, via Filament Shield. Users are assigned roles in-panel. **Permissions and roles live in the DB, not in git** — regenerate/assign separately on each environment.
- **User model:** implements `FilamentUser`, uses spatie `HasRoles` trait, `canAccessPanel(): bool` returns `true`.
- **Jalali/Gregorian:** DB stores **Gregorian**, UI displays **Jalali**. Forms: `->jalali()` on DatePickers. Tables: `->jalaliDate()` / `->jalaliDateTime()` on `TextColumn`. Infolists: same macros work on `TextEntry` (from `ariaieboy/filament-jalali`). Display is currently **always Jalali** (not conditional) for table/view consistency.
- **Inquiries direction/calendar:** `direction` (inbound/outbound) and `calendar` (jalali/gregorian) columns + nullable `company_id` FK; `Company.locale` (`fa`/`en`) is the single source of truth for calendar decisions.
- **Navigation groups:** business modules under «فروش و تأمین»; `UserResource` + Shield Roles unified under «مدیریت کاربری» (Shield's group set by overriding the `nav.group` key in `lang/vendor/filament-shield/fa/filament-shield.php`).
- **Tax:** per-row `round()` for form/model/PDF consistency.
- **Soft deletes** removed from Invoice (hard delete with confirmation). **Company delete guard:** `deleting` hook blocks deletion when active invoices exist.
- **`verify_url_base`** on Company (for QR URLs).

---

## 6. Pitfalls & Lessons Learned (highest-value section)

- **Infolist `RepeatableEntry` has NO `relationship()`** (unlike the form `Repeater`). Bind by state path: `RepeatableEntry::make('items')`. Rows resolve model accessors (e.g. `unit_label`).
- **Infolist `TextEntry` has NO `boolean()`** (that's `IconEntry`). Render booleans via `->badge()->formatStateUsing(...)->color(...)`.
- **Shield, per NEW resource:** run `php artisan shield:generate --resource=XResource --option=permissions --ignore-existing-policies --panel=admin` then `optimize:clear`, so new permissions show up on the Roles page. Per-new-resource dev work, never per-user.
- **Run Claude Code as `appuser` inside the container** or files come out root-owned. The VS Code Claude Code *extension* runs on the host as root — do not use it to create files.
- **Git is on the host, invisible inside the container** — commit from a host terminal.
- **`ImageEntry` on the private disk:** `->disk('local')`; images serve via signed `temporaryUrl` (the local disk has `'serve' => true`).
- **`dehydrated(false)` silently drops fields** from `$data` — never on real DB columns. For "required-on-create, optional-on-edit" password, use conditional `dehydrated(fn ($state) => filled($state))`.
- **`$fillable` corruption:** cast definitions placed inside `$fillable` make `fill()` silently ignore fields — verify by `cat`-ing the full model.
- **`saving` vs `saved` hooks for Repeater data:** `saving` computes from form input; `saved` reads costs from DB after the Repeater writes, then `saveQuietly()` to avoid a loop.
- **`Section->icon()` does not exist in this Filament v5** — use `->beforeLabel(Icon::make(Heroicon::...))`.
- **Full-width dashboard** needs a custom `App\Filament\Pages\Dashboard` overriding `getMaxContentWidth()`.
- **Docker volume mount** can come up empty after a restart — fix: `docker compose down && docker compose up -d`.

---

## 7. Current State & Open Items

> The only section that changes often.

**View pages (finish the pattern):**
- DONE: Suppliers, Customers, Sourcing, Companies, Inquiries.
- REMAINING: **Sales, Invoices** — both have Repeaters → use `RepeatableEntry`; money fields need thousands formatting; Sales exchange-rate fields conditional on `currency != IRR`.

**Data debt:**
- Uploaded files (company **logos/stamps**) were not migrated to the server → render empty in the view and the table image column until re-uploaded (Edit form) or restored.

**Prod rollout of RBAC (pending, careful):**
- Not just `git pull`. Order: backup prod DB → `git pull` → run the Shield migration → generate permissions → assign `super_admin` on prod → `optimize:clear`.

**Security / infra:**
- **Rotate the database password** — exposed in an earlier session; oldest open item.

**Invoices module:**
- PDF header alignment (national-ID / economic-code boxes vs. barcode).
- English/LTR invoice template for foreign customers.

**On the horizon:**
- AI sourcing agent inside the Inquiries module (swappable `LlmProviderInterface`; Phase 1: Gemini 2.5 Flash + Brave Search + Tesseract OCR).
- Regular automated prod DB backups.
