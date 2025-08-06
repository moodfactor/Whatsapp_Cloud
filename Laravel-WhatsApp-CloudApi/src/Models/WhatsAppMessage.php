<?php

namespace BiztechEG\WhatsAppCloudApi\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'session_id',
        'direction',
        'recipient',
        'message_type',
        'payload',
        'delivery_status',
        'delivered_at',
        'read_at'
    ];

    protected $casts = [
        // The payload will be automatically encrypted when stored and decrypted when retrieved.
        'payload' => 'encrypted:array',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];
}
