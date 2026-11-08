#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/app/orders storage/framework/{cache,sessions,views} storage/logs bootstrap/cache database
touch database/database.sqlite

chown -R www-data:www-data storage bootstrap/cache database
chmod -R ug+rwx storage bootstrap/cache database

(
  i=0
  until php artisan asaas:sync-ngrok; do
    i=$((i + 1))
    if [ "$i" -gt 15 ]; then
      echo "Ngrok ainda não publicou a URL. O webhook será sincronizado no próximo checkout." >&2
      break
    fi
    sleep 2
  done
) &

exec php-fpm
