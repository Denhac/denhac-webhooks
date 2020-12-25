#!/bin/sh
set -e

echo "Deploying application ..."

# Enter maintenance mode
(php artisan down --message 'The app is being updated. Please try again in a minute.') || true
    # Update codebase
    git fetch origin master
    git reset --hard origin/master

    # Install dependencies based on lock file
    composer install --no-interaction --prefer-dist --optimize-autoloader

    # Migrate database
    php artisan migrate --force

    # Terminate queue workers so they'll restart
    php artisan horizon:terminate

    # Clear cache
    php artisan optimize
# Exit maintenance mode
php artisan up

echo "Application deployed!"
