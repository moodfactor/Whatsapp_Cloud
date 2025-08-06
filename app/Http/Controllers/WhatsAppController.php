<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Conversation;
use App\Models\WhatsAppInteractionMessage;
use App\Services\CountryService;
use App\Services\MetaWhatsappService;

class WhatsAppController extends BaseController
{
    protected $whatsappService;

    public function __construct(MetaWhatsappService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function dashboard()
    {
        $user = $this->getCurrentUser();
        
        return view("whatsapp.dashboard", [
            "user" => $user
        ]);
    }

    public function getConversations(Request $request)
    {
        $user = $this->getCurrentUser();
        
        $query = Conversation::query();
        
        // Apply role-based filtering
        if (!$user['permissions']['can_see_all']) {
            $query->where('assigned_to', $user['id']);
        }
        
        $conversations = $query->with(['messages' => function($q) {
            $q->latest('time_sent')->limit(1);
        }])->orderBy('last_msg_time', 'desc')->get();
        
        // Format conversations for API response
        $formattedConversations = $conversations->map(function($conversation) use ($user) {
            $phoneDisplay = CountryService::formatPhoneForDisplay(
                $conversation->decrypted_phone,
                $conversation->contact_name,
                $user['permissions']['can_see_phone']
            );
            
            return [
                'id' => $conversation->id,
                'contact_name' => $phoneDisplay['display_name'],
                'contact_phone' => $phoneDisplay['display_phone'],
                'country_flag' => $phoneDisplay['country_flag'],
                'country_name' => $phoneDisplay['country_name'],
                'is_arab' => $phoneDisplay['is_arab'],
                'last_message' => $conversation->last_message ?? 'No messages yet',
                'last_msg_time' => $conversation->last_msg_time,
                'unread' => $conversation->unread ?? 0,
                'status' => $conversation->status ?? 'new',
                'assigned_to' => $conversation->assigned_to,
                'can_see_full_phone' => $user['permissions']['can_see_phone'],
                'full_phone' => $user['permissions']['can_see_phone'] ? $phoneDisplay['full_phone'] : null
            ];
        });
        
        return response()->json([
            'conversations' => $formattedConversations,
            'user_permissions' => $user['permissions']
        ]);
    }

    public function getMessages(Request $request, $conversationId)
    {
        $user = $this->getCurrentUser();
        
        $conversation = Conversation::findOrFail($conversationId);
        
        // Check if user can access this conversation
        if (!$user['permissions']['can_see_all'] && $conversation->assigned_to !== $user['id']) {
            return response()->json(['error' => 'Access denied'], 403);
        }
        
        $messages = WhatsAppInteractionMessage::where('interaction_id', $conversationId)
            ->orderBy('time_sent', 'asc')
            ->get()
            ->map(function($message) {
                return [
                    'id' => $message->id,
                    'text' => $message->message,
                    'type' => $message->nature, // 'sent' or 'received'
                    'message_type' => $message->type ?? 'text',
                    'time' => $message->time_sent->format('H:i'),
                    'status' => $message->status ?? 'delivered',
                    'media_url' => $message->url ?? null
                ];
            });
        
        $phoneDisplay = CountryService::formatPhoneForDisplay(
            $conversation->decrypted_phone,
            $conversation->contact_name,
            $user['permissions']['can_see_phone']
        );
        
        return response()->json([
            'messages' => $messages,
            'conversation' => [
                'id' => $conversation->id,
                'contact_name' => $phoneDisplay['display_name'],
                'contact_phone' => $phoneDisplay['display_phone'],
                'country_flag' => $phoneDisplay['country_flag'],
                'country_name' => $phoneDisplay['country_name'],
                'status' => $conversation->status
            ]
        ]);
    }

    public function sendMessage(Request $request)
    {
        $user = $this->getCurrentUser();
        
        $request->validate([
            'conversation_id' => 'required|exists:whatsapp_interactions,id',
            'message' => 'required|string|max:1000'
        ]);
        
        $conversation = Conversation::findOrFail($request->conversation_id);
        
        // Check if user can access this conversation
        if (!$user['permissions']['can_see_all'] && $conversation->assigned_to !== $user['id']) {
            return response()->json(['error' => 'Access denied'], 403);
        }
        
        try {
            // Send message via WhatsApp API
            $result = $this->whatsappService->sendMessage(
                $conversation->decrypted_phone,
                $request->message
            );
            
            // Store message in database
            $message = WhatsAppInteractionMessage::create([
                'interaction_id' => $conversation->id,
                'message' => $request->message,
                'type' => 'text',
                'nature' => 'sent',
                'status' => 'sent',
                'time_sent' => now()
            ]);
            
            // Update conversation last message
            $conversation->update([
                'last_message' => $request->message,
                'last_msg_time' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'text' => $message->message,
                    'type' => 'sent',
                    'time' => $message->time_sent->format('H:i'),
                    'status' => 'sent'
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to send message: ' . $e->getMessage()
            ], 500);
        }
    }

    public function uploadMedia(Request $request)
    {
        $user = $this->getCurrentUser();
        
        $request->validate([
            'conversation_id' => 'required|exists:whatsapp_interactions,id',
            'media' => 'required|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,mp3,mp4,wav|max:16384' // 16MB max
        ]);
        
        $conversation = Conversation::findOrFail($request->conversation_id);
        
        // Check permissions
        if (!$user['permissions']['can_see_all'] && $conversation->assigned_to !== $user['id']) {
            return response()->json(['error' => 'Access denied'], 403);
        }
        
        try {
            // Store file
            $file = $request->file('media');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('whatsapp', $filename, 'public');
            $fullUrl = asset('uploads/' . $path);
            
            // Send media via WhatsApp API
            $result = $this->whatsappService->sendMedia(
                $conversation->decrypted_phone,
                $fullUrl,
                $file->getClientOriginalName()
            );
            
            // Store message in database
            $message = WhatsAppInteractionMessage::create([
                'interaction_id' => $conversation->id,
                'message' => $file->getClientOriginalName(),
                'type' => $this->getMediaType($file->getClientOriginalExtension()),
                'nature' => 'sent',
                'status' => 'sent',
                'url' => $fullUrl,
                'time_sent' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'text' => $file->getClientOriginalName(),
                    'type' => 'sent',
                    'message_type' => $message->type,
                    'time' => $message->time_sent->format('H:i'),
                    'media_url' => $fullUrl,
                    'status' => 'sent'
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to send media: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getCurrentUser(): array
    {
        // This should be replaced with actual authentication logic
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if ($admin) {
            return [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role ?? 'agent',
                'permissions' => [
                    'role_name' => $this->getRoleName($admin->role ?? 'agent'),
                    'can_see_all' => in_array($admin->role, ['super_admin', 'admin']),
                    'can_assign' => in_array($admin->role, ['super_admin', 'admin', 'supervisor']),
                    'can_delete' => in_array($admin->role, ['super_admin', 'admin']),
                    'can_see_phone' => ($admin->role === 'super_admin')
                ]
            ];
        }
        
        // Fallback for development/testing
        return [
            'id' => 1,
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'role' => 'agent',
            'permissions' => [
                'role_name' => 'Agent',
                'can_see_all' => false,
                'can_assign' => false,
                'can_delete' => false,
                'can_see_phone' => false
            ]
        ];
    }

    private function getRoleName(string $role): string
    {
        return match($role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'supervisor' => 'Supervisor',
            'agent' => 'Agent',
            default => 'User'
        };
    }

    private function getMediaType(string $extension): string
    {
        return match(strtolower($extension)) {
            'jpg', 'jpeg', 'png', 'gif' => 'image',
            'mp4', 'avi', 'mov' => 'video',
            'mp3', 'wav', 'ogg' => 'audio',
            'pdf', 'doc', 'docx', 'txt' => 'document',
            default => 'file'
        };
    }

    /**
     * Handle WhatsApp webhook verification and message reception
     */
    public function webhook(Request $request)
    {
        // Handle GET request for webhook verification (Meta requirement)
        if ($request->isMethod('GET')) {
            $verifyToken = config('whatsapp.webhook_secret', 'your-verify-token');
            $mode = $request->query('hub_mode');
            $token = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');

            if ($mode === 'subscribe' && $token === $verifyToken) {
                return response($challenge, 200)
                    ->header('Content-Type', 'text/plain');
            } else {
                return response('Forbidden', 403);
            }
        }

        // Handle POST request for incoming messages
        if ($request->isMethod('POST')) {
            $payload = $request->all();
            
            // Log incoming webhook for debugging
            \Log::info('WhatsApp webhook received:', $payload);
            
            // Verify webhook signature if configured
            $webhookSecret = config('whatsapp.webhook_secret');
            if ($webhookSecret) {
                $signature = $request->header('X-Hub-Signature-256');
                if ($signature) {
                    $expectedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $webhookSecret);
                    if (!hash_equals($expectedSignature, $signature)) {
                        \Log::warning('WhatsApp webhook signature verification failed');
                        return response('Unauthorized', 401);
                    }
                }
            }

            // Process the webhook payload
            if (isset($payload['entry'])) {
                foreach ($payload['entry'] as $entry) {
                    if (isset($entry['changes'])) {
                        foreach ($entry['changes'] as $change) {
                            if ($change['field'] === 'messages') {
                                $this->processMessage($change['value']);
                            }
                        }
                    }
                }
            }
            
            return response()->json(['status' => 'success'], 200);
        }

        return response('Method not allowed', 405);
    }

    /**
     * Process incoming WhatsApp message
     */
    private function processMessage(array $messageData)
    {
        try {
            if (isset($messageData['messages'])) {
                foreach ($messageData['messages'] as $message) {
                    $phoneNumber = $message['from'] ?? null;
                    $messageText = $message['text']['body'] ?? null;
                    $messageType = $message['type'] ?? 'text';
                    $timestamp = $message['timestamp'] ?? time();

                    if ($phoneNumber) {
                        // Find or create conversation
                        $conversation = Conversation::firstOrCreate(
                            ['phone_number' => $phoneNumber],
                            [
                                'contact_name' => $phoneNumber,
                                'last_message' => $messageText ?: '[' . ucfirst($messageType) . ']',
                                'last_msg_time' => now(),
                                'status' => 'active'
                            ]
                        );

                        // Create message record
                        WhatsAppInteractionMessage::create([
                            'conversation_id' => $conversation->id,
                            'message' => $messageText ?: '[' . ucfirst($messageType) . ']',
                            'nature' => 'received',
                            'status' => 'delivered',
                            'time_sent' => now(),
                            'message_data' => json_encode($message)
                        ]);

                        // Update conversation
                        $conversation->update([
                            'last_message' => $messageText ?: '[' . ucfirst($messageType) . ']',
                            'last_msg_time' => now()
                        ]);

                        \Log::info("Processed WhatsApp message from {$phoneNumber}");
                    }
                }
            }

            // Process status updates
            if (isset($messageData['statuses'])) {
                foreach ($messageData['statuses'] as $status) {
                    $messageId = $status['id'] ?? null;
                    $statusType = $status['status'] ?? null;
                    
                    if ($messageId && $statusType) {
                        // Update message status if we have it
                        WhatsAppInteractionMessage::where('whatsapp_message_id', $messageId)
                            ->update(['status' => $statusType]);
                        
                        \Log::info("Updated message {$messageId} status to {$statusType}");
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error processing WhatsApp message: ' . $e->getMessage(), [
                'data' => $messageData,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
