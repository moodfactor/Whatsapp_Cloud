<div x-data="mediaPreviewHandler()" x-cloak>
    <!-- File Input (Hidden) -->
    <input type="file" 
           x-ref="fileInput"
           @change="handleFileSelection($event)"
           accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.mp3,.mp4,.wav,.ogg,.m4a,.3gp"
           style="display: none;">

    <!-- Media Preview Dialog -->
    <div x-show="$wire.isOpen" 
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
                    <button @click="$wire.closeDialog()" 
                            class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Dialog Body -->
                <div class="p-6 max-h-[60vh] overflow-y-auto">
                    @if(!$selectedFile)
                        <!-- File Selection Area -->
                        <div class="text-center py-12">
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
                    @else
                        <!-- File Preview -->
                        <div class="space-y-6">
                            <!-- Preview Area -->
                            <div class="bg-gray-50 rounded-lg p-6 text-center">
                                @if($previewData && $previewData['type'] === 'image')
                                    <img src="{{ $previewData['url'] }}" 
                                         alt="Preview" 
                                         class="max-w-full max-h-80 mx-auto rounded-lg shadow-md object-contain">
                                @else
                                    <div class="flex flex-col items-center space-y-4">
                                        <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center">
                                            @if($previewData)
                                                @switch($previewData['icon'] ?? 'file')
                                                    @case('video')
                                                        <svg class="w-10 h-10 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M8 5v14l11-7z"/>
                                                        </svg>
                                                        @break
                                                    @case('music')
                                                        <svg class="w-10 h-10 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                                                        </svg>
                                                        @break
                                                    @case('file-pdf')
                                                        <svg class="w-10 h-10 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                                        </svg>
                                                        @break
                                                    @case('file-word')
                                                        <svg class="w-10 h-10 text-blue-700" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                                        </svg>
                                                        @break
                                                    @default
                                                        <svg class="w-10 h-10 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                                        </svg>
                                                @endswitch
                                            @endif
                                        </div>
                                        <div class="text-center">
                                            <p class="font-medium text-gray-900">{{ $previewData['filename'] ?? 'Unknown file' }}</p>
                                            <p class="text-sm text-gray-500 mt-1">
                                                {{ strtoupper($previewData['extension'] ?? 'File') }} • {{ $previewData['size'] ?? 'Unknown size' }}
                                            </p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- File Info & Change Button -->
                            <div class="flex items-center justify-between bg-gray-50 rounded-lg p-4">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">{{ $previewData['filename'] ?? 'Unknown file' }}</p>
                                    <p class="text-sm text-gray-500">{{ $previewData['size'] ?? 'Unknown size' }}</p>
                                </div>
                                <button @click="$refs.fileInput.click()" 
                                        class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                    Change File
                                </button>
                            </div>

                            <!-- Caption Input -->
                            <div>
                                <label for="caption" class="block text-sm font-medium text-gray-700 mb-2">
                                    Caption (optional)
                                </label>
                                <textarea wire:model.defer="caption"
                                          id="caption"
                                          rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none"
                                          placeholder="Add a caption to your media..."></textarea>
                                @error('caption')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Dialog Footer -->
                @if($selectedFile)
                    <div class="flex items-center justify-end space-x-3 p-4 border-t border-gray-200 bg-gray-50">
                        <button @click="$wire.closeDialog()" 
                                class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition-colors"
                                :disabled="$wire.isUploading">
                            Cancel
                        </button>
                        <button wire:click="sendMedia" 
                                :disabled="$wire.isUploading"
                                class="px-6 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2">
                            <span x-show="!$wire.isUploading">Send</span>
                            <span x-show="$wire.isUploading">Sending...</span>
                            <div x-show="$wire.isUploading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function mediaPreviewHandler() {
            return {
                handleFileSelection(event) {
                    const file = event.target.files[0];
                    if (file) {
                        // Validate file size (16MB)
                        if (file.size > 16 * 1024 * 1024) {
                            alert('File size must be less than 16MB');
                            return;
                        }

                        // Validate file type
                        const allowedTypes = [
                            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
                            'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'audio/mpeg', 'audio/mp4', 'audio/wav', 'audio/ogg',
                            'video/mp4', 'video/3gpp'
                        ];

                        if (!allowedTypes.includes(file.type)) {
                            alert('File type not supported. Please select an image, document, audio, or video file.');
                            return;
                        }

                        // Set the file in Livewire
                        this.$wire.set('selectedFile', event.target.files[0]);
                    }
                }
            }
        }
    </script>
</div>