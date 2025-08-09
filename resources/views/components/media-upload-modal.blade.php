<script>
function mediaUploadModal() {
    return {
        isOpen: false,
        selectedFiles: [],
        caption: '',
        conversationId: null,
        dragActive: false,
        isUploading: false,
        uploadProgress: 0,
        maxFiles: 10,
        
        openDialog(convId) {
            console.log('Media modal openDialog called with:', convId);
            this.conversationId = convId;
            this.isOpen = true;
            window.isMediaModalOpen = true;
            this.resetForm();
            console.log('Modal state set to open:', this.isOpen);
            
            // Focus on the dialog for accessibility
            this.$nextTick(() => {
                const modal = this.$refs.modal;
                if (modal) modal.focus();
            });
        },
        
        closeDialog() {
            this.isOpen = false;
            window.isMediaModalOpen = false;
            this.resetForm();
        },
        
        resetForm() {
            this.selectedFiles = [];
            this.caption = '';
            this.dragActive = false;
            this.isUploading = false;
            this.uploadProgress = 0;
        },
        
        // Handle drag and drop events
        onDragEnter(e) {
            e.preventDefault();
            e.stopPropagation();
            this.dragActive = true;
        },
        
        onDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            // Only deactivate if leaving the drop zone completely
            if (!e.currentTarget.contains(e.relatedTarget)) {
                this.dragActive = false;
            }
        },
        
        onDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
        },
        
        onDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            this.dragActive = false;
            
            const files = Array.from(e.dataTransfer.files);
            this.handleFiles(files);
        },
        
        handleFileSelection(event) {
            const files = Array.from(event.target.files);
            this.handleFiles(files);
            // Clear the input so same file can be selected again
            event.target.value = '';
        },
        
        handleFiles(files) {
            const validFiles = files.filter(file => {
                // Check file size (16MB limit)
                if (file.size > 16 * 1024 * 1024) {
                    this.showError(`File "${file.name}" is too large. Maximum size is 16MB.`);
                    return false;
                }
                
                // Check file type
                const allowedTypes = [
                    'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
                    'video/mp4', 'video/mov', 'video/avi', 'video/quicktime',
                    'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/m4a', 'audio/aac',
                    'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text/plain'
                ];
                
                if (!allowedTypes.includes(file.type)) {
                    this.showError(`File type "${file.type}" is not supported.`);
                    return false;
                }
                
                return true;
            });
            
            // Check total files limit
            if (this.selectedFiles.length + validFiles.length > this.maxFiles) {
                this.showError(`You can only select up to ${this.maxFiles} files at once.`);
                return;
            }
            
            // Add files to selected files
            validFiles.forEach(file => {
                const fileData = {
                    file: file,
                    id: Date.now() + Math.random(),
                    name: file.name,
                    size: this.formatFileSize(file.size),
                    type: this.getFileType(file),
                    preview: null,
                    icon: this.getFileIcon(file)
                };
                
                // Generate preview for images
                if (fileData.type === 'image') {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        fileData.preview = e.target.result;
                        this.$nextTick();
                    };
                    reader.readAsDataURL(file);
                }
                
                this.selectedFiles.push(fileData);
            });
            
            console.log('Selected files:', this.selectedFiles);
        },
        
        removeFile(fileId) {
            this.selectedFiles = this.selectedFiles.filter(f => f.id !== fileId);
        },
        
        getFileType(file) {
            if (file.type.startsWith('image/')) return 'image';
            if (file.type.startsWith('video/')) return 'video';
            if (file.type.startsWith('audio/')) return 'audio';
            return 'document';
        },
        
        getFileIcon(file) {
            const extension = file.name.split('.').pop()?.toLowerCase();
            const iconMap = {
                'pdf': 'fa-file-pdf',
                'doc': 'fa-file-word',
                'docx': 'fa-file-word',
                'xls': 'fa-file-excel',
                'xlsx': 'fa-file-excel',
                'ppt': 'fa-file-powerpoint',
                'pptx': 'fa-file-powerpoint',
                'txt': 'fa-file-alt',
                'mp3': 'fa-file-audio',
                'wav': 'fa-file-audio',
                'ogg': 'fa-file-audio',
                'm4a': 'fa-file-audio',
                'mp4': 'fa-file-video',
                'mov': 'fa-file-video',
                'avi': 'fa-file-video',
                'zip': 'fa-file-archive',
                'rar': 'fa-file-archive',
                'default': 'fa-file'
            };
            return iconMap[extension] || iconMap.default;
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
        
        async uploadFiles() {
            if (this.selectedFiles.length === 0 || !this.conversationId) return;
            
            this.isUploading = true;
            this.uploadProgress = 0;
            
            try {
                // Upload files one by one for better progress tracking
                for (let i = 0; i < this.selectedFiles.length; i++) {
                    const fileData = this.selectedFiles[i];
                    await this.uploadSingleFile(fileData, i);
                }
                
                // Close modal and refresh messages
                this.closeDialog();
                window.dispatchEvent(new CustomEvent('media-sent', { 
                    detail: { message: 'Files uploaded successfully' }
                }));
                
            } catch (error) {
                console.error('Upload error:', error);
                this.showError('Failed to upload files. Please try again.');
            } finally {
                this.isUploading = false;
                this.uploadProgress = 0;
            }
        },
        
        async uploadSingleFile(fileData, index) {
            const formData = new FormData();
            formData.append('media', fileData.file);
            formData.append('caption', index === 0 ? this.caption : ''); // Only add caption to first file
            formData.append('conversation_id', this.conversationId);
            
            const response = await fetch('/api/whatsapp/upload-media', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Upload failed');
            }
            
            // Update progress
            this.uploadProgress = Math.round(((index + 1) / this.selectedFiles.length) * 100);
        },
        
        showError(message) {
            // Create a temporary error notification
            const errorEl = document.createElement('div');
            errorEl.className = 'fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded-lg shadow-lg z-[100000]';
            errorEl.textContent = message;
            document.body.appendChild(errorEl);
            
            setTimeout(() => {
                if (errorEl.parentNode) {
                    document.body.removeChild(errorEl);
                }
            }, 5000);
        },
        
        // Handle keyboard shortcuts
        handleKeydown(e) {
            if (e.key === 'Escape') {
                this.closeDialog();
            } else if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
                this.uploadFiles();
            }
        }
    }
}
</script>

