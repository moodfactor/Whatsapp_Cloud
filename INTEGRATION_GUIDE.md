# WhatsApp Microservice with Admin Panel - Integration Guide

## 🎯 Overview

This is a **comprehensive WhatsApp microservice** that includes:
- 🔐 **Full Admin Panel** with user management
- 👥 **Role-based permissions** (Super Admin, Admin, Supervisor, Agent)
- 💬 **Conversation management** with assignment and status tracking
- 📊 **Dashboard with statistics** and real-time data
- 🔗 **Integration capability** with your main site at `journals.mejsp.com`

Runs on `connect.al-najjarstore.com` as a standalone application.

## 🚀 Deployment

### Quick Deployment
```bash
# 1. Navigate to microservice directory
cd whatsapp-microservice

# 2. Make script executable
chmod +x deploy-microservice.sh

# 3. Deploy
./deploy-microservice.sh

# 4. After deployment, run migrations and seed admin users
ssh -p 65002 u539863725@185.212.71.93
cd /home/u539863725/domains/al-najjarstore.com/public_html/connect
php artisan migrate --force
php artisan db:seed --class=AdminSeeder
```

## 🔐 Admin Panel Access

After deployment, access the admin panel at:
```
https://connect.al-najjarstore.com/admin/login
```

### 🔑 Default Login Credentials (Change immediately!)
- **Super Admin**: admin@connect.al-najjarstore.com / admin123
- **Admin**: whatsapp-admin@connect.al-najjarstore.com / whatsapp123  
- **Supervisor**: supervisor@connect.al-najjarstore.com / supervisor123
- **Agent**: agent@connect.al-najjarstore.com / agent123

## 🔗 Integration with Main Site

### 1. Add WhatsApp Button to Admin Dashboard

Add this button to your admin dashboard at `journals.mejsp.com`:

```html
<!-- In your admin dashboard blade file -->
<div class="whatsapp-integration">
    <a href="javascript:void(0)" onclick="openWhatsAppChat()" class="btn btn-success">
        <i class="fab fa-whatsapp"></i> WhatsApp Chat
    </a>
</div>

<script>
function openWhatsAppChat() {
    // Get current admin info
    const adminId = {{ auth('admin')->id() }};
    const adminRole = '{{ auth('admin')->user()->role ?? 'admin' }}';
    
    // Generate secure token (implement this in your backend)
    fetch('/api/generate-whatsapp-token', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            user_id: adminId,
            user_type: adminRole
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.token) {
            const whatsappUrl = `https://connect.al-najjarstore.com/auth/main-site?token=${data.token}&user_id=${adminId}&user_type=${adminRole}`;
            window.open(whatsappUrl, 'whatsapp-chat', 'width=1200,height=800');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to open WhatsApp chat');
    });
}
</script>
```

### 2. Create Token Generation API on Main Site

Add this route to your main site (`journals.mejsp.com`):

```php
// In routes/api.php or routes/web.php
Route::post('/api/generate-whatsapp-token', function(Request $request) {
    // Verify admin is authenticated
    if (!Auth::guard('admin')->check()) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    
    $admin = Auth::guard('admin')->user();
    $userId = $request->user_id;
    $userType = $request->user_type ?? 'admin';
    
    // Create secure token (expires in 1 hour)
    $token = encrypt([
        'user_id' => $userId,
        'user_type' => $userType,
        'generated_at' => now(),
        'expires_at' => now()->addHour()
    ]);
    
    return response()->json([
        'token' => $token,
        'expires_in' => 3600 // 1 hour
    ]);
});

