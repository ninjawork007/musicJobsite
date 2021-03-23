#!/bin/bash

if [ "$1" == "sudo" ]; then
    cd /var/vhosts/test.vocalizr.com/
    output=$( sudo -u ubuntu -H /usr/bin/git pull 2>&1 )
    echo ${output}
else
    cd /var/vhosts/test.vocalizr.com/
    exec /usr/bin/git pull
fi

php /var/vhosts/test.vocalizr.com/app/console doctrine:schema:update --force
php /var/vhosts/test.vocalizr.com/app/console assetic:dump
php /var/vhosts/test.vocalizr.com/app/console assets:install
php /var/vhosts/test.vocalizr.com/app/console assetic:dump --env=prod
php /var/vhosts/test.vocalizr.com/app/console assets:install --env=prod --symlink

if [ "$1" != "sudo" ]; then
    sudo chown -R www-data:www-data /var/vhosts/test.vocalizr.com/web/js
    sudo chown -R www-data:www-data /var/vhosts/test.vocalizr.com/web/css
fi

rm -rf /var/vhosts/test.vocalizr.com/app/cache/*

echo "Update Complete!"