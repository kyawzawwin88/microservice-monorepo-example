#!/usr/bin/env bash
set -e

if [ ! -f vendor/autoload.php ]; then
    echo "⏳  vendor/autoload.php missing — running composer install …"
    composer install --no-interaction --optimize-autoloader
elif [ composer.json -nt vendor/autoload.php ]; then
    echo "🔄  composer.json changed — running composer install …"
    composer install --no-interaction --optimize-autoloader
fi

mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

run_migrations() {
    echo "🗄️  Running migrations …"
    set +e
    php artisan migrate --force
    migrate_status=$?
    set -e
    if [ "$migrate_status" -ne 0 ]; then
        echo "⚠️  Migration failed (exit $migrate_status) — retrying in 5s …"
        sleep 5
        set +e
        php artisan migrate --force
        migrate_status=$?
        set -e
        if [ "$migrate_status" -ne 0 ]; then
            echo "⚠️  Migrations still failing — starting app anyway (schema may already be applied)."
        fi
    fi
}

# Only the HTTP service should migrate; queue workers start in parallel and can race on DDL.
if [ "${SKIP_MIGRATIONS:-false}" != "true" ]; then
    run_migrations
else
    echo "⏭️  Skipping migrations (SKIP_MIGRATIONS=true)."
fi

# Publish Waterline workflow monitoring assets
echo "🌊  Publishing Waterline assets …"
php artisan waterline:publish 2>/dev/null || true

exec "$@"
