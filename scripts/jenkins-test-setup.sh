#!/bin/bash

# Jenkins Test Setup Script
# This script sets up the testing environment for Jenkins CI/CD pipeline

set -e  # Exit on any error

echo "🚀 Starting Jenkins test environment setup..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Create necessary directories
echo "📁 Creating required directories..."
mkdir -p test-results
mkdir -p coverage/html
mkdir -p storage/logs
mkdir -p bootstrap/cache
print_status "Directories created"

# Copy environment configuration
echo "⚙️  Setting up environment configuration..."
if [ ! -f .env.testing ]; then
    cp .env.example .env.testing
    print_status "Environment file created"
else
    print_status "Environment file already exists"
fi

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
composer install --optimize-autoloader --no-interaction
print_status "PHP dependencies installed"

# Install Node.js dependencies
echo "📦 Installing Node.js dependencies..."
npm ci --silent
print_status "Node.js dependencies installed"

# Generate application key
echo "🔑 Generating application key..."
php artisan key:generate --env=testing --force
print_status "Application key generated"

# Clear all caches
echo "🧹 Clearing application caches..."
php artisan config:clear --env=testing
php artisan cache:clear --env=testing
php artisan route:clear --env=testing
php artisan view:clear --env=testing
print_status "Caches cleared"

# Set up test database
echo "🗄️  Setting up test database..."
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    # Create SQLite database file if needed
    touch database/testing.sqlite
    print_status "SQLite test database file created"
fi

# Run database migrations
echo "🔄 Running database migrations..."
php artisan migrate:fresh --env=testing --force
print_status "Database migrations completed"

# Seed test database
echo "🌱 Seeding test database..."
php artisan db:seed --env=testing --force --class=TestingSeeder
print_status "Test database seeded"

# Cache configuration for better performance
echo "⚡ Caching configuration..."
php artisan config:cache --env=testing
print_status "Configuration cached"

# Verify setup
echo "🔍 Verifying setup..."

# Check if Laravel can boot
php artisan --version > /dev/null
print_status "Laravel application boots successfully"

# Check database connection
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connection successful';" > /dev/null
print_status "Database connection verified"

# Check if tests can run
if vendor/bin/phpunit --list-tests > /dev/null 2>&1; then
    print_status "PHPUnit configuration is valid"
else
    print_warning "PHPUnit configuration may have issues"
fi

echo ""
echo "🎉 Jenkins test environment setup completed successfully!"
echo ""
echo "📊 Environment Summary:"
echo "   - PHP Version: $(php --version | head -n1)"
echo "   - Laravel Version: $(php artisan --version)"
echo "   - Node.js Version: $(node --version)"
echo "   - NPM Version: $(npm --version)"
echo "   - Database: ${DB_CONNECTION:-sqlite}"
echo ""
echo "🧪 Ready to run tests!"