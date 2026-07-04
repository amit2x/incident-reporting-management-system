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
    // async function handleFiles(files) {
    //     const fileArray = Array.from(files);

    //     for (const file of fileArray) {
    //         // Validate file size
    //         if (file.size > MAX_FILE_SIZE) {
    //             alert('File "' + file.name + '" is too large (' + formatSize(file.size) + '). Max 20MB allowed.');
    //             continue;
    //         }

    //         // Check for duplicate
    //         let isDuplicate = false;
    //         Array.from(selectedFiles.files).forEach(f => {
    //             if (f.name === file.name && f.size === file.size) isDuplicate = true;
    //         });
    //         if (isDuplicate) continue;

    //         // Show compressing indicator if image
    //         let processedFile = file;
    //         if (file.type.startsWith('image/') && file.size > 200 * 1024) {
    //             processedFile = await compressImageNative(file);
    //         }

    //         // Add to DataTransfer
    //         selectedFiles.items.add(processedFile);

    //         // Create preview
    //         createPreview(processedFile);
    //     }

    //     // Update file input with all files
    //     fileInput.files = selectedFiles.files;
    // }
    async function handleFiles(files) {
    const fileArray = Array.from(files);
    let currentIndex = selectedFiles.files.length;

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

        // Create preview with correct index
        createPreview(processedFile, currentIndex);
        currentIndex++;
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
    // function createPreview(file) {
    //     const div = document.createElement('div');
    //     div.className = 'preview-item';
    //     div.dataset.filename = file.name;

    //     if (file.type.startsWith('image/')) {
    //         // Show image preview
    //         const reader = new FileReader();
    //         reader.onload = function(e) {
    //             div.innerHTML = `
    //                 <img src="${e.target.result}" alt="${escapeHtml(file.name)}">
    //                 <button type="button" class="btn-remove" data-filename="${escapeHtml(file.name)}">×</button>
    //             `;
    //         };
    //         reader.readAsDataURL(file);
    //     } else if (file.type.startsWith('video/')) {
    //         div.innerHTML = `
    //             <div class="file-preview-fallback">
    //                 <i class="fas fa-video"></i>
    //                 <span class="file-name">${escapeHtml(file.name)}</span>
    //                 <span class="file-size">${formatSize(file.size)}</span>
    //             </div>
    //             <button type="button" class="btn-remove" data-filename="${escapeHtml(file.name)}">×</button>
    //         `;
    //     } else {
    //         let icon = 'fa-file';
    //         if (file.type.includes('pdf')) icon = 'fa-file-pdf';
    //         else if (file.type.includes('word') || file.type.includes('document')) icon = 'fa-file-word';
    //         else if (file.type.includes('excel') || file.type.includes('sheet')) icon = 'fa-file-excel';
    //         else if (file.type.includes('zip') || file.type.includes('rar')) icon = 'fa-file-zipper';
    //         else if (file.type.includes('text')) icon = 'fa-file-lines';

    //         div.innerHTML = `
    //             <div class="file-preview-fallback">
    //                 <i class="fas ${icon}"></i>
    //                 <span class="file-name">${escapeHtml(file.name)}</span>
    //                 <span class="file-size">${formatSize(file.size)}</span>
    //             </div>
    //             <button type="button" class="btn-remove" data-filename="${escapeHtml(file.name)}">×</button>
    //         `;
    //     }

    //     previewContainer.appendChild(div);
    // }
    
    // function createPreview(file, fileIndex = 0) {
    //     const div = document.createElement('div');
    //     div.className = 'preview-item';
    //     div.dataset.filename = file.name;
    
    //     if (file.type.startsWith('image/')) {
    //         // Show image preview with edit button
    //         const reader = new FileReader();
    //         reader.onload = function(e) {
    //             div.innerHTML = `
    //                 <img src="${e.target.result}" alt="${escapeHtml(file.name)}" data-file-index="${fileIndex}">
    //                 <div class="preview-overlay">
    //                     <button type="button" class="btn-edit-image" data-image="${e.target.result}" data-index="${fileIndex}" title="Edit Image">
    //                         <i class="fas fa-pen"></i>
    //                     </button>
    //                 </div>
    //                 <button type="button" class="btn-remove" data-filename="${escapeHtml(file.name)}">×</button>
    //             `;
    //         };
    //         reader.readAsDataURL(file);
    //     } else if (file.type.startsWith('video/')) {
    //         div.innerHTML = `
    //             <div class="file-preview-fallback">
    //                 <i class="fas fa-video"></i>
    //                 <span class="file-name">${escapeHtml(file.name)}</span>
    //                 <span class="file-size">${formatSize(file.size)}</span>
    //             </div>
    //             <button type="button" class="btn-remove" data-filename="${escapeHtml(file.name)}">×</button>
    //         `;
    //     }else {
    //         // Non-image preview
    //         let icon = 'fa-file';
    //         if (file.type.includes('pdf')) icon = 'fa-file-pdf';
    //         else if (file.type.includes('word')) icon = 'fa-file-word';
    //         else if (file.type.includes('excel')) icon = 'fa-file-excel';
    //         else if (file.type.includes('zip')) icon = 'fa-file-zipper';
    
    //         div.innerHTML = `
    //             <div class="file-preview-fallback">
    //                 <i class="fas ${icon}"></i>
    //                 <span class="file-name">${escapeHtml(file.name)}</span>
    //                 <span class="file-size">${formatSize(file.size)}</span>
    //             </div>
    //             <button type="button" class="btn-remove" data-filename="${escapeHtml(file.name)}">×</button>
    //         `;
    //     }

    //     previewContainer.appendChild(div);
        
    //     // Add edit event listeners to image edit buttons
    //     div.querySelector('.btn-edit-image')?.addEventListener('click', function(e) {
    //         e.stopPropagation();
    //         const imageData = this.dataset.image;
    //         const index = this.dataset.index;
    //         if (window.openImageEditor) {
    //             window.openImageEditor(imageData, index);
    //         }
    //     });
    // }
    

function createPreview(file, fileIndex = 0) {
    const div = document.createElement('div');
    div.className = 'preview-item';
    div.dataset.filename = file.name;

    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const imageData = e.target.result;
            // Store in global for later access
            window.editedImages = window.editedImages || {};
            window.editedImages['original_' + fileIndex] = imageData;
            
            div.innerHTML = `
                <img src="${imageData}" alt="${escapeHtml(file.name)}" data-file-index="${fileIndex}">
                <div class="preview-overlay">
                    <button type="button" class="btn-edit-image" data-image="${imageData}" data-index="${fileIndex}" title="Edit Image">
                        <i class="fas fa-pen"></i>
                    </button>
                </div>
                <button type="button" class="btn-remove" data-filename="${escapeHtml(file.name)}">×</button>
            `;
            
            // Add edit button event listener
            const editBtn = div.querySelector('.btn-edit-image');
            if (editBtn) {
                editBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const imageData = this.dataset.image;
                    const index = parseInt(this.dataset.index);
                    
                    // Check if we have an edited version
                    const editedKey = 'edited_image_' + index;
                    const finalImageData = (window.editedImages && window.editedImages[editedKey]) 
                        ? window.editedImages[editedKey] 
                        : imageData;
                    
                    if (window.openImageEditor) {
                        window.openImageEditor(finalImageData, index);
                    } else {
                        console.error('Image editor not initialized');
                    }
                });
            }
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
        else if (file.type.includes('word')) icon = 'fa-file-word';
        else if (file.type.includes('excel')) icon = 'fa-file-excel';
        else if (file.type.includes('zip')) icon = 'fa-file-zipper';

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
    if (previewContainer) {
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
    }

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
    // FORM SUBMISSION - LOADING STATE WITH DOUBLE SUBMIT PROTECTION
    // ==========================================
    const form = document.getElementById('incidentForm');
    const submitBtn = document.getElementById('submitBtn');
    
    if (form && submitBtn) {
        let isSubmitting = false;
        
        form.addEventListener('submit', function(e) {
            // Prevent double submission
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            
            isSubmitting = true;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
            
            // Optional: Add timeout to re-enable button if submission fails (10 seconds)
            setTimeout(function() {
                if (submitBtn.disabled) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Incident';
                    isSubmitting = false;
                }
            }, 10000);
        });
    }

});



