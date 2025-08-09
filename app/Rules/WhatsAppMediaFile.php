<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;

class WhatsAppMediaFile implements Rule
{
    protected $message = 'Invalid file provided.';

    public function passes($attribute, $value)
    {
        if (!($value instanceof UploadedFile)) {
            return false;
        }

        $mimeType = $value->getMimeType();
        $size = $value->getSize(); // Size in bytes

        // Image validation
        if (str_starts_with($mimeType, 'image/')) {
            if (!in_array($mimeType, ['image/jpeg', 'image/png'])) {
                $this->message = 'The uploaded image must be a JPEG or PNG file.';
                return false;
            }
            if ($size > 5 * 1024 * 1024) { // 5MB
                $this->message = 'Image size cannot exceed 5MB.';
                return false;
            }
            return true;
        }

        // Video validation
        if (str_starts_with($mimeType, 'video/')) {
            if (!in_array($mimeType, ['video/mp4', 'video/3gpp'])) {
                $this->message = 'The uploaded video must be an MP4 or 3GPP file.';
                return false;
            }
            if ($size > 16 * 1024 * 1024) { // 16MB
                $this->message = 'Video size cannot exceed 16MB.';
                return false;
            }
            return true;
        }

        // Audio validation
        if (str_starts_with($mimeType, 'audio/')) {
            if (!in_array($mimeType, ['audio/aac', 'audio/mp4', 'audio/mpeg', 'audio/amr', 'audio/ogg'])) {
                $this->message = 'The uploaded audio must be an AAC, MP4, MP3, AMR, or OGG file.';
                return false;
            }
            if ($size > 16 * 1024 * 1024) { // 16MB
                $this->message = 'Audio size cannot exceed 16MB.';
                return false;
            }
            return true;
        }

        // Document validation
        if ($size > 100 * 1024 * 1024) { // 100MB
            $this->message = 'Document size cannot exceed 100MB.';
            return false;
        }

        return true; // Default to true for other document types within size limit
    }

    public function message()
    {
        return $this->message;
    }
}