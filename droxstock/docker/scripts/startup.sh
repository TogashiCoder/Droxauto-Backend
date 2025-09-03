#!/bin/sh

# Laravel startup script
echo "Starting Laravel application..."

# Wait for database connection
echo "Waiting for database connection..."
sleep 5

# Create database tables if they don't exist
echo "Creating database tables..."
php artisan cache:table 2>/dev/null || echo "Cache table already exists"
php artisan session:table 2>/dev/null || echo "Session table already exists"
php artisan queue:table 2>/dev/null || echo "Queue table already exists"

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Clear and cache configuration
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Laravel startup complete!"
