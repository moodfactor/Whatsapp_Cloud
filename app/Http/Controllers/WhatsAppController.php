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
        // Use exact same logic as AdminController
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!$admin) {
            return redirect()->route('admin.login')->with('info', 'Please log in to access WhatsApp chat.');
        }
        
        // Create user array exactly like AdminController expects
        $user = [
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => $admin->role,
            'permissions' => [
                'role_name' => $this->getRoleName($admin->role),
                'can_see_all' => in_array($admin->role, ['super_admin', 'admin']),
                'can_assign' => in_array($admin->role, ['super_admin', 'admin', 'supervisor']),
                'can_delete' => in_array($admin->role, ['super_admin', 'admin']),
                'can_see_phone' => ($admin->role === 'super_admin')
            ]
        ];
        
        return view("whatsapp.dashboard", [
            "user" => $user
        ]);
    }

    public function getConversations(Request $request)
    {
        // Use exact same logic as AdminController
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!$admin) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
        
        // Debug logging
        \Log::info('WhatsApp getConversations Debug:', [
            'admin_id' => $admin->id,
            'admin_role' => $admin->role,
            'admin_data' => $admin->toArray()
        ]);
        
        $query = Conversation::with('assignedTo');
        
        // Apply role-based filtering (same as AdminController)
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            $query->where('assigned_to', $admin->id);
        }
        
        $conversations = $query->orderBy('last_msg_time', 'desc')
            ->get()
            ->map(function($conversation) use ($admin) {
                $phoneDisplay = CountryService::formatPhoneForDisplay(
                    $conversation->decrypted_phone,
                    $conversation->contact_name,
                    ($admin->role === 'super_admin')
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
                    'can_see_full_phone' => ($admin->role === 'super_admin'),
                    'full_phone' => ($admin->role === 'super_admin') ? $phoneDisplay['full_phone'] : null,
                    'debug_admin_role' => $admin->role,
                    'debug_is_super_admin' => ($admin->role === 'super_admin')
                ];
            });
        
        // Create user permissions exactly like AdminController logic
        $userPermissions = [
            'role_name' => $this->getRoleName($admin->role),
            'can_see_all' => in_array($admin->role, ['super_admin', 'admin']),
            'can_assign' => in_array($admin->role, ['super_admin', 'admin', 'supervisor']),
            'can_delete' => in_array($admin->role, ['super_admin', 'admin']),
            'can_see_phone' => ($admin->role === 'super_admin'),
            'debug_admin_role' => $admin->role,
            'debug_role_check' => in_array($admin->role, ['super_admin', 'admin'])
        ];
        
        \Log::info('WhatsApp permissions debug:', $userPermissions);
        
        return response()->json([
            'conversations' => $conversations,
            'user_permissions' => $userPermissions
        ]);
    }

    public function getMessages(Request $request, $conversationId)
    {
        // Use same authentication pattern as getConversations
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!$admin) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
        
        $conversation = Conversation::findOrFail($conversationId);
        
        // Check if user can access this conversation (same logic as AdminController)
        if (!in_array($admin->role, ['super_admin', 'admin']) && $conversation->assigned_to !== $admin->id) {
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
                    'time' => \Carbon\Carbon::parse($message->time_sent)->format('H:i'),
                    'status' => $message->status ?? 'delivered',
                    'media_url' => $message->url ?? null
                ];
            });
        
        $phoneDisplay = CountryService::formatPhoneForDisplay(
            $conversation->decrypted_phone,
            $conversation->contact_name,
            ($admin->role === 'super_admin')
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
        // Use same authentication pattern as other methods
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!$admin) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
        
        $request->validate([
            'conversation_id' => 'required|exists:whatsapp_interactions,id',
            'message' => 'required|string|max:1000'
        ]);
        
        $conversation = Conversation::findOrFail($request->conversation_id);
        
        // Check if user can access this conversation
        if (!in_array($admin->role, ['super_admin', 'admin']) && $conversation->assigned_to !== $admin->id) {
            return response()->json(['error' => 'Access denied'], 403);
        }
        
        try {
            \Log::info('Sending WhatsApp message:', [
                'to' => $conversation->decrypted_phone,
                'message' => $request->message,
                'conversation_id' => $conversation->id
            ]);
            
            // Send message via WhatsApp API
            $result = $this->whatsappService->sendMessage(
                $conversation->decrypted_phone,
                $request->message
            );
            
            \Log::info('WhatsApp API Result:', [
                'success' => !!$result,
                'result' => $result
            ]);
            
            // Determine status based on API result
            $status = $result ? 'sent' : 'failed';
            
            // Store message in database
            $message = WhatsAppInteractionMessage::create([
                'interaction_id' => $conversation->id,
                'message' => $request->message,
                'type' => 'text',
                'nature' => 'sent',
                'status' => $status,
                'time_sent' => now(),
                'whatsapp_message_id' => $result ? (is_array($result) && isset($result['messages']) ? $result['messages'][0]['id'] ?? null : null) : null
            ]);
            
            // Update conversation last message
            $conversation->update([
                'last_message' => $request->message,
                'last_msg_time' => now()
            ]);
            
            if (!$result) {
                return response()->json([
                    'success' => false,
                    'error' => 'WhatsApp API returned false - check access token, phone number ID, and account status'
                ], 500);
            }
            
            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'text' => $message->message,
                    'type' => 'sent',
                    'time' => \Carbon\Carbon::parse($message->time_sent)->format('H:i'),
                    'status' => $status
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Send message exception:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
        $admin = Auth::guard('whatsapp_admin')->user();
        
        \Log::info('WhatsApp Auth Debug:', [
            'admin' => $admin ? $admin->toArray() : 'null',
            'guard_check' => Auth::guard('whatsapp_admin')->check()
        ]);
        
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
        
        // If no authenticated admin, throw an error - no fallback
        throw new \Exception('No authenticated admin user found');
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
