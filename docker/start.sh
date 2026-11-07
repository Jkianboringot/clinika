#!/bin/sh
set -e

cd /app

echo "Running migrations..."

php artisan migrate --force
# php artisan db:seed --force

echo "Caching config, routes and views..."
php artisan optimize

php artisan storage:link || true

echo "Starting FrankenPHP on port ${PORT:-8080}..."
exec frankenphp run --config /etc/caddy/Caddyfile