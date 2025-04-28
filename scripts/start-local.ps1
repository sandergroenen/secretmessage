# Windows PowerShell script for starting Laravel Docker environment

Write-Host "Starting Laravel Docker environment..."

# Check architecture (Windows ARM64 handling)
$arch = (Get-WmiObject -Class Win32_Processor).Architecture
if ($arch -eq 12) { # ARM64 architecture code
    Write-Host "Detected ARM64 architecture"
    Write-Host "Using platform-specific settings for compatibility"
    # This helps with platform compatibility issues
    $env:DOCKER_DEFAULT_PLATFORM = "linux/amd64"
}

# Add debugging information
Write-Host "Current directory: $(Get-Location)"
Write-Host "Checking for .env file: $(if (Test-Path ".env*") { Get-ChildItem -Path ".env*" | Select-Object -ExpandProperty Name } else { '.env* files not found' })"
Write-Host ".env exists: $(if (Test-Path ".env") { 'Yes' } else { 'No' })"
Write-Host ".env.example exists: $(if (Test-Path ".env.example") { 'Yes' } else { 'No' })"

# Check if .env file exists, if not copy from .env.example
if (-not (Test-Path ".env") -and (Test-Path ".env.example")) {
    Write-Host "Creating .env file from .env.example..."
    Copy-Item -Path ".env.example" -Destination ".env"
    Write-Host "After copy, .env exists: $(if (Test-Path ".env") { 'Yes' } else { 'No' })"
}
else {
    Write-Host "Not creating .env file because:"
    if (Test-Path ".env") { Write-Host "- .env file already exists" }
    if (-not (Test-Path ".env.example")) { Write-Host "- .env.example file does not exist" }
}

# Manually create .env file if it still doesn't exist
if (-not (Test-Path ".env")) {
    Write-Host "Manually creating .env file..."
    New-Item -Path ".env" -ItemType File -Force
    Get-Content -Path ".env.example" | Set-Content -Path ".env"
}

# Build and start the containers
Write-Host "Building and starting Docker containers..."
docker compose up -d --build

Write-Host "Waiting for containers to start..."
Start-Sleep -Seconds 5

# Verify .env file is visible in container
Write-Host "Verifying .env file in container..."
docker compose exec -T app ls -la /var/www | Select-String ".env"

# If .env file is not visible in container, create it directly
Write-Host "Ensuring .env file exists in container..."
docker compose exec -T app bash -c "if [ ! -f /var/www/.env ]; then cp /var/www/.env.example /var/www/.env; fi"

# Install Laravel dependencies
Write-Host "Installing dependencies..."
docker compose exec -T app composer install

# Generate application key
Write-Host "Generating application key..."
docker compose exec -T app php artisan key:generate --no-interaction

# Clear all caches
Write-Host "Clearing all caches..."
docker compose exec -T app php artisan optimize:clear

# Run migrations
Write-Host "Running database migrations..."
docker compose exec -T app php artisan migrate --force

# Seed the database
Write-Host "Seeding the database..."
docker compose exec -T app php artisan db:seed --force

# Install npm dependencies
Write-Host "Installing npm dependencies..."
docker compose exec -T app npm install

# Start Vite development server in the background within the app container
Write-Host "Starting Vite development server in the background..."
docker compose exec -T app bash -c "cd /var/www && npm run vite &"

Write-Host "Laravel application is now running at http://localhost"
Write-Host "Vite development server is running at http://localhost:5173"
