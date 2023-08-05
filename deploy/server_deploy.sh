#!/bin/sh
set -e

echo "Deploying application ..."

# Enter maintenance mode
php artisan down

# Update codebase
git fetch origin main
git reset --hard origin/main

# Install dependencies based on lock file
composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs

# Migrate database
php artisan migrate --force

# Terminate queue workers so they'll restart
php artisan horizon:terminate

# Clear cache
php artisan optimize

# Exit maintenance mode
php artisan up

echo "Application deployed!"
