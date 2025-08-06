<?php

namespace BiztechEG\WhatsAppCloudApi\Messages;

class InteractiveMessage implements MessageInterface
{
    protected string $recipient;
    protected array $interactiveContent;
    protected ?string $sessionId = null;

    public function __construct(string $recipient, array $interactiveContent)
    {
        $this->recipient = $recipient;
        $this->interactiveContent = $interactiveContent;
    }

    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function toArray(): array
    {
        $payload = [
            'to'   => $this->recipient,
            'type' => 'interactive',
            'interactive' => $this->interactiveContent,
        ];

        // Include session id as custom metadata if available.
        if ($this->sessionId) {
            $payload['metadata'] = ['session_id' => $this->sessionId];
        }

        return $payload;
    }
}
