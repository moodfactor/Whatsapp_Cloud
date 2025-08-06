<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Chat - {{ $user['name'] }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f0f0; }
        .container { max-width: 1200px; margin: 0 auto; display: flex; height: 100vh; }
        .sidebar { width: 300px; background: white; border-right: 1px solid #ddd; }
        .chat-area { flex: 1; background: white; display: flex; flex-direction: column; }
        .header { padding: 20px; background: #075e54; color: white; }
        .user-info { display: flex; align-items: center; gap: 10px; }
        .conversations { flex: 1; overflow-y: auto; }
        .conversation { padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; }
        .conversation:hover { background: #f5f5f5; }
        .conversation.active { background: #dcf8c6; }
        .chat-header { padding: 15px; background: #075e54; color: white; }
        .messages { flex: 1; padding: 20px; overflow-y: auto; background: #e5ddd5; }
        .message { margin: 10px 0; }
        .message.sent { text-align: right; }
        .message-bubble { display: inline-block; padding: 8px 12px; border-radius: 8px; max-width: 70%; }
        .message.sent .message-bubble { background: #dcf8c6; }
        .message.received .message-bubble { background: white; }
        .message-input { padding: 15px; background: #f0f0f0; display: flex; gap: 10px; }
        .message-input input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 20px; }
        .message-input button { padding: 10px 20px; background: #075e54; color: white; border: none; border-radius: 20px; cursor: pointer; }
        .role-badge { padding: 2px 8px; background: #e3f2fd; color: #1976d2; border-radius: 12px; font-size: 12px; }
        .permissions { padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; margin: 10px 0; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="header">
                <div class="user-info">
                    <div>
                        <h3>{{ $user['name'] }}</h3>
                        <span class="role-badge">{{ $user['permissions']['role_name'] }}</span>
                        <div class="permissions">
                            @if($user['permissions']['can_see_all'])
                                ✓ Can see all conversations
                            @else
                                ⚠ Limited to assigned conversations
                            @endif
                            @if($user['permissions']['can_see_phone'])
                                ✓ Can see phone numbers
                            @else
                                ⚠ Phone numbers are masked
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="conversations" id="conversations">
                <div class="conversation">
                    <strong>Loading conversations...</strong>
                    <p>Connecting to WhatsApp...</p>
                </div>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
            <div class="chat-header" id="chat-header">
                <h3>Select a conversation</h3>
            </div>
            <div class="messages" id="messages">
                <div style="text-align: center; color: #666; margin-top: 50px;">
                    <h3>WhatsApp Chat Interface</h3>
                    <p>Select a conversation from the left to start chatting</p>
                </div>
            </div>
            <div class="message-input">
                <input type="text" id="messageInput" placeholder="Type a message..." disabled>
                <button onclick="sendMessage()" id="sendBtn" disabled>Send</button>
            </div>
        </div>
    </div>

    <script>
        let currentConversation = null;
        let conversations = [];

        // Load conversations on page load
        window.addEventListener('load', function() {
            loadConversations();
        });

        function loadConversations() {
            // This would normally make an API call to your backend
            // For now, showing mock data
            conversations = [
                {
                    id: 1,
                    name: 'Customer #1',
                    phone: '{{ $user['permissions']['can_see_phone'] ? '1234567890' : '123*****890' }}',
                    lastMessage: 'Hello, I need help with my order',
                    unread: 2
                },
                {
                    id: 2,
                    name: 'Customer #2', 
                    phone: '{{ $user['permissions']['can_see_phone'] ? '0987654321' : '098*****321' }}',
                    lastMessage: 'Thank you for the support',
                    unread: 0
                }
            ];

            displayConversations();
        }

        function displayConversations() {
            const container = document.getElementById('conversations');
            container.innerHTML = '';

            conversations.forEach(conv => {
                const div = document.createElement('div');
                div.className = 'conversation';
                div.onclick = () => selectConversation(conv.id);
                
                div.innerHTML = `
                    <strong>${conv.name}</strong>
                    <p style="color: #666; font-size: 14px;">${conv.phone}</p>
                    <p style="color: #333; font-size: 13px;">${conv.lastMessage}</p>
                    ${conv.unread > 0 ? `<span style="background: #075e54; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px;">${conv.unread}</span>` : ''}
                `;
                
                container.appendChild(div);
            });
        }

        function selectConversation(id) {
            currentConversation = id;
            const conv = conversations.find(c => c.id === id);
            
            // Update header
            document.getElementById('chat-header').innerHTML = `
                <h3>${conv.name}</h3>
                <p style="font-size: 14px;">${conv.phone}</p>
            `;
            
            // Enable input
            document.getElementById('messageInput').disabled = false;
            document.getElementById('sendBtn').disabled = false;
            
            // Load messages (mock for now)
            loadMessages(id);
            
            // Mark as active
            document.querySelectorAll('.conversation').forEach(el => el.classList.remove('active'));
            event.target.closest('.conversation').classList.add('active');
        }

        function loadMessages(conversationId) {
            // Mock messages
            const messages = [
                { id: 1, text: 'Hello, I need help with my order', type: 'received', time: '10:30 AM' },
                { id: 2, text: 'Hi! I\'d be happy to help you. What\'s your order number?', type: 'sent', time: '10:32 AM' },
                { id: 3, text: 'My order number is #12345', type: 'received', time: '10:33 AM' }
            ];

            const container = document.getElementById('messages');
            container.innerHTML = '';

            messages.forEach(msg => {
                const div = document.createElement('div');
                div.className = `message ${msg.type}`;
                div.innerHTML = `
                    <div class="message-bubble">
                        ${msg.text}
                        <div style="font-size: 11px; color: #666; margin-top: 5px;">${msg.time}</div>
                    </div>
                `;
                container.appendChild(div);
            });

            // Scroll to bottom
            container.scrollTop = container.scrollHeight;
        }

        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message || !currentConversation) return;
            
            // Add message to UI
            const container = document.getElementById('messages');
            const div = document.createElement('div');
            div.className = 'message sent';
            div.innerHTML = `
                <div class="message-bubble">
                    ${message}
                    <div style="font-size: 11px; color: #666; margin-top: 5px;">Sending...</div>
                </div>
            `;
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
            
            // Clear input
            input.value = '';
            
            // Here you would normally send the message via API
            console.log('Sending message:', message, 'to conversation:', currentConversation);
        }

        // Allow Enter key to send message
        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    </script>
</body>
</html>