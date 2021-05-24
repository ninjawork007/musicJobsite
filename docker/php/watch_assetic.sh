#!/bin/sh
set -e

asseticDir=src/Vocalizr/AppBundle/Resources/public

while inotifywait -qqre modify "$asseticDir"; do
  php app/console assetic:dump --env=dev
done