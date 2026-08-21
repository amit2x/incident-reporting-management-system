/**
 * ==========================================
 * IMAGE UPLOAD HANDLER - Reusable Module
 * ==========================================
 * 
 * Usage:
 * 
 * 1. Initialize:
 *    const uploader = new ImageUploadHandler({
 *        maxSize: 20 * 1024 * 1024, // 20MB
 *        maxDimension: 1920,
 *        webpQuality: 0.82,
 *        preserveExif: true,
 *        onFileProcessed: function(file, exifData) { ... },
 *        onError: function(message) { ... },
 *    });
 * 
 * 2. Process files:
 *    uploader.processFiles(files).then(results => {
 *        // results contains processed files with metadata
 *    });
 * 
 * 3. Attach to input:
 *    uploader.attachToInput(document.getElementById('fileInput'));
 * 
 * 4. Attach to dropzone:
 *    uploader.attachToDropzone(document.getElementById('dropzone'));
 * 
 * 5. Get processed files:
 *    const files = uploader.getFiles(); // Returns DataTransfer
 *    const metadata = uploader.getMetadata(); // Returns all EXIF data
 * 
 * 6. Reset:
 *    uploader.reset();
 */

class ImageUploadHandler {
    constructor(options = {}) {
        // Configuration
        this.config = {
            maxSize: options.maxSize || 20 * 1024 * 1024, // 20MB
            maxDimension: options.maxDimension || 1920,
            webpQuality: options.webpQuality || 0.82,
            preserveExif: options.preserveExif !== undefined ? options.preserveExif : true,
            allowedTypes: options.allowedTypes || ['image/*', 'video/*', '.pdf', '.doc', '.docx', '.xls', '.xlsx'],
            maxFiles: options.maxFiles || 50,
            onFileProcessed: options.onFileProcessed || null,
            onError: options.onError || null,
            onProgress: options.onProgress || null,
            debug: options.debug || false,
        };

        // State
        this.files = new DataTransfer();
        this.metadata = {};
        this.processedFiles = [];
        this.fileIndexMap = {};
        this.isProcessing = false;

        // Bind methods
        this.handleFiles = this.handleFiles.bind(this);
        this.processFile = this.processFile.bind(this);
        this.extractExif = this.extractExif.bind(this);
        this.convertToWebP = this.convertToWebP.bind(this);
        this.removeFile = this.removeFile.bind(this);
        this.reset = this.reset.bind(this);

        // EXIF library status
        this.exifLoaded = false;
        this.exifLoading = false;

        // Load EXIF library if needed
        if (this.config.preserveExif) {
            this.loadExifLibrary();
        }
    }

    /**
     * Process multiple files
     */
    async processFiles(fileList, options = {}) {
        if (this.isProcessing) {
            this._log('Already processing, please wait...');
            return;
        }

        this.isProcessing = true;
        const results = [];
        const startIndex = this.files.files.length;

        try {
            const files = Array.from(fileList);
            const filteredFiles = this._filterFiles(files);

            for (let i = 0; i < filteredFiles.length; i++) {
                const file = filteredFiles[i];
                const index = startIndex + i;

                this._log('Processing file:', file.name);

                // Emit progress
                if (this.config.onProgress) {
                    this.config.onProgress({
                        current: i + 1,
                        total: filteredFiles.length,
                        file: file.name,
                        status: 'processing',
                    });
                }

                const result = await this.processFile(file, index);
                results.push(result);

                if (result.success) {
                    this.files.items.add(result.file);
                    this.metadata[index] = result.metadata;
                    this.processedFiles.push(result);
                    this.fileIndexMap[file.name] = index;

                    // Callback
                    if (this.config.onFileProcessed) {
                        this.config.onFileProcessed(result.file, result.metadata, index);
                    }

                    this._log('File processed:', result.file.name, result.file.size);
                }
            }

            this.isProcessing = false;

            if (this.config.onProgress) {
                this.config.onProgress({
                    current: filteredFiles.length,
                    total: filteredFiles.length,
                    status: 'completed',
                });
            }

            return {
                success: true,
                files: this.files.files,
                metadata: this.metadata,
                processedFiles: this.processedFiles,
                total: this.files.files.length,
            };

        } catch (error) {
            this.isProcessing = false;
            this._log('Error processing files:', error);

            if (this.config.onError) {
                this.config.onError(error.message || 'Failed to process files');
            }

            return {
                success: false,
                error: error.message,
            };
        }
    }

