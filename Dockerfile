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

# Composer (copied from official image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ساخت کاربر هم‌UID با میزبان تا فایل‌های ساخته‌شده مال کاربر میزبان باشند
ARG UID=1000
ARG GID=1000
RUN groupadd -g ${GID} appuser \
    && useradd -u ${UID} -g ${GID} -m -s /bin/bash appuser
USER appuser
