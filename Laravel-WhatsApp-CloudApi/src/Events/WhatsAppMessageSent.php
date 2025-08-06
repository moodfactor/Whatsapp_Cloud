<?php

namespace BiztechEG\WhatsAppCloudApi\Events;

class WhatsAppMessageSent
{
    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
}
