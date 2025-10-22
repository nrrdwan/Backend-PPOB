#!/usr/bin/env bash

echo "Running Composer"
composer install --no-dev --working-dir=/var/www/html

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Running migrations..."
php artisan migrate --force

# Penting: Pastikan public storage dilink
php artisan storage:link

echo "Deployment complete."