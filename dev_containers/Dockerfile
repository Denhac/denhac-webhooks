FROM php:8.2-fpm

# When the container runs, the application will be mounted at /var/www/html
# This mount is not available at build time, and anything written to /var/www/html here will be overwritten at runtime.
# The goal here is to install php and our application dependencies, which the application expects to live at /var/www/html/vendor.
# We're going to install them to /var/www/vendor at build time, and create a symlink when the container starts.

WORKDIR /var/www/html

RUN apt-get update && \
    apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip bcmath pdo_mysql

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# We need a copy of (most of) the application files in order for composer to install the dependencies
COPY . .
RUN composer install --ignore-platform-reqs

COPY dev_containers/app_startup.sh .

# At runtime, we'll want to be working in html/ rather than container_build/
WORKDIR /var/www/html
# This command is executed at runtime, after /var/www/html is mounted from the host
CMD ["bash", "-c", "/var/www/html/app_startup.sh"]

EXPOSE 9000
