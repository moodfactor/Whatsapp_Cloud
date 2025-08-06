#!/bin/bash

# Complete WhatsApp Microservice Deployment with Laravel Structure Fix
# This script uploads the missing Laravel files and completes the setup

set -e # Exit on any error

# Configuration
SSH_USER="u539863725"
SSH_HOST="185.212.71.93"
SSH_PORT="65002"
REMOTE_PATH="/home/u539863725/domains/al-najjarstore.com/public_html/connect"
ZIP_FILE="laravel-structure-fix.zip"

echo "🚀 Complete Laravel Structure Deployment"
echo "========================================"

# Step 1: Create package with Laravel structure files
echo "📦 Creating Laravel structure package..."

# Remove old zip file if it exists
rm -f "$ZIP_FILE"

# Create zip with only the Laravel structure files we just created
zip -r "$ZIP_FILE" \
    app/Providers/ \
    app/Http/Kernel.php \
    app/Http/Middleware/ \
    complete-deployment.sh \
    -x "*.git*"

if [ ! -f "$ZIP_FILE" ]; then
    echo "❌ Failed to create Laravel structure package"
    exit 1
fi

echo "✅ Laravel structure package created: $ZIP_FILE"
echo "📊 Package size: $(du -h $ZIP_FILE | cut -f1)"

# Step 2: Transfer files to server
echo ""
echo "📡 Transferring Laravel structure to server..."
echo "Target: $SSH_USER@$SSH_HOST:$REMOTE_PATH"

scp -P $SSH_PORT $ZIP_FILE $SSH_USER@$SSH_HOST:$REMOTE_PATH/$ZIP_FILE

if [ $? -ne 0 ]; then
    echo "❌ Failed to transfer files. Check your SSH connection."
    exit 1
fi

echo "✅ Files transferred successfully"

# Step 3: Deploy Laravel structure and complete setup
echo ""
echo "🔧 Deploying Laravel structure and completing setup..."

ssh -p $SSH_PORT $SSH_USER@$SSH_HOST << 'REMOTE_COMMANDS'
    echo "📍 Navigating to project directory..."
    cd /home/u539863725/domains/al-najjarstore.com/public_html/connect || { 
        echo "❌ Failed to navigate to project directory"; 
        exit 1; 
    }
    
    echo "📦 Extracting Laravel structure files..."
    unzip -o laravel-structure-fix.zip -d . || { 
        echo "❌ Failed to extract files"; 
        exit 1; 
    }
    
    # Clean up zip file
    rm laravel-structure-fix.zip
    
    echo "🔧 Making deployment script executable..."
    chmod +x complete-deployment.sh
    
    echo "🚀 Running complete deployment script..."
    ./complete-deployment.sh
    
    echo "✅ Laravel structure deployment completed"
REMOTE_COMMANDS

if [ $? -eq 0 ]; then
    echo ""
    echo "🎉 Complete Laravel deployment successful!"
    echo ""
    echo "📋 The application should now work properly at:"
    echo "   https://connect.al-najjarstore.com/admin/login"
    echo ""
    echo "🔑 Default Admin Credentials:"
    echo "   Email: admin@connect.al-najjarstore.com"
    echo "   Password: admin123"
    echo ""
    echo "⚠️ IMPORTANT: Change the default password immediately!"
    echo ""
    echo "🔍 If you still get 404 errors, check:"
    echo "   1. Database configuration in .env file"
    echo "   2. Run: php artisan route:list to verify routes"
    echo "   3. Check Laravel logs: tail -f storage/logs/laravel.log"
else
    echo "❌ Deployment failed. Check the output above for errors."
    exit 1
fi

# Clean up local zip file
rm -f "$ZIP_FILE"
echo "🧹 Local cleanup completed"