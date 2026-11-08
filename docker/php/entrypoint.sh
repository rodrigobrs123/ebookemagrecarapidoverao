#!/bin/sh
set -e

cd /var/www/html

i=0
until php -r '
  $pdo = new PDO(
    "mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"),
    getenv("DB_USERNAME"),
    getenv("DB_PASSWORD")
  );
' >/dev/null 2>&1; do
  i=$((i + 1))
  if [ "$i" -gt 30 ]; then
    echo "MySQL não ficou pronto a tempo." >&2
    exit 1
  fi
  sleep 2
done

php artisan migrate --force
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

(
  i=0
  until php artisan asaas:sync-ngrok; do
    i=$((i + 1))
    if [ "$i" -gt 30 ]; then
      echo "Ngrok ainda não publicou a URL. O webhook será sincronizado no próximo checkout." >&2
      break
    fi
    sleep 2
  done
) &

exec php-fpm
