#!/bin/bash
# E2E Test Setup Script
# This script sets up the environment for running E2E tests

set -e

echo "🚀 Setting up E2E test environment..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker Desktop."
    exit 1
fi

echo "✅ Docker is running"

# Start Docker containers
echo "📦 Starting Docker containers..."
docker-compose up -d

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 10

# Check if containers are running
if ! docker-compose ps | grep -q "Up"; then
    echo "❌ Docker containers are not running properly"
    exit 1
fi

echo "✅ Docker containers are running"

# Run database migrations
echo "🗄️  Running database migrations..."
docker-compose exec -T app php artisan migrate:fresh --force

# Seed database with test data
echo "🌱 Seeding database with test data..."
docker-compose exec -T app php artisan db:seed --force

echo "✅ Database seeded successfully"

# Clear cache
echo "🧹 Clearing cache..."
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan route:clear
docker-compose exec -T app php artisan view:clear

echo "✅ Cache cleared"

# Start Laravel development server (if not running)
echo "🌐 Checking if Laravel server is running..."
if ! curl -s http://localhost:8000 > /dev/null; then
    echo "⚠️  Laravel server is not running on port 8000"
    echo "   Starting server in background..."
    docker-compose exec -d app php artisan serve --host=0.0.0.0 --port=8000
    sleep 5
fi

# Verify server is accessible
if curl -s http://localhost:8000 > /dev/null; then
    echo "✅ Laravel server is accessible at http://localhost:8000"
else
    echo "❌ Laravel server is not accessible"
    exit 1
fi

echo ""
echo "✅ E2E test environment is ready!"
echo ""
echo "You can now run E2E tests with:"
echo "  npm run test:e2e"
echo ""

