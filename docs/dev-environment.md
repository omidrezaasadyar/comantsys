# COMANTSYS — Development Environment Guide

> Practical reference for running, stopping, and troubleshooting the COMANTSYS
> Docker environment on Windows 11 + WSL2 + Docker Desktop.
> Save this file at `docs/dev-environment.md` in the repo root.

---

## 1. Architecture (30-second overview)

COMANTSYS runs as three Docker containers orchestrated by `compose.yaml` at
the repo root:

```text
comantsys-db    postgres:17-alpine   port 5432 (internal)   PostgreSQL 17
comantsys-app   comantsys-app        port 9000 (internal)   PHP-FPM 8.4 + Laravel
comantsys-web   nginx:1.27-alpine    127.0.0.1:8095 -> 80   nginx reverse proxy
```

Data volumes:

```text
Postgres data:      Docker named volume (survives container removal)
Application code:   ./src (bind mount, edited directly in VS Code)
Uploads/private:    src/storage/app/private (bind mount)
```

Runtime layers involved when a browser hits `http://localhost:8095`:

```text
Windows browser
  -> Windows loopback :8095
     -> Docker Desktop port forwarder (vpnkit / hns)
        -> WSL2 virtual network
           -> comantsys-web (nginx)
              -> comantsys-app (PHP-FPM)
                 -> comantsys-db (PostgreSQL)
```

The Docker Desktop layer is the most fragile piece and the source of the
recurring port-forwarding issue documented in section 5.

---

## 2. Starting a work session

Every day, in this order:

```bash
# 1. Open Ubuntu terminal (Start menu -> Ubuntu, or WSL tab in Windows Terminal)

# 2. Go to project root
cd ~/projects/comantsys

# 3. Check current container state
docker compose ps

# 4a. If empty or all Exited -> start the stack
docker compose up -d

# 4b. If already all Up -> nothing to do, skip to step 6

# 5. Wait ~10-20 seconds, then verify all three services are Up
docker compose ps
```

Expected healthy output:

```text
NAME            STATUS   PORTS
comantsys-db    Up       5432/tcp
comantsys-app   Up       9000/tcp
comantsys-web   Up       127.0.0.1:8095->80/tcp
```

Then open the browser:

```text
http://localhost:8095
```

If the Filament dashboard loads, the environment is ready. If not, jump to
section 4 (Troubleshooting).

---

## 3. Ending a work session

Three scenarios, pick the one that matches what you want:

### Scenario A — Leaving the computer on, resume later today

Do nothing. Close the browser tab. Containers keep running in the background
with negligible resource use. Next time you open the browser, the app is
already there.

### Scenario B — Freeing resources but keeping containers ready

```bash
cd ~/projects/comantsys
docker compose stop
```

Containers stop but are NOT removed. Volumes, networks, and config are kept.
To resume next time, use `docker compose start` (faster than `up`) or the
usual `docker compose up -d`.

### Scenario C — Shutting down Windows / restarting

Do nothing before shutdown. Windows will stop WSL, WSL will stop Docker,
Docker will stop containers. Data on volumes is safe.

After the next boot, follow section 2 to bring the stack back up.

---

## 4. Common issues — quick fixes

### 4.1 `http://localhost:8095` shows "This site can't be reached" / connection refused

The `web` container isn't up or the port isn't forwarded.

```bash
docker compose ps
```

If `comantsys-web` is missing or Exited:

```bash
docker compose logs web --tail=50
```

Read the last 50 log lines. If you see the port-forwarding error described in
section 5, follow that fix. Otherwise:

```bash
docker compose down
docker compose up -d
```

### 4.2 Browser shows 500 Internal Server Error

The web layer is up but the PHP layer is failing. Three common causes:

Cause A — database not ready yet after boot. Wait 20 seconds and reload the
page. Postgres takes a few seconds after container start before it accepts
connections.

Cause B — Laravel/config cache is stale.

```bash
docker compose exec app php artisan optimize:clear
```

Cause C — Vite build assets are missing.

```bash
docker compose exec app npm run build
```

If none of these work, check the app logs:

```bash
docker compose exec app tail -100 storage/logs/laravel.log
```

### 4.3 Docker Desktop won't start

This is a Windows/WSL issue, not a project issue. Close Docker Desktop
completely (right-click tray icon -> Quit Docker Desktop), wait 10 seconds,
and start it again from the Start menu. If it still fails, restart Windows.

### 4.4 WSL commands hang or the terminal is unresponsive

Close the terminal window. From PowerShell:

```powershell
wsl --shutdown
```

Wait 10 seconds, then open a new Ubuntu terminal. This is safe — no data is
lost.

### 4.5 Volume looks empty after Windows restart

Occasionally after reboot, bind mounts appear empty inside the container.
Reliable fix:

```bash
cd ~/projects/comantsys
docker compose down
docker compose up -d
```

`down` fully removes containers (not volumes, not data). `up -d` recreates
them and re-establishes the mount cleanly.

---

## 5. The Docker Desktop hns port-forwarding issue

This is the issue that hit on 2026-07-07 after a Docker Desktop auto-update.
Because it will recur after future updates, the diagnostic and fix are
documented here in full.

### 5.1 Symptoms

Every `docker compose up` or `docker run -p` fails with a variant of:

```text
Error response from daemon: ports are not available:
exposing port TCP 127.0.0.1:8095 -> 127.0.0.1:0:
/forwards/expose returned unexpected status: 500
```

Key signals:

