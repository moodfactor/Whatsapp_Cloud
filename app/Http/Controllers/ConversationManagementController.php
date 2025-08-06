<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Conversation;
use App\Models\WhatsappAdmin;
use App\Models\WhatsAppInteractionMessage;
use App\Services\CountryService;

class ConversationManagementController extends BaseController
{
    public function index(Request $request)
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        $query = Conversation::with(['assignedTo', 'messages' => function($q) {
            $q->latest('time_sent')->limit(1);
        }]);
        
        // Apply role-based filtering
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            $query->where('assigned_to', $admin->id);
        }
        
        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('receiver_id', 'like', "%{$search}%")
                  ->orWhere('wa_no', 'like', "%{$search}%");
            });
        }
        
        $conversations = $query->orderBy('last_msg_time', 'desc')
            ->paginate(20);
        
        // Format conversations for display
        $conversations->getCollection()->transform(function($conversation) use ($admin) {
            $phoneDisplay = CountryService::formatPhoneForDisplay(
                $conversation->decrypted_phone,
                $conversation->contact_name,
                ($admin->role === 'super_admin')
            );
            
            $conversation->display_name = $phoneDisplay['display_name'];
            $conversation->display_phone = $phoneDisplay['display_phone'];
            $conversation->country_flag = $phoneDisplay['country_flag'];
            $conversation->country_name = $phoneDisplay['country_name'];
            
            return $conversation;
        });
        
        // Get all agents for assignment dropdown
        $agents = WhatsappAdmin::where('status', 'active')
            ->whereIn('role', ['admin', 'supervisor', 'agent'])
            ->orderBy('name')
            ->get();
        
        // Get statistics
        $stats = $this->getConversationStats($admin);
        
        return view('admin.conversations.index', compact('conversations', 'admin', 'agents', 'stats'));
    }
    
    public function show($id)
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        $conversation = Conversation::with(['assignedTo', 'messages' => function($q) {
            $q->orderBy('time_sent', 'asc');
        }])->findOrFail($id);
        
        // Check permissions
        if (!in_array($admin->role, ['super_admin', 'admin']) && $conversation->assigned_to !== $admin->id) {
            return redirect()->route('admin.conversations')->with('error', 'Access denied.');
        }
        
        // Format conversation for display
        $phoneDisplay = CountryService::formatPhoneForDisplay(
            $conversation->decrypted_phone,
            $conversation->contact_name,
            ($admin->role === 'super_admin')
        );
        
        $conversation->display_name = $phoneDisplay['display_name'];
        $conversation->display_phone = $phoneDisplay['display_phone'];
        $conversation->country_flag = $phoneDisplay['country_flag'];
        $conversation->country_name = $phoneDisplay['country_name'];
        
        // Mark as read if unread
        if ($conversation->unread > 0) {
            $conversation->update(['unread' => 0]);
        }
        
        // Get all agents for assignment
        $agents = WhatsappAdmin::where('status', 'active')
            ->whereIn('role', ['admin', 'supervisor', 'agent'])
            ->orderBy('name')
            ->get();
        
        return view('admin.conversations.show', compact('conversation', 'admin', 'agents'));
    }
    
    public function assign(Request $request, $id)
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!in_array($admin->role, ['super_admin', 'admin', 'supervisor'])) {
            return response()->json(['success' => false, 'error' => 'Access denied.']);
        }
        
        $conversation = Conversation::findOrFail($id);
        
        $request->validate([
            'assigned_to' => 'nullable|exists:whatsapp_admins,id'
        ]);
        
        $conversation->update([
            'assigned_to' => $request->assigned_to,
            'status' => $request->assigned_to ? 'assigned' : 'new'
        ]);
        
        $assignedUser = $request->assigned_to ? WhatsappAdmin::find($request->assigned_to) : null;
        $message = $assignedUser 
            ? "Conversation assigned to {$assignedUser->name}"
            : "Conversation unassigned";
        
        return response()->json(['success' => true, 'message' => $message]);
    }
    
    public function updateStatus(Request $request, $id)
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        $conversation = Conversation::findOrFail($id);
        
        // Check permissions
        if (!in_array($admin->role, ['super_admin', 'admin']) && $conversation->assigned_to !== $admin->id) {
            return response()->json(['success' => false, 'error' => 'Access denied.']);
        }
        
        $request->validate([
            'status' => 'required|in:new,open,assigned,resolved,closed'
        ]);
        
        $conversation->update(['status' => $request->status]);
        
        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }
    
    public function destroy($id)
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            return response()->json(['success' => false, 'error' => 'Access denied.']);
        }
        
        $conversation = Conversation::findOrFail($id);
        
        // Delete associated messages first
        WhatsAppInteractionMessage::where('interaction_id', $id)->delete();
        
        // Delete conversation
        $conversation->delete();
        
        return response()->json(['success' => true, 'message' => 'Conversation deleted successfully.']);
    }
    
    public function bulkAction(Request $request)
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        
        $request->validate([
            'action' => 'required|in:assign,delete,mark_read,change_status',
            'conversation_ids' => 'required|array',
            'conversation_ids.*' => 'exists:whatsapp_interactions,id',
            'assigned_to' => 'nullable|exists:whatsapp_admins,id',
            'status' => 'nullable|in:new,open,assigned,resolved,closed'
        ]);
        
        $conversations = Conversation::whereIn('id', $request->conversation_ids);
        
        // Apply role-based filtering
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            $conversations->where('assigned_to', $admin->id);
        }
        
        $count = 0;
        
        switch ($request->action) {
            case 'assign':
                if (!in_array($admin->role, ['super_admin', 'admin', 'supervisor'])) {
                    return response()->json(['success' => false, 'error' => 'Access denied.']);
                }
                $count = $conversations->update(['assigned_to' => $request->assigned_to]);
                break;
                
            case 'delete':
                if (!in_array($admin->role, ['super_admin', 'admin'])) {
                    return response()->json(['success' => false, 'error' => 'Access denied.']);
                }
                // Delete associated messages
                WhatsAppInteractionMessage::whereIn('interaction_id', $request->conversation_ids)->delete();
                $count = $conversations->delete();
                break;
                
            case 'mark_read':
                $count = $conversations->update(['unread' => 0]);
                break;
                
            case 'change_status':
                $count = $conversations->update(['status' => $request->status]);
                break;
        }
        
        return response()->json([
            'success' => true, 
            'message' => "Action completed successfully on {$count} conversations."
        ]);
    }
    
    public function statistics()
    {
        $admin = Auth::guard('whatsapp_admin')->user();
        $stats = $this->getConversationStats($admin);
        
        return response()->json($stats);
    }
    
    private function getConversationStats($admin)
    {
        $query = Conversation::query();
        
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            $query->where('assigned_to', $admin->id);
        }
        
        $total = $query->count();
        $new = $query->where('status', 'new')->count();
        $open = $query->where('status', 'open')->count();
        $assigned = $query->where('status', 'assigned')->count();
        $resolved = $query->where('status', 'resolved')->count();
        $closed = $query->where('status', 'closed')->count();
        $unread = $query->where('unread', '>', 0)->count();
        
        return [
            'total' => $total,
            'new' => $new,
            'open' => $open,
            'assigned' => $assigned,
            'resolved' => $resolved,
            'closed' => $closed,
            'unread' => $unread,
            'active' => $new + $open + $assigned
        ];
    }
}
