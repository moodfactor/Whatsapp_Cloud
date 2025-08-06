<?php

namespace App\Services;

use BiztechEG\WhatsAppCloudApi\WhatsAppClient;
use BiztechEG\WhatsAppCloudApi\Messages\TextMessage;
use BiztechEG\WhatsAppCloudApi\Messages\TemplateMessage;
use BiztechEG\WhatsAppCloudApi\Messages\DocumentMessage;
use BiztechEG\WhatsAppCloudApi\Messages\ImageMessage;
use BiztechEG\WhatsAppCloudApi\Messages\VideoMessage;
use BiztechEG\WhatsAppCloudApi\Messages\AudioMessage;
use BiztechEG\WhatsAppCloudApi\Messages\InteractiveMessage;
use BiztechEG\WhatsAppCloudApi\Messages\LocationMessage;
use BiztechEG\WhatsAppCloudApi\Messages\ContactsMessage;
use BiztechEG\WhatsAppCloudApi\Jobs\SendWhatsAppMessageJob;
use BiztechEG\WhatsAppCloudApi\Models\WhatsAppMessage;
use BiztechEG\WhatsAppCloudApi\InteractiveSessionManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class MetaWhatsappService
{
    protected $whatsapp;
    protected $sessionManager;

    public function __construct(WhatsAppClient $whatsapp, InteractiveSessionManager $sessionManager)
    {
        $this->whatsapp = $whatsapp;
        $this->sessionManager = $sessionManager;
    }

    public function sendMessage($to, $message)
    {
        try {
            $cleanPhone = $this->cleanPhoneNumber($to);
            
            $textMessage = new TextMessage($cleanPhone, $message);
            $result = $this->whatsapp->sendMessage($textMessage);
            
            if (!$result) {
                Log::info('Text message failed, trying template message');
                $templateMessage = new TemplateMessage($cleanPhone, [
                    'name' => 'hello_world',
                    'language' => [
                        'code' => 'en_US'
                    ]
                ]);
                $result = $this->whatsapp->sendMessage($templateMessage);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('WhatsApp service error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendMediaMessage(string $to, string $mediaId, string $mediaType, string $caption = '')
    {
        try {
            $cleanPhone = $this->cleanPhoneNumber($to);
            
            $message = null;
            switch ($mediaType) {
                case 'image':
                    $message = new ImageMessage($cleanPhone, $mediaId, $caption);
                    break;
                case 'video':
                    $message = new VideoMessage($cleanPhone, $mediaId, $caption);
                    break;
                case 'document':
                    $message = new DocumentMessage($cleanPhone, $mediaId, $caption);
                    break;
                case 'audio':
                    $message = new AudioMessage($cleanPhone, $mediaId);
                    break;
            }

            if ($message) {
                return $this->whatsapp->sendMessage($message);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Media message error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendInteractiveMessage(string $to, array $interactive, bool $async = false)
    {
        try {
            $cleanPhone = $this->cleanPhoneNumber($to);
            $message = new InteractiveMessage($cleanPhone, $interactive);
            
            if ($async) {
                SendWhatsAppMessageJob::dispatch($message);
                return true;
            }
            
            return $this->whatsapp->sendMessage($message);
        } catch (\Exception $e) {
            Log::error('Interactive message error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendInteractiveMessageAndWait(string $to, array $interactive, int $timeoutSeconds = 30)
    {
        try {
            $cleanPhone = $this->cleanPhoneNumber($to);
            $message = new InteractiveMessage($cleanPhone, $interactive);
            
            return $this->whatsapp->sendInteractiveMessageAndWait($message, $timeoutSeconds);
        } catch (\Exception $e) {
            Log::error('Interactive message with wait error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendLocationMessage(string $to, float $latitude, float $longitude, string $name = '', string $address = '')
    {
        try {
            $cleanPhone = $this->cleanPhoneNumber($to);
            $locationData = [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'name' => $name,
                'address' => $address
            ];
            
            $message = new LocationMessage($cleanPhone, $locationData);
            return $this->whatsapp->sendMessage($message);
        } catch (\Exception $e) {
            Log::error('Location message error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendContactMessage(string $to, array $contacts)
    {
        try {
            $cleanPhone = $this->cleanPhoneNumber($to);
            $message = new ContactsMessage($cleanPhone, $contacts);
            
            return $this->whatsapp->sendMessage($message);
        } catch (\Exception $e) {
            Log::error('Contact message error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendMessageAsync(string $to, string $message)
    {
        try {
            $cleanPhone = $this->cleanPhoneNumber($to);
            $textMessage = new TextMessage($cleanPhone, $message);
            
            SendWhatsAppMessageJob::dispatch($textMessage);
            return true;
        } catch (\Exception $e) {
            Log::error('Async message error: ' . $e->getMessage());
            return false;
        }
    }

    public function getSessionResponse(string $sessionId)
    {
        try {
            return $this->sessionManager->getResponse($sessionId);
        } catch (\Exception $e) {
            Log::error('Session response error: ' . $e->getMessage());
            return null;
        }
    }

    public function createSession(string $phoneNumber, array $sessionData = [])
    {
        try {
            return $this->sessionManager->create($phoneNumber, $sessionData);
        } catch (\Exception $e) {
            Log::error('Create session error: ' . $e->getMessage());
            return null;
        }
    }

    public function logMessage(string $phoneNumber, string $messageId, string $content, string $type = 'text', string $direction = 'outbound')
    {
        try {
            WhatsAppMessage::create([
                'phone_number' => $phoneNumber,
                'message_id' => $messageId,
                'content' => $content,
                'type' => $type,
                'direction' => $direction,
                'status' => 'sent',
                'sent_at' => now()
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Message logging error: ' . $e->getMessage());
            return false;
        }
    }

    private function cleanPhoneNumber($phone)
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        $cleaned = ltrim($cleaned, '+');
        if (strlen($cleaned) >= 10) {
            if (!preg_match('/^20/', $cleaned)) {
                if (substr($cleaned, 0, 1) === '0') {
                    $cleaned = '20' . substr($cleaned, 1);
                } else {
                    $cleaned = '20' . $cleaned;
                }
            }
        }
        return $cleaned;
    }
}