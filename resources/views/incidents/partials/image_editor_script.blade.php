<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
<script>
// ==========================================
// IMAGE EDITOR - Complete Fixed (No Duplicates)
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    let editorCanvas = null;
    let currentImageData = null;
    let currentFileIndex = null;
    let activeTool = 'pen';
    let history = [];
    let currentHistoryIndex = -1;
    let fabricInitialized = false;
    let shape = null;
    let mouseDown = false;
    let origX, origY;

    // Store edited images globally
    window.editedImages = {};

    // Wait for Fabric.js to load
    function waitForFabric() {
        if (typeof fabric !== 'undefined') {
            fabricInitialized = true;
            return true;
        }
        return false;
    }

    // Initialize Fabric.js canvas with image
    function initEditor(imageData, fileIndex) {
        if (!waitForFabric()) {
            setTimeout(() => initEditor(imageData, fileIndex), 500);
            return;
        }

        const canvasElement = document.getElementById('editorCanvas');
        if (!canvasElement) {
            console.error('Canvas element not found');
            return;
        }
        
        // Destroy existing canvas if any
        if (editorCanvas) {
            try {
                editorCanvas.dispose();
            } catch(e) {}
            editorCanvas = null;
        }
        
        // Load image
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = function() {
            const maxWidth = window.innerWidth * 0.85;
            const maxHeight = window.innerHeight * 0.75;
            let width = img.width;
            let height = img.height;
            
            if (width > maxWidth) {
                const ratio = maxWidth / width;
                width = maxWidth;
                height = height * ratio;
            }
            if (height > maxHeight) {
                const ratio = maxHeight / height;
                height = maxHeight;
                width = width * ratio;
            }
            
            // Create Fabric canvas
            editorCanvas = new fabric.Canvas(canvasElement, {
                width: width || 800,
                height: height || 600,
                backgroundColor: 'white',
                isDrawingMode: false,
                selection: true,
            });
            
            // Add image as background
            fabric.Image.fromURL(imageData, function(imgObj) {
                imgObj.scaleToWidth(width);
                imgObj.scaleToHeight(height);
                editorCanvas.setBackgroundImage(imgObj, editorCanvas.renderAll.bind(editorCanvas));
                editorCanvas.renderAll();
                
                currentImageData = imageData;
                currentFileIndex = fileIndex;
                
                saveHistory();
                setupTools();
                loadSavedDrawings(fileIndex);
            }, { crossOrigin: 'anonymous' });
        };
        img.onerror = function() {
            showToast('Failed to load image for editing');
        };
        img.src = imageData;
    }

    // Save current canvas state
    function saveCanvasState() {
        if (!editorCanvas || currentFileIndex === null) return;
        
        try {
            const dataURL = editorCanvas.toDataURL('image/png');
            const key = 'edited_image_' + currentFileIndex;
            window.editedImages[key] = dataURL;
            
            // Save drawings separately
            const objects = editorCanvas.getObjects();
            if (objects.length > 0) {
                const drawings = [];
                objects.forEach(obj => {
                    if (obj !== editorCanvas.backgroundImage) {
                        drawings.push(obj.toJSON());
                    }
                });
                if (drawings.length > 0) {
                    window.editedImages[key + '_drawings'] = drawings;
                }
            }
        } catch(e) {
            console.error('Save canvas state error:', e);
        }
    }

    // Load saved drawings
    function loadSavedDrawings(fileIndex) {
        if (!editorCanvas) return;
        
        try {
            const key = 'edited_image_' + fileIndex + '_drawings';
            const savedDrawings = window.editedImages[key];
            
            if (savedDrawings && savedDrawings.length > 0) {
                savedDrawings.forEach(drawingJSON => {
                    fabric.util.enlivenObjects([drawingJSON], function(objects) {
                        objects.forEach(obj => {
                            if (obj) {
                                editorCanvas.add(obj);
                            }
                        });
                        editorCanvas.renderAll();
                        saveHistory();
                    });
                });
            }
        } catch(e) {
            console.error('Load saved drawings error:', e);
        }
    }

    function saveHistory() {
        if (!editorCanvas) return;
        
        try {
            history = history.slice(0, currentHistoryIndex + 1);
            const json = editorCanvas.toJSON(['id', 'editable']);
            history.push(json);
            currentHistoryIndex = history.length - 1;
            
            if (history.length > 30) {
                history.shift();
                currentHistoryIndex--;
            }
        } catch(e) {
            console.error('Save history error:', e);
        }
    }

    function undoDraw() {
        if (!editorCanvas || currentHistoryIndex <= 0) return;
        
        try {
            currentHistoryIndex--;
            const json = history[currentHistoryIndex];
            editorCanvas.loadFromJSON(json, function() {
                editorCanvas.renderAll();
                saveCanvasState();
            });
        } catch(e) {
            console.error('Undo error:', e);
        }
    }

    function clearDraw() {
        if (!editorCanvas) return;
        
        try {
            const objects = editorCanvas.getObjects();
            objects.forEach(obj => {
                if (obj !== editorCanvas.backgroundImage) {
                    editorCanvas.remove(obj);
                }
            });
            editorCanvas.renderAll();
            saveHistory();
            saveCanvasState();
        } catch(e) {
            console.error('Clear error:', e);
        }
    }

    function setupTools() {
        if (!editorCanvas) return;

        document.querySelectorAll('[data-tool]').forEach(btn => {
            btn.removeEventListener('click', toolClickHandler);
            btn.addEventListener('click', toolClickHandler);
        });

        function toolClickHandler() {
            document.querySelectorAll('[data-tool]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeTool = this.dataset.tool;
            
            if (activeTool === 'pen') {
                editorCanvas.isDrawingMode = true;
                editorCanvas.freeDrawingBrush.color = document.getElementById('drawColor').value;
                editorCanvas.freeDrawingBrush.width = parseInt(document.getElementById('drawSize').value);
                editorCanvas.selection = false;
            } else {
                editorCanvas.isDrawingMode = false;
                editorCanvas.selection = true;
            }
        }

        const penBtn = document.querySelector('[data-tool="pen"]');
        if (penBtn) penBtn.classList.add('active');

        document.getElementById('drawColor')?.addEventListener('change', function() {
            if (activeTool === 'pen' && editorCanvas) {
                editorCanvas.freeDrawingBrush.color = this.value;
            }
        });

        document.getElementById('drawSize')?.addEventListener('input', function() {
            if (activeTool === 'pen' && editorCanvas) {
                editorCanvas.freeDrawingBrush.width = parseInt(this.value);
            }
        });

        document.getElementById('undoDraw')?.addEventListener('click', undoDraw);
        document.getElementById('clearDraw')?.addEventListener('click', clearDraw);
        document.getElementById('saveDraw')?.addEventListener('click', saveEditedImage);

        editorCanvas.on('object:added', function() {
            if (editorCanvas._objects && editorCanvas._objects.length > 0) {
                saveHistory();
                saveCanvasState();
            }
        });

        editorCanvas.on('object:modified', function() {
            saveHistory();
            saveCanvasState();
        });

        editorCanvas.on('object:removed', function() {
            saveCanvasState();
        });

        editorCanvas.off('mouse:down');
        editorCanvas.off('mouse:move');
        editorCanvas.off('mouse:up');
        
        editorCanvas.on('mouse:down', function(options) {
            if (activeTool === 'pen' || !options) return;
            
            const pointer = options.pointer;
            mouseDown = true;
            origX = pointer.x;
            origY = pointer.y;
            
            const color = document.getElementById('drawColor').value;
            const size = parseInt(document.getElementById('drawSize').value);
            
            switch(activeTool) {
                case 'line':
                    shape = new fabric.Line([origX, origY, origX, origY], {
                        stroke: color,
                        strokeWidth: size,
                        selectable: true,
                        originX: 'center',
                        originY: 'center'
                    });
                    break;
                case 'circle':
                    shape = new fabric.Circle({
                        left: origX,
                        top: origY,
                        radius: 0,
                        fill: 'transparent',
                        stroke: color,
                        strokeWidth: size,
                        selectable: true,
                        originX: 'center',
                        originY: 'center'
                    });
                    break;
                case 'rect':
                    shape = new fabric.Rect({
                        left: origX,
                        top: origY,
                        width: 0,
                        height: 0,
                        fill: 'transparent',
                        stroke: color,
                        strokeWidth: size,
                        selectable: true,
                        originX: 'center',
                        originY: 'center'
                    });
                    break;
                case 'text':
                    const text = new fabric.IText('Edit this text', {
                        left: origX,
                        top: origY,
                        fill: color,
                        fontSize: size * 5,
                        selectable: true
                    });
                    editorCanvas.add(text);
                    saveHistory();
                    saveCanvasState();
                    shape = null;
                    return;
            }
            
            if (shape) {
                editorCanvas.add(shape);
            }
        });

        editorCanvas.on('mouse:move', function(options) {
            if (!mouseDown || !shape || activeTool === 'pen' || !options) return;
            
            const pointer = options.pointer;
            
            switch(activeTool) {
                case 'line':
                    shape.set({ x2: pointer.x, y2: pointer.y });
                    break;
                case 'circle':
                    const radius = Math.sqrt(Math.pow(pointer.x - origX, 2) + Math.pow(pointer.y - origY, 2));
                    shape.set({ radius: radius });
                    break;
                case 'rect':
                    const width = pointer.x - origX;
                    const height = pointer.y - origY;
                    shape.set({ width: width, height: height });
                    break;
            }
            editorCanvas.renderAll();
        });

        editorCanvas.on('mouse:up', function() {
            if (mouseDown && shape && activeTool !== 'pen') {
                mouseDown = false;
                saveHistory();
                saveCanvasState();
                shape = null;
            }
        });
    }

    // Save edited image - CRITICAL FIX: Replace the file properly
    function saveEditedImage() {
        if (!editorCanvas) {
            showToast('No image to save');
            return;
        }
        
        try {
            const dataURL = editorCanvas.toDataURL('image/png');
            
            // Save to global storage
            const key = 'edited_image_' + currentFileIndex;
            window.editedImages[key] = dataURL;
            
            // Get the file input
            const fileInput = document.getElementById('fileInput');
            if (!fileInput) {
                showToast('File input not found');
                return;
            }
            
            // CRITICAL: Replace the file at the current index
            const currentFiles = fileInput.files;
            const dt = new DataTransfer();
            
            // Convert dataURL to File
            const editedFile = dataURLtoFile(dataURL, 'edited_image_' + Date.now() + '.png');
            if (!editedFile) {
                showToast('Failed to create edited file');
                return;
            }
            
            // Add all files, REPLACING the one at currentFileIndex
            for (let i = 0; i < currentFiles.length; i++) {
                if (i === currentFileIndex) {
                    // Replace with edited file
                    dt.items.add(editedFile);
                    console.log('Replaced file at index:', i);
                } else {
                    dt.items.add(currentFiles[i]);
                }
            }
            
            // Update the file input
            fileInput.files = dt.files;
            
            // Update the preview image
            updatePreviewImage(dataURL, currentFileIndex);
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('imageEditorModal'));
            if (modal) {
                modal.hide();
            }
            
            showToast('Image edited and saved successfully! ✅');
            
        } catch(e) {
            console.error('Save error:', e);
            showToast('Error saving image: ' + e.message);
        }
    }

    // Update preview image without creating duplicates
    function updatePreviewImage(dataURL, index) {
        const previewItems = document.querySelectorAll('.preview-item');
        
        // Find the preview item at the correct index
        let targetItem = null;
        let currentIndex = 0;
        
        for (const item of previewItems) {
            const img = item.querySelector('img');
            if (img && parseInt(img.dataset.fileIndex) === index) {
                targetItem = item;
                break;
            }
            if (img) currentIndex++;
        }
        
        if (targetItem) {
            const img = targetItem.querySelector('img');
            if (img) {
                img.src = dataURL;
                img.dataset.edited = 'true';
                console.log('Preview updated for index:', index);
            }
        } else {
            console.warn('Preview item not found for index:', index);
        }
    }

    // Helper: Convert dataURL to File
    function dataURLtoFile(dataurl, filename) {
        try {
            const arr = dataurl.split(',');
            const mime = arr[0].match(/:(.*?);/)[1];
            const bstr = atob(arr[1]);
            let n = bstr.length;
            const u8arr = new Uint8Array(n);
            while(n--) {
                u8arr[n] = bstr.charCodeAt(n);
            }
            return new File([u8arr], filename, {type: mime});
        } catch(e) {
            console.error('DataURL to file error:', e);
            return null;
        }
    }

    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 start-50 translate-middle-x mb-3 p-2 bg-dark text-white rounded-pill px-3';
        toast.style.zIndex = '9999';
        toast.style.fontSize = '0.8rem';
        toast.innerHTML = '<i class="fas fa-info-circle me-1"></i> ' + message;
        document.body.appendChild(toast);
        setTimeout(function() { 
            if (toast.parentNode) toast.remove(); 
        }, 3000);
    }

    // Expose editor function
    window.openImageEditor = function(imageData, fileIndex) {
        if (!imageData) {
            showToast('No image data found');
            return;
        }
        
        currentFileIndex = fileIndex || 0;
        
        // Check for edited version
        const key = 'edited_image_' + currentFileIndex;
        if (window.editedImages[key]) {
            imageData = window.editedImages[key];
        }
        
        // Mark preview as being edited
        document.querySelectorAll('.preview-item img').forEach((img) => {
            if (parseInt(img.dataset.fileIndex) === currentFileIndex) {
                img.dataset.editing = 'true';
            } else {
                img.dataset.editing = 'false';
            }
        });
        
        // Show modal
        const modalElement = document.getElementById('imageEditorModal');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        
        const onShown = function() {
            initEditor(imageData, currentFileIndex);
            modalElement.removeEventListener('shown.bs.modal', onShown);
        };
        modalElement.addEventListener('shown.bs.modal', onShown, { once: true });
        
        const onHidden = function() {
            if (editorCanvas) {
                try {
                    editorCanvas.dispose();
                } catch(e) {}
                editorCanvas = null;
            }
            document.querySelectorAll('.preview-item img').forEach(img => {
                img.dataset.editing = 'false';
            });
            modalElement.removeEventListener('hidden.bs.modal', onHidden);
        };
        modalElement.addEventListener('hidden.bs.modal', onHidden, { once: true });
    };
});

