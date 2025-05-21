FROM node:24.0.2-alpine3.21 AS build-node
WORKDIR /build
# Copy files and run npm install
COPY --chown=node:node gulpfile.js package.json package-lock.json ./
RUN npm ci


FROM composer:2.2.25 AS build-php
WORKDIR /build
COPY composer.json composer.lock ./
COPY ./library/classes/ ./library/classes/
RUN composer install --ignore-platform-req=ext-dom \
                     --ignore-platform-req=ext-fileinfo \
                     --ignore-platform-req=ext-gd \
                     --ignore-platform-req=ext-simplexml \
                     --ignore-platform-req=ext-soap \
                     --ignore-platform-req=ext-xml \
                     --ignore-platform-req=ext-xmlreader \
                     --ignore-platform-req=ext-xmlwriter \
                     --ignore-platform-req=ext-zip \
                     --no-dev \
                     --no-progress \
                     --no-scripts \
                     --optimize-autoloader \
                     --prefer-dist


FROM scratch AS dist
COPY --from=build-node /build/public/assets /dist/public/assets
COPY --from=build-php /build/vendor /dist/vendor


FROM php:8.4.7-apache AS runtime
# Enable extensions
# TODO: missing pdo_mysql/mysqli maybe
COPY ./.php.ini.d/ $PHP_INI_DIR/conf.d/
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY . .
COPY --from=dist /dist/public/assets ./public/assets
COPY --from=dist /dist/vendor ./vendor
