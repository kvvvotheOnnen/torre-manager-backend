FROM php:8.4-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-install pdo_pgsql zip opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN groupmod -o -g 1000 www-data \
    && usermod -o -u 1000 -g www-data www-data

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install --no-interaction --no-scripts --prefer-dist --no-progress

COPY . .

RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
