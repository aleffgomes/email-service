#!/bin/sh

composer install --no-scripts

if [ "$APP_ENV" = "development" ]; then
  php -a
else
  php server.php
fi

exec "$@"