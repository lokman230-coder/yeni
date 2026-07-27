# Ahost Bilişim — Production Dockerfile
FROM php:8.2-fpm-alpine

# Sistem paketleri + PHP extensions
RUN apk add --no-cache \
        curl \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        freetype-dev \
        libzip-dev \
        oniguruma-dev \
        icu-dev \
        mariadb-client \
        nginx \
        supervisor \
        tzdata \
        bash \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        gd \
        zip \
        intl \
        opcache \
        bcmath \
    && rm -rf /var/cache/apk/*

# Timezone
ENV TZ=Europe/Istanbul
RUN cp /usr/share/zoneinfo/Europe/Istanbul /etc/localtime && echo "Europe/Istanbul" > /etc/timezone

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# OPcache production ayarları
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.revalidate_freq=60'; \
    echo 'opcache.validate_timestamps=1'; \
    echo 'opcache.save_comments=1'; \
    echo 'opcache.jit=1255'; \
    echo 'opcache.jit_buffer_size=128M'; \
} > /usr/local/etc/php/conf.d/opcache.ini

RUN { \
    echo 'memory_limit=256M'; \
    echo 'upload_max_filesize=32M'; \
    echo 'post_max_size=32M'; \
    echo 'max_execution_time=60'; \
    echo 'expose_php=Off'; \
    echo 'display_errors=Off'; \
    echo 'log_errors=On'; \
} > /usr/local/etc/php/conf.d/production.ini

# Uygulama dosyaları
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

COPY . .

# Yazılabilir dizinler
RUN mkdir -p storage/logs storage/cache storage/sessions storage/uploads storage/btk-exports storage/builder-exports \
    && chown -R www-data:www-data storage \
    && chmod -R 775 storage

# Nginx + supervisord config
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/crontab /etc/crontabs/www-data
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
