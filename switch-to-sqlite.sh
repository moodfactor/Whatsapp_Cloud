#!/bin/bash

echo "🔄 Switching to SQLite Database"
echo "==============================="

# Backup current .env
cp .env .env.backup

# Update .env to use SQLite
echo "📝 Updating .env configuration..."
sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env
sed -i 's/DB_HOST=127.0.0.1/# DB_HOST=127.0.0.1/' .env
sed -i 's/DB_PORT=3306/# DB_PORT=3306/' .env
sed -i 's/DB_DATABASE=u539863725_next_mejsp_bd/# DB_DATABASE=u539863725_next_mejsp_bd/' .env
sed -i 's/DB_USERNAME=root/# DB_USERNAME=root/' .env
sed -i 's/DB_PASSWORD=/# DB_PASSWORD=/' .env

echo "✅ Database configuration updated"

# Create database directory and file
echo "📁 Creating SQLite database..."
mkdir -p database
touch database/database.sqlite
chmod 664 database/database.sqlite

echo "✅ SQLite database file created"

# Clear Laravel caches
echo "🧹 Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear

echo "🗃️ Running database migrations..."
php artisan migrate --force

if [ $? -eq 0 ]; then
    echo "✅ Database migrations completed successfully!"
    
    echo "👤 Creating admin users..."
    php artisan db:seed --class=AdminSeeder --force
    
    if [ $? -eq 0 ]; then
        echo "✅ Admin users created successfully!"
        echo ""
        echo "🎉 Database setup complete!"
        echo ""
        echo "📋 You can now access:"
        echo "   Admin Panel: https://connect.al-najjarstore.com/admin/login"
        echo "   Email: admin@connect.al-najjarstore.com"
        echo "   Password: admin123"
    else
        echo "⚠️ Admin seeding failed, but you can create admin manually"
    fi
else
    echo "❌ Migration failed. Check the error above."
fi

echo ""
echo "📊 Current database configuration:"
grep -E "^(DB_CONNECTION|# DB_)" .env