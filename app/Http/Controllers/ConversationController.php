<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use BiztechEG\WhatsAppCloudApi\Models\WhatsAppInteraction;
use BiztechEG\WhatsAppCloudApi\Models\WhatsAppMessage;
use BiztechEG\WhatsAppCloudApi\Models\InteractiveSession;
use BiztechEG\WhatsAppCloudApi\WhatsAppClient;
use App\Services\MetaWhatsappService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ConversationController extends Controller
{
    // Original conversation methods
    public function index()
    {
        return view('conversations.index');
    }

    public function show(Conversation $conversation)
    {
        return view('conversations.show', compact('conversation'));
    }

    // =================== CLIENT CHAT METHODS ===================
    
    /**
     * Show client chat interface (no authentication required)
     */
    public function clientIndex()
    {
        return view('conversations.client-chat');
    }

    /**
     * Start a new conversation from client side
     */
    public function startConversation(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'message' => 'required|string|max:1000'
        ]);

        try {
            $cleanPhone = preg_replace('/[^\d+]/', '', $request->phone);
            if (!str_starts_with($cleanPhone, '+')) {
                if (str_starts_with($cleanPhone, '0')) {
                    $cleanPhone = '+20' . substr($cleanPhone, 1);
                } else {
                    $cleanPhone = '+20' . $cleanPhone;
                }
            }

            $conversation = WhatsAppInteraction::updateOrCreate(
                ['wa_no' => $cleanPhone],
                [
                    'name' => $request->name,
                    'last_message' => $request->message,
                    'last_msg_time' => now(),
                    'unread' => 1,
                    'status' => 'new'
                ]
            );

            WhatsAppMessage::create([
                'interaction_id' => $conversation->id,
                'message_id' => 'client_' . time() . '_' . rand(1000, 9999),
                'from_phone' => $cleanPhone,
                'to_phone' => config('whatsapp.phone_number', '15551364016'),
                'message_type' => 'text',
                'payload' => ['text' => ['body' => $request->message]],
                'timestamp' => now(),
                'status' => 'received',
                'direction' => 'inbound'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'رسالتك تم إرسالها بنجاح! سيتم الرد عليك قريباً.',
                'conversation_id' => $conversation->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الرسالة. حاول مرة أخرى.'
            ], 500);
        }
    }

    /**
     * Send message from client side
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:whatsapp_interactions,id',
            'message' => 'required|string|max:1000'
        ]);

        try {
            $conversation = WhatsAppInteraction::findOrFail($request->conversation_id);

            WhatsAppMessage::create([
                'interaction_id' => $conversation->id,
                'message_id' => 'client_' . time() . '_' . rand(1000, 9999),
                'from_phone' => $conversation->wa_no,
                'to_phone' => config('whatsapp.phone_number', '15551364016'),
                'message_type' => 'text',
                'payload' => ['text' => ['body' => $request->message]],
                'timestamp' => now(),
                'status' => 'received',
                'direction' => 'inbound'
            ]);

            $conversation->update([
                'last_message' => $request->message,
                'last_msg_time' => now(),
                'unread' => ($conversation->unread ?? 0) + 1
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الرسالة بنجاح!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الرسالة.'
            ], 500);
        }
    }

    /**
     * Get messages for a conversation (client side)
     */
    public function getMessages($conversation_id)
    {
        try {
            $conversation = WhatsAppInteraction::findOrFail($conversation_id);
            
            $messages = WhatsAppMessage::where('interaction_id', $conversation_id)
                ->orderBy('timestamp', 'asc')
                ->get()
                ->map(function ($message) use ($conversation) {
                    return [
                        'id' => $message->id,
                        'message' => $message->payload['text']['body'] ?? '',
                        'timestamp' => $message->timestamp->format('Y-m-d H:i:s'),
                        'formatted_time' => $message->timestamp->format('H:i'),
                        'is_outbound' => $message->direction === 'outbound',
                        'status' => $message->status
                    ];
                });

            return response()->json([
                'success' => true,
                'messages' => $messages,
                'conversation' => [
                    'id' => $conversation->id,
                    'name' => $conversation->name,
                    'phone' => $conversation->wa_no
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في جلب الرسائل'
            ], 500);
        }
    }

    // =================== BIZTECHEG WEBHOOK INTEGRATION ===================
    
    /**
     * Handle WhatsApp webhook events from BiztechEG package
     */
    public function handleBiztechWebhook(Request $request)
    {
        try {
            Log::info('BiztechEG webhook received', ['payload' => $request->all()]);
            
            $entry = $request->input('entry', []);
            
            foreach ($entry as $entryItem) {
                $changes = $entryItem['changes'] ?? [];
                
                foreach ($changes as $change) {
                    $value = $change['value'] ?? [];
                    
                    // Handle incoming messages
                    if (isset($value['messages'])) {
                        foreach ($value['messages'] as $message) {
                            $this->processIncomingMessage($message, $value);
                        }
                    }
                    
                    // Handle message status updates
                    if (isset($value['statuses'])) {
                        foreach ($value['statuses'] as $status) {
                            $this->processMessageStatus($status);
                        }
                    }
                }
            }
            
            return response()->json(['status' => 'success']);
            
        } catch (\Exception $e) {
            Log::error('BiztechEG webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }
    
    /**
     * Process incoming WhatsApp messages
     */
    private function processIncomingMessage($message, $value)
    {
        try {
            $phoneNumber = $message['from'] ?? '';
            $messageId = $message['id'] ?? '';
            $timestamp = $message['timestamp'] ?? time();
            
            // Extract message content based on type
            $content = '';
            $messageType = $message['type'] ?? 'text';
            
            switch ($messageType) {
                case 'text':
                    $content = $message['text']['body'] ?? '';
                    break;
                case 'image':
                    $content = $message['image']['caption'] ?? 'Image received';
                    break;
                case 'document':
                    $content = $message['document']['filename'] ?? 'Document received';
                    break;
                case 'audio':
                    $content = 'Audio message received';
                    break;
                case 'video':
                    $content = $message['video']['caption'] ?? 'Video received';
                    break;
                case 'interactive':
                    $content = $this->extractInteractiveContent($message['interactive']);
                    break;
                default:
                    $content = 'Message received';
            }
            
            // Store in BiztechEG WhatsAppMessage model
            WhatsAppMessage::create([
                'phone_number' => $phoneNumber,
                'message_id' => $messageId,
                'content' => $content,
                'type' => $messageType,
                'direction' => 'inbound',
                'status' => 'received',
                'received_at' => Carbon::createFromTimestamp($timestamp),
                'raw_data' => json_encode($message)
            ]);
            
            // Also store in legacy format for backward compatibility
            $this->storeLegacyMessage($phoneNumber, $content, $messageType, $messageId);
            
            Log::info('Processed incoming message', [
                'phone' => $phoneNumber,
                'type' => $messageType,
                'message_id' => $messageId
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error processing incoming message: ' . $e->getMessage());
        }
    }
    
    /**
     * Process message status updates
     */
    private function processMessageStatus($status)
    {
        try {
            $messageId = $status['id'] ?? '';
            $statusValue = $status['status'] ?? '';
            $timestamp = $status['timestamp'] ?? time();
            
            // Update message status in BiztechEG model
            WhatsAppMessage::where('message_id', $messageId)
                ->update([
                    'status' => $statusValue,
                    'updated_at' => Carbon::createFromTimestamp($timestamp)
                ]);
                
            Log::info('Updated message status', [
                'message_id' => $messageId,
                'status' => $statusValue
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error processing message status: ' . $e->getMessage());
        }
    }
    
    /**
     * Extract content from interactive messages
     */
    private function extractInteractiveContent($interactive)
    {
        if (isset($interactive['button_reply'])) {
            return 'Button: ' . ($interactive['button_reply']['title'] ?? 'Button clicked');
        }
        
        if (isset($interactive['list_reply'])) {
            return 'List: ' . ($interactive['list_reply']['title'] ?? 'List item selected');
        }
        
        return 'Interactive message received';
    }
    
    /**
     * Store message in legacy format for backward compatibility
     */
    private function storeLegacyMessage($phoneNumber, $content, $type, $messageId)
    {
        try {
            // Check if whatsapp_interactions table exists and store there
            if (DB::getSchemaBuilder()->hasTable('whatsapp_interactions')) {
                $interaction = DB::table('whatsapp_interactions')
                    ->where('receiver_id', $phoneNumber)
                    ->first();
                    
                if (!$interaction) {
                    $interactionId = DB::table('whatsapp_interactions')->insertGetId([
                        'receiver_id' => $phoneNumber,
                        'name' => 'Unknown Contact',
                        'status' => 'active',
                        'unread' => 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } else {
                    $interactionId = $interaction->id;
                    // Update unread count
                    DB::table('whatsapp_interactions')
                        ->where('id', $interactionId)
                        ->update(['unread' => 1, 'updated_at' => now()]);
                }
                
                // Store message
                DB::table('whatsapp_interaction_messages')->insert([
                    'interaction_id' => $interactionId,
                    'message' => $content,
                    'type' => $type,
                    'nature' => 'received',
                    'time_sent' => now(),
                    'status' => 'delivered',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error storing legacy message: ' . $e->getMessage());
        }
    }
}