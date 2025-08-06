#!/bin/bash

# This script will force the correct database credentials into the .env file,
# clear all caches, and then run the migrations.

# Set the correct database credentials
DB_DATABASE=u539863725_whatsapp
DB_USERNAME=u539863725_ahmedegy
DB_PASSWORD=stmUA123

# Force the credentials into the .env file
sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_DATABASE/" .env
sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USERNAME/" .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" .env

# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Run the migrations
php artisan migrate --force
