<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use App\Services\CountryService;

class Conversation extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_interactions';

    protected $fillable = ["name", "wa_no", "wa_no_id", "receiver_id", "last_message", "last_msg_time", "time_sent", "type", "type_id", "unread", "assigned_to", "status"];

    protected $appends = ['contact_name', 'contact_phone', 'decrypted_phone'];
    
    protected $casts = [
        'last_msg_time' => 'datetime',
        'time_sent' => 'datetime',
        'unread' => 'integer'
    ];

    public function messages()
    {
        return $this->hasMany(\App\Models\WhatsAppInteractionMessage::class, 'interaction_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(\App\Models\Admin\Admins::class, 'assigned_to');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Virtual attribute for contact_name (maps to name field)
     */
    public function getContactNameAttribute(): string
    {
        return $this->name ?? 'Unknown';
    }

    /**
     * Virtual attribute for contact_phone (maps to wa_no field)
     */
    public function getContactPhoneAttribute(): string
    {
        // Try both wa_no and receiver_id fields for compatibility
        return $this->wa_no ?? $this->receiver_id ?? '';
    }

    /**
     * Get decrypted phone number safely
     */
    public function getDecryptedPhoneAttribute(): string
    {
        // Try both wa_no and receiver_id for compatibility
        $phone = $this->wa_no ?? $this->receiver_id ?? '';
        
        if (empty($phone)) {
            return '';
        }
        
        try {
            // Check if it looks encrypted (base64 pattern)
            if (strpos($phone, 'eyJpdiI6') === 0) {
                return Crypt::decryptString($phone);
            } else {
                // Return as plain text if not encrypted
                return $phone;
            }
        } catch (\Exception $e) {
            // If decryption fails, assume it's plain text
            return $phone;
        }
    }

    /**
     * Get normalized phone number
     */
    public function getNormalizedPhoneAttribute(): string
    {
        return CountryService::normalizePhoneNumber($this->getDecryptedPhoneAttribute());
    }

    /**
     * Get country information
     */
    public function getCountryInfoAttribute(): array
    {
        return CountryService::getCountryFromPhone($this->getDecryptedPhoneAttribute());
    }

    /**
     * Get display name with country flag and name
     */
    public function getDisplayNameAttribute(): string
    {
        return CountryService::getDisplayName(
            $this->getDecryptedPhoneAttribute(), 
            $this->contact_name
        );
    }

    /**
     * Get masked phone number for privacy
     */
    public function getDisplayPhoneAttribute(): string
    {
        return CountryService::getMaskedPhone($this->getDecryptedPhoneAttribute());
    }

    /**
     * Get the last message in this conversation
     */
    public function getLastMessageAttribute()
    {
        // Use the last_message field from the database or get from messages relationship
        if (isset($this->attributes['last_message']) && !empty($this->attributes['last_message'])) {
            return $this->attributes['last_message'];
        }
        
        try {
            $latestMessage = $this->messages()->latest('time_sent')->first();
            return $latestMessage ? $latestMessage->message : '';
        } catch (\Exception $e) {
            return $this->attributes['last_message'] ?? '';
        }
    }

    /**
     * Check if conversation is from Arab country
     */
    public function getIsArabCountryAttribute(): bool
    {
        return CountryService::isArabCountry($this->getDecryptedPhoneAttribute());
    }

    /**
     * Find or create conversation by phone number (prevents duplicates)
     */
    public static function findOrCreateByPhone(string $phone, string $contactName = 'Unknown'): self
    {
        $normalizedPhone = CountryService::normalizePhoneNumber($phone);
        
        // Try to find existing conversation with this phone number
        // Check both wa_no and receiver_id fields
        $existing = self::where(function($query) use ($normalizedPhone, $phone) {
            $query->where('wa_no', $normalizedPhone)
                  ->orWhere('wa_no', $phone)
                  ->orWhere('receiver_id', $normalizedPhone)
                  ->orWhere('receiver_id', $phone);
            
            // Also try encrypted versions
            try {
                $encryptedNormalized = Crypt::encryptString($normalizedPhone);
                $encryptedOriginal = Crypt::encryptString($phone);
                $query->orWhere('wa_no', $encryptedNormalized)
                      ->orWhere('wa_no', $encryptedOriginal)
                      ->orWhere('receiver_id', $encryptedNormalized)
                      ->orWhere('receiver_id', $encryptedOriginal);
            } catch (\Exception $e) {
                // Ignore encryption errors
            }
        })->first();

        if ($existing) {
            return $existing;
        }

        // Create new conversation with normalized phone
        return self::create([
            'receiver_id' => $normalizedPhone, // Store in receiver_id for consistency
            'name' => $contactName,
            'status' => 'new',
            'last_msg_time' => now()
        ]);
    }
}
