FROM php:8.3-apache

RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo pdo_pgsql

# Включение mod_rewrite
RUN a2enmod rewrite

COPY src/ /var/www/html/
COPY init.sql /docker-entrypoint-initdb.d/
COPY src/apache2.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80
