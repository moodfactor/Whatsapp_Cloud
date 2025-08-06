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
                <a href="#" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-comments"></i> Conversations
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-users"></i> Users
                </a>
            </div>
            <div class="nav-item">
                <a href="/dashboard" class="nav-link">
                    <i class="fab fa-whatsapp"></i> WhatsApp Chat
                </a>
            </div>
        </nav>
        
        <div class="user-info">
            <div class="user-name">Admin User</div>
            <div class="user-role">Super Admin</div>
            <button class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <div>
                <span style="color: #666; font-size: 14px;">
                    Welcome back, Admin!
                </span>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>0</h3>
                    <p>Total Users (0 active)</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon conversations">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-info">
                    <h3>0</h3>
                    <p>Total Conversations (0 active)</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon messages">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-info">
                    <h3>0</h3>
                    <p>Total Messages (0 today)</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="stat-info">
                    <h3>0</h3>
                    <p>Active This Week</p>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Conversations -->
            <div class="content-card">
                <div class="card-header">
                    <h3>Recent Conversations</h3>
                    <a href="#" class="btn btn-primary">View All</a>
                </div>
                <div class="card-content">
                    <div class="empty-state">
                        <i class="fas fa-comments" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                        <p>No conversations yet</p>
                    </div>
                </div>
            </div>

            <!-- Recent Messages -->
            <div class="content-card">
                <div class="card-header">
                    <h3>Recent Messages</h3>
                    <a href="#" class="btn btn-primary">View All</a>
                </div>
                <div class="card-content">
                    <div class="empty-state">
                        <i class="fas fa-envelope" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                        <p>No messages yet</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php /**PATH /home/u539863725/domains/al-najjarstore.com/public_html/connect/resources/views/admin/dashboard.blade.php ENDPATH**/ ?>