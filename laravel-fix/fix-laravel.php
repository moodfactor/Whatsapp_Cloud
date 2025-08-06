<?php
/**
 * Laravel Structure Fix Script
 * Upload this file to your server and run: php fix-laravel.php
 */

echo "🚀 Fixing Laravel Application Structure\n";
echo "=====================================\n";

// Create directories if they don't exist
$directories = [
    'app/Providers',
    'app/Http/Middleware',
    'storage/framework/sessions',
    'storage/framework/cache/data',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "✅ Created directory: $dir\n";
    }
}

// Create Service Providers
$providers = [
    'app/Providers/AppServiceProvider.php' => '<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        //
    }
}',

    'app/Providers/AuthServiceProvider.php' => '<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot()
    {
        $this->registerPolicies();
    }
}',

    'app/Providers/RouteServiceProvider.php' => '<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = "/admin/dashboard";

    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware("api")
                ->prefix("api")
                ->group(base_path("routes/api.php"));

            Route::middleware("web")
                ->group(base_path("routes/web.php"));
        });
    }

    protected function configureRateLimiting()
    {
        RateLimiter::for("api", function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}'
];

// Create HTTP Kernel
$kernel = 'app/Http/Kernel.php';
$kernelContent = '<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    protected $middlewareGroups = [
        "web" => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
        "api" => [
            "throttle:api",
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    protected $routeMiddleware = [
        "auth" => \App\Http\Middleware\Authenticate::class,
        "guest" => \App\Http\Middleware\RedirectIfAuthenticated::class,
        "admin.auth" => \App\Http\Middleware\AdminAuth::class,
        "throttle" => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    ];
}';

// Create essential middleware
$middleware = [
    'app/Http/Middleware/TrustProxies.php' => '<?php
namespace App\Http\Middleware;
use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;
class TrustProxies extends Middleware {
    protected $proxies;
    protected $headers = Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO;
}',

    'app/Http/Middleware/PreventRequestsDuringMaintenance.php' => '<?php
namespace App\Http\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;
class PreventRequestsDuringMaintenance extends Middleware {
    protected $except = [];
}',

    'app/Http/Middleware/TrimStrings.php' => '<?php
namespace App\Http\Middleware;
use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;
class TrimStrings extends Middleware {
    protected $except = ["password", "password_confirmation"];
}',

    'app/Http/Middleware/EncryptCookies.php' => '<?php
namespace App\Http\Middleware;
use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;
class EncryptCookies extends Middleware {
    protected $except = [];
}',

    'app/Http/Middleware/VerifyCsrfToken.php' => '<?php
namespace App\Http\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
class VerifyCsrfToken extends Middleware {
    protected $except = ["whatsapp/webhook", "api/*"];
}',

    'app/Http/Middleware/Authenticate.php' => '<?php
namespace App\Http\Middleware;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
class Authenticate extends Middleware {
    protected function redirectTo($request) {
        if (!$request->expectsJson()) {
            return route("admin.login");
        }
    }
}',

    'app/Http/Middleware/RedirectIfAuthenticated.php' => '<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class RedirectIfAuthenticated {
    public function handle(Request $request, Closure $next, ...$guards) {
        foreach ($guards ?: [null] as $guard) {
            if (Auth::guard($guard)->check()) {
                return redirect("/admin/dashboard");
            }
        }
        return $next($request);
    }
}'
];

// Write all files
$allFiles = array_merge($providers, [$kernel => $kernelContent], $middleware);

foreach ($allFiles as $file => $content) {
    file_put_contents($file, $content);
    echo "✅ Created: $file\n";
}

// Set permissions
chmod('storage', 0755);
chmod('bootstrap/cache', 0755);
if (is_dir('storage/framework/sessions')) {
    chmod('storage/framework/sessions', 0777);
}

echo "\n🔧 Running Laravel setup commands...\n";

// Clear caches
exec('php artisan config:clear', $output1);
exec('php artisan cache:clear', $output2);
exec('php artisan route:clear', $output3);
exec('php artisan view:clear', $output4);

echo "✅ Caches cleared\n";

// Generate key if needed
if (!getenv('APP_KEY') || empty(getenv('APP_KEY'))) {
    exec('php artisan key:generate --force', $keyOutput);
    echo "✅ App key generated\n";
}

// Try to optimize
exec('php artisan config:cache', $configCache);
exec('php artisan route:cache', $routeCache);
echo "✅ Optimized for production\n";

// Test routes
exec('php artisan route:list --compact 2>/dev/null', $routeList);
$routeCount = count($routeList);

echo "\n🎉 Laravel structure fix completed!\n";
echo "Routes found: $routeCount\n";
echo "\n📋 Next steps:\n";
echo "1. Visit: https://connect.al-najjarstore.com/admin/login\n";
echo "2. If still 404, run: php artisan migrate --force\n";
echo "3. Default login: admin@connect.al-najjarstore.com / admin123\n";

?>