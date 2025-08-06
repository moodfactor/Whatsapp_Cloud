<?php

namespace BiztechEG\WhatsAppCloudApi\Messages;

class AudioMessage implements MessageInterface
{
    protected string $recipient;
    protected string $audioUrl;

    public function __construct(string $recipient, string $audioUrl)
    {
        $this->recipient = $recipient;
        $this->audioUrl  = $audioUrl;
    }

    public function toArray(): array
    {
        return [
            'to'   => $this->recipient,
            'type' => 'audio',
            'audio' => [
                'link' => $this->audioUrl,
            ],
        ];
    }
}
