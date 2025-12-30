#!/bin/bash
set -e

echo "🚀 Setting up FitAndFocused on exe.dev VM..."

# Add PHP repository
echo "📦 Adding PHP 8.4 repository..."
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update

# Install system dependencies
echo "📦 Installing system dependencies..."
sudo apt-get install -y \
    php8.4-cli \
    php8.4-fpm \
    php8.4-mbstring \
    php8.4-xml \
    php8.4-bcmath \
    php8.4-curl \
    php8.4-zip \
    php8.4-sqlite3 \
    php8.4-gd \
    sqlite3 \
    git \
    curl \
    unzip

# Install Composer
if [ ! -f /usr/local/bin/composer ]; then
    echo "📦 Installing Composer..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi

# Install Node.js 20.x
if ! command -v node &> /dev/null; then
    echo "📦 Installing Node.js..."
    curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
    sudo apt-get install -y nodejs
fi

# Clone repository if not exists
if [ ! -d "$HOME/FitAndFocused" ]; then
    echo "📥 Cloning repository..."
    cd $HOME
    git clone https://github.com/cvr2496/FitAndFocused.git
fi

cd $HOME/FitAndFocused

# Pull latest changes
echo "📥 Pulling latest changes..."
git pull origin main

# Install PHP dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-interaction

# Install Node dependencies
echo "📦 Installing npm dependencies..."
npm ci

# Setup .env
if [ ! -f .env ]; then
    echo "⚙️  Setting up .env..."
    cp .env.example .env
    # Set APP_URL to current hostname
    sed -i 's|APP_URL=https://golf-scarlet.exe.xyz|APP_URL=https://'"$(hostname)"'.exe.xyz|' .env
    # Set API key if provided via environment variable
    if [ -n "$ANTHROPIC_API_KEY" ]; then
        sed -i 's/ANTHROPIC_API_KEY=your-api-key-here/ANTHROPIC_API_KEY='"$ANTHROPIC_API_KEY"'/' .env
    fi
    php artisan key:generate
fi

# Create database
echo "🗄️  Setting up database..."
touch database/database.sqlite
php artisan migrate --force

# Build frontend assets
echo "🎨 Building frontend assets..."
npm run build

# Set permissions
echo "🔒 Setting permissions..."
chmod -R 775 storage bootstrap/cache
chmod 664 database/database.sqlite

echo "✅ Setup complete!"
echo ""
echo "To start the server, run:"
echo "  cd ~/FitAndFocused && php artisan serve --host=0.0.0.0 --port=8000"
echo ""
echo "Or to keep it running in the background:"
echo "  cd ~/FitAndFocused && nohup php artisan serve --host=0.0.0.0 --port=8000 > server.log 2>&1 &"

