<?php

namespace BiztechEG\WhatsAppCloudApi\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use BiztechEG\WhatsAppCloudApi\InteractiveSessionManager;
use BiztechEG\WhatsAppCloudApi\Models\WhatsAppMessage;
use BiztechEG\WhatsAppCloudApi\WhatsAppRouter;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle the incoming webhook from WhatsApp.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info('Received WhatsApp webhook:', $payload);

        // Check if this is a status update payload
        if (isset($payload['statuses'])) {
            foreach ($payload['statuses'] as $statusUpdate) {
                // Expected structure: id, status, timestamp
                $messageId = $statusUpdate['id'] ?? null;
                $status = $statusUpdate['status'] ?? null;
                $timestamp = $statusUpdate['timestamp'] ?? null;

                if ($messageId && $status) {
                    // Attempt to update a message with the matching WhatsApp message id.
                    // Assuming the original payload stored in our messages contains a 'message_id' key.
                    $message = WhatsAppMessage::where('payload->message_id', $messageId)->first();
                    if ($message) {
                        $data = ['delivery_status' => $status];
                        if ($status === 'delivered') {
                            $data['delivered_at'] = Carbon::createFromTimestamp($timestamp);
                        }
                        if ($status === 'read') {
                            $data['read_at'] = Carbon::createFromTimestamp($timestamp);
                        }
                        $message->update($data);
                    } else {
                        Log::warning("Message with ID {$messageId} not found for status update.");
                    }
                }
            }
        } else {
            // Log the inbound message if not a status update
            WhatsAppMessage::create([
                'id' => (string) Str::uuid(),
                'session_id' => $payload['metadata']['session_id'] ?? null,
                'direction' => 'inbound',
                'recipient' => $payload['from'] ?? 'unknown',
                'message_type' => $payload['type'] ?? 'unknown',
                'payload' => $payload,
            ]);

            // Dispatch the message through the router for smart replies.
            $actionResult = WhatsAppRouter::dispatch($payload);
            Log::info('Router dispatch result:', ['result' => $actionResult]);

            // Check if metadata includes a session id for interactive sessions.
            if (isset($payload['metadata']['session_id'])) {
                $sessionId = $payload['metadata']['session_id'];
                $session = InteractiveSessionManager::getSession($sessionId);
                if ($session) {
                    // Update the session with the user's response details.
                    InteractiveSessionManager::updateSession($sessionId, [
                        'status' => 'received',
                        'response' => $payload,
                    ]);
                }
            } else {
                Log::warning('Webhook received without session_id metadata.', $payload);
            }
        }

        return response()->json(['status' => 'acknowledged'], 200);
    }
}
