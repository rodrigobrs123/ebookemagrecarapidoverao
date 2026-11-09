#!/bin/sh
set -e

cd /app/backend

mkdir -p \
  storage/app/private \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

if [ ! -f storage/app/private/ebook.pdf ] && [ -f /app/Como_Emagrecer_Rapido_para_o_Verao.pdf ]; then
  cp /app/Como_Emagrecer_Rapido_para_o_Verao.pdf storage/app/private/ebook.pdf
fi

export APP_ENV="${APP_ENV:-production}"
export APP_DEBUG="${APP_DEBUG:-false}"
export DB_CONNECTION="${DB_CONNECTION:-mysql}"
export SESSION_DRIVER="${SESSION_DRIVER:-file}"
export CACHE_STORE="${CACHE_STORE:-file}"
export QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"
export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"
export EBOOK_PATH="${EBOOK_PATH:-/app/backend/storage/app/private/ebook.pdf}"

if [ -z "$APP_KEY" ]; then
  APP_KEY="base64:$(openssl rand -base64 32 | tr -d '\n')"
  export APP_KEY
fi

php artisan config:clear >/dev/null 2>&1 || true
php artisan migrate --force --no-interaction

PORT="${PORT:-8080}"
exec php -S "0.0.0.0:${PORT}" -t public
