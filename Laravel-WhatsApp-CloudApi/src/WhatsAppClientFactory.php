<?php

namespace BiztechEG\WhatsAppCloudApi;

use BiztechEG\WhatsAppCloudApi\WhatsAppClient;
use InvalidArgumentException;

class WhatsAppClientFactory
{
    /**
     * Create a WhatsAppClient instance based on a given account key.
     *
     * @param string $accountKey
     * @return WhatsAppClient
     * @throws InvalidArgumentException
     */
    public static function create(string $accountKey = 'default'): WhatsAppClient
    {
        $accounts = config('whatsapp.accounts');
        if (!isset($accounts[$accountKey])) {
            throw new InvalidArgumentException("WhatsApp account configuration for key '{$accountKey}' not found.");
        }
        $config = $accounts[$accountKey];
        return new WhatsAppClient($config);
    }
}
