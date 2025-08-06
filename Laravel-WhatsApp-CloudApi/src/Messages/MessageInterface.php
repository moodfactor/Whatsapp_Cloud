<?php
namespace BiztechEG\WhatsAppCloudApi\Messages;

interface MessageInterface
{
    /**
     * Convert the message to an array that conforms with the API payload.
     */
    public function toArray(): array;
}
