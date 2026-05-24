@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ==========================================
    // CHARACTER COUNTER
    // ==========================================
    const textarea = document.querySelector('textarea[name="description"]');
    const charCount = document.getElementById('charCount');
    if (textarea && charCount) {
        charCount.textContent = textarea.value.length;
        textarea.addEventListener('input', () => charCount.textContent = textarea.value.length);
    }

    // ==========================================
    // TAG MANAGEMENT
    // ==========================================
    const tagSelector = document.getElementById('tagSelector');
    const selectedTagsDisplay = document.getElementById('selectedTagsDisplay');
    const customTagInput = document.getElementById('customTagInput');
    const tagsHidden = document.getElementById('tagsHidden');
    let selectedTags = [];

    // Initialize from old value
    if (tagsHidden && tagsHidden.value) {
        selectedTags = tagsHidden.value.split(',').map(t => t.trim()).filter(t => t);
        updateTagsDisplay();
        updateTagChips();
    }

    // Click predefined tags
    if (tagSelector) {
        tagSelector.addEventListener('click', function(e) {
            const chip = e.target.closest('.tag-chip');
            if (!chip) return;

            const tag = chip.dataset.tag;
            if (selectedTags.includes(tag)) {
                selectedTags = selectedTags.filter(t => t !== tag);
            } else {
                selectedTags.push(tag);
            }
            updateTagsDisplay();
            updateTagChips();
            updateHiddenInput();
        });
    }

    // Custom tag input
    if (customTagInput) {
        customTagInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const tag = this.value.trim().replace(/,/g, '').toLowerCase();
                if (tag && !selectedTags.includes(tag)) {
                    selectedTags.push(tag);
                    updateTagsDisplay();
                    updateHiddenInput();
                }
                this.value = '';
            }
        });
    }

    // Remove tag
    if (selectedTagsDisplay) {
        selectedTagsDisplay.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-tag')) {
                const tag = e.target.dataset.tag;
                selectedTags = selectedTags.filter(t => t !== tag);
                updateTagsDisplay();
                updateTagChips();
                updateHiddenInput();
            }
        });
    }

    function updateTagsDisplay() {
        if (!selectedTagsDisplay) return;
        selectedTagsDisplay.innerHTML = selectedTags.map(tag => `
            <span class="selected-tag">
                ${escapeHtml(tag)}
                <span class="remove-tag" data-tag="${escapeHtml(tag)}">×</span>
            </span>
        `).join('');
    }

    function updateTagChips() {
        if (!tagSelector) return;
        tagSelector.querySelectorAll('.tag-chip').forEach(chip => {
            if (selectedTags.includes(chip.dataset.tag)) {
                chip.classList.add('selected');
            } else {
                chip.classList.remove('selected');
            }
        });
    }

    function updateHiddenInput() {
        if (tagsHidden) tagsHidden.value = selectedTags.join(',');
    }

    // ==========================================
    // FILE UPLOAD WITH PURE JS COMPRESSION
    // ==========================================
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');
    const previewContainer = document.getElementById('previewContainer');
    const MAX_FILE_SIZE = 20 * 1024 * 1024; // 20MB
    const MAX_IMAGE_DIMENSION = 1920;
    const COMPRESSION_QUALITY = 0.7;

    let selectedFiles = new DataTransfer();

    // Open file dialog
    if (uploadZone) {
        uploadZone.addEventListener('click', () => fileInput.click());

        // Drag & drop
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('drag-active');
        });
        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('drag-active');
        });
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('drag-active');
            handleFiles(e.dataTransfer.files);
        });
    }

    // File input change
    if (fileInput) {
        fileInput.addEventListener('change', () => handleFiles(fileInput.files));
    }

    /**
     * Handle selected files - compress images, create previews
     */
    async function handleFiles(files) {
        const fileArray = Array.from(files);

        for (const file of fileArray) {
            // Validate file size
            if (file.size > MAX_FILE_SIZE) {
                alert('File "' + file.name + '" is too large (' + formatSize(file.size) + '). Max 20MB allowed.');
                continue;
            }

            // Check for duplicate
            let isDuplicate = false;
            Array.from(selectedFiles.files).forEach(f => {
                if (f.name === file.name && f.size === file.size) isDuplicate = true;
            });
            if (isDuplicate) continue;

            // Show compressing indicator if image
            let processedFile = file;
            if (file.type.startsWith('image/') && file.size > 200 * 1024) {
                processedFile = await compressImageNative(file);
            }

            // Add to DataTransfer
            selectedFiles.items.add(processedFile);

            // Create preview
            createPreview(processedFile);
        }

        // Update file input with all files
        fileInput.files = selectedFiles.files;
    }

    /**
     * Pure JavaScript image compression using Canvas API
     * No external dependencies required
     */
    function compressImageNative(file) {
        return new Promise((resolve) => {
            // Skip if not an image or already small
            if (!file.type.startsWith('image/') || file.size < 200 * 1024) {
                resolve(file);
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    // Calculate new dimensions
                    let width = img.width;
                    let height = img.height;

                    // Resize if too large
                    if (width > MAX_IMAGE_DIMENSION || height > MAX_IMAGE_DIMENSION) {
                        if (width > height) {
                            height = Math.round((height * MAX_IMAGE_DIMENSION) / width);
                            width = MAX_IMAGE_DIMENSION;
                        } else {
                            width = Math.round((width * MAX_IMAGE_DIMENSION) / height);
                            height = MAX_IMAGE_DIMENSION;
                        }
                    }

                    // Draw on canvas
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    // Convert to blob with compression
                    canvas.toBlob((blob) => {
                        if (blob && blob.size < file.size) {
                            // Compression was effective
                            const compressedFile = new File([blob], file.name, {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            });
                            console.log(
                                'Compressed: ' + formatSize(file.size) +
                                ' → ' + formatSize(compressedFile.size) +
                                ' (' + Math.round((1 - compressedFile.size / file.size) * 100) + '% smaller)'
                            );
                            resolve(compressedFile);
                        } else {
                            // Compression didn't help, use original
                            console.log('Compression skipped for ' + file.name + ' (already optimized)');
                            resolve(file);
                        }
                    }, 'image/jpeg', COMPRESSION_QUALITY);
                };
                img.onerror = function() {
                    // Failed to load image, use original
                    resolve(file);
                };
                img.src = e.target.result;
            };
            reader.onerror = function() {
                // Failed to read file, use original
                resolve(file);
            };
            reader.readAsDataURL(file);
        });
    }

    /**
     * Create preview element for a file
     */
    function createPreview(file) {
        const div = document.createElement('div');
        div.className = 'preview-item';
        div.dataset.filename = file.name;

        if (file.type.startsWith('image/')) {
            // Show image preview
            const reader = new FileReader();
            reader.onload = function(e) {
                div.innerHTML = `
                    <img src="${e.target.result}" alt="${escapeHtml(file.name)}">
                    <button type="button" class="btn-remove" data-filename="${escapeHtml(file.name)}">×</button>
                `;
            };
            reader.readAsDataURL(file);
        } else if (file.type.startsWith('video/')) {
            div.innerHTML = `
                <div class="file-preview-fallback">
                    <i class="fas fa-video"></i>
                    <span class="file-name">${escapeHtml(file.name)}</span>
                    <span class="file-size">${formatSize(file.size)}</span>
                </div>
                <button type="button" class="btn-remove" data-filename="${escapeHtml(file.name)}">×</button>
            `;
        } else {
            let icon = 'fa-file';
            if (file.type.includes('pdf')) icon = 'fa-file-pdf';
            else if (file.type.includes('word') || file.type.includes('document')) icon = 'fa-file-word';
            else if (file.type.includes('excel') || file.type.includes('sheet')) icon = 'fa-file-excel';
            else if (file.type.includes('zip') || file.type.includes('rar')) icon = 'fa-file-zipper';
            else if (file.type.includes('text')) icon = 'fa-file-lines';

            div.innerHTML = `
                <div class="file-preview-fallback">
                    <i class="fas ${icon}"></i>
                    <span class="file-name">${escapeHtml(file.name)}</span>
                    <span class="file-size">${formatSize(file.size)}</span>
                </div>
                <button type="button" class="btn-remove" data-filename="${escapeHtml(file.name)}">×</button>
            `;
        }

        previewContainer.appendChild(div);
    }

    /**
     * Remove file from selection
     */
    previewContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove')) {
            const filename = e.target.dataset.filename;

            // Remove from DataTransfer
            const newDT = new DataTransfer();
            Array.from(selectedFiles.files).forEach(f => {
                if (f.name !== filename) newDT.items.add(f);
            });
            selectedFiles = newDT;
            fileInput.files = selectedFiles.files;

            // Remove preview element
            const previewItem = e.target.closest('.preview-item');
            if (previewItem) previewItem.remove();
        }
    });

    /**
     * Format file size to human readable
     */
    function formatSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    // ==========================================
    // FORM SUBMISSION - LOADING STATE
    // ==========================================
    const form = document.getElementById('incidentForm');
    const submitBtn = document.getElementById('submitBtn');

    if (form && submitBtn) {
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
        });
    }

});
</script>
@endpush