    /**
     * Process a single file
     */
    async processFile(file, index) {
        const result = {
            success: false,
            file: file,
            metadata: null,
            originalName: file.name,
            originalSize: file.size,
            originalType: file.type,
        };

        try {
            // Validate file
            const validation = this._validateFile(file);
            if (!validation.valid) {
                throw new Error(validation.message);
            }

            let processedFile = file;
            let exifData = null;

            // Process image files
            if (file.type.startsWith('image/')) {
                // Extract EXIF first (if enabled)
                if (this.config.preserveExif) {
                    exifData = await this.extractExif(file);
                }

                // Convert to WebP
                processedFile = await this.convertToWebP(file);
            }

            // Build metadata
            const metadata = {
                original: {
                    name: file.name,
                    size: file.size,
                    type: file.type,
                },
                processed: {
                    name: processedFile.name,
                    size: processedFile.size,
                    type: processedFile.type,
                    compression_ratio: processedFile.size < file.size
                        ? Math.round((1 - processedFile.size / file.size) * 100)
                        : 0,
                },
                exif: exifData,
                timestamp: new Date().toISOString(),
                index: index,
            };

            // Extract GPS if available
            if (exifData && exifData.gps) {
                metadata.location = exifData.gps;
            }

            result.success = true;
            result.file = processedFile;
            result.metadata = metadata;

            return result;

        } catch (error) {
            result.error = error.message;
            this._log('File processing failed:', file.name, error.message);

            if (this.config.onError) {
                this.config.onError(`Failed to process ${file.name}: ${error.message}`);
            }

            return result;
        }
    }

    /**
     * Extract EXIF data from image
     */
    extractExif(file) {
        return new Promise((resolve) => {
            if (!this.config.preserveExif || !file.type.startsWith('image/')) {
                resolve(null);
                return;
            }

            // Check if EXIF is available
            if (typeof EXIF === 'undefined') {
                this.loadExifLibrary().then(() => {
                    this._extractExifFromFile(file, resolve);
                }).catch(() => {
                    resolve(null);
                });
                return;
            }

            this._extractExifFromFile(file, resolve);
        });
    }

    /**
     * Extract EXIF from file using EXIF.js
     */
    _extractExifFromFile(file, resolve) {
        try {
            EXIF.getData(file, function() {
                const exifData = EXIF.getAllTags(this);

                if (Object.keys(exifData).length > 0) {
                    const cleanExif = this._cleanExifData(exifData);
                    resolve(cleanExif);
                } else {
                    resolve(null);
                }
            }.bind(this));
        } catch (e) {
            this._log('EXIF extraction failed:', e.message);
            resolve(null);
        }
    }

    /**
     * Clean EXIF data
     */
    _cleanExifData(exifData) {
        const cleaned = {};
        const skipKeys = ['undefined', 'UserComment', 'MakerNote', 'thumbnail'];

        for (const [key, value] of Object.entries(exifData)) {
            if (skipKeys.includes(key)) continue;
            if (value === undefined || value === null) continue;

            if (typeof value === 'number' || typeof value === 'string') {
                cleaned[key] = value;
            } else if (value && typeof value === 'object' && value.numerator !== undefined) {
                cleaned[key] = value.numerator / value.denominator;
            } else if (value && typeof value === 'object') {
                try {
                    cleaned[key] = JSON.stringify(value);
                } catch (e) {
                    cleaned[key] = String(value);
                }
            }
        }

        // Extract GPS data
        if (exifData.GPSLatitude && exifData.GPSLongitude) {
            cleaned.gps = {
                latitude: this._convertGpsToDecimal(exifData.GPSLatitude, exifData.GPSLatitudeRef),
                longitude: this._convertGpsToDecimal(exifData.GPSLongitude, exifData.GPSLongitudeRef),
                altitude: exifData.GPSAltitude || null,
            };
        }

        return cleaned;
    }

