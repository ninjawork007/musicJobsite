FROM php:7.2-fpm AS vocalizr_php


# Dirty hack for iconv


COPY php.ini /usr/local/etc/php/php.ini
COPY php-cli.ini /usr/local/etc/php/php-cli.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
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

RUN apt-get update && apt-get install -y --fix-missing \
    apt-utils \
    gnupg

RUN echo "deb http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list
RUN echo "deb-src http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list
RUN curl -sS --insecure https://www.dotdeb.org/dotdeb.gpg | apt-key add -

RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    libzip-dev
RUN apt-get update && apt-get install -y libmagickwand-dev --no-install-recommends && rm -rf /var/lib/apt/lists/*
RUN pecl install imagick-beta
RUN apt-get update

RUN yes | apt install zip unzip exif
RUN yes | apt install wget
RUN apt-get update
RUN yes | apt-get install openssl libssl-dev libcurl4-openssl-dev
RUN pecl install mongodb

RUN wget https://get.symfony.com/cli/installer -O - | bash
RUN mv /root/.symfony/bin/symfony /usr/local/bin/symfony
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-install pdo pdo_mysql zip exif
RUN docker-php-ext-enable imagick mongodb


ENTRYPOINT /usr/local/bin/entrypoint.sh "dev"