<div x-data="mediaUploadModal()" 
     x-cloak
     x-init="console.log('Enhanced media modal initialized');"
     @open-media-dialog.window="openDialog($event.detail.conversationId)">

    <!-- Hidden file input -->
    <input type="file" 
           x-ref="fileInput"
           @change="handleFileSelection($event)"
           accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.mp3,.mp4,.wav,.ogg,.m4a,.mov,.avi,.txt,.xls,.xlsx"
           multiple
           style="display: none;">

    <!-- Modal overlay -->
    <div x-show="isOpen" 
         class="modal-overlay"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="closeDialog()"
         @keydown.window="handleKeydown($event)">
        
        <!-- Modal dialog -->
        <div class="modal-dialog" 
             x-ref="modal"
             tabindex="-1"
             @click.stop>
            
            <!-- Header -->
            <div class="modal-header">
                <div class="flex items-center gap-3">
                    <i class="fas fa-paperclip text-green-600"></i>
                    <h3>Send Media</h3>
                    <span class="file-counter" x-show="selectedFiles.length > 0">
                        <span x-text="selectedFiles.length"></span>/<span x-text="maxFiles"></span>
                    </span>
                </div>
                <button @click="closeDialog()" class="close-button" :disabled="isUploading">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Content -->
            <div class="modal-content">
                
                <!-- Drop zone / File selection area -->
                <div class="drop-zone"
                     :class="{ 'drag-active': dragActive, 'has-files': selectedFiles.length > 0 }"
                     @dragenter="onDragEnter($event)"
                     @dragleave="onDragLeave($event)"
                     @dragover="onDragOver($event)"
                     @drop="onDrop($event)"
                     x-show="selectedFiles.length === 0">
                    
                    <div class="drop-zone-content">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <h4>Drag and drop files here</h4>
                        <p>or</p>
                        <button type="button" 
                                class="select-files-btn"
                                @click="$refs.fileInput.click()"
                                :disabled="isUploading">
                            <i class="fas fa-plus mr-2"></i>
                            Choose Files
                        </button>
                        <div class="supported-formats">
                            <p>Supported formats:</p>
                            <div class="format-tags">
                                <span>Images</span>
                                <span>Videos</span>
                                <span>Audio</span>
                                <span>Documents</span>
                            </div>
                            <p class="size-limit">Maximum file size: 16MB | Up to <span x-text="maxFiles"></span> files</p>
                        </div>
                    </div>
                </div>
                
                <!-- Selected files preview -->
                <div class="selected-files" x-show="selectedFiles.length > 0">
                    <div class="files-header">
                        <h4>Selected Files (<span x-text="selectedFiles.length"></span>)</h4>
                        <button type="button" 
                                class="add-more-btn"
                                @click="$refs.fileInput.click()"
                                :disabled="isUploading || selectedFiles.length >= maxFiles">
                            <i class="fas fa-plus"></i>
                            Add More
                        </button>
                    </div>
                    
                    <div class="files-grid">
                        <template x-for="fileData in selectedFiles" :key="fileData.id">
                            <div class="file-item">
                                <!-- Remove button -->
                                <button class="remove-file-btn" 
                                        @click="removeFile(fileData.id)"
                                        :disabled="isUploading"
                                        title="Remove file">
                                    <i class="fas fa-times"></i>
                                </button>
                                
                                <!-- File preview -->
                                <div class="file-preview">
                                    <!-- Image preview -->
                                    <template x-if="fileData.type === 'image' && fileData.preview">
                                        <img :src="fileData.preview" :alt="fileData.name" class="preview-image">
                                    </template>
                                    
                                    <!-- Video preview -->
                                    <template x-if="fileData.type === 'video'">
                                        <div class="video-preview">
                                            <i class="fas fa-play-circle"></i>
                                            <span>Video</span>
                                        </div>
                                    </template>
                                    
                                    <!-- Audio preview -->
                                    <template x-if="fileData.type === 'audio'">
                                        <div class="audio-preview">
                                            <i class="fas fa-music"></i>
                                            <span>Audio</span>
                                        </div>
                                    </template>
                                    
                                    <!-- Document preview -->
                                    <template x-if="fileData.type === 'document'">
                                        <div class="document-preview">
                                            <i :class="`fas ${fileData.icon}`"></i>
                                        </div>
                                    </template>
                                </div>
                                
                                <!-- File info -->
                                <div class="file-info">
                                    <div class="file-name" :title="fileData.name" x-text="fileData.name"></div>
                                    <div class="file-size" x-text="fileData.size"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                
                <!-- Caption input -->
                <div class="caption-section" x-show="selectedFiles.length > 0">
                    <label for="caption">Caption (optional)</label>
                    <textarea x-model="caption" 
                              id="caption"
                              rows="2" 
                              placeholder="Add a caption for your media..."
                              :disabled="isUploading"
                              maxlength="1000"></textarea>
                    <div class="caption-counter">
                        <span x-text="caption.length"></span>/1000
                    </div>
                </div>
                
                <!-- Upload progress -->
                <div class="upload-progress" x-show="isUploading">
                    <div class="progress-info">
                        <span>Uploading files...</span>
                        <span x-text="`${uploadProgress}%`"></span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" :style="`width: ${uploadProgress}%`"></div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer" x-show="selectedFiles.length > 0">
                <button type="button" 
                        class="cancel-btn"
                        @click="closeDialog()" 
                        :disabled="isUploading">
                    Cancel
                </button>
                <button type="button" 
                        class="send-btn"
                        @click="uploadFiles()" 
                        :disabled="isUploading || selectedFiles.length === 0">
                    <span x-show="!isUploading" class="flex items-center">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Send <span x-text="selectedFiles.length > 1 ? `(${selectedFiles.length})` : ''"></span>
                    </span>
                    <span x-show="isUploading" class="flex items-center">
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        Sending...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal Base Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.75);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    padding: 20px;
}

