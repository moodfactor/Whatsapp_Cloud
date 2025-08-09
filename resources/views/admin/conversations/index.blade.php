<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversations - WhatsApp Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            overflow-x: hidden;
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
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        
        .sidebar.open {
            transform: translateX(0);
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }
        
        .sidebar-overlay.show {
            display: block;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #064940;
        }
        
        .sidebar-header h2 {
            font-size: 18px;
            margin-bottom: 5px;
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
        
        .main-content {
            margin-left: 0;
            padding: 20px;
            padding-top: 70px;
            min-height: 100vh;
        }
        
        .mobile-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: #075e54;
            color: white;
            display: flex;
            align-items: center;
            padding: 0 20px;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            margin-right: 15px;
        }
        
        .mobile-title {
            font-size: 18px;
            font-weight: 600;
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
        
        .filters {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: 500;
        }
        
        .filter-group input, .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 24px;
            margin-bottom: 5px;
            color: #333;
        }
        
        .stat-card p {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        
        .card {
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
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 500;
            color: #495057;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .conversation-row:hover {
            background: #f8f9fa;
        }
        
        .contact-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .country-flag {
            font-size: 18px;
        }
        
        .contact-name {
            font-weight: 600;
            color: #333;
        }
        
        .contact-phone {
            font-size: 12px;
            color: #666;
        }
        
        .message-preview {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #666;
            font-size: 13px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-new {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-open {
            background: #d4edda;
            color: #155724;
        }
        
        .status-assigned {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-resolved {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .status-closed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .unread-badge {
            background: #25d366;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
        }
        
        .time-ago {
            font-size: 12px;
            color: #999;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .bulk-actions {
            background: #f8f9fa;
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: none;
        }
        
        .bulk-actions.show {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .empty-state {
            padding: 60px;
            text-align: center;
            color: #666;
        }
        
        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
        }
        
        /* Desktop Styles */
        @media (min-width: 768px) {
            .mobile-header {
                display: none;
            }
            
            .sidebar {
                transform: translateX(0);
                position: fixed;
            }
            
            .main-content {
                margin-left: 260px;
                padding: 30px;
                padding-top: 30px;
            }
            
            .filters {
                padding: 20px;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .stats-row {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }
        }
        
        /* Mobile Styles */
        @media (max-width: 767px) {
            .header h1 {
                font-size: 20px;
            }
            
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            table {
                min-width: 600px;
            }
            
            th, td {
                padding: 8px;
                font-size: 13px;
            }
            
            .contact-name {
                font-size: 13px;
            }
            
            .contact-phone {
                font-size: 10px;
            }
            
            .message-preview {
                max-width: 150px;
                font-size: 12px;
            }
            
            .actions {
                gap: 3px;
            }
            
            .btn-sm {
                padding: 3px 6px;
                font-size: 11px;
            }
            
            .bulk-actions {
                padding: 10px 15px;
                font-size: 13px;
                flex-wrap: wrap;
            }
            
            .bulk-actions select {
                font-size: 12px;
                padding: 5px 8px;
            }
            
            .country-flag {
                font-size: 16px;
            }
            
            .stat-card {
                padding: 12px;
            }
            
            .stat-card h3 {
                font-size: 18px;
            }
            
            .stat-card p {
                font-size: 10px;
            }
        }
        
        /* Very Small Screens */
        @media (max-width: 480px) {
            table {
                min-width: 500px;
            }
            
            .filters {
                padding: 10px;
            }
            
            .filter-group input, .filter-group select {
                padding: 6px 8px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="mobile-title">🚀 Conversations</div>
    </div>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" onclick="closeSidebar()"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>🚀 WhatsApp Admin</h2>
            <p>Conversations</p>
        </div>
        
        <nav class="nav-menu">
            <div class="nav-item">
                <a href="{{ route('admin.dashboard') }}" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('admin.conversations') }}" class="nav-link active">
                    <i class="fas fa-comments"></i> Conversations
                </a>
            </div>
            @if(in_array($admin->role, ['super_admin', 'admin']))
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
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>
                @if(in_array($admin->role, ['super_admin', 'admin']))
                    All Conversations
                @else
                    My Conversations
                @endif
            </h1>
            <div>
                <span style="color: #666; font-size: 14px;">
                    @if(in_array($admin->role, ['super_admin', 'admin']))
                        {{ $conversations->total() }} total conversations
                    @else
                        {{ $conversations->total() }} assigned to me
                    @endif
                </span>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        <!-- Statistics -->
        <div class="stats-row">
            <div class="stat-card">
                <h3>{{ $stats['total'] ?? 0 }}</h3>
                <p>
                    @if(in_array($admin->role, ['super_admin', 'admin']))
                        Total
                    @else
                        Mine
                    @endif
                </p>
            </div>
            <div class="stat-card">
                <h3>{{ $stats['new'] ?? 0 }}</h3>
                <p>New</p>
            </div>
            <div class="stat-card">
                <h3>{{ $stats['open'] ?? 0 }}</h3>
                <p>Open</p>
            </div>
            <div class="stat-card">
                <h3>{{ $stats['assigned'] ?? 0 }}</h3>
                <p>Assigned</p>
            </div>
            <div class="stat-card">
                <h3>{{ $stats['unread'] ?? 0 }}</h3>
                <p>Unread</p>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" action="{{ route('admin.conversations') }}" class="filters">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" placeholder="Search conversations..." 
                       value="{{ request('search') }}">
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="new" {{ request('status') == 'new' ? 'selected' : '' }}>New</option>
                    <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                    <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}>Assigned</option>
                    <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
            @if(in_array($admin->role, ['super_admin', 'admin']))
            <div class="filter-group">
                <label>Assigned To</label>
                <select name="assigned_to">
                    <option value="">All Agents</option>
                    <option value="0" {{ request('assigned_to') === '0' ? 'selected' : '' }}>Unassigned</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" {{ request('assigned_to') == $agent->id ? 'selected' : '' }}>
                            {{ $agent->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="filter-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </form>

        <div class="card">
            <div class="card-header">
                <h3>
                    @if(in_array($admin->role, ['super_admin', 'admin']))
                        All Conversations
                    @else
                        My Conversations
                    @endif
                </h3>
                <div>
                    <button onclick="toggleBulkActions()" class="btn btn-warning btn-sm">
                        <i class="fas fa-tasks"></i> Bulk Actions
                    </button>
                </div>
            </div>
            
            <div class="table-container">
                @if($conversations->count() > 0)
                    <table>
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()">
                                </th>
                                <th>Contact</th>
                                <th>Last Message</th>
                                <th>Status</th>
                                @if(in_array($admin->role, ['super_admin', 'admin']))
                                <th>Assigned To</th>
                                @endif
                                <th>Last Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($conversations as $conversation)
                                <tr class="conversation-row">
                                    <td>
                                        <input type="checkbox" class="conversation-checkbox" 
                                               value="{{ $conversation->id }}">
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            <span class="country-flag">{{ $conversation->country_flag }}</span>
                                            <div>
                                                <div class="contact-name">{{ $conversation->display_name }}</div>
                                                <div class="contact-phone">{{ $conversation->display_phone }}</div>
                                            </div>
                                            @if($conversation->unread > 0)
                                                <div class="unread-badge">{{ $conversation->unread }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="message-preview">
                                            {{ $conversation->last_message ?? 'No messages yet' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-{{ $conversation->status }}">
                                            {{ ucfirst($conversation->status ?? 'new') }}
                                        </span>
                                    </td>
                                    @if(in_array($admin->role, ['super_admin', 'admin']))
                                    <td>
                                        {{ $conversation->assignedTo ? $conversation->assignedTo->name : 'Unassigned' }}
                                    </td>
                                    @endif
                                    <td>
                                        <div class="time-ago">
                                            {{ $conversation->last_msg_time ? $conversation->last_msg_time->diffForHumans() : 'Never' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="{{ route('admin.conversations.show', $conversation->id) }}" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if(in_array($admin->role, ['super_admin', 'admin']))
                                            <button onclick="deleteConversation({{ $conversation->id }})" 
                                                    class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    
                    <!-- Bulk Actions -->
                    <div class="bulk-actions" id="bulkActions">
                        <span id="selectedCount">0</span> conversations selected
                        @if(in_array($admin->role, ['super_admin', 'admin', 'supervisor']))
                        <select id="bulkAssignTo">
                            <option value="">Assign to...</option>
                            <option value="0">Unassign</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                            @endforeach
                        </select>
                        <button onclick="bulkAssign()" class="btn btn-success btn-sm">Assign</button>
                        @endif
                        
                        <select id="bulkStatus">
                            <option value="">Change status...</option>
                            <option value="new">New</option>
                            <option value="open">Open</option>
                            <option value="assigned">Assigned</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                        <button onclick="bulkChangeStatus()" class="btn btn-warning btn-sm">Update Status</button>
                        
                        <button onclick="bulkMarkRead()" class="btn btn-success btn-sm">Mark Read</button>
                        
                        @if(in_array($admin->role, ['super_admin', 'admin']))
                        <button onclick="bulkDelete()" class="btn btn-danger btn-sm">Delete</button>
                        @endif
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <h3>No Conversations Found</h3>
                        <p>Conversations will appear here when customers start messaging.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Pagination -->
        @if($conversations->hasPages())
            <div class="pagination">
                {{ $conversations->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    <script>
        let selectedConversations = [];

        function toggleAllCheckboxes() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.conversation-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateSelectedConversations();
        }

        function updateSelectedConversations() {
            const checkboxes = document.querySelectorAll('.conversation-checkbox:checked');
            selectedConversations = Array.from(checkboxes).map(cb => cb.value);
            
            document.getElementById('selectedCount').textContent = selectedConversations.length;
            
            const bulkActions = document.getElementById('bulkActions');
            if (selectedConversations.length > 0) {
                bulkActions.classList.add('show');
            } else {
                bulkActions.classList.remove('show');
            }
        }

        function toggleBulkActions() {
            const bulkActions = document.getElementById('bulkActions');
            if (bulkActions.classList.contains('show')) {
                bulkActions.classList.remove('show');
                // Uncheck all
                document.getElementById('selectAll').checked = false;
                document.querySelectorAll('.conversation-checkbox').forEach(cb => cb.checked = false);
                selectedConversations = [];
            }
        }

        // Add event listeners to checkboxes
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.conversation-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedConversations);
            });
        });

        async function bulkAction(action, data = {}) {
            if (selectedConversations.length === 0) {
                alert('Please select conversations first');
                return;
            }

            const requestData = {
                action: action,
                conversation_ids: selectedConversations,
                ...data
            };

            try {
                const response = await fetch('/admin/conversations/bulk-action', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(requestData)
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.error || 'Action failed');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to perform action');
            }
        }

        function bulkAssign() {
            const assignTo = document.getElementById('bulkAssignTo').value;
            if (!assignTo && assignTo !== '0') {
                alert('Please select an agent to assign to');
                return;
            }
            
            bulkAction('assign', { assigned_to: assignTo === '0' ? null : assignTo });
        }

        function bulkChangeStatus() {
            const status = document.getElementById('bulkStatus').value;
            if (!status) {
                alert('Please select a status');
                return;
            }
            
            bulkAction('change_status', { status: status });
        }

        function bulkMarkRead() {
            bulkAction('mark_read');
        }

        function bulkDelete() {
            if (confirm('Are you sure you want to delete the selected conversations? This action cannot be undone.')) {
                bulkAction('delete');
            }
        }

        function deleteConversation(id) {
            if (!confirm('Are you sure you want to delete this conversation? This action cannot be undone.')) {
                return;
            }

            fetch(`/admin/conversations/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Failed to delete conversation');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete conversation');
            });
        }
        
        // Mobile sidebar functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        }
        
        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        }
        
        // Close sidebar when clicking on nav links (mobile)
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    closeSidebar();
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                closeSidebar();
            }
        });
    </script>
</body>
</html>