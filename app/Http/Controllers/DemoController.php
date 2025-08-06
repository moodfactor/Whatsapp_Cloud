<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Services\CountryService;

class DemoController extends BaseController
{
    /**
     * Demo dashboard with different user roles for testing
     */
    public function dashboard(Request $request)
    {
        $role = $request->get('role', 'agent');
        
        $users = [
            'super_admin' => [
                'id' => 1,
                'name' => 'Super Admin',
                'email' => 'admin@connect.al-najjarstore.com',
                'role' => 'super_admin',
                'permissions' => [
                    'role_name' => 'Super Admin',
                    'can_see_all' => true,
                    'can_assign' => true,
                    'can_delete' => true,
                    'can_see_phone' => true
                ]
            ],
            'admin' => [
                'id' => 2,
                'name' => 'Admin User',
                'email' => 'whatsapp-admin@connect.al-najjarstore.com',
                'role' => 'admin',
                'permissions' => [
                    'role_name' => 'Admin',
                    'can_see_all' => true,
                    'can_assign' => true,
                    'can_delete' => true,
                    'can_see_phone' => false
                ]
            ],
            'supervisor' => [
                'id' => 3,
                'name' => 'Supervisor',
                'email' => 'supervisor@connect.al-najjarstore.com',
                'role' => 'supervisor',
                'permissions' => [
                    'role_name' => 'Supervisor',
                    'can_see_all' => false,
                    'can_assign' => true,
                    'can_delete' => false,
                    'can_see_phone' => false
                ]
            ],
            'agent' => [
                'id' => 4,
                'name' => 'Agent',
                'email' => 'agent@connect.al-najjarstore.com',
                'role' => 'agent',
                'permissions' => [
                    'role_name' => 'Agent',
                    'can_see_all' => false,
                    'can_assign' => false,
                    'can_delete' => false,
                    'can_see_phone' => false
                ]
            ]
        ];
        
        $user = $users[$role] ?? $users['agent'];
        
        return view("whatsapp.dashboard", [
            "user" => $user
        ]);
    }
    
    /**
     * Demo API endpoint for conversations
     */
    public function getConversations(Request $request)
    {
        $role = $request->get('role', 'agent');
        $canSeePhone = ($role === 'super_admin');
        $canSeeAll = in_array($role, ['super_admin', 'admin']);
        
        // Demo conversations with different countries and phone numbers
        $demoConversations = [
            [
                'id' => 1,
                'phone' => '+966501234567',  // Saudi Arabia
                'name' => 'أحمد محمد',
                'last_message' => 'السلام عليكم، أحتاج مساعدة في طلبي',
                'last_msg_time' => now()->subMinutes(5),
                'unread' => 2,
                'status' => 'new',
                'assigned_to' => null
            ],
            [
                'id' => 2,
                'phone' => '+201012345678',  // Egypt
                'name' => 'فاطمة حسن',
                'last_message' => 'شكرا لكم على الدعم الممتاز',
                'last_msg_time' => now()->subMinutes(15),
                'unread' => 0,
                'status' => 'resolved',
                'assigned_to' => 4
            ],
            [
                'id' => 3,
                'phone' => '+971501234567',  // UAE
                'name' => 'Mohammed Al-Rashid',
                'last_message' => 'When will my order be delivered?',
                'last_msg_time' => now()->subMinutes(30),
                'unread' => 1,
                'status' => 'open',
                'assigned_to' => 3
            ],
            [
                'id' => 4,
                'phone' => '+12125551234',   // USA
                'name' => 'John Smith',
                'last_message' => 'Thanks for the quick response!',
                'last_msg_time' => now()->subHour(),
                'unread' => 0,
                'status' => 'closed',
                'assigned_to' => 2
            ],
            [
                'id' => 5,
                'phone' => '+447700900123',  // UK
                'name' => 'Sarah Johnson',
                'last_message' => 'I have a question about pricing',
                'last_msg_time' => now()->subHours(2),
                'unread' => 3,
                'status' => 'new',
                'assigned_to' => null
            ],
            [
                'id' => 6,
                'phone' => '+33123456789',   // France
                'name' => 'Pierre Dubois',
                'last_message' => 'Bonjour, j\'ai un problème avec ma commande',
                'last_msg_time' => now()->subHours(3),
                'unread' => 1,
                'status' => 'open',
                'assigned_to' => 1
            ]
        ];
        
        // Filter conversations based on role
        if (!$canSeeAll) {
            $userId = $request->get('user_id', 4); // Default to agent user
            $demoConversations = array_filter($demoConversations, function($conv) use ($userId) {
                return $conv['assigned_to'] === $userId;
            });
        }
        
        // Format conversations for API response
        $formattedConversations = array_map(function($conv) use ($canSeePhone) {
            $phoneDisplay = CountryService::formatPhoneForDisplay(
                $conv['phone'],
                $conv['name'],
                $canSeePhone
            );
            
            return [
                'id' => $conv['id'],
                'contact_name' => $phoneDisplay['display_name'],
                'contact_phone' => $phoneDisplay['display_phone'],
                'country_flag' => $phoneDisplay['country_flag'],
                'country_name' => $phoneDisplay['country_name'],
                'is_arab' => $phoneDisplay['is_arab'],
                'last_message' => $conv['last_message'],
                'last_msg_time' => $conv['last_msg_time']->toISOString(),
                'unread' => $conv['unread'],
                'status' => $conv['status'],
                'assigned_to' => $conv['assigned_to'],
                'can_see_full_phone' => $canSeePhone,
                'full_phone' => $canSeePhone ? $phoneDisplay['full_phone'] : null
            ];
        }, $demoConversations);
        
        return response()->json([
            'conversations' => array_values($formattedConversations),
            'user_permissions' => [
                'can_see_all' => $canSeeAll,
                'can_see_phone' => $canSeePhone,
                'can_assign' => in_array($role, ['super_admin', 'admin', 'supervisor']),
                'can_delete' => in_array($role, ['super_admin', 'admin'])
            ]
        ]);
    }
    
