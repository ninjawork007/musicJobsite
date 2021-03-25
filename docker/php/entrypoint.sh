#!/bin/sh
set -e

if [ $1 = "dev" ]; then
  APP_ENV="dev"
elif [ $1 = "demo" ]; then
  APP_ENV="prod"
else
  echo "Unknown env"
  exit 1
fi

composer self-update --2
composer dump-autoload
composer require doctrine/annotations
php app/console cache:clear --env="${APP_ENV}"

php app/console assetic:dump --env=dev
if [ "${APP_ENV}" = "dev" ]; then
  watch_assetic.sh &
else
  php app/console assets:install --env=prod
fi

php app/console assets:install --symlink --env="${APP_ENV}"

until php app/console doctrine:query:sql "select 1" >/dev/null 2>&1; do
    (>&2 echo "Waiting for MySQL to be ready...")
  sleep 1
done

php app/console doctrine:schema:update --force --env="${APP_ENV}"
php app/console doctrine:mongodb:schema:update --env="${APP_ENV}"

setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX app/cache app/sessions app/logs uploads tmp web/uploads

crond -f -d 4 &

exec php-fpm