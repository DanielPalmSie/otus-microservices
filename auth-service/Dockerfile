# Используем официальный образ PHP с Apache
FROM php:8.2-apache

RUN echo 'Acquire::http::Pipeline-Depth 0;\nAcquire::http::No-Cache true;\nAcquire::BrokenProxy true;' > /etc/apt/apt.conf.d/99fixbadproxy

RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && rm -rf /var/lib/apt/lists/*

# Включаем модуль Apache rewrite
RUN a2enmod rewrite

# Копирование Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создаем пользователя для безопасной работы
RUN useradd -m myuser
USER myuser

COPY apache2.conf /etc/apache2/sites-available/000-default.conf

# Установка рабочей директории
WORKDIR /var/www/html

# Копирование файлов проекта
COPY --chown=myuser:myuser . /var/www/html

COPY php.ini /usr/local/etc/php/conf.d/php.ini

# Установка зависимостей
RUN composer install --no-dev --optimize-autoloader

# Возвращаем пользователя root для дальнейших настроек
USER root
RUN chown -R www-data:www-data /var/www/html

EXPOSE 81