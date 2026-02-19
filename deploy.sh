#!/bin/bash
set -e

echo "🚀 Deploying Family Hub..."

# Pull latest code
git pull origin main

# Build and restart
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d

# Setup
docker exec family_prod_app php artisan config:cache
docker exec family_prod_app php artisan route:cache
docker exec family_prod_app php artisan view:cache
docker exec family_prod_app php artisan mongo:indexes
docker exec family_prod_app php artisan db:seed --class=ShoppingItemSeeder --force

echo "✅ Deploy complete!"
