# Manual Setup Guide - WhatsApp Microservice

If the automated deployment fails due to the WhatsApp package dependency, follow these manual steps:

## 📋 Manual Installation Steps

### 1. Upload Files to Server

```bash
# Create a zip of the microservice (excluding problematic dependencies)
zip -r whatsapp-microservice.zip . \
    -x "Laravel-WhatsApp-CloudApi/*" \
    -x "vendor/*" \
    -x "composer.lock" \
    -x "*.log"

# Upload to server
scp -P 65002 whatsapp-microservice.zip u539863725@185.212.71.93:/home/u539863725/domains/al-najjarstore.com/public_html/connect/
```

### 2. Extract and Setup on Server

```bash
# SSH into server
ssh -p 65002 u539863725@185.212.71.93

# Navigate to project directory
cd /home/u539863725/domains/al-najjarstore.com/public_html/connect

# Extract files
unzip -o whatsapp-microservice.zip
rm whatsapp-microservice.zip

# Setup environment
cp .env.production .env

# Edit database credentials
nano .env
```
  

### 3. Install Dependencies (Without WhatsApp Package)

```bash
# Use fallback composer configuration
cp composer-fallback.json composer.json

# Download Composer
curl -sS https://getcomposer.org/installer | php

# Install dependencies
php composer.phar install --no-dev --optimize-autoloader
```

### 4. Setup Database

```bash
# Generate application key
php artisan key:generate --force

# Run migrations
php artisan migrate --force

# Seed admin users
php artisan db:seed --class=AdminSeeder
```

### 5. Set Permissions

```bash
# Set proper file permissions
chmod -R 755 storage bootstrap/cache
chmod -R 777 storage/framework/sessions/
```

### 6. Clear and Cache

```bash
# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
```

## 🔧 WhatsApp Package Manual Setup (Optional)

If you want WhatsApp functionality, you can add it manually later:

### Option 1: Install from Packagist (if available)

```bash
php composer.phar require biztecheg/laravel-whatsapp-cloud-api
```

### Option 2: Manual Package Installation

```bash
# Create directory for package
mkdir -p vendor/biztecheg/laravel-whatsapp-cloud-api

# Upload the Laravel-WhatsApp-CloudApi files to this directory
# Then run:
php composer.phar dump-autoload
php artisan package:discover
```

## ✅ Verification

After setup, verify everything works:

1. **Visit admin panel**: `https://connect.al-najjarstore.com/admin/login`
2. **Check health**: `https://connect.al-najjarstore.com/health`
3. **Test login** with default credentials

### Default Admin Credentials

- **Super Admin**: admin@connect.al-najjarstore.com / admin123
- **Admin**: whatsapp-admin@connect.al-najjarstore.com / whatsapp123
- **Supervisor**: supervisor@connect.al-najjarstore.com / supervisor123
- **Agent**: agent@connect.al-najjarstore.com / agent123

## 🚨 Important Security Notes

1. **Change default passwords** immediately after first login
2. **Update database credentials** in `.env` file
3. **Set proper file permissions** for security
4. **Enable SSL** for production use

## 📞 Support

If you encounter issues:

1. Check server error logs in cPanel
2. Check Laravel logs: `tail -f storage/logs/laravel.log`
3. Verify PHP version is 8.0+ with required extensions

The admin panel will work without the WhatsApp package - you can add WhatsApp functionality later once the core system is running.
