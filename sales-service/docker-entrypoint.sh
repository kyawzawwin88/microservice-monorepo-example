#!/usr/bin/env bash
set -e

# Named volume for vendor/ preserves data from image build,
# but we still need to run composer install if:
#   1. vendor/autoload.php is missing (first run with empty volume)
#   2. composer.json has changed since last install
if [ ! -f vendor/autoload.php ]; then
    echo "⏳  vendor/autoload.php missing — running composer install …"
    composer install --no-interaction --optimize-autoloader
elif [ composer.json -nt vendor/autoload.php ]; then
    echo "🔄  composer.json changed — running composer install …"
    composer install --no-interaction --optimize-autoloader
fi

# Ensure storage directories exist with correct permissions
mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Run database migrations
echo "🗄️  Running migrations …"
php artisan migrate --force 2>/dev/null || {
    echo "⚠️  Migration failed — retrying in 5s …"
    sleep 5
    php artisan migrate --force
}

# Publish Waterline workflow monitoring assets
echo "🌊  Publishing Waterline assets …"
php artisan waterline:publish 2>/dev/null || true

exec "$@"
