FROM nginx:alpine AS vocalizr_nginx

COPY conf.d/default.conf /etc/nginx/conf.d/default.conf
COPY htpasswd /etc/nginx/

WORKDIR /srv/vocalizr