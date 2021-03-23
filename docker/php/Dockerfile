FROM timofeyn/vocalizr-php AS vocalizr_php

RUN apk del gnu-libiconv --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/community/ --allow-untrusted

# Dirty hack for iconv
RUN apk add gnu-libiconv --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/v3.10/community/ --allow-untrusted
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

COPY php.ini /usr/local/etc/php/php.ini
COPY php-cli.ini /usr/local/etc/php/php-cli.ini

WORKDIR /srv/vocalizr

VOLUME /srv/vocalizr/app/cache

COPY config/* /srv/vocalizr/app/config/

COPY crontab /etc/crontabs/www-data

RUN touch /var/log/cron.log

COPY entrypoint.sh /usr/local/bin/docker-entrypoint
COPY watch_assetic.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint && chmod +x /usr/local/bin/watch_assetic.sh

ENTRYPOINT ["docker-entrypoint"]
