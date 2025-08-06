<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Microservice - Role Demo</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .demo-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
            max-width: 600px;
            width: 100%;
        }
        .logo {
            font-size: 48px;
            color: #25d366;
            margin-bottom: 20px;
        }
        h1 {
            color: #111b21;
            margin-bottom: 12px;
            font-size: 28px;
        }
        .subtitle {
            color: #667781;
            margin-bottom: 40px;
            font-size: 16px;
            line-height: 1.5;
        }
        .role-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .role-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 24px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .role-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
            border-color: #25d366;
        }
        .role-super-admin {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
        }
        .role-admin {
            background: linear-gradient(135deg, #4ecdc4, #44a08d);
            color: white;
        }
        .role-supervisor {
            background: linear-gradient(135deg, #45b7d1, #96c93d);
            color: white;
        }
        .role-agent {
            background: linear-gradient(135deg, #f7b731, #f0932b);
            color: white;
        }
        .role-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }
        .role-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .role-permissions {
            font-size: 12px;
            opacity: 0.9;
            line-height: 1.4;
        }
        .features-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
            margin-top: 30px;
            text-align: left;
        }
        .features-title {
            font-size: 18px;
            font-weight: 600;
            color: #111b21;
            margin-bottom: 16px;
            text-align: center;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #667781;
        }
        .feature-icon {
            color: #25d366;
            font-size: 16px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #8696a0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <div class="logo">
            <i class="fab fa-whatsapp"></i>
        </div>
        <h1>WhatsApp Microservice Demo</h1>
        <p class="subtitle">
            Test the enhanced WhatsApp interface with different user roles.<br>
            Each role has different privacy levels and permissions.
        </p>
        
        <div class="role-grid">
            <a href="/demo/super-admin" class="role-card role-super-admin">
                <div class="role-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="role-title">Super Admin</div>
                <div class="role-permissions">
                    ✓ See all conversations<br>
                    ✓ See full phone numbers<br>
                    ✓ Manage users & assignments<br>
                    ✓ Delete conversations
                </div>
            </a>
            
            <a href="/demo/admin" class="role-card role-admin">
                <div class="role-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="role-title">Admin</div>
                <div class="role-permissions">
                    ✓ See all conversations<br>
                    ⚠ Phone numbers masked<br>
                    ✓ Manage assignments<br>
                    ✓ Delete conversations
                </div>
            </a>
            
            <a href="/demo/supervisor" class="role-card role-supervisor">
                <div class="role-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div class="role-title">Supervisor</div>
                <div class="role-permissions">
                    ⚠ Assigned conversations only<br>
                    ⚠ Phone numbers masked<br>
                    ✓ Can assign conversations<br>
                    ❌ Cannot delete
                </div>
            </a>
            
            <a href="/demo/agent" class="role-card role-agent">
                <div class="role-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <div class="role-title">Agent</div>
                <div class="role-permissions">
                    ⚠ Assigned conversations only<br>
                    ⚠ Phone numbers masked<br>
                    ❌ Cannot assign<br>
                    ❌ Cannot delete
                </div>
            </a>
        </div>
        
        <div class="features-section">
            <div class="features-title">🚀 Enhanced Features</div>
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-flag feature-icon"></i>
                    <span>Country flags for all phone numbers</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-eye-slash feature-icon"></i>
                    <span>Privacy-focused phone number masking</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-globe-americas feature-icon"></i>
                    <span>Support for 40+ countries</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-language feature-icon"></i>
                    <span>RTL support for Arabic text</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-mobile-alt feature-icon"></i>
                    <span>Fully responsive design</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-shield-alt feature-icon"></i>
                    <span>Role-based access control</span>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>🔒 Privacy Enhanced • 🌍 Multi-Country • 📱 Mobile Ready</p>
            <p style="margin-top: 8px;">Built with Laravel & Meta WhatsApp Cloud API</p>
        </div>
    </div>
</body>
</html>