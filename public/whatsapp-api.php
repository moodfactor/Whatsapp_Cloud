<?php
// Direct PHP API endpoint - bypasses ALL Laravel routing/middleware
// Place this file in: public/whatsapp-api.php
// Access via: https://journals.mejsp.com/whatsapp-api.php

// Start session to access Laravel session data
session_start();

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Simple authentication check using your session system
function isAdminAuthenticated() {
    $session_key = 'login_admin_59ba36addc2b2f9401580f014c7f58ea4e30989d';
    return isset($_SESSION[$session_key]) && $_SESSION[$session_key] == 1;
}

// Check authentication
if (!isAdminAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get request parameters
$action = $_GET['action'] ?? 'interactions';
$id = $_GET['id'] ?? null;

try {
    // Include Laravel's bootstrap to access database
    require_once __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    // Database connection
    $db = $app->make('db');
    
    switch ($action) {
        case 'interactions':
            // Get conversations
            $interactions = $db->table('whatsapp_interactions')
                ->select([
                    'id',
                    'name', 
                    'receiver_id',
                    'last_message',
                    'last_msg_time',
                    'status',
                    'wa_no',
                    'wa_no_id'
                ])
                ->orderBy('last_msg_time', 'desc')
                ->limit(20)
                ->get();
            
            // Format response like Laravel pagination
            $response = [
                'data' => $interactions->map(function($interaction) {
                    return [
                        'id' => $interaction->id,
                        'name' => $interaction->name ?: 'Unknown',
                        'receiver_id' => $interaction->receiver_id,
                        'last_message' => $interaction->last_message,
                        'last_msg_time' => $interaction->last_msg_time,
                        'status' => $interaction->status ?: 'new',
                        'masked_phone' => substr($interaction->receiver_id, 0, 3) . '*****' . substr($interaction->receiver_id, -3),
                        'country_code' => 'Unknown',
                        'country_name' => 'Unknown'
                    ];
                })->toArray(),
                'current_page' => 1,
                'per_page' => 20,
                'total' => count($interactions),
                'debug' => 'Direct PHP API working'
            ];
            
            echo json_encode($response);
            break;
            
        case 'messages':
            if (!$id) {
                throw new Exception('Conversation ID required');
            }
            
            // Get conversation
            $interaction = $db->table('whatsapp_interactions')
                ->where('id', $id)
                ->first();
                
            if (!$interaction) {
                throw new Exception('Conversation not found');
            }
            
            // Get messages
            $messages = $db->table('whatsapp_interaction_messages')
                ->where('interaction_id', $id)
                ->orderBy('time_sent', 'asc')
                ->limit(50)
                ->get();
            
            // Mark as read
            $db->table('whatsapp_interactions')
                ->where('id', $id)
                ->update(['unread' => 0]);
            
            $response = [
                'interaction' => [
                    'id' => $interaction->id,
                    'name' => $interaction->name ?: 'Unknown',
                    'receiver_id' => $interaction->receiver_id,
                    'status' => $interaction->status ?: 'new',
                    'masked_phone' => substr($interaction->receiver_id, 0, 3) . '*****' . substr($interaction->receiver_id, -3),
                ],
                'messages' => [
                    'data' => $messages->map(function($msg) {
                        return [
                            'id' => $msg->id,
                            'message' => $msg->message,
                            'type' => $msg->type ?: 'text',
                            'nature' => $msg->nature ?: 'received',
                            'time_sent' => $msg->time_sent,
                            'status' => $msg->status ?: 'delivered',
                            'url' => $msg->url
                        ];
                    })->toArray(),
                    'current_page' => 1,
                    'per_page' => 50,
                    'total' => count($messages)
                ],
                'debug' => 'Messages loaded via direct PHP'
            ];
            
            echo json_encode($response);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'debug' => 'Direct PHP API error',
        'trace' => $e->getTraceAsString()
    ]);
}
?>
