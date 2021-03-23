FROM nginx:alpine AS vocalizr_nginx

ARG CFG_FILE=conf.d/default.conf

COPY ${CFG_FILE} /etc/nginx/conf.d/default.conf
COPY htpasswd /etc/nginx/

WORKDIR /srv/vocalizr