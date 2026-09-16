#!/bin/bash
set -e

cd /var/www/html

# Cache config/routes/views for production speed
php artisan config:cache
php artisan route:cache
php artisan view:cache

# --- DEBUG: check if ca.pem made it into the image ---
echo "----- storage/ contents -----"
ls -la /var/www/html/storage/
echo "----- ca.pem details -----"
ls -la /var/www/html/storage/ca.pem || echo "ca.pem NOT FOUND at that path"
echo "-----------------------------"
# --- END DEBUG ---

# Run migrations (safe to run on every deploy; --force skips the confirmation prompt)
php artisan migrate --force

# php artisan db:seed --force
# Link storage (safe if already linked)
php artisan storage:link || true

echo "Starting FrankenPHP on port ${PORT:-8080}..."
exec frankenphp run --config /etc/caddy/Caddyfile