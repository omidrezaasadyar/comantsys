FROM php:8.4-fpm

# System deps for PHP extensions (intl=Persian/Jalali, pgsql=DB, oniguruma=mbstring)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libwebp-dev \
        libonig-dev \
        zip unzip git curl ca-certificates gnupg \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        intl \
        mbstring \
        bcmath \
        gd \
        zip \
        exif \
        pcntl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Node.js 22 LTS (for Filament theme / Vite asset builds)
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Chromium + fonts for Browsershot PDF generation
RUN apt-get update && apt-get install -y --no-install-recommends \
        chromium \
        chromium-sandbox \
        fonts-liberation \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
# PostgreSQL client 17 (matches server; for DB backup/restore via pg_dump/pg_restore)
RUN curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor -o /usr/share/keyrings/pgdg.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/pgdg.gpg] https://apt.postgresql.org/pub/repos/apt trixie-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update && apt-get install -y --no-install-recommends postgresql-client-17 \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
# Puppeteer uses the system Chromium instead of downloading its own
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

# Composer (copied from official image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Container start-time entrypoint (copied as root so it is executable & root-owned,
# placed outside /var/www/html so the ./src bind mount cannot overlay it).
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# ساخت کاربر هم‌UID با میزبان تا فایل‌های ساخته‌شده مال کاربر میزبان باشند
ARG UID=1000
ARG GID=1000
RUN groupadd -g ${GID} appuser \
    && useradd -u ${UID} -g ${GID} -m -s /bin/bash appuser
USER appuser

# Run per-session steps at START (as appuser), then exec the CMD (php-fpm).
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]