// Enhanced WhatsApp Notification System
class WhatsAppNotifications {
    constructor() {
        this.notificationSound = null;
        this.audioContext = null;
        this.pollingInterval = null;
        this.lastUnreadCount = 0;
        this.isPageVisible = true;
        this.notificationPermission = 'default';
        
        this.init();
    }

    async init() {
        // Initialize audio context
        this.initializeAudioContext();
        
        // Request notification permission
        await this.requestNotificationPermission();
        
        // Track page visibility
        this.trackPageVisibility();
        
        // Start monitoring for new messages
        this.startMessagePolling();
        
        console.log('WhatsApp Notifications initialized');
    }

    initializeAudioContext() {
        try {
            // Create audio context for better browser support
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            // Create notification sound using Web Audio API
            this.notificationSound = {
                play: () => {
                    if (!this.audioContext || this.isPageVisible) return;
                    
                    try {
                        // WhatsApp-like notification sound
                        const oscillator = this.audioContext.createOscillator();
                        const gainNode = this.audioContext.createGain();
                        
                        oscillator.connect(gainNode);
                        gainNode.connect(this.audioContext.destination);
                        
                        // First tone
                        oscillator.frequency.setValueAtTime(800, this.audioContext.currentTime);
                        oscillator.frequency.setValueAtTime(600, this.audioContext.currentTime + 0.1);
                        
                        gainNode.gain.setValueAtTime(0.3, this.audioContext.currentTime);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.5);
                        
                        oscillator.start(this.audioContext.currentTime);
                        oscillator.stop(this.audioContext.currentTime + 0.5);
                        
                        // Second tone (echo effect)
                        setTimeout(() => {
                            const oscillator2 = this.audioContext.createOscillator();
                            const gainNode2 = this.audioContext.createGain();
                            
                            oscillator2.connect(gainNode2);
                            gainNode2.connect(this.audioContext.destination);
                            
                            oscillator2.frequency.setValueAtTime(700, this.audioContext.currentTime);
                            gainNode2.gain.setValueAtTime(0.2, this.audioContext.currentTime);
                            gainNode2.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.3);
                            
                            oscillator2.start(this.audioContext.currentTime);
                            oscillator2.stop(this.audioContext.currentTime + 0.3);
                        }, 200);
                        
                    } catch (error) {
                        console.warn('Audio playback failed:', error);
                    }
                }
            };
        } catch (error) {
            console.warn('Web Audio API not supported:', error);
        }
    }

    async requestNotificationPermission() {
        if ('Notification' in window) {
            const permission = await Notification.requestPermission();
            this.notificationPermission = permission;
            
            if (permission === 'granted') {
                console.log('Notification permission granted');
            } else {
                console.warn('Notification permission denied');
            }
        }
    }

    trackPageVisibility() {
        document.addEventListener('visibilitychange', () => {
            this.isPageVisible = !document.hidden;
        });
        
        window.addEventListener('focus', () => {
            this.isPageVisible = true;
        });
        
        window.addEventListener('blur', () => {
            this.isPageVisible = false;
        });
    }

    startMessagePolling() {
        // Poll every 3 seconds for new messages
        this.pollingInterval = setInterval(() => {
            this.checkForNewMessages();
        }, 3000);
    }

    async checkForNewMessages() {
        try {
            const response = await fetch('/api/whatsapp/interactions', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                const currentUnreadCount = this.getTotalUnreadCount(data.data || data);
                
                if (currentUnreadCount > this.lastUnreadCount) {
                    this.handleNewMessage(data.data || data);
                }
                
                this.lastUnreadCount = currentUnreadCount;
                this.updateConversationsList(data.data || data);
            }
        } catch (error) {
            console.error('Failed to check for new messages:', error);
        }
    }

    getTotalUnreadCount(conversations) {
        return conversations.reduce((total, conv) => total + (conv.unread || 0), 0);
    }

    handleNewMessage(conversations) {
        // Play notification sound
        if (this.notificationSound && !this.isPageVisible) {
            this.notificationSound.play();
        }
        
        // Show browser notification
        this.showBrowserNotification(conversations);
        
        // Update page title with unread count
        this.updatePageTitle();
        
        // Visual notification in interface
        this.showVisualNotification();
    }

    showBrowserNotification(conversations) {
        if (this.notificationPermission !== 'granted' || this.isPageVisible) return;
        
        const newConversations = conversations.filter(conv => conv.unread > 0);
        if (newConversations.length === 0) return;
        
        const firstNewConv = newConversations[0];
        const title = `New WhatsApp Message`;
        const body = `${firstNewConv.name}: ${firstNewConv.last_message || 'New message'}`;
        
        const notification = new Notification(title, {
            body: body,
            icon: '/favicon.ico',
            badge: '/favicon.ico',
            tag: 'whatsapp-new-message',
            requireInteraction: false,
            silent: false
        });
        
        // Auto close after 5 seconds
        setTimeout(() => notification.close(), 5000);
        
        // Click to focus window
        notification.onclick = () => {
            window.focus();
            notification.close();
        };
    }

    updatePageTitle() {
        const unreadCount = this.lastUnreadCount;
        const baseTitle = 'WhatsApp Chat';
        
        if (unreadCount > 0) {
            document.title = `(${unreadCount}) ${baseTitle}`;
        } else {
            document.title = baseTitle;
        }
    }

    showVisualNotification() {
        // Create a temporary visual notification
        const notification = document.createElement('div');
        notification.className = 'toast-notification';
        notification.innerHTML = `
            <div class="alert alert-success alert-dismissible fade show" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
                <i class="fas fa-comment-dots me-2"></i>
                New WhatsApp message received!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 4 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 4000);
    }

    updateConversationsList(conversations) {
        // Update the conversations list in real-time
        const conversationsList = document.getElementById('conversationsList');
        if (!conversationsList) return;
        
        conversations.forEach(conversation => {
            const existingItem = document.querySelector(`[data-id="${conversation.id}"]`);
            if (existingItem) {
                // Update unread badge
                const unreadBadge = existingItem.querySelector('.unread-badge');
                if (conversation.unread > 0) {
                    if (unreadBadge) {
                        unreadBadge.textContent = conversation.unread;
                    } else {
                        // Add unread badge
                        const timeElement = existingItem.querySelector('.conversation-time');
                        if (timeElement) {
                            const badge = document.createElement('span');
                            badge.className = 'unread-badge';
                            badge.textContent = conversation.unread;
                            timeElement.parentNode.appendChild(badge);
                        }
                    }
                    
                    // Add new message indicator animation
                    if (!existingItem.querySelector('.new-message-indicator')) {
                        const indicator = document.createElement('div');
                        indicator.className = 'new-message-indicator';
                        existingItem.style.position = 'relative';
                        existingItem.appendChild(indicator);
                        
                        // Remove indicator after animation
                        setTimeout(() => indicator.remove(), 3000);
                    }
                } else if (unreadBadge) {
                    unreadBadge.remove();
                }
                
                // Update last message
                const previewElement = existingItem.querySelector('.conversation-preview');
                if (previewElement && conversation.last_message) {
                    previewElement.textContent = conversation.last_message.substring(0, 50) + (conversation.last_message.length > 50 ? '...' : '');
                }
            }
        });
    }

    stop() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }
        
        if (this.audioContext) {
            this.audioContext.close();
        }
    }

    // Manual trigger for testing
    testNotification() {
        this.handleNewMessage([{
            id: 'test',
            name: 'Test Contact',
            last_message: 'This is a test notification',
            unread: 1
        }]);
    }
}

// Auto-initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    window.whatsappNotifications = new WhatsAppNotifications();
    
    // Add test button for development (remove in production)
    if (window.location.hostname === 'localhost' || window.location.hostname.includes('test')) {
        const testButton = document.createElement('button');
        testButton.textContent = 'Test Notification';
        testButton.className = 'btn btn-sm btn-warning position-fixed';
        testButton.style.top = '10px';
        testButton.style.left = '10px';
        testButton.style.zIndex = '9999';
        testButton.onclick = () => window.whatsappNotifications.testNotification();
        document.body.appendChild(testButton);
    }
});