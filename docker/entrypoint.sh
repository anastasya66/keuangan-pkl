#!/bin/sh
set -e

echo "==> Starting FinToko Application Container..."

# Ensure storage and bootstrap directories exist with correct permissions
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Setup .env if missing from .env.example
if [ ! -f /var/www/html/.env ]; then
    if [ -f /var/www/html/.env.example ]; then
        echo "==> Copying .env.example to .env..."
        cp /var/www/html/.env.example /var/www/html/.env
    fi
fi

# Generate APP_KEY if not already set
if ! grep -q "^APP_KEY=base64:" /var/www/html/.env 2>/dev/null; then
    echo "==> Generating Application Key..."
    php artisan key:generate --force
fi

# Wait for MariaDB to become ready
if [ -n "$DB_HOST" ]; then
    echo "==> Waiting for MariaDB at $DB_HOST:${DB_PORT:-3306}..."
    max_tries=30
    count=0
    until nc -z -v -w3 "$DB_HOST" "${DB_PORT:-3306}" 2>/dev/null; do
        count=$((count + 1))
        if [ $count -ge $max_tries ]; then
            echo "==> Timeout waiting for MariaDB database!"
            break
        fi
        sleep 2
    done
    echo "==> MariaDB is ready!"
fi

# Run Database Migrations
echo "==> Running Database Migrations..."
php artisan migrate --force

# Optional Database Seeding
if [ "$SEED_DATABASE" = "true" ]; then
    echo "==> Seeding Database..."
    php artisan db:seed --force
fi

# Create Storage Symlink
php artisan storage:link --force 2>/dev/null || true

# Clear and optimize configuration cache
php artisan optimize:clear

echo "==> FinToko Container initialization complete. Handing over to PHP-FPM..."

exec "$@"
