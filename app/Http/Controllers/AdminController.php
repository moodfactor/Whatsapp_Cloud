<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\WhatsappAdmin;
use App\Models\Conversation;
use App\Models\WhatsAppInteractionMessage;
use App\Services\CountryService;
use App\Services\MetaWhatsappService;
use Carbon\Carbon;

class AdminController extends BaseController
{
    protected $whatsappService;

    public function __construct(MetaWhatsappService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('whatsapp_admin')->attempt($credentials)) {
            $request->session()->regenerate();
            
            // Update last login
            $admin = Auth::guard('whatsapp_admin')->user();
            $admin->update(['last_login' => now()]);
            
            return redirect()->intended('/admin/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('whatsapp_admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('admin.login');
    }

    public function dashboard()
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        // Get statistics
        $stats = $this->getDashboardStats($admin);
        
        // Get recent conversations
        $recentConversations = $this->getRecentConversations($admin);
        
        // Get recent messages
        $recentMessages = $this->getRecentMessages($admin);
        
        return view('admin.dashboard', compact('admin', 'stats', 'recentConversations', 'recentMessages'));
    }

    public function users()
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        // Check permissions
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            return redirect()->route('admin.dashboard')->with('error', 'Access denied.');
        }
        
        $users = WhatsappAdmin::orderBy('created_at', 'desc')->get();
        
        return view('admin.users.index', compact('users', 'admin'));
    }

    public function createUser()
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            return redirect()->route('admin.dashboard')->with('error', 'Access denied.');
        }
        
        return view('admin.users.create', compact('admin'));
    }

    public function storeUser(Request $request)
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            return redirect()->route('admin.dashboard')->with('error', 'Access denied.');
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:whatsapp_admins',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:super_admin,admin,supervisor,agent',
            'status' => 'required|in:active,inactive'
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // Only super_admin can create super_admin users
        if ($request->role === 'super_admin' && $admin->role !== 'super_admin') {
            return redirect()->back()
                ->with('error', 'Only Super Admins can create Super Admin users.')
                ->withInput();
        }
        
        WhatsappAdmin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status,
            'created_by' => $admin->id
        ]);
        
        return redirect()->route('admin.users')
            ->with('success', 'User created successfully.');
    }

    public function editUser($id)
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            return redirect()->route('admin.dashboard')->with('error', 'Access denied.');
        }
        
        $user = WhatsappAdmin::findOrFail($id);
        
        return view('admin.users.edit', compact('user', 'admin'));
    }

    public function updateUser(Request $request, $id)
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            return redirect()->route('admin.dashboard')->with('error', 'Access denied.');
        }
        
        $user = WhatsappAdmin::findOrFail($id);
        
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:whatsapp_admins,email,' . $id,
            'role' => 'required|in:super_admin,admin,supervisor,agent',
            'status' => 'required|in:active,inactive'
        ];
        
        if ($request->filled('password')) {
            $rules['password'] = 'string|min:6|confirmed';
        }
        
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // Only super_admin can modify super_admin users or assign super_admin role
        if (($user->role === 'super_admin' || $request->role === 'super_admin') && $admin->role !== 'super_admin') {
            return redirect()->back()
                ->with('error', 'Only Super Admins can modify Super Admin users.')
                ->withInput();
        }
        
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'status' => $request->status
        ];
        
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }
        
        $user->update($updateData);
        
        return redirect()->route('admin.users')
            ->with('success', 'User updated successfully.');
    }

    public function deleteUser(Request $request, $id)
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            return response()->json(['success' => false, 'error' => 'Access denied.']);
        }
        
        $user = WhatsappAdmin::findOrFail($id);
        
        // Cannot delete self
        if ($user->id === $admin->id) {
            return response()->json(['success' => false, 'error' => 'Cannot delete yourself.']);
        }
        
        // Only super_admin can delete super_admin users
        if ($user->role === 'super_admin' && $admin->role !== 'super_admin') {
            return response()->json(['success' => false, 'error' => 'Only Super Admins can delete Super Admin users.']);
        }
        
        $user->delete();
        
        return response()->json(['success' => true]);
    }

    public function checkAuth()
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!$admin) {
            return response()->json(['authenticated' => false]);
        }
        
        return response()->json([
            'authenticated' => true,
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
                'permissions' => $this->getUserPermissions($admin)
            ]
        ]);
    }

    private function getDashboardStats($admin)
    {
        $stats = [];
        
        // Users statistics (only for super_admin and admin)
        if (in_array($admin->role, ['super_admin', 'admin'])) {
            $stats['total_users'] = WhatsappAdmin::count();
            $stats['active_users'] = WhatsappAdmin::where('status', 'active')->count();
            $stats['show_user_stats'] = true;
        } else {
            $stats['total_users'] = 0;
            $stats['active_users'] = 0;
            $stats['show_user_stats'] = false;
        }
        
        // Conversations statistics
        $conversationQuery = Conversation::query();
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            $conversationQuery->where('assigned_to', $admin->id);
        }
        
        $stats['total_conversations'] = $conversationQuery->count();
        $stats['active_conversations'] = $conversationQuery->whereIn('status', ['new', 'open', 'assigned'])->count();
        $stats['unread_conversations'] = $conversationQuery->where('unread', '>', 0)->count();
        
        // Messages statistics
        $messageQuery = WhatsAppInteractionMessage::query();
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            $messageQuery->whereHas('interaction', function($q) use ($admin) {
                $q->where('assigned_to', $admin->id);
            });
        }
        
        $stats['total_messages'] = $messageQuery->count();
        $stats['messages_today'] = $messageQuery->whereDate('time_sent', today())->count();
        $stats['messages_this_week'] = $messageQuery->whereBetween('time_sent', [now()->startOfWeek(), now()->endOfWeek()])->count();
        
        return $stats;
    }

    private function getRecentConversations($admin)
    {
        $query = Conversation::with('assignedTo');
        
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            $query->where('assigned_to', $admin->id);
        }
        
        return $query->orderBy('last_msg_time', 'desc')
            ->limit(5)
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
                    'last_message' => $conversation->last_message,
                    'last_msg_time' => $conversation->last_msg_time,
                    'status' => $conversation->status,
                    'unread' => $conversation->unread,
                    'assigned_to' => $conversation->assignedTo ? $conversation->assignedTo->name : 'Unassigned'
                ];
            });
    }

    private function getRecentMessages($admin)
    {
        $query = WhatsAppInteractionMessage::with('interaction');
        
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            $query->whereHas('interaction', function($q) use ($admin) {
                $q->where('assigned_to', $admin->id);
            });
        }
        
        return $query->orderBy('time_sent', 'desc')
            ->limit(10)
            ->get()
            ->map(function($message) use ($admin) {
                $phoneDisplay = CountryService::formatPhoneForDisplay(
                    $message->interaction->decrypted_phone ?? '',
                    $message->interaction->contact_name ?? 'Unknown',
                    ($admin->role === 'super_admin')
                );
                
                return [
                    'id' => $message->id,
                    'contact_name' => $phoneDisplay['display_name'],
                    'message' => $message->message,
                    'type' => $message->nature, // sent or received
                    'time_sent' => $message->time_sent,
                    'conversation_id' => $message->interaction_id
                ];
            });
    }

    private function getUserPermissions($admin)
    {
        return [
            'role_name' => $this->getRoleName($admin->role),
            'can_see_all' => in_array($admin->role, ['super_admin', 'admin']),
            'can_assign' => in_array($admin->role, ['super_admin', 'admin', 'supervisor']),
            'can_delete' => in_array($admin->role, ['super_admin', 'admin']),
            'can_see_phone' => ($admin->role === 'super_admin'),
            'can_manage_users' => in_array($admin->role, ['super_admin', 'admin'])
        ];
    }

    private function getRoleName($role)
    {
        return match($role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'supervisor' => 'Supervisor',
            'agent' => 'Agent',
            default => 'User'
        };
    }

    public function whatsappSettings()
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            return redirect()->route('admin.dashboard')->with('error', 'Access denied.');
        }
        
        // Check token status
        $tokenStatus = $this->checkTokenStatus();
        
        return view('admin.whatsapp-settings', compact('admin', 'tokenStatus'));
    }

    public function updateWhatsappToken(Request $request)
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            return redirect()->route('admin.dashboard')->with('error', 'Access denied.');
        }
        
        $request->validate([
            'access_token' => 'required|string|min:50',
            'phone_number_id' => 'nullable|string'
        ]);
        
        try {
            // Test the new token first
            $testResult = $this->whatsappService->testAccessToken($request->access_token);
            
            if (!$testResult['success']) {
                return redirect()->back()
                    ->with('error', 'Token validation failed: ' . $testResult['error'])
                    ->withInput();
            }
            
            // Update .env file
            $this->updateEnvFile([
                'WHATSAPP_ACCESS_TOKEN' => $request->access_token,
                'META_API_TOKEN' => '"' . $request->access_token . '"'
            ]);
            
            if ($request->phone_number_id) {
                $this->updateEnvFile([
                    'WHATSAPP_PHONE_NUMBER_ID' => $request->phone_number_id,
                    'META_PHONE_NUMBER_ID' => '"' . $request->phone_number_id . '"'
                ]);
            }
            
            // Clear config cache
            \Artisan::call('config:clear');
            
            return redirect()->back()->with('success', 'WhatsApp token updated successfully!');
            
        } catch (\Exception $e) {
            \Log::error('WhatsApp token update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update token: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function testWhatsappToken(Request $request)
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            return response()->json(['success' => false, 'error' => 'Access denied']);
        }
        
        try {
            $result = $this->whatsappService->testAccessToken();
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function checkTokenStatus()
    {
        try {
            $result = $this->whatsappService->testAccessToken();
            
            if ($result['success']) {
                return [
                    'status' => 'valid',
                    'message' => 'Token is valid',
                    'expires_at' => $result['expires_at'] ?? null
                ];
            } else {
                return [
                    'status' => 'expired',
                    'message' => 'Token is expired or invalid: ' . $result['error']
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unknown',
                'message' => 'Could not verify token: ' . $e->getMessage()
            ];
        }
    }

    private function updateEnvFile(array $data)
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);
        
        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";
            
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }
        
        file_put_contents($envFile, $envContent);
    }
}