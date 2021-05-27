#!/bin/sh
set -e

#php bin/console assetic:dump --env=dev
if [ "${APP_ENV}" = "dev" ]; then
  composer install
  watch_assetic.sh &
  php bin/console assets:install --symlink
else
  composer install -ao
  php bin/console assets:install
fi

php bin/console cache:clear

until php bin/console doctrine:query:sql "select 1" >/dev/null 2>&1; do
    (>&2 echo "Waiting for MySQL to be ready...")
  sleep 1
done

php bin/console doctrine:schema:update --force
php bin/console doctrine:mongodb:schema:update

mkdir -p tmp

setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX var tmp
setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX var tmp

crond -f -d 8 &

exec php-fpm