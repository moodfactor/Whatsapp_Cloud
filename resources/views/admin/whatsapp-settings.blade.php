<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Settings - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8f9fa; }
        
        .container { max-width: 800px; margin: 50px auto; padding: 20px; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); overflow: hidden; }
        .card-header { padding: 20px; border-bottom: 1px solid #eee; background: #075e54; color: white; }
        .card-body { padding: 30px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #333; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .form-control:focus { outline: none; border-color: #075e54; box-shadow: 0 0 0 2px rgba(7, 94, 84, 0.1); }
        
        .btn { padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; transition: all 0.3s; }
        .btn-primary { background: #075e54; color: white; }
        .btn-primary:hover { background: #064940; }
        .btn-secondary { background: #6c757d; color: white; margin-right: 10px; }
        .btn-secondary:hover { background: #5a6268; }
        
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        .status-indicator { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .status-valid { background: #d4edda; color: #155724; }
        .status-expired { background: #f8d7da; color: #721c24; }
        .status-unknown { background: #e2e3e5; color: #383d41; }
        
        .back-link { display: inline-block; margin-bottom: 20px; color: #075e54; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        
        .token-info { background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .token-info h4 { margin-bottom: 10px; color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('admin.dashboard') }}" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fab fa-whatsapp"></i> WhatsApp API Settings</h2>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    </div>
                @endif

                <div class="token-info">
                    <h4>Current Token Status</h4>
                    <p>Status: <span class="status-indicator status-{{ $tokenStatus['status'] }}">{{ $tokenStatus['message'] }}</span></p>
                    @if(isset($tokenStatus['expires_at']))
                        <p>Expires: {{ $tokenStatus['expires_at'] }}</p>
                    @endif
                    <p><small>Last checked: {{ now()->format('Y-m-d H:i:s') }}</small></p>
                </div>

                <div class="alert alert-info">
                    <h4><i class="fas fa-info-circle"></i> How to get a new WhatsApp Access Token:</h4>
                    <ol>
                        <li>Go to <a href="https://developers.facebook.com/" target="_blank">Meta Developer Console</a></li>
                        <li>Select your WhatsApp Business app</li>
                        <li>Navigate to WhatsApp > Getting Started</li>
                        <li>Generate a permanent access token</li>
                        <li>Copy the token and paste it below</li>
                    </ol>
                </div>

                <form method="POST" action="{{ route('admin.whatsapp.update-token') }}">
                    @csrf
                    
                    <div class="form-group">
                        <label for="access_token">WhatsApp Business API Access Token</label>
                        <textarea name="access_token" id="access_token" class="form-control" rows="3" 
                                  placeholder="Paste your new WhatsApp Business API access token here..." required></textarea>
                        <small style="color: #666;">This token will be used to authenticate with Meta's WhatsApp Business API</small>
                    </div>

                    <div class="form-group">
                        <label for="phone_number_id">Phone Number ID (optional)</label>
                        <input type="text" name="phone_number_id" id="phone_number_id" class="form-control" 
                               value="{{ config('whatsapp.phone_number_id') }}" 
                               placeholder="Your WhatsApp Business phone number ID">
                        <small style="color: #666;">Only update if you're changing phone numbers</small>
                    </div>

                    <div class="form-group">
                        <button type="button" class="btn btn-secondary" onclick="testCurrentToken()">
                            <i class="fas fa-vial"></i> Test Current Token
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Token
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        async function testCurrentToken() {
            try {
                const response = await fetch('/admin/whatsapp/test-token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Token is valid! Expires: ' + (result.expires_at || 'Unknown'));
                } else {
                    alert('❌ Token test failed: ' + result.error);
                }
            } catch (error) {
                alert('❌ Error testing token: ' + error.message);
            }
        }
    </script>
</body>
</html>