// ==========================================
// FORM VALIDATION - RED/GREEN BORDER
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    
    // Get all required form controls
    const requiredFields = document.querySelectorAll('input:required, select:required, textarea:required');
    
    // Add validation status indicators
    requiredFields.forEach(function(field) {
        // Create status indicator
        const statusSpan = document.createElement('span');
        statusSpan.className = 'validation-status';
        statusSpan.style.display = 'none';
        
        // Add it after the field
        field.parentNode.appendChild(statusSpan);
        
        // Initial validation check
        validateField(field);
        
        // Validate on input/change
        field.addEventListener('input', function() {
            validateField(this);
        });
        
        field.addEventListener('change', function() {
            validateField(this);
        });
        
        // Validate on blur (to show invalid state if user leaves empty)
        field.addEventListener('blur', function() {
            validateField(this, true);
        });
    });
    
    // Function to validate a single field
    function validateField(field, showInvalid = false) {
        const isValid = field.checkValidity();
        const statusSpan = field.parentNode.querySelector('.validation-status');
        
        // Remove any existing validation classes
        field.classList.remove('validation-valid', 'validation-invalid');
        
        if (isValid && field.value.trim() !== '') {
            // Valid - green border
            field.classList.add('validation-valid');
            if (statusSpan) {
                statusSpan.innerHTML = '<i class="fas fa-check-circle text-success"></i>';
                statusSpan.style.display = 'inline-block';
                statusSpan.style.color = '#28a745';
            }
        } else if (showInvalid && field.value.trim() === '') {
            // Invalid - red border with shake
            field.classList.add('validation-invalid');
            if (statusSpan) {
                statusSpan.innerHTML = '<i class="fas fa-exclamation-circle text-danger"></i>';
                statusSpan.style.display = 'inline-block';
                statusSpan.style.color = '#dc3545';
            }
            // Add shake animation
            field.style.animation = 'none';
            setTimeout(() => {
                field.style.animation = 'invalidShake 0.5s ease';
            }, 10);
        } else {
            // Neutral state
            if (statusSpan) {
                statusSpan.style.display = 'none';
            }
        }
    }
    
    // Optional: Show validation summary
    const form = document.getElementById('incidentForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            let hasErrors = false;
            const requiredFields = this.querySelectorAll('input:required, select:required, textarea:required');
            
            requiredFields.forEach(function(field) {
                if (!field.checkValidity() || field.value.trim() === '') {
                    hasErrors = true;
                    validateField(field, true);
                    // Focus first error field
                    if (!document.querySelector('.validation-invalid')) {
                        field.focus();
                    }
                }
            });
            
            if (hasErrors) {
                e.preventDefault();
                // Show error message
                const errorMsg = document.createElement('div');
                errorMsg.className = 'alert alert-danger alert-dismissible fade show mt-3';
                errorMsg.role = 'alert';
                errorMsg.innerHTML = `
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Please fill in all required fields highlighted in red.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                const formSection = document.querySelector('.form-section');
                if (formSection) {
                    formSection.prepend(errorMsg);
                    setTimeout(() => {
                        errorMsg.remove();
                    }, 5000);
                }
            }
        });
    }
});

// Also add CSS for validation classes
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
        .validation-valid {
            border-color: #28a745 !important;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.15) !important;
        }
        
        .validation-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15) !important;
        }
        
        .validation-valid:focus {
            border-color: #28a745 !important;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.25) !important;
        }
        
        .validation-invalid:focus {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.25) !important;
        }
    `;
    document.head.appendChild(style);
});    
    
    
// ==========================================
// AUTO-FILL: Category → Department & Title
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('categorySelect');
    const departmentSelect = document.getElementById('departmentSelect');
    const incidentTitle = document.getElementById('incidentTitle');
    const titleHelper = document.getElementById('titleHelper');
    const departmentHelper = document.getElementById('departmentHelper');
    const categoryHelper = document.getElementById('categoryHelper');
    
    // Store user's default department as fallback
    const userDepartmentId = "{{ auth()->user()->department_id ?? '' }}";
    
    // Store the original title if user manually edited it
    let userEditedTitle = false;
    let originalAutoTitle = '';
    
    // Enhanced function to generate title from category
    // function generateTitleFromCategory(categoryName, categoryDescription) {
    //     let suggestedTitle = '';
        
    //     // Remove common prefixes from description
    //     const description = categoryDescription || '';
        
    //     // Check if description contains common incident patterns
    //     const patterns = [
    //         /issue with/i,
    //         /problem (with|in)/i,
    //         /error in/i,
    //         /failure of/i,
    //         /damage to/i,
    //         /leakage (from|in)/i,
    //         /malfunction (of|in)/i,
    //         /breakdown (of|in)/i,
    //         /not working/i,
    //         /outage/i,
    //         /disruption/i
    //     ];
        
    //     let foundPattern = false;
    //     for (const pattern of patterns) {
    //         if (pattern.test(description)) {
    //             // Use the description as is (it already has context)
    //             suggestedTitle = description.charAt(0).toUpperCase() + description.slice(1);
    //             foundPattern = true;
    //             break;
    //         }
    //     }
        
    //     if (!foundPattern && description) {
    //         // If description exists but no pattern found, use it with proper casing
    //         suggestedTitle = description.charAt(0).toUpperCase() + description.slice(1);
    //     } else if (!description) {
    //         // If no description, create from category name
    //         const prefixes = [
    //             'Report: ',
    //             'Incident: ',
    //             'Issue: ',
    //             'Problem: '
    //         ];
    //         const randomPrefix = prefixes[Math.floor(Math.random() * prefixes.length)];
    //         suggestedTitle = randomPrefix + categoryName;
    //     }
        
    //     // Limit length
    //     if (suggestedTitle.length > 100) {
    //         suggestedTitle = suggestedTitle.substring(0, 97) + '...';
    //     }
        
    //     return suggestedTitle;
    // }
