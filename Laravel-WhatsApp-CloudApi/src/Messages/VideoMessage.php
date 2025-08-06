<?php

namespace BiztechEG\WhatsAppCloudApi\Messages;

class VideoMessage implements MessageInterface
{
    protected string $recipient;
    protected string $videoUrl;
    protected ?string $caption;

    public function __construct(string $recipient, string $videoUrl, ?string $caption = null)
    {
        $this->recipient = $recipient;
        $this->videoUrl  = $videoUrl;
        $this->caption   = $caption;
    }

    public function toArray(): array
    {
        $payload = [
            'to'   => $this->recipient,
            'type' => 'video',
            'video' => [
                'link' => $this->videoUrl,
            ],
        ];
        if ($this->caption) {
            $payload['video']['caption'] = $this->caption;
        }
        return $payload;
    }
}
