<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $admin = Session::get('admin_user');
        
        // Check if admin is logged in
        if (!$admin) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            return redirect()->route('admin.login')
                ->with('error', 'Please login to access admin panel');
        }
        
        // Check specific permissions if provided
        if (!empty($permissions)) {
            $hasPermission = false;
            
            // Super admin has all permissions
            if ($admin['role'] === 'super_admin') {
                $hasPermission = true;
            } else {
                // Check if user has any of the required permissions
                foreach ($permissions as $permission) {
                    if (in_array($permission, $admin['permissions'])) {
                        $hasPermission = true;
                        break;
                    }
                }
            }
            
            if (!$hasPermission) {
                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json(['error' => 'Insufficient permissions'], 403);
                }
                
                return redirect()->route('admin.dashboard')
                    ->with('error', 'You do not have permission to access this resource');
            }
        }
        
        return $next($request);
    }
}