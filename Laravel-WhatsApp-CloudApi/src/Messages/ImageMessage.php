<?php

namespace BiztechEG\WhatsAppCloudApi\Messages;

class ImageMessage implements MessageInterface
{
    protected string $recipient;
    protected string $imageUrl;
    protected ?string $caption;

    public function __construct(string $recipient, string $imageUrl, ?string $caption = null)
    {
        $this->recipient = $recipient;
        $this->imageUrl  = $imageUrl;
        $this->caption   = $caption;
    }

    public function toArray(): array
    {
        $payload = [
            'to'   => $this->recipient,
            'type' => 'image',
            'image' => [
                'link' => $this->imageUrl,
            ],
        ];
        if ($this->caption) {
            $payload['image']['caption'] = $this->caption;
        }
        return $payload;
    }
}
