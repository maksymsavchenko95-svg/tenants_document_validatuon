FROM php:8.4-cli AS base


RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libsqlite3-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" intl pdo_sqlite \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# xdebug settings
COPY dockers/app/xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app


EXPOSE 8765

ENTRYPOINT ["dockers/app/entrypoint.sh"]

# run CakePHP's built-in dev server
CMD ["php", "-d", "memory_limit=256M", "bin/cake.php", "server", "-H", "0.0.0.0", "-p", "8765"]