function generateTitleFromCategory(categoryName, categoryDescription) {
  let suggestedTitle = '';
  // Clean up description completely
  const description = (categoryDescription || '').replace(/\s+/g, ' ').trim(); 

  const patterns = [
    /issue with/i, /problem (with|in)/i, /error in/i, 
    /failure of/i, /damage to/i, /leakage (from|in)/i, 
    /malfunction (of|in)/i, /breakdown (of|in)/i, 
    /not working/i, /outage/i, /disruption/i
  ];

  let foundPattern = false;
  for (const pattern of patterns) {
    if (pattern.test(description)) {
      suggestedTitle = description.charAt(0).toUpperCase() + description.slice(1);
      foundPattern = true;
      break;
    }
  }

  if (!foundPattern && description) {
    suggestedTitle = description.charAt(0).toUpperCase() + description.slice(1);
  } else if (!description) {
    const prefixes = ['Report: ', 'Incident: ', 'Issue: ', 'Problem: '];
    const randomPrefix = prefixes[Math.floor(Math.random() * prefixes.length)];
    // Clean up category name completely
    const cleanCategory = (categoryName || '').replace(/\s+/g, ' ').trim();
    suggestedTitle = randomPrefix + cleanCategory; 
  }

  // Final safety trim before truncation
  suggestedTitle = suggestedTitle.trim();

  if (suggestedTitle.length > 100) {
    suggestedTitle = suggestedTitle.substring(0, 97).trim() + '...';
  }

  return suggestedTitle;
}


    // Function to auto-fill based on category selection
    function autoFillFromCategory() {
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        
        if (selectedOption && selectedOption.value) {
            const departmentId = selectedOption.dataset.department;
            const categoryDescription = selectedOption.dataset.description || '';
            const categoryName = selectedOption.textContent.trim();
            
            // 1. Auto-select Department
            if (departmentId) {
                departmentSelect.value = departmentId;
                if (departmentHelper) {
                    departmentHelper.innerHTML = '✅ Auto-selected from category <i class="fas fa-check-circle text-success"></i>';
                    setTimeout(() => {
                        departmentHelper.innerHTML = 'Auto-selected based on category, but you can change';
                    }, 3000);
                }
            } else {
                // Fallback to user's department if no department associated
                if (userDepartmentId) {
                    departmentSelect.value = userDepartmentId;
                }
            }
            
            // 2. Auto-fill Title (only if user hasn't manually edited it)
            if (!userEditedTitle || !incidentTitle.value || incidentTitle.value === originalAutoTitle) {
                // Generate title from category description or name
                let suggestedTitle = generateTitleFromCategory(categoryName, categoryDescription);
                
                // Set the title
                incidentTitle.value = suggestedTitle;
                originalAutoTitle = suggestedTitle;
                
                // Show indication
                if (titleHelper) {
                    titleHelper.innerHTML = '✅ Auto-filled from category <i class="fas fa-check-circle text-success"></i>';
                    setTimeout(() => {
                        titleHelper.innerHTML = 'You can edit this title';
                    }, 3000);
                }
                
                // Mark that auto-fill was used
                incidentTitle.dataset.autoFilled = 'true';
            }
            
            // 3. Update category helper
            if (categoryHelper) {
                categoryHelper.innerHTML = '✅ Selected: ' + categoryName + ' <i class="fas fa-check-circle text-success"></i>';
                setTimeout(() => {
                    categoryHelper.innerHTML = 'Select a category to auto-fill department and title';
                }, 3000);
            }
            
        } else {
            // No category selected - clear auto-fill
            if (!userEditedTitle) {
                incidentTitle.value = '';
                originalAutoTitle = '';
            }
            
            if (userDepartmentId) {
                departmentSelect.value = userDepartmentId;
            }
            
            if (categoryHelper) {
                categoryHelper.innerHTML = 'Select a category to auto-fill department and title';
            }
        }
    }
    
    // Debounce function to prevent rapid fire
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Detect when user manually edits the title
    if (incidentTitle) {
        incidentTitle.addEventListener('input', function() {
            if (this.value !== originalAutoTitle) {
                userEditedTitle = true;
                this.dataset.autoFilled = 'false';
                if (titleHelper) {
                    titleHelper.innerHTML = '✏️ Custom title (edited)';
                    setTimeout(() => {
                        titleHelper.innerHTML = 'You can edit this title';
                    }, 2000);
                }
            } else {
                userEditedTitle = false;
            }
        });
    }
    
    // Auto-fill on category change with debounce
    if (categorySelect) {
        const debouncedAutoFill = debounce(function() {
            // If user manually edited title, don't auto-fill again
            if (userEditedTitle && incidentTitle.value) {
                // Still auto-select department but keep custom title
                const selectedOption = categorySelect.options[categorySelect.selectedIndex];
                if (selectedOption && selectedOption.value) {
                    const departmentId = selectedOption.dataset.department;
                    if (departmentId) {
                        departmentSelect.value = departmentId;
                        if (departmentHelper) {
                            departmentHelper.innerHTML = '✅ Department updated <i class="fas fa-check-circle text-success"></i>';
                            setTimeout(() => {
                                departmentHelper.innerHTML = 'Auto-selected based on category, but you can change';
                            }, 3000);
                        }
                    }
                }
                // Show a subtle notification
                showToast('Department updated, keeping your custom title');
            } else {
                // Auto-fill everything
                autoFillFromCategory();
            }
        }, 300);
        
        categorySelect.addEventListener('change', debouncedAutoFill);
    }
    
    // Auto-fill on page load if category is pre-selected
    if (categorySelect && categorySelect.value) {
        autoFillFromCategory();
    }
    
    // Show toast notification helper
    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 start-50 translate-middle-x mb-3 p-2 bg-dark text-white rounded-pill px-3';
        toast.style.zIndex = '9999';
        toast.style.fontSize = '0.8rem';
        toast.innerHTML = '<i class="fas fa-info-circle me-1"></i> ' + message;
        document.body.appendChild(toast);
        setTimeout(function() { toast.remove(); }, 3000);
    }
    
    // Manual override: Add a small "Auto-fill" button next to title
    const titleInput = document.getElementById('incidentTitle');
    if (titleInput) {
        // Check if button already exists to prevent duplicates
        if (!document.getElementById('autoFillBtn')) {
            const autoFillBtn = document.createElement('button');
            autoFillBtn.type = 'button';
            autoFillBtn.id = 'autoFillBtn';
            autoFillBtn.className = 'btn btn-sm btn-outline-primary ms-2';
            autoFillBtn.innerHTML = '<i class="fas fa-magic"></i>';
            autoFillBtn.title = 'Auto-fill from category';
            autoFillBtn.style.display = 'inline-block';
            autoFillBtn.style.marginTop = '5px';
            
            // Find the parent container and add button
            const parentDiv = titleInput.parentNode;
            const inputGroup = parentDiv.querySelector('.input-group');
            
            if (inputGroup) {
                // If inside input-group, add after
                inputGroup.appendChild(autoFillBtn);
            } else {
                // Otherwise add directly after the input
                parentDiv.appendChild(autoFillBtn);
            }
            
            autoFillBtn.addEventListener('click', function() {
                // Reset user edit flag and auto-fill
                userEditedTitle = false;
                autoFillFromCategory();
                showToast('Title auto-filled from category');
            });
        }
    }
});

</script>
@endpush