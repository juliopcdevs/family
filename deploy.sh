#!/bin/bash
set -e

echo "🚀 Deploying Family Hub..."

# Pull latest code
git pull origin main

# Build and restart
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d

# Run migrations
docker exec family_prod_app php artisan migrate --force

# Clear caches
docker exec family_prod_app php artisan config:cache
docker exec family_prod_app php artisan route:cache
docker exec family_prod_app php artisan view:cache

echo "✅ Deploy complete!"
