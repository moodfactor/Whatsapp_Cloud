<!-- Simple Media Upload Modal (Pure Alpine.js fallback) -->
<script>
function mediaUploadModal() {
    return {
        isOpen: false,
        selectedFile: null,
        caption: '',
        conversationId: null,
        previewData: {
            type: null,
            url: null,
            filename: null,
            size: null,
            icon: null
        },
        isUploading: false,
        
        openDialog(convId) {
            console.log('Media modal openDialog called with:', convId);
            this.conversationId = convId;
            this.isOpen = true;
            this.resetForm();
            console.log('Modal state set to open:', this.isOpen);
        },
        
        closeDialog() {
            this.isOpen = false;
            this.resetForm();
        },
        
        resetForm() {
            this.selectedFile = null;
            this.caption = '';
            this.previewData = {
                type: null,
                url: null,
                filename: null,
                size: null,
                icon: null
            };
            this.isUploading = false;
        },
        
        handleFileSelection(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            // Validate file size (16MB)
            if (file.size > 16 * 1024 * 1024) {
                alert('File size must be less than 16MB');
                return;
            }
            
            this.selectedFile = file;
            this.generatePreview();
        },
        
        generatePreview() {
            if (!this.selectedFile) return;
            
            const extension = this.selectedFile.name.split('.').pop().toLowerCase();
            const size = this.formatFileSize(this.selectedFile.size);
            
            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previewData = {
                        type: 'image',
                        url: e.target.result,
                        filename: this.selectedFile.name,
                        size: size
                    };
                };
                reader.readAsDataURL(this.selectedFile);
            } else {
                this.previewData = {
                    type: 'document',
                    filename: this.selectedFile.name,
                    size: size,
                    icon: this.getFileIcon(extension)
                };
            }
        },
        
        getFileIcon(extension) {
            const icons = {
                'pdf': 'fa-file-pdf',
                'doc': 'fa-file-word',
                'docx': 'fa-file-word',
                'mp3': 'fa-file-audio',
                'mp4': 'fa-file-video',
                'default': 'fa-file'
            };
            return icons[extension] || icons.default;
        },
        
        formatFileSize(bytes) {
            const units = ['B', 'KB', 'MB', 'GB'];
            let size = bytes;
            let unitIndex = 0;
            while (size >= 1024 && unitIndex < units.length - 1) {
                size /= 1024;
                unitIndex++;
            }
            return Math.round(size * 100) / 100 + ' ' + units[unitIndex];
        },
        
        async uploadMedia() {
            if (!this.selectedFile || !this.conversationId) return;
            
            this.isUploading = true;
            
            try {
                const formData = new FormData();
                formData.append('media', this.selectedFile);
                formData.append('caption', this.caption);
                formData.append('conversation_id', this.conversationId);
                
                const response = await fetch('/api/whatsapp/upload-media', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Trigger page refresh or add message to UI
                    window.dispatchEvent(new CustomEvent('media-sent', { 
                        detail: result.message 
                    }));
                    this.closeDialog();
                } else {
                    alert('Failed to send media: ' + result.error);
                }
            } catch (error) {
                console.error('Upload error:', error);
                alert('Failed to upload media. Please try again.');
            } finally {
                this.isUploading = false;
            }
        }
    }
}
</script>

<div x-data="mediaUploadModal()" 
     x-cloak
     x-init="console.log('Media modal initialized');"
     @open-media-dialog.window="openDialog($event.detail.conversationId)">

    <!-- File Input (Hidden) -->
    <input type="file" 
           x-ref="fileInput"
           @change="handleFileSelection($event)"
           accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.mp3,.mp4,.wav,.ogg,.m4a,.3gp"
           style="display: none;">

    <!-- Media Preview Dialog -->
    <div x-show="isOpen" 
         class="fixed inset-0 flex items-center justify-center overflow-y-auto"
         style="z-index: 9999; background: rgba(0,0,0,0.5);"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <!-- Dialog container -->
        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[80vh] overflow-hidden m-4"
             @click.away="closeDialog()">
            
            <!-- Dialog Header -->
            <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-lg font-semibold">Send Media</h3>
                <button @click="closeDialog()" class="p-2 hover:bg-gray-100 rounded-full">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Dialog Body -->
            <div class="p-6 overflow-y-auto">
                <div x-show="!selectedFile" class="text-center py-10">
                    <button @click="$refs.fileInput.click()" class="bg-green-500 text-white px-6 py-3 rounded-lg font-semibold text-lg">
                        <i class="fas fa-upload mr-2"></i> Choose File
                    </button>
                </div>

                <div x-show="selectedFile" class="space-y-4">
                    <!-- Preview Area -->
                    <div class="bg-gray-100 rounded-lg p-4 text-center">
                        <div x-show="previewData && previewData.type === 'image'">
                            <img :src="previewData.url" alt="Preview" class="max-h-60 mx-auto rounded-md">
                        </div>
                        <div x-show="previewData && previewData.type === 'document'" class="flex flex-col items-center space-y-3 py-4">
                            <i :class="`fas ${previewData.icon || 'fa-file'} text-5xl text-gray-500`"></i>
                            <p class="font-medium" x-text="previewData.filename || 'Document'"></p>
                            <p class="text-sm text-gray-500" x-text="previewData.size || ''"></p>
                        </div>
                    </div>

                    <!-- Change File Button -->
                    <div class="text-center">
                        <button @click="$refs.fileInput.click()" class="text-sm text-blue-600 hover:underline">Change file</button>
                    </div>

                    <!-- Caption Input -->
                    <div>
                        <textarea x-model="caption" rows="3" class="w-full p-2 border rounded-md" placeholder="Add a caption..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Dialog Footer -->
            <div x-show="selectedFile" class="flex justify-end p-4 border-t bg-gray-50">
                <button @click="closeDialog()" class="px-4 py-2 mr-2 bg-gray-200 rounded-lg" :disabled="isUploading">Cancel</button>
                <button @click="uploadMedia()" class="px-6 py-2 bg-green-500 text-white rounded-lg" :disabled="isUploading">
                    <span x-show="!isUploading">Send</span>
                    <span x-show="isUploading">Sending... <i class="fas fa-spinner fa-spin"></i></span>
                </button>
            </div>
        </div>
    </div>
</div>
