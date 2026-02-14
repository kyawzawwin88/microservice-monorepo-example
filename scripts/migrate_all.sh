#!/usr/bin/env bash
# Run migrations for all microservices
# Usage: bazelisk run //:migrate_all
set -euo pipefail

cd "${BUILD_WORKSPACE_DIRECTORY:-.}"

echo "============================================"
echo " Running migrations for ALL services"
echo "============================================"

for service in sales-service invoice-service payment-service inventory-service; do
    echo ""
    echo "--- Migrating: ${service} ---"
    docker compose exec "${service}" php artisan migrate --force 2>/dev/null || \
        docker compose run --rm "${service}" php artisan migrate --force
done

echo ""
echo "All migrations complete."