// Token verification endpoint (for microservice to verify)
Route::get('/api/verify-whatsapp-token', function(Request $request) {
    try {
        $token = $request->get('token');
        $userId = $request->get('user_id');
        
        $decrypted = decrypt($token);
        
        // Verify token hasn't expired
        if (now()->gt($decrypted['expires_at'])) {
            return response()->json(['valid' => false, 'error' => 'Token expired'], 401);
        }
        
        // Verify user ID matches
        if ($decrypted['user_id'] != $userId) {
            return response()->json(['valid' => false, 'error' => 'Invalid user'], 401);
        }
        
        // Get user data
        $admin = DB::table('admins')->where('id', $userId)->first();
        
        if (!$admin) {
            return response()->json(['valid' => false, 'error' => 'User not found'], 404);
        }
        
        return response()->json([
            'valid' => true,
            'user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role ?? 'admin'
            ]
        ]);
        
    } catch (Exception $e) {
        return response()->json(['valid' => false, 'error' => 'Invalid token'], 401);
    }
});
```

## 📱 WhatsApp Features Available

### For Administrators
- ✅ **View all conversations**
- ✅ **See full phone numbers**
- ✅ **Send/receive messages**
- ✅ **Send media files**
- ✅ **Assign conversations**

### For Supervisors
- ⚠️ **View assigned conversations only**
- ⚠️ **Phone numbers are masked**
- ✅ **Send/receive messages**
- ✅ **Send media files**
- ❌ **Cannot assign conversations**

## 🔧 Configuration

### Environment Variables (.env.production)
```env
# Main site integration
MAIN_SITE_URL=https://journals.mejsp.com
MAIN_SITE_API_KEY=your_secure_api_key

# WhatsApp Configuration
WHATSAPP_ACCESS_TOKEN=your_whatsapp_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_id
WHATSAPP_WEBHOOK_URL=https://connect.al-najjarstore.com/whatsapp/webhook
```

### Database Connection
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u539863725_connect
DB_USERNAME=u539863725_connect
DB_PASSWORD=your_database_password
```

## 🗃️ Database Tables Needed

The microservice expects these tables to exist:

```sql
-- WhatsApp interactions (conversations)
CREATE TABLE whatsapp_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    receiver_id VARCHAR(50),
    last_message TEXT,
    last_msg_time TIMESTAMP,
    status VARCHAR(20) DEFAULT 'new',
    assigned_to INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- WhatsApp messages
CREATE TABLE whatsapp_interaction_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    interaction_id INT,
    message TEXT,
    type VARCHAR(20) DEFAULT 'text',
    nature VARCHAR(10), -- 'sent' or 'received'
    status VARCHAR(20) DEFAULT 'delivered',
    url VARCHAR(500) NULL, -- for media messages
    time_sent TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (interaction_id) REFERENCES whatsapp_interactions(id)
);
```

## 🌐 API Endpoints

### Authentication
- `POST /auth/main-site` - Authenticate from main site
- `GET /auth/check` - Check authentication status
- `POST /logout` - Logout

### WhatsApp API
- `POST /api/whatsapp/send-text` - Send text message
- `POST /api/whatsapp/send-media` - Send media message
- `GET /api/whatsapp/messages` - Get conversation messages
- `POST /whatsapp/webhook` - WhatsApp webhook endpoint

### Health Check
- `GET /health` - Service health status

## 🔒 Security

- ✅ **Token-based authentication** between sites
- ✅ **Role-based permissions** (admin vs supervisor)
- ✅ **Phone number masking** for supervisors
- ✅ **Secure session management**
- ✅ **CSRF protection**

## 🧪 Testing

1. **Deploy the microservice**: `./deploy-microservice.sh`
2. **Visit directly**: `https://connect.al-najjarstore.com/health`
3. **Test webhook**: Update Meta Developer Console
4. **Test integration**: Add button to main site admin panel

## 🆘 Troubleshooting

### Common Issues

1. **Authentication fails**: Check token generation/verification
2. **Database connection**: Verify credentials in `.env`
3. **WhatsApp webhook**: Ensure URL is accessible
4. **File permissions**: Check storage directory permissions

### Debug Commands
```bash
# SSH into server
ssh -p 65002 u539863725@185.212.71.93

# Check logs
cd /home/u539863725/domains/al-najjarstore.com/public_html/connect
tail -f storage/logs/laravel.log

# Test database connection
php artisan tinker
DB::connection()->getPdo();
```

## ✅ Success!

Your WhatsApp microservice is now ready to integrate with your main site. Users can click the WhatsApp button in the admin dashboard and seamlessly access the chat interface on the subdomain.

**The microservice provides standalone WhatsApp functionality while maintaining secure integration with your main application!** 🚀