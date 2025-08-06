<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User - WhatsApp Admin</title>
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
        
        .breadcrumb {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .breadcrumb a {
            color: #007bff;
            text-decoration: none;
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
            background: #f8f9fa;
        }
        
        .card-header h3 {
            color: #333;
            margin: 0;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .form-group .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .form-control.is-invalid {
            border-color: #dc3545;
        }
        
        .role-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 13px;
            color: #666;
        }
        
        .role-info h5 {
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .role-info ul {
            margin: 5px 0 0 20px;
        }
        
        .role-info li {
            margin-bottom: 3px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>🚀 WhatsApp Admin</h2>
            <p>User Management</p>
        </div>
        
        <nav class="nav-menu">
            <div class="nav-item">
                <a href="{{ route('admin.dashboard') }}" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('admin.conversations') }}" class="nav-link">
                    <i class="fas fa-comments"></i> Conversations
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('admin.users') }}" class="nav-link active">
                    <i class="fas fa-users"></i> Users
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('whatsapp.dashboard') }}" class="nav-link">
                    <i class="fab fa-whatsapp"></i> WhatsApp Chat
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a> / 
            <a href="{{ route('admin.users') }}">Users</a> / 
            Create New User
        </div>
        
        <div class="header">
            <h1>Create New User</h1>
            <a href="{{ route('admin.users') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <strong>Please fix the following errors:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3>User Information</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.users.store') }}" method="POST">
                    @csrf
                    
                    <div class="form-grid">
                        <div>
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" 
                                       class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                                       value="{{ old('name') }}" 
                                       placeholder="Enter full name" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" 
                                       class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                       value="{{ old('email') }}" 
                                       placeholder="Enter email address" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="help-text">This will be used for login</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" id="password" name="password" 
                                       class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                       placeholder="Enter password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="help-text">Minimum 6 characters</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="password_confirmation">Confirm Password *</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" 
                                       class="form-control"
                                       placeholder="Confirm password" required>
                                <div class="help-text">Re-enter the password to confirm</div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="form-group">
                                <label for="role">Role *</label>
                                <select id="role" name="role" 
                                        class="form-control {{ $errors->has('role') ? 'is-invalid' : '' }}"
                                        onchange="showRoleInfo()" required>
                                    <option value="">Select Role</option>
                                    @if($admin->role === 'super_admin')
                                        <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                                    @endif
                                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="supervisor" {{ old('role') === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                                    <option value="agent" {{ old('role') === 'agent' ? 'selected' : '' }}>Agent</option>
                                </select>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                
                                <div id="roleInfo" class="role-info" style="display: none;">
                                    <div id="roleDescription"></div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status *</label>
                                <select id="status" name="status" 
                                        class="form-control {{ $errors->has('status') ? 'is-invalid' : '' }}" required>
                                    <option value="">Select Status</option>
                                    <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="help-text">Active users can login and use the system</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create User
                        </button>
                        <a href="{{ route('admin.users') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showRoleInfo() {
            const role = document.getElementById('role').value;
            const roleInfo = document.getElementById('roleInfo');
            const roleDescription = document.getElementById('roleDescription');
            
            const descriptions = {
                super_admin: {
                    title: 'Super Admin',
                    permissions: [
                        'Full system access and control',
                        'Can see all phone numbers (no masking)',
                        'Manage all users and roles',
                        'View and manage all conversations',
                        'Delete conversations and users',
                        'System configuration access'
                    ]
                },
                admin: {
                    title: 'Admin',
                    permissions: [
                        'Manage users and conversations',
                        'Phone numbers are masked for privacy',
                        'View all conversations',
                        'Assign conversations to agents',
                        'Delete conversations',
                        'Cannot create Super Admin users'
                    ]
                },
                supervisor: {
                    title: 'Supervisor',
                    permissions: [
                        'View assigned conversations only',
                        'Phone numbers are masked',
                        'Can assign conversations to agents',
                        'Cannot delete conversations or users',
                        'Limited user management access'
                    ]
                },
                agent: {
                    title: 'Agent',
                    permissions: [
                        'View only assigned conversations',
                        'Phone numbers are masked',
                        'Cannot assign conversations',
                        'Cannot delete anything',
                        'Basic chat functionality only'
                    ]
                }
            };
            
            if (role && descriptions[role]) {
                const desc = descriptions[role];
                roleDescription.innerHTML = `
                    <h5>${desc.title} Permissions:</h5>
                    <ul>
                        ${desc.permissions.map(perm => `<li>${perm}</li>`).join('')}
                    </ul>
                `;
                roleInfo.style.display = 'block';
            } else {
                roleInfo.style.display = 'none';
            }
        }

        // Show role info on page load if role is selected
        document.addEventListener('DOMContentLoaded', function() {
            showRoleInfo();
        });
    </script>
</body>
</html>