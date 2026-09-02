# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# base — PHP-FPM + расширения, общие для dev и production
# ---------------------------------------------------------------------------
ARG PHP_VERSION=8.5

FROM php:${PHP_VERSION}-fpm-alpine AS base

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apk add --no-cache \
        bash \
        git \
        postgresql-client \
        shadow \
        tzdata \
    && install-php-extensions \
        bcmath \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        pdo_sqlite \
        redis \
        sockets \
        zip \
    && rm -rf /var/cache/apk/*

# UID/GID процесса php-fpm выравниваются с хостовым пользователем,
# иначе файлы, созданные в контейнере, окажутся недоступны на хосте.
ARG UID=1000
ARG GID=1000

RUN groupmod -o -g "${GID}" www-data \
    && usermod -o -u "${UID}" -g "${GID}" www-data

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    COMPOSER_MEMORY_LIMIT=-1

WORKDIR /var/www/html

COPY docker/php/conf.d/app.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# ---------------------------------------------------------------------------
# development — исходники монтируются томом, Xdebug выключен по умолчанию
# ---------------------------------------------------------------------------
FROM base AS development

RUN install-php-extensions xdebug

COPY docker/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini
COPY docker/php/conf.d/opcache-dev.ini /usr/local/etc/php/conf.d/zz-opcache.ini

USER www-data

# ---------------------------------------------------------------------------
# vendor — зависимости для production-образа (кешируемый слой)
# ---------------------------------------------------------------------------
FROM base AS vendor

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

# ---------------------------------------------------------------------------
# production — код внутри образа, оптимизированный автозагрузчик и OPcache
# ---------------------------------------------------------------------------
FROM base AS production

ENV APP_ENV=production \
    APP_DEBUG=false

COPY docker/php/conf.d/opcache-prod.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY --from=vendor /var/www/html/vendor ./vendor
COPY --chown=www-data:www-data . .

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