// ==========================================
// FILE HANDLING - No Duplicates
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const previewContainer = document.getElementById('previewContainer');
    const fileInput = document.getElementById('fileInput');
    let selectedFiles = new DataTransfer();

    // Initialize with existing files if any
    if (fileInput && fileInput.files.length > 0) {
        for (let i = 0; i < fileInput.files.length; i++) {
            selectedFiles.items.add(fileInput.files[i]);
            createPreview(fileInput.files[i], i);
        }
    }

    // Handle file selection
    window.handleFiles = async function(files) {
        const fileArray = Array.from(files);
        let currentIndex = selectedFiles.files.length;

        for (const file of fileArray) {
            // Validate file size
            if (file.size > 20 * 1024 * 1024) {
                alert('File "' + file.name + '" is too large. Max 20MB allowed.');
                continue;
            }

            // Check for duplicate
            let isDuplicate = false;
            Array.from(selectedFiles.files).forEach(f => {
                if (f.name === file.name && f.size === file.size) isDuplicate = true;
            });
            if (isDuplicate) continue;

            // Process image
            let processedFile = file;
            if (file.type.startsWith('image/') && file.size > 200 * 1024) {
                processedFile = await window.compressImageNative(file);
            }

            // Add to DataTransfer
            selectedFiles.items.add(processedFile);

            // Create preview
            createPreview(processedFile, currentIndex);
            currentIndex++;
        }

        // Update file input
        fileInput.files = selectedFiles.files;
        
        // Trigger change event
        const event = new Event('change', { bubbles: true });
        fileInput.dispatchEvent(event);
    };

    // Create preview with proper tracking
    function createPreview(file, fileIndex) {
        const div = document.createElement('div');
        div.className = 'preview-item';
        div.dataset.filename = file.name;
        div.dataset.fileIndex = fileIndex;

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const imageData = e.target.result;
                div.innerHTML = `
                    <img src="${imageData}" alt="${escapeHtml(file.name)}" data-file-index="${fileIndex}">
                    <div class="preview-overlay">
                        <button type="button" class="btn-edit-image" data-index="${fileIndex}" title="Edit Image">
                            <i class="fas fa-pen"></i>
                        </button>
                    </div>
                    <button type="button" class="btn-remove" data-file-index="${fileIndex}">×</button>
                `;
                
                // Edit button
                const editBtn = div.querySelector('.btn-edit-image');
                if (editBtn) {
                    editBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const index = parseInt(this.dataset.index);
                        const img = div.querySelector('img');
                        if (img && window.openImageEditor) {
                            // Check if there's an edited version
                            const key = 'edited_image_' + index;
                            const imageData = window.editedImages && window.editedImages[key] 
                                ? window.editedImages[key] 
                                : img.src;
                            window.openImageEditor(imageData, index);
                        }
                    });
                }

                // Remove button
                const removeBtn = div.querySelector('.btn-remove');
                if (removeBtn) {
                    removeBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const index = parseInt(this.dataset.fileIndex);
                        removeFile(index);
                    });
                }
            };
            reader.readAsDataURL(file);
        } else {
            // Non-image preview
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
                <button type="button" class="btn-remove" data-file-index="${fileIndex}">×</button>
            `;
            
            const removeBtn = div.querySelector('.btn-remove');
            if (removeBtn) {
                removeBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const index = parseInt(this.dataset.fileIndex);
                    removeFile(index);
                });
            }
        }

        previewContainer.appendChild(div);
    }

    // Remove file and its preview
    function removeFile(index) {
        // Remove from DataTransfer
        const newDT = new DataTransfer();
        Array.from(selectedFiles.files).forEach((f, i) => {
            if (i !== index) newDT.items.add(f);
        });
        selectedFiles = newDT;
        fileInput.files = selectedFiles.files;

        // Remove preview element
        const items = previewContainer.querySelectorAll('.preview-item');
        items.forEach((item, i) => {
            if (i === index) {
                item.remove();
            }
        });

        // Update indices of remaining items
        previewContainer.querySelectorAll('.preview-item').forEach((item, i) => {
            item.dataset.fileIndex = i;
            const img = item.querySelector('img');
            if (img) img.dataset.fileIndex = i;
            const editBtn = item.querySelector('.btn-edit-image');
            if (editBtn) editBtn.dataset.index = i;
            const removeBtn = item.querySelector('.btn-remove');
            if (removeBtn) removeBtn.dataset.fileIndex = i;
        });

        // Clean up edited image data
        cleanupEditedData(index);
    }

    // Clean up edited image data for removed file
    function cleanupEditedData(index) {
        if (window.editedImages) {
            // Remove edited data for this index
            delete window.editedImages['edited_image_' + index];
            delete window.editedImages['edited_image_' + index + '_drawings'];
            
            // Shift data for higher indices
            const keys = Object.keys(window.editedImages);
            keys.forEach(key => {
                const match = key.match(/edited_image_(\d+)/);
                if (match && parseInt(match[1]) > index) {
                    const newIndex = parseInt(match[1]) - 1;
                    const newKey = key.replace(/edited_image_\d+/, 'edited_image_' + newIndex);
                    window.editedImages[newKey] = window.editedImages[key];
                    delete window.editedImages[key];
                }
            });
        }
    }

    // Helper functions
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

    function formatSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    // Image compression function
    window.compressImageNative = function(file) {
        return new Promise((resolve) => {
            if (!file.type.startsWith('image/') || file.size < 200 * 1024) {
                resolve(file);
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    const maxDimension = 1920;
                    let width = img.width;
                    let height = img.height;

                    if (width > maxDimension || height > maxDimension) {
                        if (width > height) {
                            height = Math.round((height * maxDimension) / width);
                            width = maxDimension;
                        } else {
                            width = Math.round((width * maxDimension) / height);
                            height = maxDimension;
                        }
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob((blob) => {
                        if (blob && blob.size < file.size) {
                            const compressedFile = new File([blob], file.name, {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            });
                            resolve(compressedFile);
                        } else {
                            resolve(file);
                        }
                    }, 'image/jpeg', 0.7);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    };

    // Expose for upload zone
    window.selectedFiles = selectedFiles;
});

</script>