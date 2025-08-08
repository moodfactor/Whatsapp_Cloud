<?php
namespace BiztechEG\WhatsAppCloudApi\Messages;

class TextMessage implements MessageInterface
{
    protected string $recipient;
    protected string $text;

    public function __construct(string $recipient, string $text)
    {
        $this->recipient = $recipient;
        $this->text = $text;
    }

    public function toArray(): array
    {
        return [
            'messaging_product' => 'whatsapp',
            'to' => $this->recipient,
            'type' => 'text',
            'text' => [
                'body' => $this->text,
            ],
        ];
    }
}
