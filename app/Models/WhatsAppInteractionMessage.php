<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppInteractionMessage extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_interaction_messages';

    protected $fillable = [
        'interaction_id', 'message', 'type', 'nature', 'time_sent', 'status', 'file_url'
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'interaction_id');
    }
}
