<?php

namespace BiztechEG\WhatsAppCloudApi;

use BiztechEG\WhatsAppCloudApi\Messages\MessageInterface;
use BiztechEG\WhatsAppCloudApi\Messages\InteractiveMessage;
use BiztechEG\WhatsAppCloudApi\Exceptions\WhatsAppException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use BiztechEG\WhatsAppCloudApi\InteractiveSessionManager;
use BiztechEG\WhatsAppCloudApi\Models\WhatsAppMessage;
use Illuminate\Support\Str;

class WhatsAppClient
{
    protected array $config;
    protected Client $http;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->http = new Client([
            'base_uri' => $this->config['api_url'],
        ]);
    }

    /**
     * Sends a message using the WhatsApp Cloud API with retry logic.
     *
     * @param MessageInterface $message
     * @return array
     * @throws WhatsAppException
     */
    public function sendMessage(MessageInterface $message): array
    {
        $payload = $message->toArray();
        $this->validatePayload($payload);

        $url = sprintf('%s/%s/messages', $this->config['api_url'], $this->config['phone_number_id']);
        $attempt = 0;
        $maxAttempts = 3;

        while ($attempt < $maxAttempts) {
            try {
                $response = $this->http->post($url, [
                    'json'    => $payload,
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->config['access_token'],
                        'Content-Type'  => 'application/json',
                    ],
                ]);
                $result = json_decode((string)$response->getBody(), true);

                // Log the outbound message
                WhatsAppMessage::create([
                    'id' => (string) Str::uuid(),
                    'session_id' => $payload['metadata']['session_id'] ?? null,
                    'direction' => 'outbound',
                    'recipient' => $payload['to'],
                    'message_type' => $payload['type'],
                    'payload' => $payload,
                ]);

                return $result;
            } catch (GuzzleException $e) {
                $attempt++;
                Log::error("WhatsApp API error on attempt {$attempt}", [
                    'error'   => $e->getMessage(),
                    'payload' => $payload,
                ]);
                if ($attempt >= $maxAttempts) {
                    throw new WhatsAppException("Error sending message after {$maxAttempts} attempts: " . $e->getMessage());
                }
                sleep(1); // Simple backoff before retrying
            }
        }
        return [];
    }

    /**
     * Sends an interactive message and starts an interactive session.
     *
     * @param InteractiveMessage $message
     * @return string The generated session ID for correlating the response.
     * @throws WhatsAppException
     */
    public function sendInteractiveMessageAndWait(InteractiveMessage $message): string
    {
        // Start a new interactive session using the database.
        $sessionId = InteractiveSessionManager::startSession($message->getRecipient(), $message->toArray());

        // Attach session id to the message payload.
        $message->setSessionId($sessionId);

        // Send the interactive message.
        $this->sendMessage($message);

        return $sessionId;
    }

    /**
     * Validates the payload.
     *
     * @param array $payload
     * @throws WhatsAppException
     */
    protected function validatePayload(array $payload): void
    {
        if (empty($payload['to']) || empty($payload['type'])) {
            throw new WhatsAppException('Invalid payload: Missing required "to" or "type" field.');
        }
    }
}
