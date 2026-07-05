#!/usr/bin/env bash
# Container START-time steps (runs as appuser, UID 1000).
# Lives outside /var/www/html so the ./src bind mount cannot overlay it.
set -uo pipefail

APP_DIR=/var/www/html
cd "$APP_DIR" || exit 1

log() { printf '[entrypoint] %s\n' "$*" >&2; }

# Rebuild only when there's no manifest yet, or a source file is newer than it.
build_needed() {
    [ -f public/build/manifest.json ] || return 0
    local newer
    newer="$(find resources package.json vite.config.js -type f \
                 -newer public/build/manifest.json 2>/dev/null | head -n 1)"
    [ -n "$newer" ]
}

# 1) Frontend assets (vite build) — warn, never fail, so php-fpm always comes up.
if [ ! -d node_modules ]; then
    log "WARNING: node_modules/ missing — skipping asset build."
    log "         Run: docker compose exec app npm install"
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

# 2) Ownership sanity check — WARN only (does not chown, does not fail).
stray="$(find storage bootstrap/cache ! -uid 1000 -print 2>/dev/null | head -n 5)"
if [ -n "$stray" ]; then
    log "WARNING: files under storage/ or bootstrap/cache/ not owned by UID 1000:"
    printf '[entrypoint]   %s\n' $stray >&2
    log "         Writes may fail. Fix on host: sudo chown -R 1000:1000 src/storage src/bootstrap/cache"
fi

# 3) Hand off to the main process (php-fpm from CMD).
log "Starting: $*"
exec "$@"
