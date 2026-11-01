# =============================================================================
# Base: shared extensions for all stages
# =============================================================================
FROM php:8.5-fpm@sha256:c34bcf437ed61bd5e4c1114494eaf1ffd54253bce2b95d263bfa18b350ee8432 AS base
RUN apt-get update && apt-get install -y libpq-dev git unzip \
    && docker-php-ext-install pdo_pgsql opcache \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html

# =============================================================================
# Dev: composer + xdebug + dev dependencies, volume mount 前提
# =============================================================================
FROM base AS dev
COPY --from=composer:latest@sha256:4ed71c38c6d788eb4e4e3747b5332f5b40d5b5a6fcadd5062a1a70986a118e0a /usr/bin/composer /usr/bin/composer
RUN pecl install xdebug && docker-php-ext-enable xdebug
COPY docker/php/php-dev.ini $PHP_INI_DIR/conf.d/app.ini
EXPOSE 9000

# =============================================================================
# Composer: install production dependencies
# =============================================================================
FROM composer:latest@sha256:4ed71c38c6d788eb4e4e3747b5332f5b40d5b5a6fcadd5062a1a70986a118e0a AS composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# =============================================================================
# Builder: compile BEAR.Sunday application
# =============================================================================
FROM base AS builder
COPY --from=composer /app/vendor vendor/
COPY . .
RUN php vendor/bin/bear.compile 'ConstraintEngine\App' prod-hal-app ./

# =============================================================================
# Runtime: production image (no composer, no dev deps, compiled)
# =============================================================================
FROM base AS runtime
COPY docker/php/php.ini $PHP_INI_DIR/conf.d/app.ini
COPY --from=builder /var/www/html /var/www/html
RUN chown -R www-data:www-data var/
EXPOSE 9000
