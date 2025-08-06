<?php

use Illuminate\Support\Facades\Route;
use BiztechEG\WhatsAppCloudApi\WhatsAppRouter;
use BiztechEG\WhatsAppCloudApi\Http\Middleware\VerifyWhatsAppWebhook;
use BiztechEG\WhatsAppCloudApi\Http\Controllers\WhatsAppWebhookController;
use BiztechEG\WhatsAppCloudApi\Http\Controllers\DeveloperPlaygroundController;

Route::group(['prefix' => 'playground'], function () {
    Route::get('/', [DeveloperPlaygroundController::class, 'index'])->name('whatsapp.playground.index');
    Route::post('/send', [DeveloperPlaygroundController::class, 'sendTestMessage'])->name('whatsapp.playground.send');
});



Route::post('webhook/whatsapp', [WhatsAppWebhookController::class, 'handle'])
    ->middleware(VerifyWhatsAppWebhook::class);


// This route simulates receiving a message that triggers a smart reply.
Route::get('simulate-smart-reply', function () {
    // Sample inbound payload
    $samplePayload = [
        'text' => ['body' => 'Hello, I need support with my order.'],
        'metadata' => ['session_id' => 'test-session-123']
    ];

    // Register a sample smart reply route.
    WhatsAppRouter::on('support', function ($payload) {
        return 'Support handler triggered for message: ' . $payload['text']['body'];
    });

    // Dispatch the payload through the router.
    $result = WhatsAppRouter::dispatch($samplePayload);

    return response()->json([
        'payload' => $samplePayload,
        'router_result' => $result,
    ]);
});
