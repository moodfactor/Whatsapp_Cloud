#!/bin/bash

# Complete Laravel Application Setup
# Run this script on the server to complete the deployment

set -e # Exit on any error

echo "🚀 Completing Laravel Application Setup"
echo "======================================"

# Navigate to project directory
echo "📍 Navigating to project directory..."
cd /home/u539863725/domains/al-najjarstore.com/public_html/connect || {
    echo "❌ Failed to navigate to project directory"
    exit 1
}

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "⚠️ .env file not found, copying from .env.production"
    if [ -f ".env.production" ]; then
        cp .env.production .env
    else
        echo "❌ .env.production not found - create manually"
        exit 1
    fi
fi

# Generate application key if needed
echo "🔑 Checking application key..."
if ! grep -q "APP_KEY=base64:" .env; then
    echo "Generating application key..."
    php artisan key:generate --force
else
    echo "✅ Application key already exists"
fi

# Clear all caches
echo "🧹 Clearing all caches..."
php artisan config:clear
php artisan cache:clear  
php artisan route:clear
php artisan view:clear

# Create storage directories if they don't exist
echo "📁 Creating storage directories..."
mkdir -p storage/framework/sessions
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/views
mkdir -p storage/logs

# Set proper permissions
echo "🔐 Setting proper permissions..."
chmod -R 755 storage bootstrap/cache 2>/dev/null || echo "⚠️ Some permission changes failed"
chmod -R 777 storage/framework/sessions/ 2>/dev/null || echo "⚠️ Session directory permissions failed"

# Check if database configuration exists
echo "🗃️ Checking database configuration..."
if ! grep -q "DB_CONNECTION=" .env; then
    echo "⚠️ Database not configured in .env file"
    echo "Please add database configuration to .env:"
    echo "DB_CONNECTION=mysql"
    echo "DB_HOST=127.0.0.1"
    echo "DB_PORT=3306"  
    echo "DB_DATABASE=your_database_name"
    echo "DB_USERNAME=your_username"
    echo "DB_PASSWORD=your_password"
fi

# Run migrations (with error handling)
echo "🗃️ Running database migrations..."
if php artisan migrate --force 2>/dev/null; then
    echo "✅ Database migrations completed"
    
    # Seed admin users
    echo "👤 Seeding admin users..."
    if php artisan db:seed --class=AdminSeeder --force 2>/dev/null; then
        echo "✅ Admin users seeded successfully"
    else
        echo "⚠️ Admin seeding failed - will seed manually"
        # Create a super admin manually using PHP
        php -r "
        require 'vendor/autoload.php';
        \$app = require_once 'bootstrap/app.php';
        \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
        \$kernel->bootstrap();
        
        try {
            \$admin = new App\Models\WhatsappAdmin();
            \$admin->name = 'Super Admin';
            \$admin->email = 'admin@connect.al-najjarstore.com';
            \$admin->password = bcrypt('admin123');
            \$admin->role = 'Super Admin';
            \$admin->permissions = json_encode(['all']);
            \$admin->created_at = now();
            \$admin->updated_at = now();
            \$admin->save();
            echo 'Super admin created successfully';
        } catch (Exception \$e) {
            echo 'Manual admin creation failed: ' . \$e->getMessage();
        }
        "
    fi
else
    echo "⚠️ Database migrations failed - check database configuration"
    echo "Make sure to:"
    echo "1. Create the database"
    echo "2. Update .env with correct database credentials"
    echo "3. Run: php artisan migrate --force"
fi

# Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache

# Test route registration
echo "🔍 Testing route registration..."
ROUTE_COUNT=$(php artisan route:list --compact | wc -l)
if [ "$ROUTE_COUNT" -gt 5 ]; then
    echo "✅ Routes registered successfully ($ROUTE_COUNT routes found)"
else
    echo "⚠️ Few or no routes found - there may still be issues"
fi

# Final verification
echo ""
echo "🎉 Laravel application setup completed!"
echo ""
echo "📋 Verification Steps:"
echo "1. Visit: https://connect.al-najjarstore.com"
echo "2. Admin login: https://connect.al-najjarstore.com/admin/login"
echo "3. Health check: https://connect.al-najjarstore.com/health"
echo ""
echo "🔑 Default Admin Login:"
echo "Email: admin@connect.al-najjarstore.com"
echo "Password: admin123"
echo ""
echo "⚠️ IMPORTANT: Change the default password after first login!"
echo ""
echo "🔍 To check logs if issues occur:"
echo "tail -f storage/logs/laravel.log"