<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Conversation;
use App\Models\WhatsAppInteractionMessage;
use App\Services\CountryService;
use App\Services\MetaWhatsappService;
use App\Rules\WhatsAppMediaFile;
use Illuminate\Support\Facades\Storage;

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
                'can_see_all' => ($admin->role === 'super_admin'), // Only super admin sees all
                'can_assign' => ($admin->role === 'super_admin'), // Only super admin can assign
                'can_delete' => ($admin->role === 'super_admin'), // Only super admin can delete
                'can_see_phone' => ($admin->role === 'super_admin') // Only super admin sees phone numbers
            ]
        ];
        
        return view("whatsapp.dashboard", [
            "user" => $user
        ]);
    }

    public function getConversations(Request $request)
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!$admin) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
        
        \Log::info('WhatsApp getConversations for role: ' . $admin->role . ' (ID: ' . $admin->id . ')');
        
        $query = Conversation::with('assignedTo');
        
        // STRICT Role-based filtering according to requirements
        switch ($admin->role) {
            case 'super_admin':
                // Super Admin sees ALL conversations (God Mode)
                break;
                
            case 'supervisor':
                // Supervisors see:
                // 1. NEW conversations (unassigned - status = 'new' AND assigned_to = null)
                // 2. Conversations assigned to them personally
                $query->where(function($q) use ($admin) {
                    $q->where(function($subq) {
                        // New conversations (unassigned)
                        $subq->where('status', 'new')
                             ->whereNull('assigned_to');
                    })->orWhere('assigned_to', $admin->id); // OR assigned to me
                });
                break;
                
            case 'agent':
                // Agents see ONLY conversations assigned to them by super admin
                $query->where('assigned_to', $admin->id);
                break;
                
            default:
                // Unknown role gets no access
                $query->where('id', -1); // No results
                break;
        }
        
        $conversations = $query->orderBy('last_msg_time', 'desc')
            ->get()
            ->map(function($conversation) use ($admin) {
                $phoneDisplay = CountryService::formatPhoneForDisplay(
                    $conversation->decrypted_phone,
                    $conversation->contact_name,
                    ($admin->role === 'super_admin') // Only super admin sees real phones
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
                    'assigned_to_name' => $conversation->assignedTo ? $conversation->assignedTo->name : null,
                    'can_see_full_phone' => ($admin->role === 'super_admin'),
                    'full_phone' => ($admin->role === 'super_admin') ? $phoneDisplay['full_phone'] : null,
                    // For supervisor: show if this is a NEW conversation they can claim
                    'can_claim' => ($admin->role === 'supervisor' && $conversation->status === 'new' && is_null($conversation->assigned_to))
                ];
            });
        
        // Create user permissions
        $userPermissions = [
            'role_name' => $this->getRoleName($admin->role),
            'can_see_all' => ($admin->role === 'super_admin'),
            'can_assign' => ($admin->role === 'super_admin'),
            'can_delete' => ($admin->role === 'super_admin'),
            'can_see_phone' => ($admin->role === 'super_admin'),
            'can_claim' => ($admin->role === 'supervisor')
        ];
        
        return response()->json([
            'conversations' => $conversations,
            'user_permissions' => $userPermissions
        ]);
    }

    public function claimConversation(Request $request, $conversationId)
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!$admin || $admin->role !== 'supervisor') {
            return response()->json(['error' => 'Only supervisors can claim conversations'], 403);
        }
        
        $conversation = Conversation::findOrFail($conversationId);
        
        // Can only claim NEW conversations that are unassigned
        if ($conversation->status !== 'new' || $conversation->assigned_to !== null) {
            return response()->json(['error' => 'This conversation cannot be claimed'], 400);
        }
        
        // Claim the conversation
        $conversation->update([
            'assigned_to' => $admin->id,
            'status' => 'open'
        ]);
        
        \Log::info("Supervisor {$admin->id} claimed conversation {$conversationId}");
        
        return response()->json([
            'success' => true,
            'message' => 'Conversation claimed successfully',
            'conversation' => [
                'id' => $conversation->id,
                'status' => 'open',
                'assigned_to' => $admin->id,
                'assigned_to_name' => $admin->name
            ]
        ]);
    }

    public function assignConversation(Request $request, $conversationId)
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        // Only super admin can assign conversations
        if (!$admin || $admin->role !== 'super_admin') {
            return response()->json(['error' => 'Only super admin can assign conversations'], 403);
        }
        
        $request->validate([
            'assigned_to' => 'nullable|exists:whatsapp_admins,id'
        ]);
        
        $conversation = Conversation::findOrFail($conversationId);
        
        $assignedToUserId = $request->input('assigned_to');
        
        if ($assignedToUserId) {
            // Verify the user being assigned to exists and has proper role
            $assignedUser = \App\Models\WhatsappAdmin::find($assignedToUserId);
            if (!$assignedUser || !in_array($assignedUser->role, ['supervisor', 'agent'])) {
                return response()->json(['error' => 'Invalid user for assignment'], 400);
            }
            
            $conversation->update([
                'assigned_to' => $assignedToUserId,
                'status' => 'assigned'
            ]);
            
            $message = "Conversation assigned to {$assignedUser->name}";
        } else {
            // Unassign conversation
            $conversation->update([
                'assigned_to' => null,
                'status' => 'new'
            ]);
            
            $message = "Conversation unassigned";
        }
        
        \Log::info("Super admin {$admin->id} assigned conversation {$conversationId} to user {$assignedToUserId}");
        
        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    public function getMessages(Request $request, $conversationId)
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!$admin) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
        
        $conversation = Conversation::findOrFail($conversationId);
        
        // Check if user can access this conversation based on role
        $canAccess = false;
        
        switch ($admin->role) {
            case 'super_admin':
                // Super admin can see all conversations
                $canAccess = true;
                break;
                
            case 'supervisor':
                // Supervisor can see: NEW conversations OR conversations assigned to them
                $canAccess = (
                    ($conversation->status === 'new' && is_null($conversation->assigned_to)) ||
                    ($conversation->assigned_to === $admin->id)
                );
                break;
                
            case 'agent':
                // Agent can only see conversations assigned to them
                $canAccess = ($conversation->assigned_to === $admin->id);
                break;
        }
        
        if (!$canAccess) {
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
                    'media_url' => $message->url ?? null,
                    'filename' => $message->filename ?? null,
                    'mime_type' => $message->mime_type ?? null,
                    'file_size' => $message->file_size ?? null,
                    'debug_type' => $message->type,
                    'debug_has_url' => !empty($message->url)
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
                'status' => $conversation->status,
                'assigned_to' => $conversation->assigned_to,
                'assigned_to_name' => $conversation->assignedTo ? $conversation->assignedTo->name : null,
                'can_claim' => ($admin->role === 'supervisor' && $conversation->status === 'new' && is_null($conversation->assigned_to))
            ]
        ]);
    }

    public function sendMessage(Request $request)
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!$admin) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
        
        $request->validate([
            'conversation_id' => 'required|exists:whatsapp_interactions,id',
            'message' => 'required|string|max:1000'
        ]);
        
        $conversation = Conversation::findOrFail($request->conversation_id);
        
        // Check if user can send messages to this conversation
        $canSend = false;
        
        switch ($admin->role) {
            case 'super_admin':
                // Super admin can send to any conversation
                $canSend = true;
                break;
                
            case 'supervisor':
                // Supervisor can send only to conversations assigned to them
                // (they must claim NEW conversations first)
                $canSend = ($conversation->assigned_to === $admin->id);
                break;
                
            case 'agent':
                // Agent can only send to conversations assigned to them
                $canSend = ($conversation->assigned_to === $admin->id);
                break;
        }
        
        if (!$canSend) {
            return response()->json(['error' => 'You can only send messages to conversations assigned to you'], 403);
        }
        
        try {
            \Log::info('Sending WhatsApp message:', [
                'to' => $conversation->decrypted_phone,
                'message' => $request->message,
                'conversation_id' => $conversation->id,
                'sent_by' => $admin->id
            ]);
            
            // Send message via WhatsApp API
            $result = $this->whatsappService->sendMessage(
                $conversation->decrypted_phone,
                $request->message
            );
            
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
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!$admin) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
        
        $request->validate([
            'conversation_id' => 'required|exists:whatsapp_interactions,id',
            'media' => ['required', 'file', new WhatsAppMediaFile],
            'caption' => 'nullable|string|max:1000'
        ]);
        
        $conversation = Conversation::findOrFail($request->conversation_id);
        
        // Check permissions (same logic as sendMessage)
        $canSend = false;
        
        switch ($admin->role) {
            case 'super_admin':
                $canSend = true;
                break;
            case 'supervisor':
            case 'agent':
                $canSend = ($conversation->assigned_to === $admin->id);
                break;
        }
        
        if (!$canSend) {
            return response()->json(['error' => 'You can only send media to conversations assigned to you'], 403);
        }
        
        try {
            $file = $request->file('media');
            $caption = $request->input('caption', '') ?? '';
            
            // Get media type from file extension
            $mediaType = $this->whatsappService->getMediaTypeFromExtension($file->getClientOriginalExtension());
            
            // Upload file to Meta WhatsApp API
            $uploadResult = $this->whatsappService->uploadMediaFile($file);
            
            if (!$uploadResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to upload media: ' . $uploadResult['error']
                ], 500);
            }
            
            $mediaId = data_get($uploadResult, 'media_id');
            if (!$mediaId) {
                throw new \Exception('Media ID not found in WhatsApp API response.');
            }

            // Send media message via WhatsApp API
            $sendResult = $this->whatsappService->sendMediaMessageWithId(
                $conversation->decrypted_phone,
                $mediaId,
                $mediaType,
                $caption,
                $file->getClientOriginalName()
            );
            
            if (!$sendResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to send media: ' . $sendResult['error']
                ], 500);
            }
            
            // Store file locally for reference (optional, can be removed to save space)
            $localFilename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $localPath = $file->storeAs('whatsapp_media', $localFilename, 'public');
            $localUrl = Storage::disk('public')->url($localPath);
            
            // Create message record
            $message = WhatsAppInteractionMessage::create([
                'interaction_id' => $conversation->id,
                'message' => $caption ?: $file->getClientOriginalName(),
                'type' => $mediaType,
                'nature' => 'sent',
                'status' => 'sent',
                'url' => $localUrl,
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'time_sent' => now(),
                'whatsapp_message_id' => $sendResult['message_id'] ?? null
            ]);
            
            // Update conversation
            $conversation->update([
                'last_message' => $caption ?: '[' . ucfirst($mediaType) . ']',
                'last_msg_time' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'text' => $message->message,
                    'type' => 'sent',
                    'message_type' => $message->type,
                    'time' => \Carbon\Carbon::parse($message->time_sent)->format('H:i'),
                    'media_url' => $message->url,
                    'filename' => $message->filename,
                    'status' => 'sent'
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Upload media exception:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
                    'can_see_all' => ($admin->role === 'super_admin'),
                    'can_assign' => ($admin->role === 'super_admin'),
                    'can_delete' => ($admin->role === 'super_admin'),
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
                    $messageType = $message['type'] ?? 'text';
                    $timestamp = $message['timestamp'] ?? time();

                    if ($phoneNumber) {
                        // Find or create conversation - use receiver_id column as per database structure
                        $conversation = Conversation::firstOrCreate(
                            ['receiver_id' => $phoneNumber],
                            [
                                'name' => $phoneNumber,
                                'last_message' => $this->getMessagePreview($message, $messageType),
                                'last_msg_time' => now(),
                                'status' => 'new', // New conversations start as 'new' so supervisors can claim them
                                'assigned_to' => null // Unassigned initially
                            ]
                        );

                        // Handle different message types
                        $messageData = $this->processIncomingMessageByType($message, $messageType);
                        
                        // Create message record
                        $dbMessage = WhatsAppInteractionMessage::create([
                            'interaction_id' => $conversation->id,
                            'message' => $messageData['text'],
                            'type' => $messageType,
                            'nature' => 'received',
                            'status' => 'delivered',
                            'time_sent' => now(),
                            'url' => $messageData['media_url'] ?? null,
                            'filename' => $messageData['filename'] ?? null,
                            'mime_type' => $messageData['mime_type'] ?? null,
                            'file_size' => $messageData['file_size'] ?? null,
                            'metadata' => json_encode($message)
                        ]);

                        // Update conversation
                        $conversation->update([
                            'last_message' => $messageData['text'],
                            'last_msg_time' => now(),
                            'unread' => ($conversation->unread ?? 0) + 1
                        ]);

                        \Log::info("Processed WhatsApp {$messageType} message from {$phoneNumber}", [
                            'message_id' => $dbMessage->id,
                            'conversation_status' => $conversation->status,
                            'assigned_to' => $conversation->assigned_to
                        ]);
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

    /**
     * Process incoming message based on its type
     */
    private function processIncomingMessageByType(array $message, string $messageType): array
    {
        $result = [
            'text' => '',
            'media_url' => null,
            'filename' => null,
            'mime_type' => null,
            'file_size' => null
        ];

        switch ($messageType) {
            case 'text':
                $result['text'] = $message['text']['body'] ?? '';
                break;

            case 'image':
                $result['text'] = $message['image']['caption'] ?? '[Image]';
                if (isset($message['image']['id'])) {
                    \Log::info('Attempting to download image with ID: ' . $message['image']['id']);
                    $mediaData = $this->whatsappService->downloadMedia($message['image']['id']);
                    \Log::info('Media download result:', $mediaData);
                    
                    if ($mediaData['success']) {
                        $result['media_url'] = $mediaData['full_url'];
                        $result['filename'] = $mediaData['filename'];
                        $result['mime_type'] = $mediaData['mime_type'];
                        $result['file_size'] = $mediaData['file_size'];
                        \Log::info('Image download successful: ' . $mediaData['full_url']);
                    } else {
                        \Log::error('Image download failed: ' . ($mediaData['error'] ?? 'Unknown error'));
                    }
                }
                break;

            case 'document':
                $result['text'] = $message['document']['caption'] ?? '[Document]';
                $result['filename'] = $message['document']['filename'] ?? 'document';
                if (isset($message['document']['id'])) {
                    $mediaData = $this->whatsappService->downloadMedia($message['document']['id']);
                    if ($mediaData['success']) {
                        $result['media_url'] = $mediaData['full_url'];
                        $result['mime_type'] = $mediaData['mime_type'];
                        $result['file_size'] = $mediaData['file_size'];
                    }
                }
                break;

            case 'audio':
                $result['text'] = '[Audio]';
                if (isset($message['audio']['id'])) {
                    $mediaData = $this->whatsappService->downloadMedia($message['audio']['id']);
                    if ($mediaData['success']) {
                        $result['media_url'] = $mediaData['full_url'];
                        $result['filename'] = $mediaData['filename'];
                        $result['mime_type'] = $mediaData['mime_type'];
                        $result['file_size'] = $mediaData['file_size'];
                    }
                }
                break;

            case 'video':
                $result['text'] = $message['video']['caption'] ?? '[Video]';
                if (isset($message['video']['id'])) {
                    $mediaData = $this->whatsappService->downloadMedia($message['video']['id']);
                    if ($mediaData['success']) {
                        $result['media_url'] = $mediaData['full_url'];
                        $result['filename'] = $mediaData['filename'];
                        $result['mime_type'] = $mediaData['mime_type'];
                        $result['file_size'] = $mediaData['file_size'];
                    }
                }
                break;

            case 'sticker':
                $result['text'] = '[Sticker]';
                if (isset($message['sticker']['id'])) {
                    $mediaData = $this->whatsappService->downloadMedia($message['sticker']['id']);
                    if ($mediaData['success']) {
                        $result['media_url'] = $mediaData['full_url'];
                        $result['filename'] = $mediaData['filename'];
                        $result['mime_type'] = $mediaData['mime_type'];
                        $result['file_size'] = $mediaData['file_size'];
                    }
                }
                break;

            default:
                $result['text'] = '[' . ucfirst($messageType) . ']';
        }

        return $result;
    }

    /**
     * Get a preview text for different message types
     */
    private function getMessagePreview(array $message, string $messageType): string
    {
        switch ($messageType) {
            case 'text':
                return $message['text']['body'] ?? '';
            case 'image':
                return $message['image']['caption'] ?? '[Image]';
            case 'document':
                return $message['document']['caption'] ?? '[Document]';
            case 'audio':
                return '[Audio]';
            case 'video':
                return $message['video']['caption'] ?? '[Video]';
            case 'sticker':
                return '[Sticker]';
            default:
                return '[' . ucfirst($messageType) . ']';
        }
    }
}