// WhatsApp Notification System
class WhatsAppNotifications {
    constructor() {
        this.audioContext = null;
        this.soundBuffer = null;
        this.isEnabled = localStorage.getItem('notifications-enabled') !== 'false';
        this.init();
    }

    init() {
        // Request notification permission
        if (Notification.permission === 'default') {
            Notification.requestPermission();
        }

        // Create notification sound programmatically (WhatsApp-like)
        this.createNotificationSound();
        
        // Listen for Livewire events
        if (typeof Livewire !== 'undefined') {
            Livewire.on('messageReceived', (data) => {
                this.showNotification(data);
            });
        }
    }

    createNotificationSound() {
        try {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            // Create WhatsApp-like notification sound
            const sampleRate = this.audioContext.sampleRate;
            const duration = 0.3; // 300ms
            const buffer = this.audioContext.createBuffer(1, sampleRate * duration, sampleRate);
            const data = buffer.getChannelData(0);

            // Generate a pleasant notification tone (two-tone beep)
            for (let i = 0; i < buffer.length; i++) {
                const t = i / sampleRate;
                if (t < 0.1) {
                    data[i] = Math.sin(2 * Math.PI * 800 * t) * 0.3 * (1 - t / 0.1);
                } else if (t < 0.15) {
                    data[i] = 0;
                } else if (t < 0.25) {
                    data[i] = Math.sin(2 * Math.PI * 1000 * t) * 0.3 * (1 - (t - 0.15) / 0.1);
                }
            }

            this.soundBuffer = buffer;
        } catch (e) {
            console.log('Audio context not available');
        }
    }

    playNotificationSound() {
        if (!this.isEnabled || !this.audioContext || !this.soundBuffer) {
            return;
        }

        try {
            const source = this.audioContext.createBufferSource();
            source.buffer = this.soundBuffer;
            source.connect(this.audioContext.destination);
            source.start();
        } catch (e) {
            console.log('Could not play notification sound');
        }
    }

    showNotification(data) {
        if (!this.isEnabled) return;

        // Play sound
        this.playNotificationSound();

        // Show browser notification
        if (Notification.permission === 'granted' && document.hidden) {
            const notification = new Notification('رسالة واتساب جديدة', {
                body: data.message || 'تم استلام رسالة جديدة',
                icon: '/favicon.ico',
                badge: '/favicon.ico',
                tag: 'whatsapp-message',
                requireInteraction: false
            });

            notification.onclick = () => {
                window.focus();
                notification.close();
            };

            // Auto close after 5 seconds
            setTimeout(() => notification.close(), 5000);
        }

        // Visual notification (flash title)
        this.flashTitle();
    }

    flashTitle() {
        const originalTitle = document.title;
        let flashCount = 0;
        
        const flashInterval = setInterval(() => {
            document.title = flashCount % 2 === 0 ? '🔔 رسالة جديدة!' : originalTitle;
            flashCount++;
            
            if (flashCount >= 6) {
                clearInterval(flashInterval);
                document.title = originalTitle;
            }
        }, 1000);

        // Stop flashing when user focuses window
        const stopFlashing = () => {
            clearInterval(flashInterval);
            document.title = originalTitle;
            window.removeEventListener('focus', stopFlashing);
        };
        
        window.addEventListener('focus', stopFlashing);
    }

    toggle() {
        this.isEnabled = !this.isEnabled;
        localStorage.setItem('notifications-enabled', this.isEnabled.toString());
        return this.isEnabled;
    }

    isNotificationEnabled() {
        return this.isEnabled;
    }
}

// Initialize notifications when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.whatsappNotifications = new WhatsAppNotifications();
});

// Add notification toggle button to UI
function addNotificationToggle() {
    const toggle = `
        <div class="notification-toggle position-fixed" style="top: 20px; right: 20px; z-index: 1050;">
            <button class="btn btn-sm btn-outline-success" onclick="toggleNotifications()" id="notification-btn">
                <i class="fas fa-bell"></i>
                <span class="ml-1">التنبيهات</span>
            </button>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', toggle);
}

function toggleNotifications() {
    if (window.whatsappNotifications) {
        const enabled = window.whatsappNotifications.toggle();
        const btn = document.getElementById('notification-btn');
        
        if (enabled) {
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-outline-success');
            btn.innerHTML = '<i class="fas fa-bell"></i><span class="ml-1">التنبيهات</span>';
        } else {
            btn.classList.remove('btn-outline-success');
            btn.classList.add('btn-outline-secondary');
            btn.innerHTML = '<i class="fas fa-bell-slash"></i><span class="ml-1">التنبيهات معطلة</span>';
        }
    }
}

// Test notification function
function testNotification() {
    if (window.whatsappNotifications) {
        window.whatsappNotifications.showNotification({
            message: 'هذه رسالة تجريبية للتنبيهات'
        });
    }
}

// Auto-add toggle when page loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addNotificationToggle);
} else {
    addNotificationToggle();
}