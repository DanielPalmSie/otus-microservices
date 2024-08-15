FROM php:8.3-apache

RUN echo 'Acquire::http::Pipeline-Depth 0;\nAcquire::http::No-Cache true;\nAcquire::BrokenProxy true;' > /etc/apt/apt.conf.d/99fixbadproxy


RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY src/ /var/www/html/

WORKDIR /var/www/html
COPY composer.json composer.lock /var/www/html/
RUN composer install


COPY src/apache2.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Установка зависимостей (если используете composer)
# COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# RUN composer install

EXPOSE 80