.modal-dialog {
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Header */
.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #e9edef;
    background: #f8f9fa;
}

.modal-header h3 {
    font-size: 18px;
    font-weight: 600;
    color: #111b21;
    margin: 0;
}

.file-counter {
    background: #e7f3ff;
    color: #0066cc;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.close-button {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s ease;
    color: #54656f;
}

.close-button:hover {
    background: #f5f6fa;
}

/* Content */
.modal-content {
    flex: 1;
    padding: 24px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Drop Zone */
.drop-zone {
    border: 2px dashed #d1d7db;
    border-radius: 12px;
    padding: 40px 20px;
    text-align: center;
    transition: all 0.3s ease;
    background: #fafbfc;
}

.drop-zone.drag-active {
    border-color: #25d366;
    background: #f0f8ff;
    transform: scale(1.02);
}

.drop-zone-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
}

.upload-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #25d366, #128c7e);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 28px;
    margin-bottom: 8px;
}

.drop-zone h4 {
    color: #111b21;
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.drop-zone p {
    color: #667781;
    margin: 0;
    font-size: 16px;
}

.select-files-btn {
    background: #25d366;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 24px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.select-files-btn:hover {
    background: #128c7e;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
}

.select-files-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.supported-formats {
    margin-top: 20px;
    text-align: center;
}

.supported-formats p {
    color: #667781;
    font-size: 14px;
    margin: 8px 0;
}

.format-tags {
    display: flex;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
    margin: 12px 0;
}

.format-tags span {
    background: #e9edef;
    color: #54656f;
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 500;
}

.size-limit {
    color: #8696a0;
    font-size: 12px;
}

/* Selected Files */
.selected-files {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.files-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.files-header h4 {
    color: #111b21;
    font-size: 16px;
    font-weight: 600;
    margin: 0;
}

.add-more-btn {
    background: #e7f3ff;
    color: #0066cc;
    border: 1px solid #0066cc;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.add-more-btn:hover {
    background: #0066cc;
    color: white;
}

.add-more-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Files Grid */
.files-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 16px;
}

.file-item {
    position: relative;
    background: white;
    border: 1px solid #e9edef;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
}

.file-item:hover {
    border-color: #25d366;
    box-shadow: 0 2px 8px rgba(37, 211, 102, 0.15);
}

.remove-file-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: none;
    background: rgba(244, 67, 54, 0.9);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
    z-index: 10;
    opacity: 0;
    transition: all 0.2s ease;
}

