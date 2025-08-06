<?php

namespace BiztechEG\WhatsAppCloudApi\Messages;

class ContactsMessage implements MessageInterface
{
    protected string $recipient;
    protected array $contacts;

    /**
     * @param string $recipient
     * @param array  $contacts An array of contacts, each formatted per WhatsApp API specification
     */
    public function __construct(string $recipient, array $contacts)
    {
        $this->recipient = $recipient;
        $this->contacts  = $contacts;
    }

    public function toArray(): array
    {
        return [
            'to'   => $this->recipient,
            'type' => 'contacts',
            'contacts' => $this->contacts,
        ];
    }
}
