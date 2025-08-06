<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VersionsController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConferencesController;
use App\Http\Controllers\InternationalJournalsController;
use App\Http\Controllers\InternationalSpecialtiesController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// WhatsApp API Routes - Enhanced with new functionality
Route::middleware(['web'])->prefix('whatsapp')->group(function () {
    // Conversation management
    Route::get('/conversations', [\App\Http\Controllers\WhatsAppController::class, 'getConversations']);
    Route::get('/messages/{conversationId}', [\App\Http\Controllers\WhatsAppController::class, 'getMessages']);
    
    // Message sending
    Route::post('/send-message', [\App\Http\Controllers\WhatsAppController::class, 'sendMessage']);
    Route::post('/upload-media', [\App\Http\Controllers\WhatsAppController::class, 'uploadMedia']);
    
    // Legacy compatibility routes
    Route::get('/interactions', [\App\Http\Controllers\WhatsAppController::class, 'getConversations']);
    Route::get('/interactions/{id}/messages', [\App\Http\Controllers\WhatsAppController::class, 'getMessages']);
});

// Legacy chat controller routes (for backward compatibility)
Route::middleware(['web'])->group(function () {
    Route::get('/conversations', [ChatController::class, 'getConversations']);
    Route::get('/conversations/{id}', [ChatController::class, 'getConversation']);
    Route::post('/conversations/{id}/claim', [ChatController::class, 'claimConversation']);
    Route::get('/conversations/updates', [ChatController::class, 'getConversationUpdates']);
    Route::get('/user/role', [ChatController::class, 'getUserRole']);
});




Route::post("versions/{id}", [VersionsController::class, 'get_version_api']);
Route::post("specialties/{id}", [InternationalSpecialtiesController::class, 'get_specialties_api']);
Route::post("journals/{id}", [InternationalJournalsController::class, 'get_jorunals_api']);
Route::post("journal-get-price/{id}", [InternationalJournalsController::class, 'get_jorunals_price_api']);




// Main
Route::post("type-of-conferences", [ConferencesController::class, 'get_typy_of_conferences']);




