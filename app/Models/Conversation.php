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

    protected $fillable = ["name", "receiver_id", "last_message", "last_msg_time", "unread", "assigned_to", "status", "metadata"];

    protected $appends = ['contact_name', 'contact_phone', 'decrypted_phone'];
    
    protected $casts = [
        'last_msg_time' => 'datetime',
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
        return $this->belongsTo(\App\Models\WhatsappAdmin::class, 'assigned_to');
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
     * Virtual attribute for contact_phone (maps to receiver_id field)
     */
    public function getContactPhoneAttribute(): string
    {
        return $this->receiver_id ?? '';
    }

    /**
     * Get decrypted phone number safely
     */
    public function getDecryptedPhoneAttribute(): string
    {
        $phone = $this->receiver_id ?? '';
        
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
        // Only check receiver_id field since wa_no doesn't exist in the database
        $existing = self::where(function($query) use ($normalizedPhone, $phone) {
            $query->where('receiver_id', $normalizedPhone)
                  ->orWhere('receiver_id', $phone);
            
            // Also try encrypted versions
            try {
                $encryptedNormalized = Crypt::encryptString($normalizedPhone);
                $encryptedOriginal = Crypt::encryptString($phone);
                $query->orWhere('receiver_id', $encryptedNormalized)
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
            'receiver_id' => $normalizedPhone,
            'name' => $contactName,
            'status' => 'new',
            'last_msg_time' => now()
        ]);
    }
}