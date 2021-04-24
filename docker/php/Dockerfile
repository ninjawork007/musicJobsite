FROM timofeyn/vocalizr-php AS vocalizr_php
#FROM php:8.0-fpm-buster AS vocalizr_php
#
#RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
#
#FROM alpine:3.7

RUN apk del gnu-libiconv --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/community/ --allow-untrusted

# Dirty hack for iconv
RUN apk add gnu-libiconv --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/v3.10/community/ --allow-untrusted
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

COPY php.ini /usr/local/etc/php/php.ini
COPY php-cli.ini /usr/local/etc/php/php-cli.ini

RUN curl -sS https://getcomposer.org/installer | php
#RUN sudo mv composer.phar /usr/bin/composer
RUN composer --version

WORKDIR /srv/vocalizr

VOLUME /srv/vocalizr/app/cache

COPY config/* /srv/vocalizr/app/config/

COPY crontab /etc/crontabs/www-data

RUN touch /var/log/cron.log

COPY ./entrypoint.sh /usr/local/bin/entrypoint.sh
COPY watch_assetic.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/watch_assetic.sh
ARG SOMETHING
RUN echo $SOMETHING
RUN echo ls
ENTRYPOINT /usr/local/bin/entrypoint.sh "dev"