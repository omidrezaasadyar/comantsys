# ~/projects/comantsys/CLAUDE.md

# COMANTSYS — Project Guide (Source of Truth)

> This file reflects the **current state** of the project, not a log of how we got here. Anything no longer true must be **corrected or removed**, not appended to.
>
> Communication language with the developer is **Persian**; this file is kept in **English** for machine-comprehension fidelity, tooling compatibility (git / Claude Code), and consistency with the code identifiers.

---

## 1. Working Rules (apply every session)

- **Step by step:** one action per message; wait for explicit confirmation before the next step. This is a firm preference.
- **Full file path** at the top of every code block.
- **Language split:** chat in Persian on claude.ai; in Claude Code terminal sessions respond in English (the terminal cannot render RTL text); terminal commands and code are always in English.
- **PHP goes in VS Code, bash goes in the terminal.** Recurring pattern: PHP accidentally pasted into the terminal causes bash syntax errors — flag when switching between the two.
- **Engineering-first answers:** professional, precise, with trade-off reasoning. The developer accepts engineering trade-offs when the reasoning is laid out.
- **Filament v5 API:** never trust internet docs; always `grep` the vendor source before writing an API call.
- The developer never shares passwords in chat; GUI guidance is given screen-by-screen from screenshots.
- **End of every working session:** update the "Current State & Open Items" section of this file to reflect what changed. Keep it a snapshot, not a log.

---

## 2. Purpose & Context

A Persian-language internal corporate management system for running operations across multiple companies (Iranian and European), with visibility into sales, costs, and profits. Private project; the developer is the sole developer and decision-maker.

---

## 3. Stack & Environment

- **Backend:** Laravel 13 + Filament v5.6.7 + PHP 8.4
- **Database:** PostgreSQL 17
- **Frontend:** Tailwind v4 + Vite
- **Environment:** Docker on WSL2/Ubuntu (Windows 11 Pro)
- **Project path:** `~/projects/comantsys` inside the Ubuntu filesystem (deliberate decision for I/O performance)
- **Git:** `git@github.com:omidrezaasadyar/comantsys.git` — SSH, ed25519 key, no passphrase
- **Container user:** `appuser` (UID/GID = 1000, matching host) to avoid root-owned files
- **Editor:** VS Code on Windows, editing files in the WSL2/Ubuntu filesystem

---

## 4. How to Run & Verify

**Repo layout:** the Laravel application lives in `src/` (repo root contains only `CLAUDE.md`, `Dockerfile`, `compose.yaml`, `nginx/`, `src/`). All `composer.json` / `artisan` / `vendor` paths are under `src/`.

**PHP is NOT installed on the host.** Every PHP/artisan/composer command must run inside the `app` container:

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose exec app composer require <pkg>
```

**Docker services:** `app` (PHP-FPM), `db` (PostgreSQL 17), `web` (nginx). Compose file is `compose.yaml` (not `docker-compose.yml`). Start/stop from the repo root:

```bash
docker compose up -d        # start
docker compose down && docker compose up -d   # reliable restart (volume-mount pitfall)
```

**Frontend assets build automatically at container start.** `docker/entrypoint.sh` (runs as `appuser`, PID 1, before `php-fpm`) runs `npm run build` when assets are stale/missing, then execs `php-fpm`. No manual `npm run build` per session. It lives outside `/var/www/html` so the `./src` bind mount can't overlay it.

**Viewing the app:** `http://localhost:8095` in the Windows browser (port bound to `127.0.0.1` only — not reachable from outside the machine).

**Verification is currently manual:** check behavior in the browser and inspect DB state via artisan/tinker inside the container. There is no automated test suite yet. After every file edit, verify the change landed with `cat`/`grep` — never assume a save or paste succeeded.

---

## 5. Completed Modules

- **Suppliers:** with Relation Managers for contacts, parts, and attachments.
- **Sales:** with a cost Repeater, currency handling, attachments.
- **Invoices / Proforma:** full PDF generation — details below.

### Invoices/Proforma module status
Largely complete. Current PDF output spec:

- **Render engine:** Browsershot/Chromium (replaced mPDF, which couldn't render Persian correctly). Chromium is installed permanently in the Dockerfile, before `USER appuser`.
- **Font:** Vazirmatn embedded as base64 in the HTML (for Chromium rendering).
- **Persian numerals:** full rendering, with `$fa` / `$faStr` / `$jdate` separation.
- **Barcode:** milon/barcode, C128 format.
- **QR:** simplesoftwareio, SVG format (no imagick needed).
- **Watermark:** faint, diagonal.
- **Template:** redesigned with rounded corners.
- **Logo/stamp:** files live in `storage/app/private`, not `public` (this was the source of a bug).

---

## 6. Locked Architectural Decisions

- **Multilingual (i18n) design — IMPORTANT:** The software must ultimately be multilingual. Primary language is currently Persian.
  - **UI labels are translatable** and must come through translation files (`lang/`), never hardcoded. Wrap all interface strings in `__()`; build every module i18n-ready from the start.
  - **User-entered data is NOT translated.** Business records/content are stored as written, in whatever language the user types. This is outside the i18n layer and never gets `__()`.
- **Tax:** computed with per-row `round()`, for form/model/PDF consistency.
- **Soft deletes:** `softDeletes` removed from the Invoice model — hard delete with a Filament confirmation.
- **Company delete guard:** a `deleting` hook on the Company model blocks deletion if active invoices exist (`CompanyHasInvoicesException`).
- **Navigation group** renamed to «فروش و تأمین».
- **`verify_url_base` field** added to Company (for QR URLs).

---

## 7. Pitfalls & Lessons Learned (highest-value section)

- **`dehydrated(false)` on Shamsi date fields removes them from `$data`** — root cause of the inquiry-date bug.
- **`$fillable` corruption:** accidentally placing cast definitions (`'field' => 'type'`) as key-value pairs inside `$fillable` makes Laravel's `fill()` silently ignore those fields — very hard to debug without reading the full model with `cat`.
- **`saving` vs `saved` hooks for Repeater data:** the `saving` hook computes revenue/totals from form input; the `saved` hook reads costs from the DB after the Repeater writes them, then uses `saveQuietly()` to avoid an infinite loop.
- **CSS in Filament v5:** dark-mode overrides need scoped selectors like `.dark .fi-X` with `!important`. `extraAttributes(['class'=>'...'])` puts the class on the element itself, not a wrapper.
- **`Section->icon()` does not exist in this Filament v5 version** — workaround: `->beforeLabel(Icon::make(Heroicon::...))`.
- **Full-width dashboard:** requires a custom `App\Filament\Pages\Dashboard` class overriding `getMaxContentWidth()`; the panel-level setting does not affect the dashboard.
- **Docker volume mount** sometimes comes up empty after a system restart — reliable fix: `docker compose down && docker compose up -d`.
- **Heredoc blocks pasted into the terminal** write the literal text instead of executing — use `printf` to append CSS; edit PHP files directly in VS Code.

---

## 8. Current State & Open Items

> The only section that changes often. Update it at the end of every working session.

**Remaining in the Invoices module:**
- English/LTR invoice template for foreign customers.

**General debt:**
- **Rotate the database password** (security hygiene).
- `compose.yaml` comment says the web port binds to the Tailscale IP, but the actual value is `127.0.0.1` — reconcile comment with reality (or intent).

**On the horizon (scope not yet defined):**
- Further module development.
</markdown>