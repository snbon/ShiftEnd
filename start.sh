#!/bin/bash

# Convert PORT to integer if it's a string
PORT=${PORT:-8080}
PORT=$(($PORT + 0))

echo "Starting Laravel on port: $PORT"

# Uncomment the next 4 lines when you need to reset the database
echo "Running database migrations..."
php artisan migrate:fresh --force
echo "Running database seeders..."
php artisan db:seed --force

# Clear all caches
echo "Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Start Laravel server
echo "Starting Laravel server..."
exec php artisan serve --host=0.0.0.0 --port=$PORT 