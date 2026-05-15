FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    unzip git curl

COPY . /var/www/html/

EXPOSE 80