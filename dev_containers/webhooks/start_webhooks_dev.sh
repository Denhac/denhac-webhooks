#! /usr/bin/env bash

# ln -s /var/www/container_build/vendor /var/www/html/vendor

echo "Running Migrations"

php artisan migrate

echo "Starting PHP"

php-fpm &

tail -fn 0 storage/logs/laravel.log
