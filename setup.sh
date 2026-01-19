#!/bin/bash

echo "🚀 ChatApp Setup Script"
echo "======================="
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "📋 Copying .env.example to .env..."
    cp .env.example .env
fi

# Generate app key
echo "🔑 Generating application key..."
php artisan key:generate

# Install dependencies if needed
if [ ! -d "vendor" ]; then
    echo "📦 Installing PHP dependencies..."
    composer install
fi

if [ ! -d "node_modules" ]; then
    echo "📦 Installing JavaScript dependencies..."
    npm install
fi

# Run migrations
echo "🗄️  Running database migrations..."
php artisan migrate

# Create storage link
echo "🔗 Creating storage symlink..."
php artisan storage:link

# Build assets
echo "🎨 Building frontend assets..."
npm run build

# Create directories
echo "📁 Creating storage directories..."
mkdir -p storage/app/public/attachments
mkdir -p storage/app/public/media
mkdir -p storage/app/public/stickers
mkdir -p storage/app/public/group-avatars

echo ""
echo "✅ Setup complete!"
echo ""
echo "⚠️  IMPORTANT: Configure your .env file with:"
echo "   - Database credentials"
echo "   - Pusher credentials (get from https://pusher.com)"
echo "   - Redis configuration"
echo ""
echo "📖 Then run: composer dev"
echo "   Or start services individually:"
echo "   - php artisan serve"
echo "   - php artisan queue:listen"
echo "   - npm run dev"
echo ""
echo "🎉 Access the app at: http://localhost:8000"