    /**
     * Demo API endpoint for messages
     */
    public function getMessages(Request $request, $conversationId)
    {
        $role = $request->get('role', 'agent');
        $canSeePhone = ($role === 'super_admin');
        
        // Demo messages for different conversations
        $demoMessages = [
            1 => [
                ['id' => 1, 'text' => 'السلام عليكم', 'type' => 'received', 'time' => '10:30', 'status' => 'read'],
                ['id' => 2, 'text' => 'وعليكم السلام، كيف يمكنني مساعدتك؟', 'type' => 'sent', 'time' => '10:31', 'status' => 'read'],
                ['id' => 3, 'text' => 'أحتاج مساعدة في طلبي رقم #12345', 'type' => 'received', 'time' => '10:32', 'status' => 'read'],
            ],
            2 => [
                ['id' => 4, 'text' => 'شكرا لكم على الدعم', 'type' => 'received', 'time' => '09:15', 'status' => 'read'],
                ['id' => 5, 'text' => 'العفو، نحن سعداء بخدمتك', 'type' => 'sent', 'time' => '09:16', 'status' => 'read'],
            ],
            3 => [
                ['id' => 6, 'text' => 'Hello, when will my order arrive?', 'type' => 'received', 'time' => '14:20', 'status' => 'delivered'],
                ['id' => 7, 'text' => 'Hi! Your order should arrive within 2-3 business days.', 'type' => 'sent', 'time' => '14:25', 'status' => 'read'],
                ['id' => 8, 'text' => 'Great, thank you!', 'type' => 'received', 'time' => '14:26', 'status' => 'read'],
            ]
        ];
        
        $messages = $demoMessages[$conversationId] ?? [
            ['id' => 99, 'text' => 'This is a demo conversation', 'type' => 'sent', 'time' => '12:00', 'status' => 'sent']
        ];
        
        // Get conversation info for header
        $phoneDisplay = CountryService::formatPhoneForDisplay(
            '+966501234567',
            'Demo Contact',
            $canSeePhone
        );
        
        return response()->json([
            'messages' => $messages,
            'conversation' => [
                'id' => $conversationId,
                'contact_name' => $phoneDisplay['display_name'],
                'contact_phone' => $phoneDisplay['display_phone'],
                'country_flag' => $phoneDisplay['country_flag'],
                'country_name' => $phoneDisplay['country_name'],
                'status' => 'open'
            ]
        ]);
    }
    
    /**
     * Demo send message endpoint
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required',
            'message' => 'required|string|max:1000'
        ]);
        
        // Simulate successful message sending
        return response()->json([
            'success' => true,
            'message' => [
                'id' => rand(1000, 9999),
                'text' => $request->message,
                'type' => 'sent',
                'time' => now()->format('H:i'),
                'status' => 'sent'
            ]
        ]);
    }
}