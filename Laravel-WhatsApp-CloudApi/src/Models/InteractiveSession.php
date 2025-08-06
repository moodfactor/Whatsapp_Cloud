<?php

namespace BiztechEG\WhatsAppCloudApi\Models;

use Illuminate\Database\Eloquent\Model;

class InteractiveSession extends Model
{
    protected $table = 'interactive_sessions';

    protected $primaryKey = 'session_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'session_id', 'recipient', 'status', 'message_payload', 'response'
    ];

    protected $casts = [
        'message_payload' => 'array',
        'response' => 'array',
    ];
}
