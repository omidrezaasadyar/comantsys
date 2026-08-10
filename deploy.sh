#!/usr/bin/env bash
#
# deploy.sh — production deploy for COMANTSYS.
#
# Safe to run every time: each conditional step is a no-op unless the
# relevant files actually changed in the commits just pulled.
#
# Usage:  cd /opt/stacks/comantsys-prod && ./deploy.sh
#
set -euo pipefail

PROD_DIR="/opt/stacks/comantsys-prod"
BACKUP_DIR="backups"
DC_APP=(docker compose exec -T -u appuser app)   # app container, as appuser
DC_DB=(docker compose exec -T db)                # postgres container

# ── Summary bookkeeping ───────────────────────────────────────────────
# Every step records itself so the run ends with an honest report of
# what actually happened.
SUMMARY=()
ran()     { SUMMARY+=("  [RAN]     $1"); }
skipped() { SUMMARY+=("  [skipped] $1"); }
say()     { printf '\n==> %s\n' "$1"; }
die()     { printf 'ERROR: %s\n' "$1" >&2; exit 1; }

# ── 1. Safety guard: production stack only ────────────────────────────
# Prevents an accidental run against the dev stack (comantsys-dev),
# which shares the same compose service names and would happily
# "deploy" onto development data.
CURRENT_DIR="$(pwd -P)"
if [[ "$CURRENT_DIR" != "$PROD_DIR" ]]; then
    die "this script must be run from $PROD_DIR
       current directory: $CURRENT_DIR
       Refusing to deploy."
fi

# ── 2. Preflight checks ───────────────────────────────────────────────
[[ -d .git ]]          || die "no .git here — is $PROD_DIR really the repo root?"
[[ -f compose.yaml ]]  || die "compose.yaml not found in $CURRENT_DIR."

# A dirty tree means someone hand-edited files on the server; pulling on
# top of that either fails halfway or silently buries their changes.
if [[ -n "$(git status --porcelain)" ]]; then
    die "working tree is not clean. Commit, stash, or revert local changes first:
$(git status --short)"
fi

# The stack must already be up — this script deploys code, it does not
# start containers.
if ! docker compose ps --status running --services | grep -qx app; then
    die "the 'app' service is not running. Start the stack first: docker compose up -d"
fi

# ── 3. Pull ───────────────────────────────────────────────────────────
OLD_COMMIT="$(git rev-parse HEAD)"
say "Pulling (current commit: ${OLD_COMMIT:0:8})"
git pull --ff-only
NEW_COMMIT="$(git rev-parse HEAD)"

# ── 4. Work out what changed ──────────────────────────────────────────
if [[ "$OLD_COMMIT" == "$NEW_COMMIT" ]]; then
    say "Already up to date — no new commits."
    CHANGED=""
    CHANGED_NODEL=""
else
    say "Updated ${OLD_COMMIT:0:8}..${NEW_COMMIT:0:8}"
    CHANGED="$(git diff --name-only "$OLD_COMMIT" "$NEW_COMMIT")"
    # Deletions excluded: a removed migration is not something to run.
    CHANGED_NODEL="$(git diff --name-only --diff-filter=ACMR "$OLD_COMMIT" "$NEW_COMMIT")"
    printf '%s\n' "$CHANGED" | sed 's/^/    /'
fi

# Returns 0 when any changed path matches the given extended regex.
touched()       { [[ -n "$CHANGED"       ]] && grep -qE "$1" <<<"$CHANGED"; }
touched_nodel() { [[ -n "$CHANGED_NODEL" ]] && grep -qE "$1" <<<"$CHANGED_NODEL"; }

# ── 5. PHP dependencies ───────────────────────────────────────────────
if touched '^src/composer\.(json|lock)$'; then
    say "composer install (production)"
    "${DC_APP[@]}" composer install --no-dev --optimize-autoloader
    ran "composer install --no-dev --optimize-autoloader"
else
    skipped "composer install (composer.json/lock unchanged)"
fi

# ── 6. Frontend assets ────────────────────────────────────────────────
# Trigger is deliberately broad (all of src/resources/, Blade included):
# an unnecessary rebuild costs minutes, a missed one ships stale assets.
if touched '^src/(package\.json|package-lock\.json|vite\.config\.js)$|^src/resources/'; then
    say "npm ci && npm run build"
    "${DC_APP[@]}" npm ci
    "${DC_APP[@]}" npm run build
    ran "npm ci + npm run build"
else
    skipped "asset build (package files / resources unchanged)"
fi

# ── 7. Database migrations (backup first, always) ─────────────────────
if touched_nodel '^src/database/migrations/'; then
    # 7a. Timestamped custom-format dump, taken BEFORE any schema change.
    #     Written to .part first so a failed dump can never be mistaken
    #     for a usable backup.
    mkdir -p "$BACKUP_DIR"
    chmod 700 "$BACKUP_DIR"
    DUMP="$BACKUP_DIR/pre-migrate-$(date +%Y%m%d-%H%M%S).dump"

    say "Backing up the database to $DUMP"
    if ! "${DC_DB[@]}" sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc' > "$DUMP.part"; then
        rm -f "$DUMP.part"
        die "pg_dump failed — aborting BEFORE migrating. Database untouched."
    fi
    [[ -s "$DUMP.part" ]] || { rm -f "$DUMP.part"; die "pg_dump produced an empty file — aborting before migrating."; }
    mv "$DUMP.part" "$DUMP"
    chmod 600 "$DUMP"
    ran "database backup -> $DUMP ($(du -h "$DUMP" | cut -f1))"

    # 7b. Only now touch the schema.
    say "Running migrations"
    "${DC_APP[@]}" php artisan migrate --force
    ran "php artisan migrate --force"
else
    skipped "migrations + pre-migrate backup (no migration files changed)"
fi

# ── 8. Cache rebuild + worker restart (always) ────────────────────────
# Unconditional: config/route/view caches must match the code on disk,
# and stale caches are the classic post-deploy failure.
say "Rebuilding production caches"
"${DC_APP[@]}" php artisan optimize:clear
"${DC_APP[@]}" php artisan config:cache
"${DC_APP[@]}" php artisan route:cache
"${DC_APP[@]}" php artisan view:cache
ran "optimize:clear + config:cache + route:cache + view:cache"

# The 'worker' container runs a long-lived queue:work process that holds
# the OLD code in memory. This tells it to finish the current job and
# exit; the container's restart policy brings it back on the new code.
say "Restarting queue workers"
"${DC_APP[@]}" php artisan queue:restart
ran "php artisan queue:restart"

# ── 9. Summary ────────────────────────────────────────────────────────
say "Deploy summary"
printf '  commit:   %s -> %s\n' "${OLD_COMMIT:0:8}" "${NEW_COMMIT:0:8}"
printf '%s\n' "${SUMMARY[@]}"
printf '\nDone.\n'
