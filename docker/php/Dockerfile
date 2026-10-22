# =============================================================================
# Base: shared extensions for all stages
# =============================================================================
FROM php:8.5-fpm@sha256:e343d72629421d3c21064ec8d94e0c121790b0e3b2a9f594e8f366123e45ecaa AS base
RUN apt-get update && apt-get install -y libpq-dev git unzip \
    && docker-php-ext-install pdo_pgsql opcache \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html

# =============================================================================
# Dev: composer + xdebug + dev dependencies, volume mount 前提
# =============================================================================
FROM base AS dev
COPY --from=composer:latest@sha256:da624c2c705d54e2f5a9bb77a5dc6c7d091711433ebefd7035672e84c2010fee /usr/bin/composer /usr/bin/composer
RUN pecl install xdebug && docker-php-ext-enable xdebug
COPY docker/php/php-dev.ini $PHP_INI_DIR/conf.d/app.ini
EXPOSE 9000

# =============================================================================
# Composer: install production dependencies
# =============================================================================
FROM composer:latest@sha256:da624c2c705d54e2f5a9bb77a5dc6c7d091711433ebefd7035672e84c2010fee AS composer
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