- The `-> 127.0.0.1:0` (target port zero) in the error is characteristic.
- The failure happens even for unrelated containers, e.g. a plain nginx.
- Restarting Docker Desktop alone does NOT fix it.

### 5.2 Diagnostic — is it really this issue?

Run an isolated test that has nothing to do with COMANTSYS:

```bash
docker run --rm -p 8097:80 nginx:alpine
```

If this fails with the same `forwards/expose ... status: 500` error, the
issue is confirmed to be at the Windows/Docker Desktop layer, not in the
project. Press `Ctrl+C` if it happened to start, and continue to the fix.

If the nginx test succeeds, the problem is elsewhere — go back to section 4.

### 5.3 Root cause

The Windows service `hns` (Host Network Service) maintains the port-forwarding
state between the Windows loopback, WSL2, and Docker Desktop. When Docker
Desktop is updated, its internal forwarder gets a new state but `hns` keeps
the old mapping tables. The two layers desynchronize and every port publish
attempt fails with the status 500 above.

Restarting Docker Desktop does not clear `hns`. Restarting WSL does not clear
`hns`. Only restarting the `hns` service itself resolves it.

### 5.4 Fix

Open PowerShell as Administrator (right-click Start -> Terminal (Admin) or
Windows PowerShell (Admin), accept UAC). Verify the window title contains
"Administrator".

Run:

```powershell
net stop hns
net start hns
```

Both commands should report success. Expected output:

```text
The Host Network Service service is stopping.
The Host Network Service service was stopped successfully.
The Host Network Service service is starting.
The Host Network Service service was started successfully.
```

After the restart, from the Docker Desktop tray icon: right-click ->
Restart. Wait 20-30 seconds for the icon to become white and stable.

Then, in a fresh Ubuntu terminal:

```bash
docker run --rm -p 8097:80 nginx:alpine
```

If nginx starts and prints `start worker processes`, the fix worked.
`Ctrl+C` to stop it, and bring COMANTSYS back up:

```bash
cd ~/projects/comantsys
docker compose down
docker compose up -d
docker compose ps
```

### 5.5 Prevention

To reduce the odds of recurrence:

Disable Docker Desktop auto-updates: Docker Desktop -> Settings (gear icon)
-> General -> uncheck "Automatically check for updates". Update manually on
a scheduled Friday or when you have time to run the fix if needed.

After any Docker Desktop update, run the nginx diagnostic before doing real
work. Five seconds up front saves hours of confusion later.

---

## 6. Database backup and restore

### 6.1 Take a backup before any risky operation

```bash
docker compose exec db pg_dump -U comantsys comantsys \
  > ~/comantsys_backup_$(date +%Y%m%d_%H%M).sql

# Verify size
ls -lh ~/comantsys_backup_*.sql
```

The file lives in your WSL home directory and survives container removal,
volume prune, and Docker Desktop reset.

### 6.2 Restore a backup

```bash
# Make sure the database container is running
docker compose ps

# Drop and recreate the database (destructive — data currently in DB is lost)
docker compose exec db psql -U comantsys -c "DROP DATABASE IF EXISTS comantsys;"
docker compose exec db psql -U comantsys -c "CREATE DATABASE comantsys;"

# Restore from file
docker compose exec -T db psql -U comantsys comantsys < ~/comantsys_backup_20260707_1129.sql
```

Adjust the filename to match the backup you want to restore.

### 6.3 When to take a backup

Always before:

- Purging Docker Desktop data.
- Deleting volumes (`docker volume rm`, `docker system prune -a --volumes`).
- Any migration that alters or drops columns/tables.
- Rotating the database password.
- Long breaks (weekly is a reasonable rhythm during active development).

---

## 7. Project reference

Quick lookup for values that don't change often:

```text
Repo path:          ~/projects/comantsys (Ubuntu filesystem)
Git remote:         git@github.com:omidrezaasadyar/comantsys.git
Compose file:       compose.yaml (NOT docker-compose.yml)
App URL:            http://localhost:8095
DB name / user:     comantsys / comantsys
DB port (internal): 5432
PHP-FPM port:       9000 (internal)
Nginx port:         80 (internal), 127.0.0.1:8095 (host)
Container user:     appuser (UID/GID 1000)
```

Useful one-liners:

```bash
# Enter the app container as appuser
docker compose exec app bash

# Run artisan
docker compose exec app php artisan <command>

# Run composer
docker compose exec app composer <command>

# Run npm
docker compose exec app npm <command>

# Watch logs of all services in real time
docker compose logs -f

# Watch logs of one service
docker compose logs -f app
```

---

## 8. Preventive practices

Habits that keep the environment stable:

Disable Docker Desktop auto-updates (section 5.5). This is the single most
effective prevention.

Take a weekly database backup, at least during active development. One line,
30 seconds.

Before shutting Windows down, if a long-running job is in progress inside a
container, stop it cleanly instead of relying on the OS to kill it. Rough
kills of containers with open ports are one path to the hns issue.

Keep `~/projects/comantsys` inside the Ubuntu filesystem, never on a Windows
drive path like `/mnt/c/...`. Cross-filesystem I/O is 10x-50x slower and
occasionally corrupts file watchers used by Vite.

After any container change (Dockerfile edit, new package installed), rebuild
explicitly and don't rely on cache:

```bash
docker compose build --no-cache app
docker compose up -d
```

Keep this file up to date. When a new class of issue is diagnosed and fixed,
add a short section here so the next occurrence is a five-minute lookup, not
a half-day investigation.