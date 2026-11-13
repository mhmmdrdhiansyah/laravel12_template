FROM php:8.3-fpm-alpine

ARG UID=1000

RUN apk add --no-cache icu-dev libzip-dev \
    && docker-php-ext-install pdo_mysql bcmath intl zip opcache

# Rebuild www-data to match the host user, so bind-mount writes stay host-owned
# (fixes storage/ + bootstrap/cache/ permission 500s with the ./ bind mount).
RUN apk add --no-cache shadow \
    && groupmod -g ${UID} www-data \
    && usermod -u ${UID} -g ${UID} www-data

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_HOME=/tmp/composer

COPY php.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html
