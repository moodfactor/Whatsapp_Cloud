#!/bin/bash

echo "🔍 Laravel Application Diagnostics"
echo "================================="
echo ""

# Check current directory
echo "📍 Current Directory:"
pwd
echo ""

# Check if we're in the right place
echo "📂 Directory Contents:"
ls -la
echo ""

# Check if Laravel files exist
echo "🔧 Laravel Core Files Check:"
echo "- composer.json: $([ -f composer.json ] && echo '✅ EXISTS' || echo '❌ MISSING')"
echo "- artisan: $([ -f artisan ] && echo '✅ EXISTS' || echo '❌ MISSING')"
echo "- vendor/: $([ -d vendor ] && echo '✅ EXISTS' || echo '❌ MISSING')"
echo "- bootstrap/app.php: $([ -f bootstrap/app.php ] && echo '✅ EXISTS' || echo '❌ MISSING')"
echo "- public/index.php: $([ -f public/index.php ] && echo '✅ EXISTS' || echo '❌ MISSING')"
echo ""

# Check .env file
echo "⚙️ Environment Configuration:"
if [ -f .env ]; then
    echo "- .env file: ✅ EXISTS"
    echo "- APP_KEY: $(grep APP_KEY .env | cut -d'=' -f2 | head -c20)..."
    echo "- APP_ENV: $(grep APP_ENV .env | cut -d'=' -f2)"
    echo "- APP_DEBUG: $(grep APP_DEBUG .env | cut -d'=' -f2)"
    echo "- APP_URL: $(grep APP_URL .env | cut -d'=' -f2)"
else
    echo "- .env file: ❌ MISSING"
fi
echo ""

# Check PHP version and extensions
echo "🐘 PHP Information:"
echo "- PHP Version: $(php -v | head -n 1)"
echo "- PHP Extensions:"
php -m | grep -E "(openssl|pdo|mbstring|tokenizer|xml|ctype|json|bcmath)" | sed 's/^/  - /'
echo ""

# Check if composer autoload exists
echo "📚 Composer Autoload:"
if [ -f vendor/autoload.php ]; then
    echo "- vendor/autoload.php: ✅ EXISTS"
else
    echo "- vendor/autoload.php: ❌ MISSING"
    echo "  Run: composer install"
fi
echo ""

# Try to run artisan commands
echo "🎯 Laravel Artisan Status:"
if [ -f artisan ]; then
    echo "Testing artisan commands..."
    
    # Test basic artisan
    if php artisan --version 2>/dev/null; then
        echo "- Artisan: ✅ WORKING"
    else
        echo "- Artisan: ❌ ERROR"
        echo "  Error details:"
        php artisan --version 2>&1 | head -5 | sed 's/^/    /'
    fi
    
    # Test route list
    echo ""
    echo "- Route List Test:"
    ROUTES=$(php artisan route:list --compact 2>/dev/null | wc -l)
    if [ "$ROUTES" -gt 5 ]; then
        echo "  ✅ $ROUTES routes found"
        echo "  Sample routes:"
        php artisan route:list --compact 2>/dev/null | head -5 | sed 's/^/    /'
    else
        echo "  ❌ No routes found ($ROUTES)"
        echo "  Route list error:"
        php artisan route:list 2>&1 | head -5 | sed 's/^/    /'
    fi
else
    echo "- Artisan: ❌ NOT FOUND"
fi
echo ""

# Check web server setup
echo "🌐 Web Server Setup:"
echo "- Document root should be: $(pwd)/public"
echo "- .htaccess in public/: $([ -f public/.htaccess ] && echo '✅ EXISTS' || echo '❌ MISSING')"
echo ""

# Check storage permissions
echo "💾 Storage & Permissions:"
echo "- storage/: $([ -d storage ] && echo '✅ EXISTS' || echo '❌ MISSING')"
echo "- storage writable: $([ -w storage ] && echo '✅ YES' || echo '❌ NO')"
echo "- bootstrap/cache/: $([ -d bootstrap/cache ] && echo '✅ EXISTS' || echo '❌ MISSING')"
echo "- bootstrap/cache writable: $([ -w bootstrap/cache ] && echo '✅ YES' || echo '❌ NO')"
echo ""

# Check Laravel logs
echo "📝 Laravel Logs:"
if [ -f storage/logs/laravel.log ]; then
    echo "- Laravel log exists: ✅ YES"
    echo "- Recent errors (last 10 lines):"
    tail -10 storage/logs/laravel.log | sed 's/^/    /'
else
    echo "- Laravel log: ❌ NOT FOUND"
fi
echo ""

# Test a simple PHP script
echo "🧪 PHP Execution Test:"
php -r "echo 'PHP is working: ✅ YES\n';"
echo ""

# Check if this is actually in the web root
echo "🔗 Web Access Test:"
echo "Current path: $(pwd)"
echo "Expected web path: /home/u539863725/domains/al-najjarstore.com/public_html/connect"
echo "Web URL: https://connect.al-najjarstore.com"
echo ""
echo "📋 To test web access, create a test file:"
echo "echo '<?php echo \"Web access working!\"; ?>' > test.php"
echo "Then visit: https://connect.al-najjarstore.com/test.php"

echo ""
echo "🎯 Diagnosis complete! Share this output to identify the issue."