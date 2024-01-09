FROM php:8.2-fpm

RUN apt-get update && \
    apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip bcmath pdo_mysql

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/build

COPY composer.lock .
COPY composer.json .
RUN composer install --ignore-platform-reqs --no-scripts --no-autoloader --no-interaction

COPY dev_containers/webhooks/start_webhooks_dev /var/www/

WORKDIR /var/www/html
CMD ["bash", "-c", "/var/www/start_webhooks_dev"]

EXPOSE 9000
