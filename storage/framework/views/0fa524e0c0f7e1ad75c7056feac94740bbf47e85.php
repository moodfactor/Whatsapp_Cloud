<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Admin Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #075e54 0%, #128c7e 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 400px;
            max-width: 90%;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #075e54;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #075e54;
        }
        
        .login-btn {
            width: 100%;
            padding: 12px;
            background: #075e54;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .login-btn:hover {
            background: #064940;
        }
        
        .login-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
        }
        
        .success-message {
            background: #efe;
            color: #363;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #cfc;
        }
        
        .demo-credentials {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            border: 1px solid #e9ecef;
        }
        
        .demo-credentials h4 {
            color: #495057;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .demo-credentials .credential {
            margin-bottom: 8px;
            font-size: 12px;
            font-family: monospace;
            background: white;
            padding: 4px 8px;
            border-radius: 3px;
            color: #6c757d;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🚀 WhatsApp Admin</h1>
            <p>Connect & Manage WhatsApp Conversations</p>
        </div>

        <?php if(session('error')): ?>
            <div class="error-message">
                <?php echo e(session('error')); ?>

            </div>
        <?php endif; ?>

        <?php if(session('success')): ?>
            <div class="success-message">
                <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="error-message">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('admin.login')); ?>">
            <?php echo csrf_field(); ?>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo e(old('email')); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="login-btn">Login to Admin Panel</button>
        </form>

        <div class="demo-credentials">
            <h4>🔑 Demo Credentials (Change in production!):</h4>
            <div class="credential"><strong>Super Admin:</strong> admin@connect.al-najjarstore.com / admin123</div>
            <div class="credential"><strong>Admin:</strong> whatsapp-admin@connect.al-najjarstore.com / whatsapp123</div>
            <div class="credential"><strong>Supervisor:</strong> supervisor@connect.al-najjarstore.com / supervisor123</div>
            <div class="credential"><strong>Agent:</strong> agent@connect.al-najjarstore.com / agent123</div>
        </div>

        <div class="footer">
            <p>WhatsApp Microservice Admin Panel</p>
            <p>Secure • Fast • Reliable</p>
        </div>
    </div>

    <script>
        // Auto-fill demo credentials for easy testing
        document.addEventListener('DOMContentLoaded', function() {
            const demoCredentials = document.querySelectorAll('.credential');
            
            demoCredentials.forEach(function(credential) {
                credential.style.cursor = 'pointer';
                credential.addEventListener('click', function() {
                    const text = this.textContent;
                    const emailMatch = text.match(/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/);
                    const passwordMatch = text.match(/\/ (\w+)/);
                    
                    if (emailMatch && passwordMatch) {
                        document.getElementById('email').value = emailMatch[1];
                        document.getElementById('password').value = passwordMatch[1];
                        
                        // Visual feedback
                        this.style.background = '#d4edda';
                        setTimeout(() => {
                            this.style.background = 'white';
                        }, 200);
                    }
                });
            });
        });
    </script>
</body>
</html><?php /**PATH /home/u539863725/domains/al-najjarstore.com/public_html/connect/resources/views/admin/login.blade.php ENDPATH**/ ?>