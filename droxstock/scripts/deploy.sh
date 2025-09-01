#!/bin/bash

# DroxStock Deployment Script for EC2
set -e

echo "🚀 Starting DroxStock deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/home/ubuntu/droxstock"
BACKUP_DIR="/home/ubuntu/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

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

# Check if running as correct user
if [ "$USER" != "ubuntu" ]; then
    print_error "This script must be run as ubuntu user"
    exit 1
fi

# Navigate to application directory
cd $APP_DIR

# Create backup directory if it doesn't exist
mkdir -p $BACKUP_DIR

# Backup database
print_status "Backing up database..."
docker-compose exec -T db pg_dump -U droxstock droxstock | gzip > "$BACKUP_DIR/db_backup_$TIMESTAMP.sql.gz"

# Pull latest code
print_status "Pulling latest code from repository..."
git fetch --all
git reset --hard origin/main

# Copy environment file if it doesn't exist
if [ ! -f .env ]; then
    print_warning ".env file not found. Copying from .env.production..."
    cp .env.production .env
fi

# Build and restart containers
print_status "Building Docker containers..."
docker-compose build --no-cache

print_status "Stopping current containers..."
docker-compose down

print_status "Starting new containers..."
docker-compose up -d

# Wait for services to be ready
print_status "Waiting for services to be ready..."
sleep 10

# Run migrations
print_status "Running database migrations..."
docker-compose exec -T app php artisan migrate --force

# Clear and rebuild caches
print_status "Clearing and rebuilding caches..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache
docker-compose exec -T app php artisan event:cache

# Restart queue workers
print_status "Restarting queue workers..."
docker-compose exec -T app php artisan queue:restart

# Set proper permissions
print_status "Setting proper permissions..."
docker-compose exec -T app chown -R www:www /var/www/html/storage
docker-compose exec -T app chmod -R 775 /var/www/html/storage
docker-compose exec -T app chmod -R 775 /var/www/html/bootstrap/cache

# Clean up old Docker resources
print_status "Cleaning up Docker resources..."
docker system prune -af --volumes

# Clean up old backups (keep last 7 days)
print_status "Cleaning up old backups..."
find $BACKUP_DIR -name "db_backup_*.sql.gz" -mtime +7 -delete

# Health check
print_status "Running health check..."
HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health)

if [ "$HEALTH_CHECK" = "200" ]; then
    print_status "Deployment completed successfully! ✨"

    # Show container status
    echo ""
    echo "Container Status:"
    docker-compose ps

    # Show recent logs
    echo ""
    echo "Recent application logs:"
    docker-compose logs --tail=20 app
else
    print_error "Health check failed! HTTP Status: $HEALTH_CHECK"

    # Show error logs
    echo ""
    echo "Error logs:"
    docker-compose logs --tail=50 app

    # Rollback option
    read -p "Do you want to rollback to previous version? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        ./scripts/rollback.sh
    fi

    exit 1
fi

echo ""
echo "📊 Deployment Summary:"
echo "- Timestamp: $TIMESTAMP"
echo "- Git Commit: $(git rev-parse --short HEAD)"
echo "- Branch: $(git branch --show-current)"
echo "- Database Backup: $BACKUP_DIR/db_backup_$TIMESTAMP.sql.gz"
echo ""
echo "🎉 DroxStock API is now live!"
