# =============================================================================
# Base: shared extensions for all stages
# =============================================================================
FROM php:8.5-fpm@sha256:0dc450d0a0e81ba501973b8e303f5d45af2ed989e08730f597d8fc07fb289efd AS base
RUN apt-get update && apt-get install -y libpq-dev git unzip \
    && docker-php-ext-install pdo_pgsql opcache \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html

# =============================================================================
# Dev: composer + xdebug + dev dependencies, volume mount 前提
# =============================================================================
FROM base AS dev
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN pecl install xdebug && docker-php-ext-enable xdebug
COPY docker/php/php-dev.ini $PHP_INI_DIR/conf.d/app.ini
EXPOSE 9000

# =============================================================================
# Composer: install production dependencies
# =============================================================================
FROM composer:latest AS composer
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
