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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Upload media file to Meta WhatsApp API and get media ID
     */
    public function uploadMediaFile(UploadedFile $file): array
    {
        try {
            $accessToken = config('whatsapp.access_token');
            $phoneNumberId = config('whatsapp.phone_number_id');
            
            if (!$accessToken || !$phoneNumberId) {
                throw new \Exception('WhatsApp configuration missing');
            }

            // Prepare the media upload request
            $response = Http::withToken($accessToken)
                ->attach('file', file_get_contents($file->path()), $file->getClientOriginalName())
                ->post("https://graph.facebook.com/v17.0/{$phoneNumberId}/media", [
                    'type' => $file->getMimeType(),
                    'messaging_product' => 'whatsapp'
                ]);

            $responseData = $response->json();
            
            if (!$response->successful() || !isset($responseData['id'])) {
                Log::error('Media upload failed', ['response' => $responseData]);
                throw new \Exception('Failed to upload media to Meta API: ' . ($responseData['error']['message'] ?? 'Unknown error'));
            }

            return [
                'media_id' => $responseData['id'],
                'success' => true
            ];

        } catch (\Exception $e) {
            Log::error('Media upload exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send media message with uploaded media ID
     */
    public function sendMediaMessageWithId(string $to, string $mediaId, string $mediaType, string $caption = '', string $filename = null): array
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
                    $message = new DocumentMessage($cleanPhone, $mediaId, $caption, $filename);
                    break;
                case 'audio':
                    $message = new AudioMessage($cleanPhone, $mediaId);
                    break;
            }

            if (!$message) {
                throw new \Exception('Unsupported media type: ' . $mediaType);
            }

            $result = $this->whatsapp->sendMessage($message);
            
            if ($result) {
                return [
                    'success' => true,
                    'message_id' => is_array($result) && isset($result['messages']) ? $result['messages'][0]['id'] ?? null : null,
                    'result' => $result
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'WhatsApp API returned false'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Send media message error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Download media from WhatsApp API
     */
    public function downloadMedia(string $mediaId): array
    {
        try {
            $accessToken = config('whatsapp.access_token');
            
            if (!$accessToken) {
                throw new \Exception('WhatsApp access token not configured');
            }

            // First, get the media URL
            $response = Http::withToken($accessToken)
                ->get("https://graph.facebook.com/v17.0/{$mediaId}");

            if (!$response->successful()) {
                throw new \Exception('Failed to get media URL from Meta API');
            }

            $mediaData = $response->json();
            $mediaUrl = $mediaData['url'] ?? null;

            if (!$mediaUrl) {
                throw new \Exception('Media URL not found in API response');
            }

            // Download the actual media file
            $mediaResponse = Http::withToken($accessToken)->get($mediaUrl);

            if (!$mediaResponse->successful()) {
                throw new \Exception('Failed to download media file');
            }

            // Generate filename
            $extension = $this->getFileExtensionFromMimeType($mediaData['mime_type'] ?? 'application/octet-stream');
            $filename = 'whatsapp_' . $mediaId . '_' . time() . '.' . $extension;
            
            // Store the file
            $path = 'whatsapp_media/' . $filename;
            Storage::disk('public')->put($path, $mediaResponse->body());
            
            return [
                'success' => true,
                'local_path' => $path,
                'full_url' => Storage::disk('public')->url($path),
                'filename' => $filename,
                'mime_type' => $mediaData['mime_type'] ?? 'application/octet-stream',
                'file_size' => $mediaData['file_size'] ?? strlen($mediaResponse->body())
            ];

        } catch (\Exception $e) {
            Log::error('Media download error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get file extension from MIME type
     */
    private function getFileExtensionFromMimeType(string $mimeType): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/3gpp' => '3gp',
            'audio/mpeg' => 'mp3',
            'audio/mp4' => 'm4a',
            'audio/amr' => 'amr',
            'audio/ogg' => 'ogg',
            'application/pdf' => 'pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'text/plain' => 'txt'
        ];

        return $extensions[$mimeType] ?? 'bin';
    }

    /**
     * Get media type from file extension
     */
    public function getMediaTypeFromExtension(string $extension): string
    {
        $extension = strtolower($extension);
        
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return 'image';
        } elseif (in_array($extension, ['mp4', '3gp', 'avi', 'mov'])) {
            return 'video';
        } elseif (in_array($extension, ['mp3', 'm4a', 'amr', 'ogg', 'wav'])) {
            return 'audio';
        } else {
            return 'document';
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

    /**
     * Test WhatsApp access token validity
     */
    public function testAccessToken(string $token = null): array
    {
        try {
            $accessToken = $token ?: config('whatsapp.access_token');
            $phoneNumberId = config('whatsapp.phone_number_id');
            
            if (!$accessToken || !$phoneNumberId) {
                throw new \Exception('WhatsApp configuration missing');
            }

            // Test token by making a request to get phone number info
            $response = Http::withToken($accessToken)
                ->get("https://graph.facebook.com/v17.0/{$phoneNumberId}");

            $responseData = $response->json();
            
            if (!$response->successful()) {
                $errorMessage = $responseData['error']['message'] ?? 'Unknown error';
                
                // Check if error is specifically about token expiration
                if (isset($responseData['error']['code']) && $responseData['error']['code'] == 190) {
                    return [
                        'success' => false,
                        'error' => 'Token expired: ' . $errorMessage,
                        'expired' => true
                    ];
                }
                
                return [
                    'success' => false,
                    'error' => $errorMessage
                ];
            }

            // Token is valid, try to get expiration info if available
            $expiresAt = null;
            if (isset($responseData['data_access_expires_at'])) {
                $expiresAt = date('Y-m-d H:i:s', $responseData['data_access_expires_at']);
            }

            return [
                'success' => true,
                'message' => 'Token is valid',
                'expires_at' => $expiresAt,
                'phone_info' => $responseData
            ];

        } catch (\Exception $e) {
            Log::error('Token test error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}