#!/bin/bash

# Stop script on any error
set -e

echo "🚀 Starting Auth Project setup..."

# Check if docker-compose exists
if [ ! -f "docker-compose.yml" ]; then
    echo "❌ docker-compose.yml not found!"
    exit 1
fi

# Create environment file
if [ ! -f ".env" ]; then
    echo "📝 Creating environment file..."
    cp .env.example .env
fi

# Start Docker containers
echo "🐳 Starting Docker containers..."
docker-compose down -v
docker-compose up -d --build

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
sleep 10

# Install Composer packages
echo "📦 Installing Composer packages..."
docker exec auth-app composer install

# Generate Laravel key
echo "🔑 Generating Laravel key..."
docker exec auth-app php artisan key:generate

# Run migrations and seeders
echo "🗄️ Running database migrations and seeders..."
docker exec auth-app php artisan migrate --seed

# Clear cache
echo "🧹 Clearing cache..."
docker exec auth-app php artisan config:clear
docker exec auth-app php artisan cache:clear

# Generate API documentation
echo "📚 Generating API documentation..."
docker exec auth-app php artisan scribe:generate

echo "✅ Setup completed!"
echo "🌐 Access the application at: http://localhost:8000"
echo "📚 API Documentation: http://localhost:8000/docs"
echo "📧 Test user email: test@example.com"
echo "🔒 Test user password: Test123!@#\$%^&*" 