    /**
     * Convert GPS coordinates to decimal
     */
    _convertGpsToDecimal(gpsArray, ref) {
        let degrees = gpsArray[0].numerator / gpsArray[0].denominator;
        let minutes = gpsArray[1].numerator / gpsArray[1].denominator;
        let seconds = gpsArray[2].numerator / gpsArray[2].denominator;

        let decimal = degrees + minutes / 60 + seconds / 3600;

        if (ref === 'S' || ref === 'W') {
            decimal = -decimal;
        }

        return parseFloat(decimal.toFixed(6));
    }

    /**
     * Convert image to WebP
     */
    convertToWebP(file) {
        return new Promise((resolve) => {
            // Skip non-images
            if (!file.type.startsWith('image/')) {
                resolve(file);
                return;
            }

            // Skip if already WebP and small enough
            if (file.type === 'image/webp' && file.size < 300 * 1024) {
                resolve(file);
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    let width = img.width;
                    let height = img.height;
                    const maxDim = this.config.maxDimension;

                    // Resize if needed
                    if (width > maxDim || height > maxDim) {
                        if (width > height) {
                            height = Math.round((height * maxDim) / width);
                            width = maxDim;
                        } else {
                            width = Math.round((width * maxDim) / height);
                            height = maxDim;
                        }
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob((blob) => {
                        if (blob) {
                            const webpFile = new File(
                                [blob],
                                file.name.replace(/\.[^.]+$/, '.webp'),
                                {
                                    type: 'image/webp',
                                    lastModified: Date.now(),
                                }
                            );
                            resolve(webpFile);
                        } else {
                            resolve(file);
                        }
                    }, 'image/webp', this.config.webpQuality);
                }.bind(this);
                img.onerror = function() {
                    resolve(file);
                };
                img.src = e.target.result;
            }.bind(this);
            reader.onerror = function() {
                resolve(file);
            };
            reader.readAsDataURL(file);
        });
    }

