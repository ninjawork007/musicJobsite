#FROM timofeyn/vocalizr-php AS vocalizr_php
FROM php:8.0.5-fpm-alpine3.13 AS vocalizr_php

RUN php -v

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

#FROM alpine:3.7

RUN pear config-set php_ini "$PHP_INI_DIR"

RUN apk add --no-cache \
      freetype \
      libjpeg-turbo \
      libpng \
      freetype-dev \
      libjpeg-turbo-dev \
      libpng-dev \
      libzip-dev \
      pcre-dev \
#      imagemagick \
#      imagemagick-libs \
      imagemagick-dev \
      $PHPIZE_DEPS \
    && docker-php-ext-configure gd \
      --with-freetype=/usr/include/ \
      # --with-png=/usr/include/ \ # No longer necessary as of 7.4; https://github.com/docker-library/php/pull/910#issuecomment-559383597
      --with-jpeg=/usr/include/ \
    && docker-php-ext-install zip \
    && pecl install mongodb \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-enable gd \
    && docker-php-ext-enable mongodb \
    && apk del --no-cache \
      freetype-dev \
      libjpeg-turbo-dev \
      libpng-dev \
    && rm -rf /tmp/*

#RUN apk add imagemagick
#RUN apk add imagemagick-dev
#
#RUN apk add --update --no-cache autoconf g++ imagemagick-dev libtool make pcre-dev \
#    && pecl install imagick \
#    && docker-php-ext-enable imagick \
#    && apk del autoconf g++ libtool make pcre-dev

RUN apk del gnu-libiconv --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/community/ --allow-untrusted

# Dirty hack for iconv
RUN apk add gnu-libiconv --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/v3.10/community/ --allow-untrusted
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

COPY php.ini /usr/local/etc/php/php.ini
COPY php-cli.ini /usr/local/etc/php/php-cli.ini

#RUN curl -sS https://getcomposer.org/installer | php
#RUN sudo mv composer.phar /usr/bin/composer
RUN composer --version

WORKDIR /srv/vocalizr

VOLUME /srv/vocalizr/var/cache

COPY crontab /etc/crontabs/www-data

RUN touch /var/log/cron.log

COPY ./entrypoint.sh /usr/local/bin/entrypoint.sh
COPY watch_assetic.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/watch_assetic.sh
ARG SOMETHING
RUN echo $SOMETHING
RUN echo ls
ENTRYPOINT /usr/local/bin/entrypoint.sh "dev"