#!/bin/sh
set -e

cd /var/www/html

mkdir -p \
  storage/app/private \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

if [ -f /var/ebook/ebook.pdf ] && [ ! -f storage/app/private/ebook.pdf ]; then
  cp /var/ebook/ebook.pdf storage/app/private/ebook.pdf || true
fi

if [ -f ../Como_Emagrecer_Rapido_para_o_Verao.pdf ] && [ ! -f storage/app/private/ebook.pdf ]; then
  cp ../Como_Emagrecer_Rapido_para_o_Verao.pdf storage/app/private/ebook.pdf || true
fi

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

i=0
until php artisan migrate --force --no-interaction; do
  i=$((i + 1))
  if [ "$i" -gt 30 ]; then
    echo "Não foi possível migrar o banco. Verifique DB_*." >&2
    break
  fi
  sleep 2
done

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
