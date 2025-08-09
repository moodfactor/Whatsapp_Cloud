<!-- Simple Media Upload Modal (Pure Alpine.js fallback) -->
<script>
function mediaUploadModal() {
    return {
        isOpen: false,
        selectedFile: null,
        caption: '',
        conversationId: null,
        previewData: null,
        isUploading: false,
        
        openDialog(convId) {
            this.conversationId = convId;
            this.isOpen = true;
            this.resetForm();
        },
        
        closeDialog() {
            this.isOpen = false;
            this.resetForm();
        },
        
        resetForm() {
            this.selectedFile = null;
            this.caption = '';
            this.previewData = null;
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
     @open-simple-media-dialog.window="openDialog($event.detail.conversationId)">

    <!-- File Input (Hidden) -->
    <input type="file" 
           x-ref="fileInput"
           @change="handleFileSelection($event)"
           accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.mp3,.mp4,.wav,.ogg,.m4a,.3gp"
           style="display: none;">

    <!-- Media Preview Dialog -->
    <div x-show="isOpen" 
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity"></div>
        
        <!-- Dialog container -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                
                <!-- Dialog Header -->
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Send Media</h3>
                    <button @click="closeDialog()" 
                            class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Dialog Body -->
                <div class="p-6 max-h-[60vh] overflow-y-auto">
                    <div x-show="!selectedFile" class="text-center py-12">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                            </svg>
                        </div>
                        <p class="text-gray-600 mb-4">Select a file to send</p>
                        <button @click="$refs.fileInput.click()" 
                                class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                            Choose File
                        </button>
                    </div>

                    <div x-show="selectedFile" class="space-y-6">
                        <!-- Preview Area -->
                        <div class="bg-gray-50 rounded-lg p-6 text-center">
                            <div x-show="previewData && previewData.type === 'image'">
                                <img :src="previewData ? previewData.url : ''" 
                                     alt="Preview" 
                                     class="max-w-full max-h-80 mx-auto rounded-lg shadow-md object-contain">
                            </div>
                            <div x-show="previewData && previewData.type !== 'image'" class="flex flex-col items-center space-y-4">
                                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i :class="previewData ? 'fas ' + previewData.icon + ' text-blue-600 text-2xl' : ''"></i>
                                </div>
                                <div class="text-center">
                                    <p class="font-medium text-gray-900" x-text="previewData ? previewData.filename : ''"></p>
                                    <p class="text-sm text-gray-500 mt-1" x-text="previewData ? previewData.size : ''"></p>
                                </div>
                            </div>
                        </div>

                        <!-- File Info & Change Button -->
                        <div class="flex items-center justify-between bg-gray-50 rounded-lg p-4">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900" x-text="previewData ? previewData.filename : ''"></p>
                                <p class="text-sm text-gray-500" x-text="previewData ? previewData.size : ''"></p>
                            </div>
                            <button @click="$refs.fileInput.click()" 
                                    class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                Change File
                            </button>
                        </div>

                        <!-- Caption Input -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Caption (optional)
                            </label>
                            <textarea x-model="caption"
                                      rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none"
                                      placeholder="Add a caption to your media..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Dialog Footer -->
                <div x-show="selectedFile" class="flex items-center justify-end space-x-3 p-4 border-t border-gray-200 bg-gray-50">
                    <button @click="closeDialog()" 
                            class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition-colors"
                            :disabled="isUploading">
                        Cancel
                    </button>
                    <button @click="uploadMedia()" 
                            :disabled="isUploading"
                            class="px-6 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2">
                        <span x-show="!isUploading">Send</span>
                        <span x-show="isUploading">Sending...</span>
                        <div x-show="isUploading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>