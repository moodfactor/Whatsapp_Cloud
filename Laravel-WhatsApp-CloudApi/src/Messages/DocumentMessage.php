<?php

namespace BiztechEG\WhatsAppCloudApi\Messages;

class DocumentMessage implements MessageInterface
{
    protected string $recipient;
    protected string $documentUrl;
    protected ?string $caption;
    protected ?string $filename;

    public function __construct(string $recipient, string $documentUrl, ?string $caption = null, ?string $filename = null)
    {
        $this->recipient   = $recipient;
        $this->documentUrl = $documentUrl;
        $this->caption     = $caption;
        $this->filename    = $filename;
    }

    public function toArray(): array
    {
        $payload = [
            'to'   => $this->recipient,
            'type' => 'document',
            'document' => [
                'link' => $this->documentUrl,
            ],
        ];
        if ($this->caption) {
            $payload['document']['caption'] = $this->caption;
        }
        if ($this->filename) {
            $payload['document']['filename'] = $this->filename;
        }
        return $payload;
    }
}
