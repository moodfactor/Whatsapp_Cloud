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
            window.isMediaModalOpen = true;
            this.resetForm();
            console.log('Modal state set to open:', this.isOpen);
        },
        
        closeDialog() {
            this.isOpen = false;
            window.isMediaModalOpen = false;
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
            console.log('File selected:', file ? file.name : 'none');
            console.log('Modal open state before file selection:', this.isOpen);
            console.log('Global modal state:', window.isMediaModalOpen);
            
            if (!file) return;
            
            if (file.size > 16 * 1024 * 1024) {
                alert('File size must be less than 16MB');
                return;
            }
            
            this.selectedFile = file;
            console.log('Generating preview for:', file.name);
            this.generatePreview();
            
            // Force modal to stay open
            setTimeout(() => {
                console.log('Modal state after file selection:', this.isOpen);
                if (!this.isOpen) {
                    console.log('Modal was closed, reopening...');
                    this.isOpen = true;
                    window.isMediaModalOpen = true;
                }
            }, 100);
        },
        
        generatePreview() {
            if (!this.selectedFile) return;
            
            const extension = this.selectedFile.name.split('.').pop().toLowerCase();
            const size = this.formatFileSize(this.selectedFile.size);
            
            console.log('Generating preview for extension:', extension);
            
            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previewData = {
                        type: 'image',
                        url: e.target.result,
                        filename: this.selectedFile.name,
                        size: size
                    };
                    console.log('Image preview generated');
                };
                reader.readAsDataURL(this.selectedFile);
            } else {
                this.previewData = {
                    type: 'document',
                    filename: this.selectedFile.name,
                    size: size,
                    icon: this.getFileIcon(extension)
                };
                console.log('Document preview generated:', this.previewData);
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

    <input type="file" 
           x-ref="fileInput"
           @change="handleFileSelection($event)"
           accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.mp3,.mp4,.wav,.ogg,.m4a,.3gp"
           style="display: none;">

    <div x-show="isOpen" 
         class="fixed inset-0 flex items-center justify-center overflow-y-auto"
         style="z-index: 99999 !important; background: rgba(0,0,0,0.8) !important; position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important;"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full max-h-[70vh] overflow-y-auto m-4"
             style="z-index: 100000 !important;"
             @click.outside="closeDialog()">
            
            <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-lg font-semibold">Send Media</h3>
                <button @click="closeDialog()" class="p-2 hover:bg-gray-100 rounded-full">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-6">
                <div x-show="!selectedFile" class="text-center py-8">
                    <div class="mb-4">
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                        <p class="text-gray-600 mb-4">Select a file to send</p>
                    </div>
                    <button @click.stop="$refs.fileInput.click()" 
                            class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <i class="fas fa-plus mr-2"></i> Choose File
                    </button>
                    <p class="text-xs text-gray-500 mt-2">Images, videos, documents up to 16MB</p>
                </div>

                <div x-show="selectedFile" class="space-y-4">
                    <div class="border-2 border-dashed border-gray-200 rounded-lg p-4">
                        <div x-show="previewData && previewData.type === 'image'" class="text-center">
                            <img :src="previewData && previewData.url" alt="Preview" class="max-h-32 max-w-full mx-auto rounded-md shadow-sm object-contain">
                            <p class="text-sm text-gray-600 mt-2" x-text="previewData && previewData.filename"></p>
                            <p class="text-xs text-gray-500" x-text="previewData && previewData.size"></p>
                        </div>
                        
                        <div x-show="previewData && previewData.type === 'document'" class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <i :class="`fas ${previewData && previewData.icon || 'fa-file'} text-3xl text-blue-500`"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 truncate" x-text="previewData && previewData.filename"></p>
                                <p class="text-sm text-gray-500" x-text="previewData && previewData.size"></p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Caption (optional)</label>
                        <textarea x-model="caption" 
                                  rows="2" 
                                  class="w-full p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                                  placeholder="Add a caption for your media..."></textarea>
                    </div>

                    <div class="text-center">
                        <button @click.stop="$refs.fileInput.click()" 
                                class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            <i class="fas fa-exchange-alt mr-1"></i> Change File
                        </button>
                    </div>
                </div>
            </div>

            <div x-show="selectedFile" class="flex justify-between items-center p-4 border-t bg-gray-50">
                <button @click="closeDialog()" 
                        class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium" 
                        :disabled="isUploading">
                    Cancel
                </button>
                <button @click="uploadMedia()" 
                        class="px-6 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition-colors flex items-center" 
                        :disabled="isUploading">
                    <span x-show="!isUploading">
                        <i class="fas fa-paper-plane mr-2"></i> Send
                    </span>
                    <span x-show="isUploading" class="flex items-center">
                        <i class="fas fa-spinner fa-spin mr-2"></i> Sending...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>