# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 9 WhatsApp Cloud API microservice that provides chat functionality and administration interface. It integrates with Meta's WhatsApp Business API and serves as a standalone service that can be integrated with other applications. The microservice runs on `connect.al-najjarstore.com` and provides real-time messaging, conversation management, and role-based admin access.

**Key Requirements:**
- PHP 8.1+
- MySQL 5.7+
- Composer
- WhatsApp Business Account with Meta Developer access
- SSL certificate for webhook URL

## Common Development Commands

### Laravel Artisan Commands
- `php artisan serve` - Start development server
- `php artisan migrate` - Run database migrations
- `php artisan migrate:fresh` - Drop all tables and re-run migrations  
- `php artisan db:seed --class=AdminSeeder` - Seed admin users
- `php artisan test` - Run PHPUnit tests
- `php artisan queue:work` - Start processing queued jobs
- `php artisan cache:clear` - Clear application cache
- `php artisan route:list` - List all registered routes

### WhatsApp-Specific Commands
- `php artisan whatsapp:test-message` - Send test WhatsApp message
- `php artisan whatsapp:sync-templates` - Sync WhatsApp message templates
- `php artisan whatsapp:campaign` - Send WhatsApp campaign messages
- `php artisan whatsapp:simulate-webhook` - Simulate webhook for testing

### Testing
- `vendor/bin/phpunit` - Run PHPUnit tests
- `php artisan test` - Run Laravel tests
- `php artisan test --filter=WhatsAppTest` - Run WhatsApp-specific tests
- `php artisan tinker` - Interactive PHP console for testing

### Deployment
- `./deploy-complete.sh` - Complete deployment script
- `./complete-deployment.sh` - Alternative deployment script
- `./fix-database.sh` - Fix database issues script

## Code Architecture

### Core Models
- **User** - Basic user authentication
- **WhatsappAdmin** - Admin users with role-based permissions (Super Admin, Admin, Supervisor, Agent)
- **Conversation** - WhatsApp conversation tracking and management
- **WhatsAppInteractionMessage** - Individual messages within conversations

### WhatsApp Integration Package
The application includes a custom WhatsApp Cloud API package in `Laravel-WhatsApp-CloudApi/` that provides:
- **WhatsAppClient** - Core API client for Meta WhatsApp Cloud API
- **Message Types** - Text, Image, Document, Audio, Video, Interactive, Location, Contacts
- **InteractiveSessionManager** - Manages conversation sessions and user interactions
- **TemplateManager** - Handles WhatsApp message templates
- **Jobs** - Queue-based message sending with retry logic
- **Webhook Controller** - Handles incoming WhatsApp messages and events

### Key Controllers
- **AdminController** - Authentication and admin dashboard functionality
- **WhatsAppController** - Main chat interface and API endpoints
- **ChatController** - Conversation management and message handling
- **ConversationController** - REST API for conversation operations
- **ConversationManagementController** - Admin conversation assignment and management

### Services
- **MetaWhatsappService** - Wrapper service for WhatsApp API operations including message sending, media handling, and phone number validation

### Database Structure
- Uses MySQL database with migrations for WhatsApp-specific tables
- Key tables: `whatsapp_admins`, `conversations`, `whatsapp_interaction_messages`, `interactive_sessions`, `whatsapp_messages`
- Foreign key relationships between conversations and messages
- Admin role-based permissions stored in user roles

### Authentication & Authorization
- Multi-guard authentication system supporting both regular users and WhatsApp admins
- Role-based access control with four levels: Super Admin, Admin, Supervisor, Agent
- Integration authentication with main site using secure token exchange
- Phone number masking for supervisor-level users

### API Endpoints
- **GET** `/api/whatsapp/conversations` - List conversations (authenticated)
- **GET** `/api/whatsapp/interactions` - Legacy conversation endpoint 
- **GET** `/api/whatsapp/messages/{conversationId}` - Get messages for conversation
- **POST** `/api/whatsapp/send-message` - Send text messages
- **POST** `/api/whatsapp/upload-media` - Upload and send media files
- **POST** `/whatsapp/webhook` - WhatsApp webhook endpoint (CSRF exempt)
- **GET** `/test-message` - Test message creation endpoint
- **GET** `/debug-auth` - Debug authentication status

### Frontend Components
- Real-time chat interface with WebSocket-like updates
- File upload handling for media messages
- Role-based UI showing different permissions
- Admin dashboard with conversation statistics and management tools

### Integration Architecture
- Designed as a microservice that can integrate with parent applications
- Token-based authentication for cross-service communication
- REST API endpoints for external system integration
- Webhook system for real-time message processing

