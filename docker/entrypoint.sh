#!/usr/bin/env bash
# Container START-time steps. Runs as appuser (UID 1000).
# Lives outside /var/www/html so the ./src bind mount cannot overlay it.
#
# Asset building is expensive and must run for the `app` service ONLY. The
# `worker` service sets RUN_ASSET_BUILD=0 so it skips the whole build/config
# block below and goes straight to exec'ing `php artisan queue:work` — that is
# how the worker avoids re-running the Vite build the app already did.
#
# Written to stay portable (no `local`, guarded expansions) so it behaves the
# same under bash or a POSIX sh.
set -uo pipefail

APP_DIR=/var/www/html
cd "$APP_DIR" || exit 1

log() { printf '[entrypoint] %s\n' "$*" >&2; }

# ── 1) Storage / bootstrap-cache writability (the chown debt) ────────────────
# Idempotent and fast. Create the dirs Laravel needs and make sure appuser can
# write them. Ownership is normally already correct (container appuser UID ==
# host UID 1000 via the bind mount). If root-owned strays exist we can only fix
# them when this happens to run as root; otherwise we chmod what we own and warn.
mkdir -p \
    storage/app/private \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache 2>/dev/null || true

if [ "$(id -u)" = "0" ]; then
    chown -R 1000:1000 storage bootstrap/cache 2>/dev/null || true
fi

chmod -R u+rwX storage bootstrap/cache 2>/dev/null || true

# Writability self-test — the thing we actually care about.
if ( : > storage/logs/.write-test ) 2>/dev/null; then
    rm -f storage/logs/.write-test 2>/dev/null || true
else
    log "WARNING: storage/ is not writable by UID $(id -u)."
    log "         Fix on host: sudo chown -R 1000:1000 src/storage src/bootstrap/cache"
fi

stray="$(find storage bootstrap/cache ! -uid 1000 -print 2>/dev/null | head -n 5)"
if [ -n "$stray" ]; then
    log "WARNING: files under storage/ or bootstrap/cache/ not owned by UID 1000 (could not auto-fix as UID $(id -u)):"
    printf '[entrypoint]   %s\n' $stray >&2
fi

# ── 2) app-only heavy startup: node deps + Vite build + config cache ─────────
# Skipped entirely by the worker service (RUN_ASSET_BUILD=0).
build_needed() {
    # Rebuild when there is no manifest yet, or a source file is newer than it.
    [ -f public/build/manifest.json ] || return 0
    newer="$(find resources package.json vite.config.js -type f \
                 -newer public/build/manifest.json 2>/dev/null | head -n 1)"
    [ -n "$newer" ]
}

if [ "${RUN_ASSET_BUILD:-1}" != "0" ]; then
    # 2a) Node deps — install only when missing (npm ci = clean, lockfile-exact).
    if [ ! -d node_modules ]; then
        log "node_modules/ missing — running 'npm ci'…"
        if npm ci; then
            log "npm ci complete."
        else
            log "WARNING: 'npm ci' failed — asset build will be skipped."
        fi
    fi

    # 2b) Vite build — only when the manifest is missing or stale.
    if [ ! -d node_modules ]; then
        log "WARNING: node_modules/ still missing — skipping asset build."
        log "         Run: docker compose exec app npm ci"
    elif build_needed; then
        log "Building frontend assets (npm run build)…"
        if npm run build; then
            log "Asset build complete."
        else
            log "WARNING: 'npm run build' failed — continuing with existing/absent assets."
        fi
    else
        log "Frontend assets up to date — skipping build."
    fi

    # 2c) Drop any stale config cache so fresh .env/config is read.
    if php artisan config:clear >/dev/null 2>&1; then
        log "Config cache cleared."
    fi
else
    log "RUN_ASSET_BUILD=0 — skipping asset build/config (worker service)."
fi

# ── 3) Hand off to the main process (php-fpm, or queue:work for the worker) ──
log "Starting: $*"
exec "$@"
