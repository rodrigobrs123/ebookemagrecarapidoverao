FROM php:8.3-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip libzip-dev git default-mysql-client \
    && docker-php-ext-install zip pdo_mysql \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app/backend

COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

COPY backend ./
COPY docker/ethiodeploy/start.sh /app/docker/ethiodeploy/start.sh
COPY Como_Emagrecer_Rapido_para_o_Verao.pdf /app/Como_Emagrecer_Rapido_para_o_Verao.pdf

RUN chmod +x /app/docker/ethiodeploy/start.sh \
    && mkdir -p \
      storage/app/private \
      storage/framework/cache \
      storage/framework/sessions \
      storage/framework/views \
      storage/logs \
      bootstrap/cache \
    && cp /app/Como_Emagrecer_Rapido_para_o_Verao.pdf storage/app/private/ebook.pdf \
    && chown -R www-data:www-data storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    DB_CONNECTION=mysql \
    EBOOK_PATH=/app/backend/storage/app/private/ebook.pdf \
    SESSION_DRIVER=file \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync \
    LOG_CHANNEL=stderr

EXPOSE 8080
CMD ["/app/docker/ethiodeploy/start.sh"]
