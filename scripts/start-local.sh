#!/bin/bash

echo "Starting Laravel Docker environment..."

# Check architecture for Apple Silicon compatibility
ARCH=$(uname -m)
if [[ "$ARCH" == "arm64" ]]; then
    echo "Detected Apple Silicon (ARM64) architecture"
    echo "Using platform-specific settings for compatibility"
    # This helps with platform compatibility issues
    export DOCKER_DEFAULT_PLATFORM=linux/amd64
fi

# Add debugging information
echo "Current directory: $(pwd)"
echo "Checking for .env file: $(ls -la .env* 2>/dev/null || echo '.env* files not found')"
echo ".env exists: $([ -f ".env" ] && echo 'Yes' || echo 'No')"
echo ".env.example exists: $([ -f ".env.example" ] && echo 'Yes' || echo 'No')"

# Check if .env file exists, if not copy from .env.example
if [ ! -f ".env" ] && [ -f ".env.example" ]; then
    echo "Creating .env file from .env.example..."
    cp .env.example .env
    # Ensure proper permissions on the .env file
    chmod 644 .env
    echo "After copy, .env exists: $([ -f ".env" ] && echo 'Yes' || echo 'No')"
else
    echo "Not creating .env file because:"
    [ -f ".env" ] && echo "- .env file already exists"
    [ ! -f ".env.example" ] && echo "- .env.example file does not exist"
fi

# Manually create .env file if it still doesn't exist
if [ ! -f ".env" ]; then
    echo "Manually creating .env file..."
    touch .env
    cat .env.example > .env
    chmod 644 .env
fi

# Build and start the containers
# temp disable build to speed up start process
# docker compose up -d --build
docker compose up -d --force-recreate

echo "Waiting for containers to start..."
sleep 5

# Verify .env file is visible in container
echo "Verifying .env file in container..."
docker compose exec -T app ls -la /var/www | grep .env

# If .env file is not visible in container, create it directly
echo "Ensuring .env file exists in container..."
docker compose exec -T app bash -c "if [ ! -f /var/www/.env ]; then cp /var/www/.env.example /var/www/.env; fi"

# Install Laravel dependencies
echo "Installing dependencies..."
docker compose exec -T app composer install --no-cache --no-interaction

# Generate application key
echo "Generating application key..."
docker compose exec -T app php artisan key:generate --no-interaction

# Clear all caches
echo "Clearing all caches..."
docker compose exec -T app php artisan optimize:clear

# Run migrations
echo "Running database migrations..."
docker compose exec -T app php artisan migrate --force

echo "Seeding the database..."
docker compose exec -T app php artisan db:seed --force

# Install npm dependencies
echo "Installing npm dependencies..."
docker compose exec -T app npm install

# Install npm dependencies
# echo "Starting reverb server in the background..."
# docker compose exec -T app bash -c "cd /var/www && php artisan reverb:start --debug &"

# Start queue worker in the background
echo "Starting queue worker in the background..."
docker compose exec -T app bash -c "cd /var/www && php artisan queue:work --tries=3 --timeout=90 &"

#Start reverb
echo "Starting reverb server in the background..."
docker compose exec -T app bash -c "cd /var/www && php artisan reverb:start --debug &"

# Start Vite development server in the background within the app container
echo "Starting Vite development server in the background..."
docker compose exec -T app bash -c "cd /var/www && npm run dev &"

echo "Laravel application is now running at http://localhost"
echo "Vite development server is running at http://localhost:5173"
