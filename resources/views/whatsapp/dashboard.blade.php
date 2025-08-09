<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Chat - {{ $user['name'] }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
    document.addEventListener('alpine:init', () => {
        console.log('Alpine.js initialized');
    });
    document.addEventListener('alpine:initialized', () => {
        console.log('Alpine.js fully loaded and initialized');
        // Test if we can find the media modal
        const modal = document.querySelector('[x-data="mediaUploadModal()"]');
        console.log('Media modal found after Alpine init:', !!modal);
    });
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f0f2f5; 
            overflow: hidden;
        }
        .container { 
            display: flex; 
            height: 100vh; 
            max-width: 1400px; 
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .sidebar { 
            width: 350px; 
            background: white; 
            border-right: 1px solid #e9edef;
            display: flex;
            flex-direction: column;
        }
        .chat-area { 
            flex: 1; 
            background: white; 
            display: flex; 
            flex-direction: column;
            min-width: 0;
        }
        .header { 
            padding: 20px; 
            background: linear-gradient(135deg, #128C7E, #075E54); 
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .user-info { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .conversations { 
            flex: 1; 
            overflow-y: auto;
            background: white;
        }
        .conversations::-webkit-scrollbar {
            width: 6px;
        }
        .conversations::-webkit-scrollbar-thumb {
            background: #d1d7db;
            border-radius: 3px;
        }
        .conversation { 
            padding: 16px 20px; 
            border-bottom: 1px solid #f0f0f0; 
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        .conversation:hover { 
            background: #f5f6fa; 
        }
        .conversation.active { 
            background: #e7f3ff;
            border-right: 3px solid #128C7E;
        }
        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }
        .contact-info {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            min-width: 0;
        }
        .contact-name {
            font-weight: 600;
            color: #111b21;
            font-size: 16px;
            white-space: nowrap;
            overflow: hidden;
            text-decoration: ellipsis;
        }
        .contact-phone {
            color: #667781;
            font-size: 13px;
            margin-top: 2px;
        }
        .country-flag {
            font-size: 18px;
            line-height: 1;
        }
        .last-message {
            color: #667781;
            font-size: 14px;
            margin-top: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .conversation-time {
            font-size: 12px;
            color: #667781;
        }
        .unread-badge {
            background: #25d366;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }
        .chat-header { 
            padding: 16px 24px; 
            background: #f0f2f5;
            border-bottom: 1px solid #e9edef;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .chat-contact-info {
            flex: 1;
        }
        .chat-contact-name {
            font-weight: 600;
            color: #111b21;
            font-size: 16px;
        }
        .chat-contact-phone {
            color: #667781;
            font-size: 13px;
        }
        .messages { 
            flex: 1; 
            padding: 20px;
            overflow-y: auto; 
            background: #efeae2;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="chat-bg" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23f0f0f0" opacity="0.3"/></pattern></defs><rect width="100" height="100" fill="url(%23chat-bg)"/></svg>');
        }
        .messages::-webkit-scrollbar {
            width: 6px;
        }
        .messages::-webkit-scrollbar-thumb {
            background: #d1d7db;
            border-radius: 3px;
        }
        .message { 
            margin: 8px 0;
            display: flex;
        }
        .message.sent { 
            justify-content: flex-end; 
        }
        .message.received {
            justify-content: flex-start;
        }
        .message-bubble { 
            max-width: 65%; 
            padding: 8px 12px;
            border-radius: 8px;
            position: relative;
            word-wrap: break-word;
        }
        .message.sent .message-bubble { 
            background: #d9fdd3;
            color: #111b21;
        }
        .message.received .message-bubble { 
            background: white;
            color: #111b21;
        }
        .message-time {
            font-size: 11px;
            color: #667781;
            margin-top: 4px;
            text-align: right;
        }
        .message-input-container { 
            padding: 16px 24px;
            background: #f0f2f5;
            border-top: 1px solid #e9edef;
        }
        .message-input { 
            display: flex; 
            gap: 12px;
            align-items: flex-end;
        }
        .message-input input { 
            flex: 1; 
            padding: 12px 16px;
            border: none;
            border-radius: 24px;
            background: white;
            font-size: 14px;
            outline: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .message-input input:focus {
            box-shadow: 0 1px 6px rgba(0,0,0,0.2);
        }
        .input-actions {
            display: flex;
            gap: 8px;
        }
        .action-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .send-btn {
            background: #25d366;
            color: white;
        }
        .send-btn:hover {
            background: #1da851;
            transform: scale(1.05);
        }
        .send-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        .attach-btn {
            background: #f0f2f5;
            color: #54656f;
        }
        .attach-btn:hover {
            background: #e4e6ea;
        }
        .role-badge { 
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .role-super-admin {
            background: linear-gradient(45deg, #ff6b6b, #ff8e53);
            color: white;
        }
        .role-admin {
            background: linear-gradient(45deg, #4ecdc4, #44a08d);
            color: white;
        }
        .role-supervisor {
            background: linear-gradient(45deg, #45b7d1, #96c93d);
            color: white;
        }
        .role-agent {
            background: linear-gradient(45deg, #f7b731, #f0932b);
            color: white;
        }
        .permissions { 
            padding: 12px;
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            margin: 12px 0;
            font-size: 12px;
            line-height: 1.4;
        }
        .permission-item {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 2px 0;
        }
        .permission-icon {
            width: 16px;
            color: #28a745;
        }
        .permission-icon.warning {
            color: #ffc107;
        }
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #25d366;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .empty-state {
            text-align: center;
            color: #667781;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 64px;
            color: #d1d7db;
            margin-bottom: 16px;
        }
        .media-message {
            max-width: 300px;
        }
        .media-message img {
            max-width: 100%;
            border-radius: 8px;
        }
        .file-input {
            display: none;
        }
        
        /* Media Message Styles */
        .media-message {
            position: relative;
            max-width: 280px;
            border-radius: 12px;
            overflow: hidden;
            background: white;
        }
        
        .media-message img {
            width: 100%;
            height: auto;
            display: block;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .media-message img:hover {
            transform: scale(1.02);
        }
        
        .media-message video {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 12px;
        }
        
        .media-message audio {
            width: 100%;
            height: 40px;
        }
        
        .document-message {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: white;
            border-radius: 12px;
            border: 1px solid #e9edef;
            max-width: 300px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .document-message:hover {
            background: #f8f9fa;
            border-color: #25d366;
        }
        
        .document-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .document-icon.pdf { background: #ff4757; color: white; }
        .document-icon.word { background: #1e3d59; color: white; }
        .document-icon.excel { background: #00a84f; color: white; }
        .document-icon.default { background: #54656f; color: white; }
        
        .document-info {
            flex: 1;
            min-width: 0;
        }
        
        .document-name {
            font-weight: 500;
            color: #111b21;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .document-size {
            font-size: 12px;
            color: #667781;
            margin-top: 2px;
        }
        
        /* Image Modal */
        .image-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .image-modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .image-modal img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }
        
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 24px;
            cursor: pointer;
            z-index: 10000;
        }
        
        /* Loading states */
        .loading-spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #25d366;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Audio message styles */
        .audio-message {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: white;
            border-radius: 12px;
            max-width: 300px;
        }
        
        .audio-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #25d366;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }
        
        .audio-info {
            flex: 1;
        }
        
        .audio-name {
            font-weight: 500;
            color: #111b21;
            font-size: 14px;
        }
        
        .audio-duration {
            font-size: 12px;
            color: #667781;
            margin-top: 2px;
        }
        
        /* Video message styles */
        .video-message {
            position: relative;
            max-width: 280px;
            border-radius: 12px;
            overflow: hidden;
            background: white;
        }
        
        .video-thumbnail {
            position: relative;
            width: 100%;
            cursor: pointer;
        }
        
        .video-play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            transition: all 0.2s ease;
        }
        
        .video-play-button:hover {
            background: rgba(0, 0, 0, 0.8);
            transform: translate(-50%, -50%) scale(1.1);
        }
        
        .media-caption {
            padding: 8px 12px;
            background: rgba(0, 0, 0, 0.05);
            font-size: 14px;
            color: #111b21;
        }
        .status-indicator {
            font-size: 12px;
            margin-left: 4px;
        }
        .arab-text {
            direction: rtl;
            text-align: right;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: absolute;
                z-index: 1000;
                height: 100vh;
            }
            .chat-area {
                display: none;
            }
            .sidebar.hidden {
                display: none;
            }
            .chat-area.mobile-visible {
                display: flex;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="header">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h3>{{ $user['name'] }}</h3>
                        <span class="role-badge role-{{ str_replace(' ', '-', strtolower($user['permissions']['role_name'])) }}">{{ $user['permissions']['role_name'] }}</span>
                        <div class="permissions">
                            <div class="permission-item">
                                <i class="fas {{ $user['permissions']['can_see_all'] ? 'fa-check-circle permission-icon' : 'fa-exclamation-triangle permission-icon warning' }}"></i>
                                <span>{{ $user['permissions']['can_see_all'] ? 'Can see all conversations' : 'Limited to assigned conversations' }}</span>
                            </div>
                            <div class="permission-item">
                                <i class="fas {{ $user['permissions']['can_see_phone'] ? 'fa-check-circle permission-icon' : 'fa-eye-slash permission-icon warning' }}"></i>
                                <span>{{ $user['permissions']['can_see_phone'] ? 'Can see phone numbers' : 'Phone numbers are masked' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="conversations" id="conversations">
                <div class="conversation">
                    <div class="contact-info">
                        <div class="loading-spinner"></div>
                        <div style="margin-left: 10px;">
                            <div class="contact-name">Loading conversations...</div>
                            <div class="contact-phone">Connecting to WhatsApp...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area" id="chatArea">
            <div class="chat-header" id="chat-header">
                <button class="back-btn" id="backBtn" onclick="showSidebar()" style="display: none;">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="chat-contact-info">
                    <div class="chat-contact-name">Select a conversation</div>
                    <div class="chat-contact-phone">Choose a contact to start chatting</div>
                </div>
            </div>
            <div class="messages" id="messages">
                <div class="empty-state">
                    <i class="fab fa-whatsapp"></i>
                    <h3>WhatsApp Business Chat</h3>
                    <p>Select a conversation from the sidebar to start messaging your customers</p>
                </div>
            </div>
            <div class="message-input-container">
                <div class="message-input">
                    <div class="input-actions">
                        <button class="action-btn attach-btn" id="attachBtn" onclick="openMediaDialog()" disabled>
                            <i class="fas fa-paperclip"></i>
                        </button>
                    </div>
                    <input type="text" id="messageInput" placeholder="Type a message..." disabled onkeypress="handleKeyPress(event)">
                    <div class="input-actions">
                        <button class="action-btn send-btn" onclick="sendMessage()" id="sendBtn" disabled>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Media Upload Modal (Simple Alpine.js) -->
    @include('components.media-upload-modal')
    
    <!-- Debug: Check if modal is included -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.querySelector('[x-data="mediaUploadModal()"]');
        console.log('Media modal element found:', !!modal);
        if (modal) {
            console.log('Modal element:', modal);
        } else {
            console.error('Media upload modal not found in DOM');
        }
    });
    </script>

    <!-- Image Modal -->
    <div class="image-modal" id="imageModal" onclick="closeImageModal()">
        <div class="modal-close" onclick="closeImageModal()">&times;</div>
        <img id="modalImage" src="" alt="Full size image">
    </div>

    <script>
        let currentConversation = null;
        let conversations = [];
        let userPermissions = {};

        // CSRF Token setup
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Load conversations on page load
        window.addEventListener('load', function() {
            loadConversations();
        });

        async function loadConversations() {
            try {
                const response = await fetch('/api/whatsapp/conversations', {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                conversations = data.conversations || [];
                userPermissions = data.user_permissions || {};
                
                // Debug: Log received data
                console.log('Debug - User Permissions:', userPermissions);
                console.log('Debug - First conversation:', conversations[0]);
                
                displayConversations();
            } catch (error) {
                console.error('Error loading conversations:', error);
                showError('Failed to load conversations. Please refresh the page.');
            }
        }

        function displayConversations() {
            const container = document.getElementById('conversations');
            
            if (conversations.length === 0) {
                container.innerHTML = `
                    <div class="empty-state" style="padding: 40px 20px; text-align: center;">
                        <i class="fas fa-comments" style="font-size: 48px; color: #d1d7db; margin-bottom: 16px;"></i>
                        <h4 style="color: #667781; margin-bottom: 8px;">No conversations yet</h4>
                        <p style="color: #8696a0; font-size: 14px;">Conversations will appear here when customers message you</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = '';

            conversations.forEach(conv => {
                const div = document.createElement('div');
                div.className = 'conversation';
                div.onclick = () => selectConversation(conv.id);
                
                const isArabText = conv.is_arab ? 'arab-text' : '';
                const timeAgo = formatTimeAgo(conv.last_msg_time);
                
                // Show full phone if permitted, otherwise show masked phone
                const displayPhone = userPermissions.can_see_phone && conv.full_phone 
                    ? conv.full_phone 
                    : conv.contact_phone;
                
                div.innerHTML = `
                    <div class="conversation-header">
                        <div class="contact-info">
                            <span class="country-flag">${conv.country_flag || '🌍'}</span>
                            <div>
                                <div class="contact-name ${isArabText}">${escapeHtml(conv.contact_name)}</div>
                                <div class="contact-phone">${escapeHtml(displayPhone)}</div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 4px;">
                            ${conv.unread > 0 ? `<div class="unread-badge">${conv.unread}</div>` : ''}
                            <div class="conversation-time">${timeAgo}</div>
                        </div>
                    </div>
                    <div class="last-message ${isArabText}">${escapeHtml(conv.last_message || 'No messages yet')}</div>
                `;
                
                container.appendChild(div);
            });
        }

        async function selectConversation(id) {
            // Remove active class from all conversations
            document.querySelectorAll('.conversation').forEach(el => el.classList.remove('active'));
            
            // Add active class to selected conversation
            event.target.closest('.conversation').classList.add('active');
            
            currentConversation = id;
            const conv = conversations.find(c => c.id === id);
            
            if (!conv) return;
            
            // Update chat header
            const isArabText = conv.is_arab ? 'arab-text' : '';
            document.getElementById('chat-header').innerHTML = `
                <button class="back-btn" id="backBtn" onclick="showSidebar()" style="display: none; background: none; border: none; cursor: pointer; padding: 8px; margin-right: 12px;">
                    <i class="fas fa-arrow-left" style="color: #54656f;"></i>
                </button>
                <span class="country-flag" style="font-size: 20px; margin-right: 8px;">${conv.country_flag || '🌍'}</span>
                <div class="chat-contact-info">
                    <div class="chat-contact-name ${isArabText}">${escapeHtml(conv.contact_name)}</div>
                    <div class="chat-contact-phone">${escapeHtml(userPermissions.can_see_phone && conv.full_phone ? conv.full_phone : conv.contact_phone)} • ${conv.country_name || 'Unknown'}</div>
                </div>
            `;
            
            // Enable inputs
            document.getElementById('messageInput').disabled = false;
            document.getElementById('sendBtn').disabled = false;
            document.getElementById('attachBtn').disabled = false;
            
            // Load messages
            await loadMessages(id);
            
            // Show chat area on mobile
            hideSidebar();
        }

        async function loadMessages(conversationId, silentRefresh = false) {
            try {
                const response = await fetch(`/api/whatsapp/messages/${conversationId}`, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                const messages = data.messages || [];

                const container = document.getElementById('messages');
                
                // Remember scroll position for silent refresh
                const wasAtBottom = silentRefresh ? 
                    (container.scrollTop + container.clientHeight >= container.scrollHeight - 5) : true;
                
                // Only show new message indicator if there are new messages during silent refresh
                if (silentRefresh) {
                    const currentMessageCount = container.children.length - (container.querySelector('.empty-state') ? 1 : 0);
                    if (messages.length > currentMessageCount) {
                        // Flash a subtle indicator for new messages
                        showNewMessageIndicator();
                    }
                }
                
                container.innerHTML = '';

                if (messages.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-comment-dots"></i>
                            <h4>No messages yet</h4>
                            <p>Start the conversation by sending a message</p>
                        </div>
                    `;
                    return;
                }

                messages.forEach(msg => {
                    const div = document.createElement('div');
                    div.className = `message ${msg.type}`;
                    
                    let messageContent = '';
                    
                    // Debug logging
                    if (msg.debug_type && msg.debug_type !== 'text') {
                        console.log('Media message debug:', {
                            message_type: msg.message_type,
                            debug_type: msg.debug_type,
                            has_url: msg.debug_has_url,
                            media_url: msg.media_url,
                            text: msg.text
                        });
                    }
                    
                    // Handle different media types
                    if (msg.message_type === 'image' && msg.media_url) {
                        messageContent = `
                            <div class="media-message">
                                <img src="${msg.media_url}" alt="Image" onclick="openImageModal('${msg.media_url}')">
                                ${msg.text && msg.text !== '[Image]' ? `<div class="media-caption">${escapeHtml(msg.text)}</div>` : ''}
                                <div class="message-time">${msg.time} ${getStatusIcon(msg.status)}</div>
                            </div>
                        `;
                    } else if (msg.message_type === 'video' && msg.media_url) {
                        messageContent = `
                            <div class="video-message">
                                <video controls>
                                    <source src="${msg.media_url}" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                                ${msg.text && msg.text !== '[Video]' ? `<div class="media-caption">${escapeHtml(msg.text)}</div>` : ''}
                                <div class="message-time">${msg.time} ${getStatusIcon(msg.status)}</div>
                            </div>
                        `;
                    } else if (msg.message_type === 'audio' && msg.media_url) {
                        messageContent = `
                            <div class="audio-message">
                                <div class="audio-icon">
                                    <i class="fas fa-music"></i>
                                </div>
                                <div class="audio-info">
                                    <div class="audio-name">${msg.filename || 'Audio'}</div>
                                    <audio controls>
                                        <source src="${msg.media_url}" type="audio/mpeg">
                                        Your browser does not support the audio tag.
                                    </audio>
                                </div>
                                <div class="message-time">${msg.time} ${getStatusIcon(msg.status)}</div>
                            </div>
                        `;
                    } else if (msg.message_type === 'document' && msg.media_url) {
                        const docIcon = getDocumentIcon(msg.filename || '');
                        messageContent = `
                            <div class="document-message" onclick="window.open('${msg.media_url}', '_blank')">
                                <div class="document-icon ${docIcon.class}">
                                    <i class="fas ${docIcon.icon}"></i>
                                </div>
                                <div class="document-info">
                                    <div class="document-name">${msg.filename || msg.text || 'Document'}</div>
                                    <div class="document-size">Document</div>
                                </div>
                                <div class="message-time">${msg.time} ${getStatusIcon(msg.status)}</div>
                            </div>
                        `;
                    } else {
                        // Regular text message
                        messageContent = `
                            <div class="message-bubble">
                                ${escapeHtml(msg.text).replace(/\n/g, '<br>')}
                                <div class="message-time">${msg.time} ${getStatusIcon(msg.status)}</div>
                            </div>
                        `;
                    }
                    
                    div.innerHTML = messageContent;
                    container.appendChild(div);
                });

                // Only scroll to bottom if user was already at bottom or it's initial load
                if (wasAtBottom || !silentRefresh) {
                    container.scrollTop = container.scrollHeight;
                }
                
            } catch (error) {
                console.error('Error loading messages:', error);
                showError('Failed to load messages.');
            }
        }

        // Show a subtle indicator when new messages arrive
        function showNewMessageIndicator() {
            const indicator = document.createElement('div');
            indicator.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #25d366;
                color: white;
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 14px;
                z-index: 9999;
                animation: slideIn 0.3s ease;
            `;
            indicator.innerHTML = '<i class="fas fa-comment-dots"></i> New message';
            document.body.appendChild(indicator);
            
            setTimeout(() => {
                if (indicator.parentNode) {
                    document.body.removeChild(indicator);
                }
            }, 3000);
        }

        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message || !currentConversation) return;
            
            // Disable send button
            const sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<div class="loading-spinner" style="width: 16px; height: 16px;"></div>';
            
            // Add message to UI immediately
            addMessageToUI({
                text: message,
                type: 'sent',
                time: new Date().toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' }),
                status: 'sending'
            });
            
            // Clear input
            input.value = '';
            
            try {
                const response = await fetch('/api/whatsapp/send-message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        conversation_id: currentConversation,
                        message: message
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update the last message in UI to show sent status
                    updateLastMessageStatus('sent');
                    
                    // Refresh conversations to update last message
                    loadConversations();
                } else {
                    updateLastMessageStatus('failed');
                    showError(data.error || 'Failed to send message');
                }
                
            } catch (error) {
                console.error('Error sending message:', error);
                updateLastMessageStatus('failed');
                showError('Failed to send message. Please try again.');
            } finally {
                // Re-enable send button
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            }
        }

        async function handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file || !currentConversation) return;
            
            // Check file size (16MB limit)
            if (file.size > 16 * 1024 * 1024) {
                showError('File size must be less than 16MB');
                return;
            }
            
            const formData = new FormData();
            formData.append('media', file);
            formData.append('conversation_id', currentConversation);
            
            try {
                // Show uploading message
                addMessageToUI({
                    text: `Uploading ${file.name}...`,
                    type: 'sent',
                    time: new Date().toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' }),
                    status: 'sending'
                });
                
                const response = await fetch('/api/whatsapp/upload-media', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Remove the uploading message and add the actual media message
                    const messages = document.getElementById('messages');
                    const lastMessage = messages.lastElementChild;
                    if (lastMessage) lastMessage.remove();
                    
                    // Add the media message
                    addMessageToUI(data.message);
                    
                    // Refresh conversations
                    loadConversations();
                } else {
                    updateLastMessageStatus('failed');
                    showError(data.error || 'Failed to send media');
                }
                
            } catch (error) {
                console.error('Error uploading media:', error);
                updateLastMessageStatus('failed');
                showError('Failed to send media. Please try again.');
            }
            
            // Clear file input
            event.target.value = '';
        }

        function addMessageToUI(message) {
            const container = document.getElementById('messages');
            
            // Remove empty state if exists
            const emptyState = container.querySelector('.empty-state');
            if (emptyState) emptyState.remove();
            
            const div = document.createElement('div');
            div.className = `message ${message.type}`;
            
            let messageContent = '';
            if (message.message_type === 'image' && message.media_url) {
                messageContent = `
                    <div class="media-message">
                        <img src="${message.media_url}" alt="Image">
                        <div class="message-time">${message.time} ${getStatusIcon(message.status)}</div>
                    </div>
                `;
            } else {
                messageContent = `
                    <div class="message-bubble">
                        ${escapeHtml(message.text).replace(/\n/g, '<br>')}
                        <div class="message-time">${message.time} ${getStatusIcon(message.status)}</div>
                    </div>
                `;
            }
            
            div.innerHTML = messageContent;
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }

        function updateLastMessageStatus(status) {
            const messages = document.getElementById('messages');
            const lastMessage = messages.querySelector('.message.sent:last-child .message-time');
            if (lastMessage) {
                const timeText = lastMessage.textContent.split(' ')[0];
                lastMessage.innerHTML = `${timeText} ${getStatusIcon(status)}`;
            }
        }

        function getStatusIcon(status) {
            switch(status) {
                case 'sending':
                    return '<i class="fas fa-clock status-indicator" style="color: #8696a0;"></i>';
                case 'sent':
                    return '<i class="fas fa-check status-indicator" style="color: #4fc3f7;"></i>';
                case 'delivered':
                    return '<i class="fas fa-check-double status-indicator" style="color: #4fc3f7;"></i>';
                case 'read':
                    return '<i class="fas fa-check-double status-indicator" style="color: #25d366;"></i>';
                case 'failed':
                    return '<i class="fas fa-exclamation-triangle status-indicator" style="color: #f44336;"></i>';
                default:
                    return '';
            }
        }

        // Media dialog functions
        function openMediaDialog() {
            console.log('openMediaDialog called', { currentConversation });
            
            if (!currentConversation) {
                showError('Please select a conversation first');
                return;
            }
            
            // Check if Alpine.js is loaded
            if (typeof Alpine === 'undefined') {
                console.warn('Alpine.js not loaded, falling back to direct modal opening');
            }
            
            console.log('Dispatching media dialog event');
            
            // Use Alpine.js event system (simple and reliable)
            const event = new CustomEvent('open-simple-media-dialog', {
                detail: { conversationId: currentConversation }
            });
            
            console.log('Event created:', event);
            window.dispatchEvent(event);
            
            // Add a fallback - try to open the modal directly after a delay
            setTimeout(() => {
                const modalElement = document.querySelector('[x-data="mediaUploadModal()"]');
                if (modalElement && modalElement._x_dataStack) {
                    console.log('Found modal element, trying to open directly');
                    modalElement._x_dataStack[0].openDialog(currentConversation);
                }
            }, 100);
        }

        // Image modal functions
        function openImageModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            modalImage.src = imageSrc;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Handle Enter key in message input
        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }

        // Get document icon based on filename
        function getDocumentIcon(filename) {
            const ext = filename.split('.').pop()?.toLowerCase() || '';
            
            switch (ext) {
                case 'pdf':
                    return { icon: 'fa-file-pdf', class: 'pdf' };
                case 'doc':
                case 'docx':
                    return { icon: 'fa-file-word', class: 'word' };
                case 'xls':
                case 'xlsx':
                    return { icon: 'fa-file-excel', class: 'excel' };
                case 'ppt':
                case 'pptx':
                    return { icon: 'fa-file-powerpoint', class: 'powerpoint' };
                case 'txt':
                    return { icon: 'fa-file-alt', class: 'text' };
                default:
                    return { icon: 'fa-file', class: 'default' };
            }
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function () {
            // Close modal on escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeImageModal();
                }
            });

            // Listen for media sent event (from simple modal)
            window.addEventListener('media-sent', function(event) {
                const message = event.detail;
                // Add the message to the UI immediately
                addMessageToUI(message);
                
                // Refresh messages to sync with server
                setTimeout(() => {
                    if (currentConversation) {
                        loadMessages(currentConversation, true);
                    }
                }, 1000);
            });

            // Optional: Listen for Livewire events if available (for backward compatibility)
            if (typeof Livewire !== 'undefined') {
                document.addEventListener('livewire:load', function () {
                    Livewire.on('mediaSent', message => {
                        addMessageToUI(message);
                        setTimeout(() => {
                            if (currentConversation) {
                                loadMessages(currentConversation, true);
                            }
                        }, 1000);
                    });

                    Livewire.on('showError', message => {
                        showError(message);
                    });
                });
            }
        });

        function formatTimeAgo(dateString) {
            if (!dateString) return '';
            
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffMins < 1) return 'now';
            if (diffMins < 60) return `${diffMins}m`;
            if (diffHours < 24) return `${diffHours}h`;
            if (diffDays < 7) return `${diffDays}d`;
            
            return date.toLocaleDateString();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showError(message) {
            // Simple error display - could be enhanced with better UI
            const errorDiv = document.createElement('div');
            errorDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #f44336; color: white; padding: 12px 20px; border-radius: 4px; z-index: 9999; font-size: 14px;';
            errorDiv.textContent = message;
            document.body.appendChild(errorDiv);
            
            setTimeout(() => {
                document.body.removeChild(errorDiv);
            }, 5000);
        }

        // Mobile responsiveness
        function showSidebar() {
            document.getElementById('sidebar').classList.remove('hidden');
            document.getElementById('chatArea').classList.remove('mobile-visible');
            document.getElementById('backBtn').style.display = 'none';
        }

        function hideSidebar() {
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.add('hidden');
                document.getElementById('chatArea').classList.add('mobile-visible');
                document.getElementById('backBtn').style.display = 'block';
            }
        }

        // Auto-refresh conversations every 5 seconds for real-time updates
        setInterval(() => {
            if (!document.hidden) {
                loadConversations();
            }
        }, 5000);

        // Auto-refresh messages in current conversation every 3 seconds
        setInterval(() => {
            if (!document.hidden && currentConversation) {
                loadMessages(currentConversation, true); // true = silent refresh
            }
        }, 3000);

        // Enter key to send message
        document.addEventListener('DOMContentLoaded', function() {
            const messageInput = document.getElementById('messageInput');
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('hidden');
                document.getElementById('chatArea').classList.remove('mobile-visible');
                document.getElementById('backBtn').style.display = 'none';
            }
        });
    </script>
</body>
</html>