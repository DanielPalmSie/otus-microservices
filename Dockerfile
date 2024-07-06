FROM php:8.3-cli

RUN apt-get update && \
    apt-get install -y git && \
    apt-get install -y libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql && \
    rm -rf /var/lib/apt/lists/*

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

COPY src /var/www/html/
COPY init.sql /docker-entrypoint-initdb.d/

WORKDIR /var/www/html/