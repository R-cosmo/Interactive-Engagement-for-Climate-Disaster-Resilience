FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    unzip git curl libssl-dev pkg-config \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . /var/www/html/

WORKDIR /var/www/html

RUN composer install --no-dev

EXPOSE 80
