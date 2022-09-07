ARG PHP_VERSION

# See https://github.com/thecodingmachine/docker-images-php
FROM php:${PHP_VERSION}-cli-alpine

RUN apk add --no-cache \
    make \
    bash \
    git

RUN apk add --no-cache --virtual .phpize-deps-configure $PHPIZE_DEPS \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del .phpize-deps-configure

RUN echo 'memory_limit=-1' > $PHP_INI_DIR/conf.d/dev.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN composer global config --no-plugins allow-plugins.symfony/flex true \
    && composer global require --no-progress --no-scripts --no-plugins symfony/flex

ENV PATH="${PATH}:./vendor/bin"
