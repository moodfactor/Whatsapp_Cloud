<?php

namespace BiztechEG\WhatsAppCloudApi\Facades;

use Illuminate\Support\Facades\Facade;

class WhatsApp extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \BiztechEG\WhatsAppCloudApi\WhatsAppClient::class;
    }
}
