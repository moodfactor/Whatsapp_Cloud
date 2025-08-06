<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ConversationManagementController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// =================== WhatsApp Microservice Routes ===================

// Home page - redirect to admin login
Route::get('/', function() {
    return redirect()->route('admin.login');
})->name('home');

// =================== ADMIN AUTHENTICATION ROUTES ===================

// Admin Login/Logout
Route::get('/admin/login', [AdminController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'login']);
Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');

// =================== PROTECTED ADMIN ROUTES ===================

Route::middleware(['web', 'auth:admin'])->prefix('admin')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    
    // User Management (requires manage_users permission)
    Route::middleware(['auth:admin','can:manage_users'])->group(function () {
        Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
        Route::get('/users/create', [AdminController::class, 'createUser'])->name('admin.users.create');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::get('/users/{id}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
        Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
    });
    
    // Conversation Management
    Route::prefix('conversations')->group(function () {
        Route::get('/', [ConversationManagementController::class, 'index'])->name('admin.conversations');
        Route::get('/{id}', [ConversationManagementController::class, 'show'])->name('admin.conversations.show');
        Route::post('/{id}/assign', [ConversationManagementController::class, 'assign'])->name('admin.conversations.assign');
        Route::post('/{id}/status', [ConversationManagementController::class, 'updateStatus'])->name('admin.conversations.status');
        Route::delete('/{id}', [ConversationManagementController::class, 'destroy'])->name('admin.conversations.delete');
        Route::post('/bulk-action', [ConversationManagementController::class, 'bulkAction'])->name('admin.conversations.bulk');
        Route::get('/api/statistics', [ConversationManagementController::class, 'statistics'])->name('admin.conversations.stats');
    });
    
    // API for authentication checks
    Route::get('/auth/check', [AdminController::class, 'checkAuth'])->name('admin.auth.check');
    
});

// =================== MAIN SITE INTEGRATION ROUTES ===================

// Authentication from main site (journals.mejsp.com)
Route::post('/auth/main-site', [AuthController::class, 'authenticateFromMainSite'])
    ->name('auth.main-site');

Route::get('/auth/check', [AuthController::class, 'check'])
    ->name('auth.check');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

// WhatsApp dashboard and API routes (for main site integration)
Route::middleware(['web'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [WhatsAppController::class, 'dashboard'])
        ->name('whatsapp.dashboard');
    
    // API routes for WhatsApp functionality
    Route::prefix('api')->group(function () {
        Route::post('/whatsapp/send-text', [WhatsAppController::class, 'sendText']);
        Route::post('/whatsapp/send-media', [WhatsAppController::class, 'sendMedia']);
        Route::get('/whatsapp/messages', [WhatsAppController::class, 'getMessages']);
    });
});

// =================== WHATSAPP WEBHOOK ROUTES ===================

// WhatsApp webhook routes (no authentication needed)
Route::match(['GET', 'POST'], '/whatsapp/webhook', [WhatsAppController::class, 'webhook'])
    ->name('whatsapp.webhook');

// =================== SYSTEM ROUTES ===================

// Health check
Route::get('/health', function() {
    return response()->json([
        'status' => 'ok',
        'service' => 'WhatsApp Microservice',
        'timestamp' => now(),
        'features' => [
            'admin_panel' => true,
            'user_management' => true,
            'conversation_management' => true,
            'whatsapp_integration' => true,
            'main_site_integration' => true
        ]
    ]);
});

// Fallback login route
Route::get('/login', function() {
    return redirect()->route('admin.login');
})->name('login');

// =================== DEMO ROUTES (for testing different roles) ===================

Route::get('/demo', function() {
    return view('demo.role-switcher');
})->name('demo.roles');

Route::prefix('demo')->group(function () {
    Route::get('/super-admin', [\App\Http\Controllers\DemoController::class, 'dashboard'])
        ->defaults('role', 'super_admin');
    Route::get('/admin', [\App\Http\Controllers\DemoController::class, 'dashboard'])
        ->defaults('role', 'admin');
    Route::get('/supervisor', [\App\Http\Controllers\DemoController::class, 'dashboard'])
        ->defaults('role', 'supervisor');
    Route::get('/agent', [\App\Http\Controllers\DemoController::class, 'dashboard'])
        ->defaults('role', 'agent');
});

// Demo API Routes (for testing)
Route::middleware(['web'])->prefix('api/whatsapp')->group(function () {
    Route::get('/conversations', [\App\Http\Controllers\DemoController::class, 'getConversations']);
    Route::get('/messages/{conversationId}', [\App\Http\Controllers\DemoController::class, 'getMessages']);
    Route::post('/send-message', [\App\Http\Controllers\DemoController::class, 'sendMessage']);
});