### Configuration Notes
- Environment-specific configurations in multiple `.env` files
- WhatsApp API credentials configuration in `config/whatsapp.php`
- Database connection uses MySQL with specific charset settings for Arabic/Unicode support
- File storage configuration for media uploads in `public/uploads/whatsapp/`
- Queue configuration for background message processing
- Session domain configured as `.al-najjarstore.com` for subdomain sharing
- Custom WhatsApp package autoloaded: `Laravel-WhatsApp-CloudApi/src/`

### Security Features
- CSRF protection on all forms and API endpoints
- Input validation and sanitization for all user inputs
- Secure file upload with type and size restrictions
- Role-based access control throughout the application
- Webhook verification for WhatsApp API security

## Recent Fixes & Improvements (August 2025)

### Authentication & Permissions Issues Fixed
- **Fixed Model Relationships**: Corrected `Conversation` model to reference `WhatsappAdmin` instead of non-existent `App\Models\Admin\Admins`
- **Fixed Database Column Mapping**: Updated `Conversation` model to use correct database columns (`receiver_id` instead of `wa_no`)
- **Fixed Route Authentication**: Added proper `auth:whatsapp_admin` middleware to all WhatsApp API routes
- **Fixed API Route Conflicts**: Disabled demo routes that were overriding authenticated routes
- **Fixed User Permissions**: Ensured Super Admin users get full permissions (`can_see_all`, `can_see_phone`, etc.)

### WhatsApp Message Sending Fixed
- **Fixed Missing Parameter**: Added required `messaging_product: 'whatsapp'` parameter to `TextMessage` and `TemplateMessage` classes
- **Updated Access Token**: Environment configured with current WhatsApp Business API access token
- **Fixed Database Logging**: Created missing `whatsapp_messages` table to prevent logging errors
- **Improved Error Handling**: Added comprehensive logging and error reporting for message sending

### Real-time Updates Implementation
- **Auto-refresh Conversations**: Reduced refresh interval from 30 seconds to 5 seconds
- **Auto-refresh Messages**: Added 3-second refresh for active conversation messages
- **Smart Scrolling**: Preserves user scroll position during auto-refresh
- **New Message Indicators**: Visual notifications for incoming messages
- **Performance Optimized**: Only refreshes when browser tab is active

### Database Structure Fixes
- **whatsapp_interactions table**: Uses `receiver_id` for phone numbers, no `wa_no` column
- **whatsapp_interaction_messages table**: Stores individual messages with proper relationships
- **whatsapp_messages table**: Logs outbound API calls for debugging and analytics
- **whatsapp_admins table**: Role-based admin authentication with proper permissions

### Current System Status
✅ **Fully Operational** - All core functionality working:
- Admin authentication with role-based permissions
- Real-time conversation and message management  
- Bi-directional WhatsApp messaging (send/receive)
- Webhook processing for incoming messages
- Super Admin can see unmasked phone numbers
- Auto-refresh for near real-time experience

### Troubleshooting Commands
- `php artisan cache:clear && php artisan config:clear && php artisan route:clear` - Clear all caches after changes
- `php artisan optimize:clear` - Clear all Laravel optimization caches including OPcache
- `php artisan route:list | grep whatsapp` - Verify WhatsApp routes are properly configured
- `tail -f storage/logs/laravel.log` - Monitor real-time logs for debugging
- `mysql -u username -p database < fix_whatsapp_logging.sql` - Fix missing logging table if needed

### Demo Credentials
- **Super Admin**: `admin@connect.al-najjarstore.com` / `admin123`
- **Admin**: `whatsapp-admin@connect.al-najjarstore.com` / `whatsapp123`
- **Supervisor**: `supervisor@connect.al-najjarstore.com` / `supervisor123`
- **Agent**: `agent@connect.al-najjarstore.com` / `agent123`

### Critical Environment Variables
```env
# WhatsApp Cloud API (Required)
WHATSAPP_BUSINESS_ACCOUNT_ID=your_business_account_id
WHATSAPP_ACCESS_TOKEN=your_access_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your_webhook_token

# Database (Required)
DB_CONNECTION=mysql
DB_DATABASE=u539863725_whatsapp
DB_USERNAME=u539863725_ahmedegy

# Session Configuration (Required for auth)
SESSION_DOMAIN=.al-najjarstore.com
SESSION_SECURE_COOKIE=true
```

