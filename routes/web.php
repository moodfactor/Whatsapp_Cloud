<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ConversationManagementController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

Route::middleware(['web', 'auth:whatsapp_admin'])->prefix('admin')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    
    // User Management (admin and super_admin only)
    Route::group([], function () {
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

// WhatsApp dashboard and API routes (with admin authentication)
Route::middleware(['web', 'auth:whatsapp_admin'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [WhatsAppController::class, 'dashboard'])
        ->name('whatsapp.dashboard');
    
    // API routes for WhatsApp functionality
    Route::prefix('api')->group(function () {
        Route::get('/whatsapp/conversations', [WhatsAppController::class, 'getConversations']);
        Route::get('/whatsapp/messages/{conversationId}', [WhatsAppController::class, 'getMessages']);
        Route::post('/whatsapp/send-message', [WhatsAppController::class, 'sendMessage']);
        Route::post('/whatsapp/upload-media', [WhatsAppController::class, 'uploadMedia']);
        Route::post('/whatsapp/send-text', [WhatsAppController::class, 'sendText']);
        Route::post('/whatsapp/send-media', [WhatsAppController::class, 'sendMedia']);
        Route::get('/whatsapp/messages', [WhatsAppController::class, 'getMessages']);
        
        // Legacy compatibility routes
        Route::get('/whatsapp/interactions', [WhatsAppController::class, 'getConversations']);
        Route::get('/whatsapp/interactions/{id}/messages', [WhatsAppController::class, 'getMessages']);
    });
});

// =================== WHATSAPP WEBHOOK ROUTES ===================

// Test endpoint
Route::get('/test-webhook', function() {
    return 'Webhook test OK';
});

// Debug authentication endpoint
Route::get('/debug-auth', function() {
    $admin = Auth::guard('whatsapp_admin')->user();
    return response()->json([
        'authenticated' => Auth::guard('whatsapp_admin')->check(),
        'admin' => $admin ? $admin->toArray() : null,
        'session_data' => session()->all(),
        'guard_name' => 'whatsapp_admin'
    ]);
})->middleware(['web']);

// Test message creation
Route::get('/test-message', function() {
    try {
        // Create test conversation
        $conversation = \App\Models\Conversation::findOrCreateByPhone('+201234567890', 'Test User');
        
        // Create test message
        $message = \App\Models\WhatsAppInteractionMessage::create([
            'interaction_id' => $conversation->id,
            'message' => 'Test incoming message from webhook',
            'type' => 'text',
            'nature' => 'received',
            'status' => 'delivered',
            'time_sent' => now(),
            'whatsapp_message_id' => 'test_' . uniqid(),
            'metadata' => json_encode(['test' => true])
        ]);
        
        // Update conversation
        $conversation->update([
            'last_message' => 'Test incoming message from webhook',
            'last_msg_time' => now(),
            'unread' => $conversation->unread + 1
        ]);
        
        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'conversation' => $conversation->toArray()
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// WhatsApp webhook verification (GET)
Route::get('/whatsapp/webhook', function(Request $request) {
    $verifyToken = '12345678';
    $mode = $request->query('hub_mode');
    $token = $request->query('hub_verify_token');
    $challenge = $request->query('hub_challenge');

    if ($mode === 'subscribe' && $token === $verifyToken) {
        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }
    return response('Forbidden', 403);
});

// WhatsApp webhook messages (POST)
Route::post('/whatsapp/webhook', function(Request $request) {
    $payload = $request->all();
    Log::info('WhatsApp webhook received:', $payload);
    
    try {
        // Process incoming webhook data
        if (isset($payload['entry'])) {
            foreach ($payload['entry'] as $entry) {
                if (isset($entry['changes'])) {
                    foreach ($entry['changes'] as $change) {
                        if ($change['field'] === 'messages' && isset($change['value']['messages'])) {
                            foreach ($change['value']['messages'] as $message) {
                                $phoneNumber = $message['from'] ?? null;
                                $messageText = $message['text']['body'] ?? null;
                                $messageType = $message['type'] ?? 'text';
                                $messageId = $message['id'] ?? null;
                                $timestamp = isset($message['timestamp']) ? \Carbon\Carbon::createFromTimestamp($message['timestamp']) : now();

                                if ($phoneNumber) {
                                    Log::info("Processing message from: {$phoneNumber}");
                                    
                                    // Find or create conversation using the improved method
                                    $conversation = \App\Models\Conversation::findOrCreateByPhone($phoneNumber, $phoneNumber);

                                    Log::info("Found/created conversation ID: {$conversation->id}");

                                    // Create message record
                                    $messageRecord = \App\Models\WhatsAppInteractionMessage::create([
                                        'interaction_id' => $conversation->id,
                                        'message' => $messageText ?: '[' . ucfirst($messageType) . ']',
                                        'type' => $messageType,
                                        'nature' => 'received',
                                        'status' => 'delivered',
                                        'time_sent' => $timestamp,
                                        'whatsapp_message_id' => $messageId,
                                        'metadata' => json_encode($message)
                                    ]);

                                    Log::info("Created message record ID: {$messageRecord->id}");

                                    // Update conversation with latest message
                                    $conversation->update([
                                        'last_message' => $messageText ?: '[' . ucfirst($messageType) . ']',
                                        'last_msg_time' => $timestamp,
                                        'status' => 'new',
                                        'unread' => $conversation->unread + 1
                                    ]);

                                    Log::info("Successfully processed WhatsApp message from {$phoneNumber}");
                                }
                            }
                        }

                        // Process status updates
                        if ($change['field'] === 'messages' && isset($change['value']['statuses'])) {
                            foreach ($change['value']['statuses'] as $status) {
                                $messageId = $status['id'] ?? null;
                                $statusType = $status['status'] ?? null;
                                
                                if ($messageId && $statusType) {
                                    \App\Models\WhatsAppInteractionMessage::where('whatsapp_message_id', $messageId)
                                        ->update(['status' => $statusType]);
                                    
                                    Log::info("Updated message {$messageId} status to {$statusType}");
                                }
                            }
                        }
                    }
                }
            }
        }
    } catch (\Exception $e) {
        Log::error('Error processing WhatsApp webhook: ' . $e->getMessage(), [
            'payload' => $payload,
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    return response()->json(['status' => 'success'], 200);
});

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

// Demo and testing routes
Route::get('/demo', function() {
    return view('demo.role-switcher');
})->name('demo.roles');

Route::get('/admin-demo', function() {
    return redirect()->route('admin.login')->with('info', 
        'Use these demo credentials:<br>' .
        '• <strong>Super Admin:</strong> admin@connect.al-najjarstore.com / admin123<br>' .
        '• <strong>Admin:</strong> whatsapp-admin@connect.al-najjarstore.com / whatsapp123<br>' .
        '• <strong>Supervisor:</strong> supervisor@connect.al-najjarstore.com / supervisor123<br>' .
        '• <strong>Agent:</strong> agent@connect.al-najjarstore.com / agent123'
    );
})->name('admin.demo');

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

// Demo API Routes (for testing) - DISABLED to prevent conflicts with authenticated routes
// Route::middleware(['web'])->prefix('api/whatsapp')->group(function () {
//     Route::get('/conversations', [\App\Http\Controllers\DemoController::class, 'getConversations']);
//     Route::get('/messages/{conversationId}', [\App\Http\Controllers\DemoController::class, 'getMessages']);
//     Route::post('/send-message', [\App\Http\Controllers\DemoController::class, 'sendMessage']);
// });
