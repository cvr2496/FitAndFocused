#!/bin/bash
set -e

echo "Deploying..."

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Setup Laravel
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache

echo "Done! Run: php artisan serve --host=0.0.0.0 --port=8000"