### Important File Locations
- **Main Controllers**: `app/Http/Controllers/WhatsAppController.php`, `app/Http/Controllers/AdminController.php`
- **Models**: `app/Models/Conversation.php`, `app/Models/WhatsappAdmin.php`, `app/Models/WhatsAppInteractionMessage.php`
- **WhatsApp Package**: `Laravel-WhatsApp-CloudApi/src/` (custom integration)
- **Routes**: All WhatsApp routes in `routes/web.php` with `auth:whatsapp_admin` middleware
- **Views**: Dashboard at `resources/views/whatsapp/dashboard.blade.php`
- **Services**: `app/Services/MetaWhatsappService.php` for WhatsApp API wrapper

## Domain Migration Guide

### Pre-Migration Checklist
- [ ] New domain has valid SSL certificate
- [ ] Database is accessible from new domain
- [ ] Meta Developer Console access available
- [ ] Backup current database and files

### Required Changes for Domain Transfer

#### 1. Environment Configuration (.env)
```env
# Update these values for new domain
APP_URL=https://new-subdomain.new-domain.com
SESSION_DOMAIN=.new-domain.com  # Note the leading dot
SESSION_SECURE_COOKIE=true

# Database - may need updating if hosted separately
DB_HOST=127.0.0.1  # Update if database host changes
DB_DATABASE=your_new_database_name  # If different
DB_USERNAME=your_new_username  # If different
DB_PASSWORD=your_new_password  # If different

# WhatsApp config remains the same unless tokens change
WHATSAPP_ACCESS_TOKEN=your_current_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your_webhook_token
```

#### 2. Meta Developer Console Updates (CRITICAL)
1. **Login to Meta Developer Console**
2. **Go to WhatsApp > Configuration**
3. **Update Webhook URL**: `https://new-subdomain.new-domain.com/whatsapp/webhook`
4. **Verify webhook** with your verify token
5. **Test webhook** by sending a test message

#### 3. Database Migration (if needed)
```bash
# Export from old server
mysqldump -u username -p database_name > whatsapp_backup.sql

# Import to new server (if database changes)
mysql -u new_username -p new_database_name < whatsapp_backup.sql
```

#### 4. File System Updates
```bash
# Clear all caches after domain change
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# Generate new app key if needed
php artisan key:generate
```

#### 5. SSL Certificate Verification
```bash
# Test SSL certificate
curl -I https://new-subdomain.new-domain.com/whatsapp/webhook

# Should return 200 OK with valid SSL
```

### Post-Migration Testing

#### 1. Admin Authentication Test
- Visit: `https://new-subdomain.new-domain.com/admin/login`
- Login with super admin credentials
- Verify dashboard loads correctly

#### 2. WhatsApp Webhook Test
```bash
# Test webhook endpoint
curl -X POST https://new-subdomain.new-domain.com/whatsapp/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "webhook"}'

# Should return success response
```

#### 3. Message Flow Test
1. **Send test WhatsApp message to your business number**
2. **Check logs**: `tail -f storage/logs/laravel.log`
3. **Verify message appears** in dashboard within 5 seconds
4. **Reply from dashboard** and confirm delivery

### Common Migration Issues & Solutions

#### Issue: "Session not working after domain change"
**Solution:**
```env
# Clear browser cookies and update .env
SESSION_DOMAIN=.new-domain.com
APP_URL=https://new-subdomain.new-domain.com
```

#### Issue: "Webhook not receiving messages"  
**Solutions:**
1. **Update Meta Developer Console webhook URL**
2. **Verify SSL certificate is valid**
3. **Check webhook verify token matches**
4. **Test with curl POST to webhook endpoint**

#### Issue: "CSRF token mismatch"
**Solution:**
```bash
# Clear all caches and regenerate key
php artisan optimize:clear
php artisan key:generate
```

#### Issue: "Database connection failed"
**Solution:**
```env
# Update database configuration in .env
DB_HOST=new_database_host
DB_DATABASE=new_database_name
DB_USERNAME=new_username
DB_PASSWORD=new_password
```

### Migration Rollback Plan
If issues occur, you can quickly rollback:
1. **Revert Meta webhook URL** to old domain
2. **Restore old .env** configuration  
3. **Clear caches** on old server
4. **Restore database** from backup if needed

### Domain-Specific Considerations

#### Subdomain Only Change (same domain)
- ✅ **Easier migration** - session domain stays same
- ⚠️ **Still need webhook URL update** in Meta Console

#### Complete Domain Change  
- 🚨 **More complex** - requires full configuration update
- 🚨 **Session domain must change** - users need to re-login
- 🚨 **All external integrations** need URL updates