    /**
     * Load EXIF.js library dynamically
     */
    loadExifLibrary() {
        return new Promise((resolve, reject) => {
            if (this.exifLoaded) {
                resolve();
                return;
            }

            if (this.exifLoading) {
                // Wait for existing load
                const checkLoaded = setInterval(() => {
                    if (this.exifLoaded) {
                        clearInterval(checkLoaded);
                        resolve();
                    }
                }, 100);
                return;
            }

            this.exifLoading = true;

            if (typeof EXIF !== 'undefined') {
                this.exifLoaded = true;
                this.exifLoading = false;
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/exif-js';
            script.onload = () => {
                this.exifLoaded = true;
                this.exifLoading = false;
                resolve();
            };
            script.onerror = () => {
                this.exifLoading = false;
                reject(new Error('Failed to load EXIF library'));
            };
            document.head.appendChild(script);
        });
    }

    /**
     * Validate a single file
     */
    _validateFile(file) {
        // Check size
        if (file.size > this.config.maxSize) {
            return {
                valid: false,
                message: `File "${file.name}" is too large (${this._formatSize(file.size)}). Max ${this._formatSize(this.config.maxSize)} allowed.`,
            };
        }

        // Check type
        const allowedTypes = this.config.allowedTypes;
        if (allowedTypes && allowedTypes.length > 0) {
            const isAllowed = allowedTypes.some(type => {
                if (type.endsWith('/*')) {
                    const prefix = type.replace('/*', '');
                    return file.type.startsWith(prefix);
                }
                if (type.startsWith('.')) {
                    const ext = '.' + file.name.split('.').pop().toLowerCase();
                    return ext === type;
                }
                return file.type === type;
            });

            if (!isAllowed) {
                return {
                    valid: false,
                    message: `File type "${file.type}" is not allowed.`,
                };
            }
        }

        return { valid: true };
    }

    /**
     * Filter files (remove duplicates, invalid types)
     */
    _filterFiles(files) {
        const filtered = [];
        const existingNames = new Set();

        // Get existing file names
        for (const f of this.files.files) {
            existingNames.add(f.name);
        }

        for (const file of files) {
            // Check duplicate
            if (existingNames.has(file.name)) {
                this._log('Duplicate file skipped:', file.name);
                continue;
            }

            filtered.push(file);
        }

        return filtered;
    }

    /**
     * Remove a file by index
     */
    removeFile(index) {
        const newDT = new DataTransfer();
        const newMetadata = {};

        // Rebuild file list
        let newIndex = 0;
        for (let i = 0; i < this.files.files.length; i++) {
            if (i !== index) {
                newDT.items.add(this.files.files[i]);
                newMetadata[newIndex] = this.metadata[i];
                newIndex++;
            }
        }

        this.files = newDT;
        this.metadata = newMetadata;

        // Update processed files
        this.processedFiles = this.processedFiles.filter((f, i) => i !== index);

        return {
            files: this.files.files,
            metadata: this.metadata,
            count: this.files.files.length,
        };
    }

    /**
     * Reset all state
     */
    reset() {
        this.files = new DataTransfer();
        this.metadata = {};
        this.processedFiles = [];
        this.fileIndexMap = {};
        this.isProcessing = false;
    }

    /**
     * Get processed files
     */
    getFiles() {
        return this.files.files;
    }

    /**
     * Get metadata for all files
     */
    getMetadata() {
        return this.metadata;
    }

    /**
     * Get file count
     */
    getFileCount() {
        return this.files.files.length;
    }

    /**
     * Check if processing
     */
    isProcessing() {
        return this.isProcessing;
    }

    /**
     * Attach to file input
     */
    attachToInput(inputElement, options = {}) {
        if (!inputElement) return;

        const handler = this;

        inputElement.addEventListener('change', function(e) {
            const files = this.files;
            const processOptions = options.processOptions || {};

            // Reset input to allow re-upload of same files
            if (options.clearOnSelect !== false) {
                this.value = '';
            }

            handler.processFiles(files, processOptions).then((result) => {
                if (result.success && options.onComplete) {
                    options.onComplete(result);
                }
            });
        });

        // Return for chaining
        return this;
    }

    /**
     * Attach to dropzone
     */
    attachToDropzone(dropzoneElement, options = {}) {
        if (!dropzoneElement) return;

        const handler = this;

        // Drag events
        dropzoneElement.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-active');
        });

        dropzoneElement.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('drag-active');
        });

        dropzoneElement.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-active');

            const files = e.dataTransfer.files;
            const processOptions = options.processOptions || {};

            handler.processFiles(files, processOptions).then((result) => {
                if (result.success && options.onComplete) {
                    options.onComplete(result);
                }
            });
        });

        // Click to open file dialog
        if (options.clickToOpen !== false) {
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.multiple = true;
            fileInput.style.display = 'none';
            dropzoneElement.appendChild(fileInput);

            dropzoneElement.addEventListener('click', function(e) {
                if (e.target.closest('.btn-remove')) return;
                fileInput.click();
            });

            this.attachToInput(fileInput, options);
        }

        return this;
    }

    /**
     * Format file size
     */
    _formatSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    /**
     * Log debug messages
     */
    _log(...args) {
        if (this.config.debug) {
            console.log('[ImageUploadHandler]', ...args);
        }
    }
}

// Export for use in browser
if (typeof window !== 'undefined') {
    window.ImageUploadHandler = ImageUploadHandler;
}

// Export for use with module bundlers
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ImageUploadHandler;
}