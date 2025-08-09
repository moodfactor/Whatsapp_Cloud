<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: #075e54;
            color: white;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #064940;
        }
        
        .sidebar-header h2 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .nav-menu {
            padding: 20px 0;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            display: block;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        .user-info {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 20px;
            background: #064940;
        }
        
        .user-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .user-role {
            font-size: 12px;
            opacity: 0.8;
            margin-bottom: 10px;
        }
        
        .logout-btn {
            width: 100%;
            padding: 8px;
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .main-content {
            margin-left: 260px;
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            font-size: 28px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 20px;
            color: white;
        }
        
        .stat-icon.users { background: #007bff; }
        .stat-icon.conversations { background: #28a745; }
        .stat-icon.messages { background: #ffc107; color: #333; }
        .stat-icon.active { background: #17a2b8; }
        
        .stat-info h3 {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            color: #666;
            font-size: 14px;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .content-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h3 {
            color: #333;
            font-size: 18px;
        }
        
        .card-content {
            padding: 0;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .conversation-item, .message-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f5f5f5;
            transition: background 0.3s;
        }
        
        .conversation-item:hover, .message-item:hover {
            background: #f8f9fa;
        }
        
        .conversation-name {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }
        
        .conversation-phone {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .conversation-message {
            font-size: 13px;
            color: #666;
        }
        
        .conversation-time {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-new { background: #fff3cd; color: #856404; }
        .status-in_progress { background: #d4edda; color: #155724; }
        .status-resolved { background: #cce5ff; color: #004085; }
        .status-closed { background: #f8d7da; color: #721c24; }
        
        .message-direction {
            font-size: 11px;
            font-weight: 500;
            margin-bottom: 3px;
        }
        
        .message-sent { color: #007bff; }
        .message-received { color: #28a745; }
        
        .empty-state {
            padding: 40px;
            text-align: center;
            color: #666;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>🚀 WhatsApp Admin</h2>
            <p>Connect & Manage</p>
        </div>
        
        <nav class="nav-menu">
            <div class="nav-item">
                <a href="{{ route('admin.dashboard') }}" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('admin.conversations') }}" class="nav-link">
                    <i class="fas fa-comments"></i> Conversations
                </a>
            </div>
            @if(in_array($admin->role ?? 'agent', ['super_admin', 'admin']))
            <div class="nav-item">
                <a href="{{ route('admin.users') }}" class="nav-link">
                    <i class="fas fa-users"></i> Users
                </a>
            </div>
            @endif
            <div class="nav-item">
                <a href="{{ route('whatsapp.dashboard') }}" class="nav-link">
                    <i class="fab fa-whatsapp"></i> WhatsApp Chat
                </a>
            </div>
            @if(in_array($admin->role ?? 'agent', ['super_admin', 'admin']))
            <div class="nav-item">
                <a href="{{ route('admin.whatsapp.settings') }}" class="nav-link">
                    <i class="fas fa-cog"></i> WhatsApp Settings
                </a>
            </div>
            @endif
        </nav>
        
        <div class="user-info">
            <div class="user-name">{{ $admin->name ?? 'Admin User' }}</div>
            <div class="user-role">{{ ucwords(str_replace('_', ' ', $admin->role ?? 'admin')) }}</div>
            <form action="{{ route('admin.logout') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <div>
                <span style="color: #666; font-size: 14px;">
                    Welcome back, {{ $admin->name ?? 'Admin' }}! 
                    <small>({{ ucwords(str_replace('_', ' ', $admin->role ?? 'admin')) }})</small>
                </span>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            @if(($stats['show_user_stats'] ?? false))
            <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['total_users'] ?? 0 }}</h3>
                    <p>Total Users ({{ $stats['active_users'] ?? 0 }} active)</p>
                </div>
            </div>
            @endif
            
            <div class="stat-card">
                <div class="stat-icon conversations">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['total_conversations'] ?? 0 }}</h3>
                    <p>
                        @if(in_array($admin->role ?? 'agent', ['super_admin', 'admin']))
                            Total Conversations ({{ $stats['active_conversations'] ?? 0 }} active)
                        @else
                            My Conversations ({{ $stats['active_conversations'] ?? 0 }} active)
                        @endif
                    </p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon messages">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['total_messages'] ?? 0 }}</h3>
                    <p>
                        @if(in_array($admin->role ?? 'agent', ['super_admin', 'admin']))
                            Total Messages ({{ $stats['messages_today'] ?? 0 }} today)
                        @else
                            My Messages ({{ $stats['messages_today'] ?? 0 }} today)
                        @endif
                    </p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['messages_this_week'] ?? 0 }}</h3>
                    <p>
                        @if(in_array($admin->role ?? 'agent', ['super_admin', 'admin']))
                            Messages This Week
                        @else
                            My Messages This Week
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Conversations -->
            <div class="content-card">
                <div class="card-header">
                    <h3>
                        @if(in_array($admin->role ?? 'agent', ['super_admin', 'admin']))
                            Recent Conversations
                        @else
                            My Recent Conversations
                        @endif
                    </h3>
                    <a href="{{ route('admin.conversations') }}" class="btn btn-primary">
                        @if(in_array($admin->role ?? 'agent', ['super_admin', 'admin']))
                            View All
                        @else
                            View Mine
                        @endif
                    </a>
                </div>
                <div class="card-content">
                    @if(count($recentConversations ?? []) > 0)
                        @foreach($recentConversations as $conversation)
                            <div class="conversation-item">
                                <div class="conversation-name">
                                    <span style="font-size: 16px; margin-right: 8px;">{{ $conversation['country_flag'] }}</span>
                                    {{ $conversation['contact_name'] }}
                                    <span class="status-badge status-{{ $conversation['status'] }}">{{ $conversation['status'] }}</span>
                                </div>
                                <div class="conversation-phone">{{ $conversation['contact_phone'] }}</div>
                                <div class="conversation-message">{{ Str::limit($conversation['last_message'] ?? '', 50) }}</div>
                                <div class="conversation-time">
                                    {{ $conversation['last_msg_time'] ? \Carbon\Carbon::parse($conversation['last_msg_time'])->diffForHumans() : '' }}
                                    @if($conversation['unread'] > 0)
                                        <span style="background: #25d366; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px; margin-left: 8px;">{{ $conversation['unread'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <i class="fas fa-comments" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                            <p>No conversations yet</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Messages -->
            <div class="content-card">
                <div class="card-header">
                    <h3>Recent Messages</h3>
                    <a href="{{ route('whatsapp.dashboard') }}" class="btn btn-primary">View Chat</a>
                </div>
                <div class="card-content">
                    @if(count($recentMessages ?? []) > 0)
                        @foreach($recentMessages as $message)
                            <div class="message-item">
                                <div class="message-direction {{ $message['type'] === 'sent' ? 'message-sent' : 'message-received' }}">
                                    {{ $message['type'] === 'sent' ? '→ Sent to' : '← Received from' }}: {{ $message['contact_name'] }}
                                </div>
                                <div style="color: #333; margin: 3px 0;">{{ Str::limit($message['message'], 60) }}</div>
                                <div style="color: #999; font-size: 11px;">{{ \Carbon\Carbon::parse($message['time_sent'])->diffForHumans() }}</div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <i class="fas fa-envelope" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                            <p>No messages yet</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
