# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 9 WhatsApp Cloud API microservice that provides chat functionality and administration interface. It integrates with Meta's WhatsApp Business API and serves as a standalone service that can be integrated with other applications. The microservice runs on `connect.al-najjarstore.com` and provides real-time messaging, conversation management, and role-based admin access.

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
- `php artisan test --filter=WhatsAppTest` - Run WhatsApp-specific tests

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
- **POST** `/api/whatsapp/send-text` - Send text messages
- **POST** `/api/whatsapp/send-media` - Send media files
- **GET** `/api/whatsapp/interactions` - List conversations
- **GET** `/api/whatsapp/interactions/{id}/messages` - Get conversation messages
- **POST** `/whatsapp/webhook` - WhatsApp webhook endpoint
- **GET** `/health` - Service health check

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

### Security Features
- CSRF protection on all forms and API endpoints
- Input validation and sanitization for all user inputs
- Secure file upload with type and size restrictions
- Role-based access control throughout the application
- Webhook verification for WhatsApp API security