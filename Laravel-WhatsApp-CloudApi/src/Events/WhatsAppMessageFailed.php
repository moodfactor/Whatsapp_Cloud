<?php

namespace BiztechEG\WhatsAppCloudApi\Events;

class WhatsAppMessageFailed
{
    public array $payload;
    public string $error;

    public function __construct(array $payload, string $error)
    {
        $this->payload = $payload;
        $this->error   = $error;
    }
}
