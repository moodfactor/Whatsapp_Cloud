<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Conversation;
use App\Services\MetaWhatsappService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MediaPreviewDialog extends Component
{
    use WithFileUploads;

    public $conversationId;
    public $selectedFile;
    public $caption = '';
    public $isOpen = false;
    public $previewData = null;
    public $isUploading = false;

    protected $listeners = [
        'fileSelected' => 'handleFileSelected',
        'openMediaDialog' => 'openDialog',
        'closeMediaDialog' => 'closeDialog'
    ];

    protected $rules = [
        'selectedFile' => 'required|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,mp3,mp4,wav,ogg,m4a,3gp|max:16384',
        'caption' => 'nullable|string|max:1000',
        'conversationId' => 'required|exists:whatsapp_interactions,id'
    ];

    public function mount($conversationId = null)
    {
        $this->conversationId = $conversationId;
    }

    public function openDialog($conversationId)
    {
        $this->conversationId = $conversationId;
        $this->isOpen = true;
        $this->resetForm();
        $this->emit('mediaDialogOpened');
    }

    public function closeDialog()
    {
        $this->isOpen = false;
        $this->resetForm();
        $this->emit('mediaDialogClosed');
    }

    public function handleFileSelected($file)
    {
        $this->selectedFile = $file;
        $this->generatePreviewData();
    }

    public function updatedSelectedFile()
    {
        $this->validate(['selectedFile' => 'required|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,mp3,mp4,wav,ogg,m4a,3gp|max:16384']);
        $this->generatePreviewData();
    }

    public function sendMedia()
    {
        $this->validate();

        $admin = Auth::guard('whatsapp_admin')->user();
        
        if (!$admin) {
            $this->emit('showError', 'Not authenticated');
            return;
        }

        $conversation = Conversation::findOrFail($this->conversationId);
        
        // Check permissions
        if (!in_array($admin->role, ['super_admin', 'admin']) && $conversation->assigned_to !== $admin->id) {
            $this->emit('showError', 'Access denied');
            return;
        }

        try {
            $this->isUploading = true;
            
            $whatsappService = app(MetaWhatsappService::class);
            
            // Get media type from file extension
            $mediaType = $whatsappService->getMediaTypeFromExtension($this->selectedFile->getClientOriginalExtension());
            
            // Upload file to Meta WhatsApp API
            $uploadResult = $whatsappService->uploadMediaFile($this->selectedFile);
            
            if (!$uploadResult['success']) {
                throw new \Exception('Failed to upload media: ' . $uploadResult['error']);
            }
            
            // Send media message via WhatsApp API
            $sendResult = $whatsappService->sendMediaMessageWithId(
                $conversation->decrypted_phone,
                $uploadResult['media_id'],
                $mediaType,
                $this->caption,
                $this->selectedFile->getClientOriginalName()
            );
            
            if (!$sendResult['success']) {
                throw new \Exception('Failed to send media: ' . $sendResult['error']);
            }
            
            // Store file locally for reference
            $localFilename = time() . '_' . uniqid() . '.' . $this->selectedFile->getClientOriginalExtension();
            $localPath = $this->selectedFile->storeAs('whatsapp_media', $localFilename, 'public');
            $localUrl = Storage::disk('public')->url($localPath);
            
            // Create message record
            $message = \App\Models\WhatsAppInteractionMessage::create([
                'interaction_id' => $conversation->id,
                'message' => $this->caption ?: $this->selectedFile->getClientOriginalName(),
                'type' => $mediaType,
                'nature' => 'sent',
                'status' => 'sent',
                'url' => $localUrl,
                'filename' => $this->selectedFile->getClientOriginalName(),
                'mime_type' => $this->selectedFile->getMimeType(),
                'file_size' => $this->selectedFile->getSize(),
                'time_sent' => now(),
                'whatsapp_message_id' => $sendResult['message_id'] ?? null
            ]);
            
            // Update conversation
            $conversation->update([
                'last_message' => $this->caption ?: '[' . ucfirst($mediaType) . ']',
                'last_msg_time' => now()
            ]);

            $this->emit('mediaSent', [
                'id' => $message->id,
                'text' => $message->message,
                'type' => 'sent',
                'message_type' => $message->type,
                'time' => \Carbon\Carbon::parse($message->time_sent)->format('H:i'),
                'media_url' => $message->url,
                'filename' => $message->filename,
                'status' => 'sent'
            ]);

            $this->closeDialog();
            
        } catch (\Exception $e) {
            \Log::error('Livewire media send error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->emit('showError', 'Failed to send media: ' . $e->getMessage());
        } finally {
            $this->isUploading = false;
        }
    }

    public function resetForm()
    {
        $this->selectedFile = null;
        $this->caption = '';
        $this->previewData = null;
        $this->isUploading = false;
        $this->resetErrorBag();
    }

    private function generatePreviewData()
    {
        if (!$this->selectedFile) {
            $this->previewData = null;
            return;
        }

        $extension = strtolower($this->selectedFile->getClientOriginalExtension());
        $mimeType = $this->selectedFile->getMimeType();
        $size = $this->formatFileSize($this->selectedFile->getSize());

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $this->previewData = [
                'type' => 'image',
                'url' => $this->selectedFile->temporaryUrl(),
                'filename' => $this->selectedFile->getClientOriginalName(),
                'size' => $size,
                'extension' => $extension
            ];
        } elseif (in_array($extension, ['mp4', '3gp', 'avi', 'mov'])) {
            $this->previewData = [
                'type' => 'video',
                'filename' => $this->selectedFile->getClientOriginalName(),
                'size' => $size,
                'extension' => $extension,
                'icon' => 'video'
            ];
        } elseif (in_array($extension, ['mp3', 'm4a', 'amr', 'ogg', 'wav'])) {
            $this->previewData = [
                'type' => 'audio',
                'filename' => $this->selectedFile->getClientOriginalName(),
                'size' => $size,
                'extension' => $extension,
                'icon' => 'music'
            ];
        } else {
            $this->previewData = [
                'type' => 'document',
                'filename' => $this->selectedFile->getClientOriginalName(),
                'size' => $size,
                'extension' => $extension,
                'icon' => $this->getDocumentIcon($extension)
            ];
        }
    }

    private function getDocumentIcon($extension)
    {
        return match($extension) {
            'pdf' => 'file-pdf',
            'doc', 'docx' => 'file-word',
            'xls', 'xlsx' => 'file-excel',
            'ppt', 'pptx' => 'file-powerpoint',
            'txt' => 'file-text',
            default => 'file'
        };
    }

    private function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function render()
    {
        return view('livewire.media-preview-dialog');
    }
}