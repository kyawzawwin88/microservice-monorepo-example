#!/bin/bash
# =================================================================
# Microservice Monorepo Setup Script
# =================================================================
# This script initializes all services, runs migrations,
# and prepares the environment for development.
# =================================================================

set -e

echo "=========================================="
echo "  Microservice Monorepo Setup"
echo "=========================================="

SERVICES=("sales-service" "invoice-service" "payment-service" "inventory-service")

# Step 1: Install Composer dependencies for each service
echo ""
echo "📦 Installing Composer dependencies..."
for service in "${SERVICES[@]}"; do
    echo "  → Installing ${service}..."
    cd "/var/www/${service}" 2>/dev/null || cd "${service}"
    composer install --no-interaction --prefer-dist
    cd ..
done

# Step 2: Run migrations for each service
echo ""
echo "🗃️  Running database migrations..."
for service in "${SERVICES[@]}"; do
    echo "  → Migrating ${service}..."
    cd "${service}"
    php artisan migrate --force
    cd ..
done

# Step 3: Run seeders (inventory service)
echo ""
echo "🌱 Seeding inventory data..."
cd inventory-service
php artisan db:seed --class=Database\\Seeders\\InventorySeeder --force
cd ..

# Step 4: Publish workflow migrations
echo ""
echo "⚙️  Publishing workflow package migrations..."
for service in "${SERVICES[@]}"; do
    echo "  → Publishing for ${service}..."
    cd "${service}"
    php artisan vendor:publish --provider="Workflow\Providers\WorkflowServiceProvider" --tag="migrations" 2>/dev/null || true
    php artisan migrate --force
    cd ..
done

echo ""
echo "=========================================="
echo "  ✅ Setup Complete!"
echo "=========================================="
echo ""
echo "Services running at:"
echo "  Sales:     http://localhost:8001/api/health"
echo "  Invoice:   http://localhost:8002/api/health"
echo "  Payment:   http://localhost:8003/api/health"
echo "  Inventory: http://localhost:8004/api/health"
echo ""
echo "To create a test order:"
echo '  curl -X POST http://localhost:8001/api/orders \'
echo '    -H "Content-Type: application/json" \'
echo '    -d '\''{"customer_name":"John Doe","customer_email":"john@example.com","total_amount":99.99,"items":[{"product_name":"Widget A","quantity":2,"unit_price":49.99}]}'\'''
echo ""
