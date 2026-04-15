#!/bin/bash
set -e

# Run migrations (sqlite file will be in the persisted volume)
php artisan migrate --force

# Ensure proper permissions on storage
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
