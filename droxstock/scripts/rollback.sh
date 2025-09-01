#!/bin/bash

# DroxStock Rollback Script
set -e

echo "🔄 Starting DroxStock rollback..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
APP_DIR="/home/ubuntu/droxstock"
BACKUP_DIR="/home/ubuntu/backups"

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

# Navigate to application directory
cd $APP_DIR

# Get previous commit
CURRENT_COMMIT=$(git rev-parse HEAD)
PREVIOUS_COMMIT=$(git rev-parse HEAD~1)

print_status "Current commit: $CURRENT_COMMIT"
print_status "Rolling back to: $PREVIOUS_COMMIT"

# Checkout previous commit
git checkout $PREVIOUS_COMMIT

# Rebuild containers with previous version
print_status "Rebuilding containers with previous version..."
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Wait for services
sleep 10

# Run migrations (rollback if needed)
print_status "Running migrations..."
docker-compose exec -T app php artisan migrate --force

# Clear caches
print_status "Clearing caches..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

# Health check
HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health)

if [ "$HEALTH_CHECK" = "200" ]; then
    print_status "Rollback completed successfully!"
else
    print_error "Rollback failed! Health check returned: $HEALTH_CHECK"
    exit 1
fi
