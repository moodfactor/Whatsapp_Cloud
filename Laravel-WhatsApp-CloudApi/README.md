# Laravel WhatsApp Cloud API Package

A Laravel package for integrating with the WhatsApp Cloud API. This package supports sending various message types (text, image, audio, video, document, interactive, location, and contacts), interactive messaging with session management, delayed sending, webhook handling, and more.

## Features

- **Multi-Message Types:** Send text, media, interactive, location, and contacts messages.
- **Interactive Messaging:** Start interactive sessions with users and correlate responses using persistent sessions.
- **Asynchronous Processing:** Utilize Laravel's queue system with advanced retry and exponential backoff strategies.
- **Security Enhancements:** Verify and sanitize incoming webhook requests.
- **Extensible & Customizable:** Override default implementations via Laravel's service container.
- **Observability & Monitoring:** Integrated logging and event dispatching for enhanced troubleshooting.

## Installation

Install the package via Composer:

```bash
composer require BiztechEG/laravel-whatsapp-cloud-api
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="BiztechEG\WhatsAppCloudApi\Providers\WhatsAppServiceProvider" --tag=config
```

Publish the migrations file:

```bash
php artisan vendor:publish --provider="BiztechEG\WhatsAppCloudApi\Providers\WhatsAppServiceProvider" --tag=migrations
```

Publish the views file:

```bash
php artisan vendor:publish --provider="BiztechEG\WhatsAppCloudApi\Providers\WhatsAppServiceProvider" --tag=views
```

Update your `.env` file with the following variables:

```dotenv
WHATSAPP_API_URL=https://graph.facebook.com/v16.0/
WHATSAPP_ACCESS_TOKEN=your_access_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_WEBHOOK_SECRET=your_webhook_secret

# Optional customization settings:
WHATSAPP_RETRY_MAX=3
WHATSAPP_RETRY_BACKOFF=2
WHATSAPP_LOGGING=true
WHATSAPP_LOG_LEVEL=error
```

## Usage

### Sending a Text Message

```php
use BiztechEG\WhatsAppCloudApi\WhatsAppClient;
use BiztechEG\WhatsAppCloudApi\Messages\TextMessage;

$client = app(WhatsAppClient::class);
$message = new TextMessage('+1234567890', 'Hello, World!');
$response = $client->sendMessage($message);
```

### Sending an Interactive Message and Waiting for a Response

```php
use BiztechEG\WhatsAppCloudApi\Messages\InteractiveMessage;

$interactiveMessage = new InteractiveMessage('+1234567890', [
    'header' => [
        'type' => 'text',
        'text' => 'Choose an option:'
    ],
    'body' => [
        'text' => 'Please select one of the options below:'
    ],
    'footer' => [
        'text' => 'Footer text'
    ],
    'action' => [
        'buttons' => [
            [
                'type' => 'reply',
                'reply' => [
                    'id' => 'option_1',
                    'title' => 'Option 1'
                ]
            ],
            [
                'type' => 'reply',
                'reply' => [
                    'id' => 'option_2',
                    'title' => 'Option 2'
                ]
            ]
        ]
    ]
]);

$sessionId = $client->sendInteractiveMessageAndWait($interactiveMessage);
```

### Webhook Setup

Configure your webhook URL in your WhatsApp Cloud API settings. The package provides a default webhook endpoint at:

```
POST /webhook/whatsapp
```

send PDF invoice
```php

use BiztechEG\WhatsAppCloudApi\Invoices\InvoiceGenerator;
use BiztechEG\WhatsAppCloudApi\Messages\DocumentMessage;
use BiztechEG\WhatsAppCloudApi\WhatsAppClient;

// HTML content for the invoice
$html = view('invoices.invoice', ['order' => $orderData])->render();

// Generate PDF invoice and get its URL
$pdfUrl = InvoiceGenerator::generateInvoice($html);

// Create a DocumentMessage with the PDF URL (assuming DocumentMessage supports a 'link')
$message = new DocumentMessage('+1234567890', $pdfUrl, 'Your invoice', 'invoice.pdf');

$client = app(WhatsAppClient::class);
$response = $client->sendMessage($message);

```


The package verifies incoming webhook requests using the `WHATSAPP_WEBHOOK_SECRET` and sanitizes all data. Customize the handling logic in `BiztechEG\WhatsAppCloudApi\Http\Controllers\WhatsAppWebhookController`.

## Testing

Run the unit tests with PHPUnit:

```bash
vendor/bin/phpunit
```

## Continuous Integration

A sample GitHub Actions workflow is included in `.github/workflows/ci.yml` for automatic testing on push and pull request events.

## Contributing

Contributions are welcome! Please fork the repository and submit a pull request with your improvements. For major changes, please open an issue first to discuss what you would like to change.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

For any issues or feature requests, please open an issue on the GitHub repository.
