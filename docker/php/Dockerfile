FROM php:8.0.5-fpm-alpine3.13 AS vocalizr_php

# Project dependencies
RUN apk add --no-cache git lame faac grep mariadb-client busybox-suid acl inotify-tools

# Build dependencies
RUN apk add --no-cache make cmake gcc g++ libmad-dev \
                         libid3tag-dev libsndfile-dev gd-dev boost-dev \
                         libgd libpng-dev zlib-dev

# Build audiowaveform
RUN git clone https://github.com/bbc/audiowaveform.git \
    && cd audiowaveform \
    && wget https://github.com/google/googletest/archive/release-1.10.0.tar.gz \
    && tar xzf release-1.10.0.tar.gz \
    && ln -s googletest-release-1.10.0/googletest googletest \
    && ln -s googletest-release-1.10.0/googlemock googlemock \
    && mkdir build \
    && cd build \
    && cmake .. \
    && make \
    && cp audiowaveform* /bin \
    && cd \
    && rm -rf /audiowaveform

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions opcache imagick apcu zip pdo_mysql intl exif gd mongodb

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

COPY php.ini /usr/local/etc/php/php.ini
COPY php-cli.ini /usr/local/etc/php/php-cli.ini

WORKDIR /srv/vocalizr

VOLUME /srv/vocalizr/var/cache

COPY crontab /etc/crontabs/www-data

RUN touch /var/log/cron.log

COPY ./entrypoint.sh /usr/local/bin/entrypoint.sh
COPY watch_assetic.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/watch_assetic.sh
ENTRYPOINT /usr/local/bin/entrypoint.sh "dev"