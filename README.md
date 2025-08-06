# 🚀 WhatsApp Cloud API Microservice

A powerful Laravel-based microservice for managing WhatsApp Business conversations through the Meta Cloud API. Features a comprehensive admin dashboard for conversation management, user administration, and real-time messaging.

## ✨ Features

### 🎯 Core Functionality
- **WhatsApp Cloud API Integration** - Send/receive messages via Meta's official API
- **Real-time Messaging** - Live chat interface with instant message delivery
- **Conversation Management** - Organize and track customer conversations
- **Multi-user Support** - Role-based access control for teams
- **Media Support** - Handle images, documents, and other media files

### 🛡️ Admin Dashboard
- **Modern UI** - Clean, responsive admin interface
- **User Management** - Create and manage admin users with different roles
- **Conversation Overview** - Monitor all active conversations
- **Statistics & Analytics** - Track message volume and user activity
- **Role-based Permissions** - Super Admin, Admin, Supervisor, Agent roles

### 🔧 Technical Features
- **Laravel Framework** - Built on Laravel for reliability and scalability
- **MySQL Database** - Robust data storage and management
- **RESTful API** - Clean API endpoints for integration
- **Webhook Support** - Real-time message receiving from WhatsApp
- **File Upload Management** - Secure media file handling

## 🚀 Quick Start

### Prerequisites
- PHP 8.1+
- MySQL 5.7+
- Composer
- WhatsApp Business Account
- Meta Developer Account

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/moodfactor/Whatsapp_Cloud.git
cd Whatsapp_Cloud
```

2. **Install dependencies**
```bash
composer install
```

3. **Environment setup**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure environment variables**
```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# WhatsApp Cloud API
WHATSAPP_BUSINESS_ACCOUNT_ID=your_business_account_id
WHATSAPP_ACCESS_TOKEN=your_access_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_PHONE_NUMBER=your_phone_number
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your_webhook_token
```

5. **Database setup**
```bash
php artisan migrate
php artisan db:seed --class=AdminSeeder
```

6. **Start the application**
```bash
php artisan serve
```

## 🎮 Usage

### Admin Access
Visit `/admin/login` and use the demo credentials:
- **Super Admin**: `admin@connect.al-najjarstore.com` / `admin123`
- **Admin**: `whatsapp-admin@connect.al-najjarstore.com` / `whatsapp123`
- **Supervisor**: `supervisor@connect.al-najjarstore.com` / `supervisor123`
- **Agent**: `agent@connect.al-najjarstore.com` / `agent123`

### WhatsApp Chat Interface
Access the chat interface at `/dashboard` to:
- View all conversations
- Send/receive messages
- Manage customer interactions
- Handle media files

### API Endpoints
```bash
# Send text message
POST /api/whatsapp/send-text
{
    "to": "phone_number",
    "message": "Hello World"
}

# Send media
POST /api/whatsapp/send-media
{
    "to": "phone_number",
    "media_url": "https://example.com/image.jpg",
    "caption": "Image caption"
}

# Get conversations
GET /api/whatsapp/interactions

# Get messages for conversation
GET /api/whatsapp/interactions/{id}/messages
```

## 🏗️ Architecture

### Database Schema
- **whatsapp_admins** - Admin user management
- **conversations** - Customer conversation tracking
- **whatsapp_interaction_messages** - Message storage
- **users** - System users (if needed)

### Key Components
- **AdminController** - Admin authentication and dashboard
- **WhatsAppController** - Chat interface and API endpoints
- **ChatController** - Conversation management
- **MetaWhatsappService** - WhatsApp API integration

## 🔧 Configuration

### WhatsApp Setup
1. Create a Meta Developer account
2. Set up WhatsApp Business API
3. Configure webhook URL: `https://yourdomain.com/whatsapp/webhook`
4. Add phone numbers to your business account

### Webhook Configuration
The webhook endpoint handles incoming messages automatically. Ensure your webhook URL is accessible and properly configured in your Meta Developer console.

## 🛡️ Security

- **Authentication** - Secure admin login system
- **Role-based Access** - Different permission levels
- **CSRF Protection** - Laravel's built-in CSRF protection
- **Input Validation** - Comprehensive request validation
- **Secure File Upload** - Protected media file handling

## 📱 Screenshots

### Admin Dashboard
![Admin Dashboard](docs/admin-dashboard.png)

### WhatsApp Chat Interface
![Chat Interface](docs/chat-interface.png)

### Conversation Management
![Conversations](docs/conversations.png)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Create an issue on GitHub
- Email: support@yourcompany.com
- Documentation: [Wiki](https://github.com/moodfactor/Whatsapp_Cloud/wiki)

## 🙏 Acknowledgments

- Laravel Framework
- Meta WhatsApp Cloud API
- Font Awesome for icons
- All contributors and testers

---

**Made with ❤️ for better customer communication**
