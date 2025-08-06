<?php

namespace BiztechEG\WhatsAppCloudApi\Messages;

class LocationMessage implements MessageInterface
{
    protected string $recipient;
    protected float $latitude;
    protected float $longitude;
    protected ?string $name;
    protected ?string $address;

    public function __construct(
        string $recipient,
        float $latitude,
        float $longitude,
        ?string $name = null,
        ?string $address = null
    ) {
        $this->recipient = $recipient;
        $this->latitude  = $latitude;
        $this->longitude = $longitude;
        $this->name      = $name;
        $this->address   = $address;
    }

    public function toArray(): array
    {
        $payload = [
            'to'   => $this->recipient,
            'type' => 'location',
            'location' => [
                'latitude'  => $this->latitude,
                'longitude' => $this->longitude,
            ],
        ];
        if ($this->name) {
            $payload['location']['name'] = $this->name;
        }
        if ($this->address) {
            $payload['location']['address'] = $this->address;
        }
        return $payload;
    }
}