.file-item:hover .remove-file-btn {
    opacity: 1;
}

.remove-file-btn:hover {
    background: #f44336;
    transform: scale(1.1);
}

/* File Preview */
.file-preview {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f6fa;
    overflow: hidden;
}

.preview-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.video-preview,
.audio-preview,
.document-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #54656f;
    font-size: 24px;
    height: 100%;
}

.video-preview span,
.audio-preview span {
    font-size: 12px;
    font-weight: 500;
}

/* File Info */
.file-info {
    padding: 12px;
    background: white;
}

.file-name {
    font-size: 13px;
    font-weight: 500;
    color: #111b21;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
}

.file-size {
    font-size: 11px;
    color: #667781;
}

/* Caption Section */
.caption-section {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.caption-section label {
    font-size: 14px;
    font-weight: 500;
    color: #111b21;
}

.caption-section textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #e9edef;
    border-radius: 8px;
    font-size: 14px;
    line-height: 1.4;
    resize: vertical;
    min-height: 60px;
    font-family: inherit;
}

.caption-section textarea:focus {
    outline: none;
    border-color: #25d366;
    box-shadow: 0 0 0 2px rgba(37, 211, 102, 0.1);
}

.caption-counter {
    text-align: right;
    font-size: 12px;
    color: #8696a0;
}

/* Upload Progress */
.upload-progress {
    background: #f8f9fa;
    padding: 16px;
    border-radius: 8px;
    border: 1px solid #e9edef;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    font-size: 14px;
}

.progress-info span:first-child {
    color: #111b21;
    font-weight: 500;
}

.progress-info span:last-child {
    color: #25d366;
    font-weight: 600;
}

.progress-bar {
    height: 6px;
    background: #e9edef;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #25d366, #128c7e);
    border-radius: 3px;
    transition: width 0.3s ease;
}

/* Footer */
.modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-top: 1px solid #e9edef;
    background: #fafbfc;
}

.cancel-btn {
    background: transparent;
    color: #667781;
    border: 1px solid #d1d7db;
    padding: 12px 24px;
    border-radius: 24px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.cancel-btn:hover {
    background: #f5f6fa;
    color: #54656f;
}

.send-btn {
    background: #25d366;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 24px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 100px;
}

.send-btn:hover:not(:disabled) {
    background: #128c7e;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
}

.send-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Responsive Design */
@media (max-width: 768px) {
    .modal-overlay {
        padding: 10px;
    }
    
    .modal-dialog {
        max-height: 95vh;
    }
    
    .modal-header,
    .modal-content,
    .modal-footer {
        padding: 16px;
    }
    
    .files-grid {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 12px;
    }
    
    .drop-zone {
        padding: 30px 16px;
    }
    
    .upload-icon {
        width: 48px;
        height: 48px;
        font-size: 20px;
    }
    
    .drop-zone h4 {
        font-size: 16px;
    }
}

/* Animation for file addition */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.file-item {
    animation: slideInUp 0.3s ease;
}

/* Focus styles for accessibility */
.modal-dialog:focus {
    outline: none;
}

button:focus,
textarea:focus {
    outline: 2px solid #25d366;
    outline-offset: 2px;
}
</style>