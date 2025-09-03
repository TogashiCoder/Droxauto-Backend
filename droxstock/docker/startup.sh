#!/bin/sh

# Wait for database to be ready
echo "Waiting for database connection..."
until php -r "
try {
    \$pdo = new PDO('pgsql:host=droxautodb-rds.cz262kws24oh.eu-north-1.rds.amazonaws.com;port=5432;dbname=droxautodb_aws', 'postgres_aws', 'api_drox_2001_togashi');
    echo 'OK';
    exit(0);
} catch (Exception \$e) {
    exit(1);
}
" > /dev/null 2>&1; do
    echo "Database not ready, waiting..."
    sleep 2
done

echo "Database connected! Setting up Laravel..."

# Install migrations table if it doesn't exist
php artisan migrate:install --force 2>/dev/null || true

# Create cache, session, and queue tables
php artisan cache:table --force 2>/dev/null || true
php artisan session:table --force 2>/dev/null || true
php artisan queue:table --force 2>/dev/null || true

# Run all migrations
php artisan migrate --force

# Run seeders to create roles, permissions, and default users
php artisan db:seed --class=RolePermissionSeeder --force

echo "Database setup complete!"

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
