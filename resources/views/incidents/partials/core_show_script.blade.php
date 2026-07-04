@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Initialize all tooltips on the page
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="modal"][title]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    // ==========================================
    // MEDIA MODAL - Image/Video Viewer
    // ==========================================
    let mediaItems = [];
    let currentMediaIndex = 0;
    let mediaModal = null;

    // Get media data from data attribute
    function getMediaData() {
        try {
            const element = document.getElementById('mediaData');
            if (element && element.dataset.media) {
                return JSON.parse(element.dataset.media);
            }
        } catch(e) {
            console.warn('Error parsing media data:', e);
        }
        return [];
    }

    // Initialize media items
    mediaItems = getMediaData();

    // Initialize modal
    const modalElement = document.getElementById('mediaModal');
    if (modalElement) {
        try {
            mediaModal = new bootstrap.Modal(modalElement, {
                backdrop: 'static',
                keyboard: true
            });
            
            modalElement.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowLeft') navigateMedia(-1);
                if (e.key === 'ArrowRight') navigateMedia(1);
            });
            
            modalElement.addEventListener('shown.bs.modal', function() {
                const nextBtn = document.getElementById('nextMediaBtn');
                if (nextBtn && nextBtn.style.display !== 'none') {
                    nextBtn.focus();
                }
            });
        } catch(e) {
            console.warn('Error initializing modal:', e);
        }
    }

    /**
     * Open media modal at specific index
     */
    window.openMediaModal = function(index) {
        if (!mediaItems.length || index >= mediaItems.length) {
            console.warn('No media items or invalid index');
            return;
        }
        
        currentMediaIndex = index;
        renderMediaThumbnails();
        displayCurrentMedia();
        if (mediaModal) {
            mediaModal.show();
        }
    };

    /**
     * Display the current media item
     */
    function displayCurrentMedia() {
        const item = mediaItems[currentMediaIndex];
        if (!item) return;

        const img = document.getElementById('modalImage');
        const video = document.getElementById('modalVideo');
        const videoSource = document.getElementById('modalVideoSource');
        const doc = document.getElementById('modalDocument');
        const docName = document.getElementById('modalDocName');
        const counter = document.getElementById('mediaCounter');
        const fileName = document.getElementById('mediaFileName');
        const downloadBtn = document.getElementById('mediaDownloadBtn');
        const loader = document.getElementById('mediaLoader');
        const prevBtn = document.getElementById('prevMediaBtn');
        const nextBtn = document.getElementById('nextMediaBtn');

        img.style.display = 'none';
        video.style.display = 'none';
        doc.style.display = 'none';
        if (loader) loader.style.display = 'block';
        img.src = '';
        if (video) {
            video.pause();
            videoSource.src = '';
        }

        if (counter) counter.textContent = (currentMediaIndex + 1) + ' / ' + mediaItems.length;
        if (fileName) fileName.textContent = item.name;
        if (downloadBtn) downloadBtn.href = item.url;

        if (prevBtn) prevBtn.style.display = currentMediaIndex > 0 ? 'block' : 'none';
        if (nextBtn) nextBtn.style.display = currentMediaIndex < mediaItems.length - 1 ? 'block' : 'none';

        document.querySelectorAll('.media-thumbnail, .media-thumbnail-doc').forEach((thumb, idx) => {
            thumb.classList.toggle('active', idx === currentMediaIndex);
        });

        if (item.is_image) {
            img.onload = function() {
                if (loader) loader.style.display = 'none';
                img.style.display = 'block';
            };
            img.onerror = function() {
                if (loader) loader.style.display = 'none';
                img.style.display = 'block';
                img.src = '/images/image-placeholder.png';
            };
            img.src = item.url;
        } else if (item.is_video) {
            videoSource.src = item.url;
            video.load();
            video.onloadeddata = function() {
                if (loader) loader.style.display = 'none';
                video.style.display = 'block';
            };
            video.onerror = function() {
                if (loader) loader.style.display = 'none';
                video.style.display = 'block';
                video.innerHTML = '<p class="text-white text-center p-4">Error loading video</p>';
            };
            video.style.display = 'none';
        } else {
            if (loader) loader.style.display = 'none';
            doc.style.display = 'block';
            if (docName) docName.textContent = item.name;
            if (downloadBtn) downloadBtn.download = item.name;
        }
    }

    /**
     * Navigate through media
     */
    function navigateMedia(direction) {
        const newIndex = currentMediaIndex + direction;
        if (newIndex >= 0 && newIndex < mediaItems.length) {
            currentMediaIndex = newIndex;
            displayCurrentMedia();
        }
    }

    /**
     * Render thumbnails at the bottom of modal
     */
    function renderMediaThumbnails() {
        const container = document.getElementById('mediaThumbnails');
        if (!container) return;

        container.innerHTML = mediaItems.map(function(item, index) {
            let content = '';
            if (item.is_image) {
                content = '<img src="' + item.thumbnail + '" class="media-thumbnail" alt="' + item.name + '" data-index="' + index + '">';
            } else if (item.is_video) {
                content = '<div class="media-thumbnail d-flex align-items-center justify-content-center bg-dark text-white" data-index="' + index + '" style="width:60px;height:60px;border-radius:8px;"><i class="fas fa-play"></i></div>';
            } else {
                let icon = 'fa-file';
                if (item.mime_type && item.mime_type.includes('pdf')) icon = 'fa-file-pdf';
                else if (item.mime_type && item.mime_type.includes('word')) icon = 'fa-file-word';
                else if (item.mime_type && item.mime_type.includes('excel')) icon = 'fa-file-excel';
                else if (item.mime_type && item.mime_type.includes('zip')) icon = 'fa-file-archive';
                
                var fileExt = 'file';
                if (item.mime_type) {
                    var parts = item.mime_type.split('/');
                    fileExt = parts[1] || 'file';
                }
                
                content = '<div class="media-thumbnail-doc" data-index="' + index + '"><i class="fas ' + icon + ' fa-lg"></i><span class="file-type-badge">' + fileExt + '</span></div>';
            }
            return '<div class="media-thumbnail-wrapper">' + content + '</div>';
        }).join('');

        container.querySelectorAll('[data-index]').forEach(function(el) {
            el.addEventListener('click', function() {
                var index = parseInt(this.dataset.index);
                if (index !== currentMediaIndex) {
                    currentMediaIndex = index;
                    displayCurrentMedia();
                }
            });
        });
    }

    /**
     * Close media modal
     */
    function closeMediaModal() {
        if (mediaModal) {
            mediaModal.hide();
            var video = document.getElementById('modalVideo');
            if (video) video.pause();
        }
    }

    // Bind navigation buttons
    var prevBtn = document.getElementById('prevMediaBtn');
    if (prevBtn) {
        prevBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            navigateMedia(-1);
        });
    }
    
    var nextBtn = document.getElementById('nextMediaBtn');
    if (nextBtn) {
        nextBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            navigateMedia(1);
        });
    }
    
    document.addEventListener('keydown', function(e) {
        var modal = document.getElementById('mediaModal');
        if (modal && modal.classList.contains('show')) {
            if (e.key === 'ArrowLeft') navigateMedia(-1);
            if (e.key === 'ArrowRight') navigateMedia(1);
            if (e.key === 'Escape') closeMediaModal();
        }
    });

    // ==========================================
    // OPEN COMMENT MEDIA IN MODAL
    // ==========================================
    window.openCommentMediaInModal = function(url, name, type) {
        console.log('Opening comment media:', url, name, type);
        
        if (!url) {
            console.warn('No URL provided for media');
            return;
        }
        
        var originalItems = mediaItems.slice(0);
        
        var tempItem = {
            id: 'comment_' + Date.now(),
            url: url,
            thumbnail: url,
            type: type || 'image',
            name: name || 'Media',
            mime_type: type === 'image' ? 'image/jpeg' : 'application/octet-stream',
            is_image: type === 'image',
            is_video: false,
            is_document: type !== 'image'
        };
        
        mediaItems = [tempItem];
        currentMediaIndex = 0;
        renderMediaThumbnails();
        displayCurrentMedia();
        
        if (mediaModal) {
            mediaModal.show();
        }
        
        var modalElement = document.getElementById('mediaModal');
        if (modalElement) {
            var restoreFn = function() {
                mediaItems = originalItems;
                if (mediaItems.length > 0) {
                    renderMediaThumbnails();
                }
                modalElement.removeEventListener('hidden.bs.modal', restoreFn);
            };
            modalElement.addEventListener('hidden.bs.modal', restoreFn);
        }
    };

    // ==========================================
    // BUILD COMMENT HTML
    // ==========================================
    function escapeHtml(text) {
        if (!text) return '';
        const map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    window.buildCommentHTML = function(comment, isReply = false) {
        let contentHtml = '';
        if (comment.content) {
            let text = escapeHtml(comment.content);
            text = text.replace(/@(\w+)/g, '<span class="text-primary fw-medium">@$1</span>');
            text = text.replace(/#(\w+)/g, '<span class="text-success fw-medium">#$1</span>');
            text = text.replace(/\n/g, '<br>');
            contentHtml = `<div class="comment-text mb-1" style="font-size:0.8125rem;word-break:break-word;">${text}</div>`;
        }
        
        let attachmentsHtml = '';
        if (comment.attachments && comment.attachments.length > 0) {
            const images = comment.attachments.filter(a => (a.type || '').startsWith('image/'));
            const files = comment.attachments.filter(a => !(a.type || '').startsWith('image/'));
            
            if (images.length > 0) {
                attachmentsHtml += '<div class="d-flex flex-wrap gap-2 mb-2">' + 
                    images.map((img, idx) => {
                        const url = `/storage/${img.path}`;
                        const fileName = img.name || 'Image';
                        return `<img src="${url}" 
                                     style="max-width:150px;max-height:150px;object-fit:cover;border-radius:8px;cursor:pointer;" 
                                     class="border comment-img-click"
                                     data-url="${url}"
                                     data-name="${fileName}"
                                     data-type="image"
                                     loading="lazy"
                                     onclick="openCommentMediaInModal('${url}', '${fileName.replace(/'/g, "\\'")}', 'image'); return false;">`;
                    }).join('') + 
                '</div>';
            }
            
            if (files.length > 0) {
                attachmentsHtml += '<div class="d-flex flex-wrap gap-1">' + 
                    files.map(f => {
                        const url = `/storage/${f.path}`;
                        let icon = 'fa-file';
                        const fileName = f.name || 'File';
                        const ext = fileName.split('.').pop().toLowerCase();
                        
                        if (['pdf'].includes(ext)) icon = 'fa-file-pdf';
                        else if (['doc', 'docx'].includes(ext)) icon = 'fa-file-word';
                        else if (['xls', 'xlsx', 'csv'].includes(ext)) icon = 'fa-file-excel';
                        else if (['zip', 'rar', '7z'].includes(ext)) icon = 'fa-file-archive';
                        else if (['txt', 'log'].includes(ext)) icon = 'fa-file-alt';
                        
                        return `<span class="badge bg-light text-dark text-decoration-none d-inline-flex align-items-center gap-1 comment-file-click"
                                     style="font-size:0.7rem;padding:6px 10px;cursor:pointer;"
                                     data-url="${url}"
                                     data-name="${fileName}"
                                     data-type="document"
                                     onclick="openCommentMediaInModal('${url}', '${fileName.replace(/'/g, "\\'")}', 'document'); return false;">
                                    <i class="fas ${icon} text-muted"></i>${fileName}
                                </span>`;
                    }).join('') + 
                '</div>';
            }
        }
        
        const marginClass = isReply ? 'ms-4 mt-2' : 'mb-3 pb-3 border-bottom';
        
        return `<div class="comment-thread ${marginClass}" id="comment-${comment.id}">
            <div class="d-flex gap-2">
                <img src="${comment.user?.avatar_url || '/images/default-avatar.png'}" class="rounded-circle flex-shrink-0" width="36" height="36">
                <div class="flex-grow-1 min-width-0">
                    <div class="d-flex gap-2 align-items-center mb-1">
                        <strong style="font-size:0.8125rem;">${comment.user?.name || 'Unknown'}</strong>
                        <small class="text-muted">${comment.created_at_diff || ''}</small>
                        ${comment.is_internal ? '<span class="badge bg-warning text-dark" style="font-size:0.6rem;">Internal</span>' : ''}
                    </div>
                    ${contentHtml}
                    ${attachmentsHtml}
                    <div class="d-flex gap-2 mt-1">
                        <button class="btn btn-link btn-sm text-muted p-0 reply-toggle-btn" style="font-size:0.6875rem;" 
                                data-comment-id="${comment.id}" data-username="${comment.user?.name || 'User'}">
                            <i class="fas fa-reply me-1"></i>Reply
                        </button>
                    </div>
                    <div class="replies-container mt-2" id="replies-${comment.id}"></div>
                </div>
            </div>
        </div>`;
    };

    // ==========================================
    // REUSABLE IMAGE COMPRESSION UTILITY
    // ==========================================
    const ImageCompressor = {
        MAX_DIMENSION: 1920,
        QUALITY: 0.7,
        MIN_SIZE_TO_COMPRESS: 200 * 1024,

        compress: function(file) {
            return new Promise((resolve) => {
                if (!file.type.startsWith('image/') || file.size < this.MIN_SIZE_TO_COMPRESS) {
                    resolve(file);
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = new Image();
                    img.onload = () => {
                        let { width, height } = img;

                        if (width > this.MAX_DIMENSION || height > this.MAX_DIMENSION) {
                            if (width > height) {
                                height = Math.round((height * this.MAX_DIMENSION) / width);
                                width = this.MAX_DIMENSION;
                            } else {
                                width = Math.round((width * this.MAX_DIMENSION) / height);
                                height = this.MAX_DIMENSION;
                            }
                        }

                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        canvas.toBlob((blob) => {
                            if (blob && blob.size < file.size) {
                                const compressed = new File([blob], file.name, {
                                    type: 'image/jpeg',
                                    lastModified: Date.now()
                                });
                                resolve(compressed);
                            } else {
                                resolve(file);
                            }
                        }, 'image/jpeg', this.QUALITY);
                    };
                    img.onerror = () => resolve(file);
                    img.src = e.target.result;
                };
                reader.onerror = () => resolve(file);
                reader.readAsDataURL(file);
            });
        },

        compressMultiple: async function(files) {
            const results = [];
            for (const file of Array.from(files)) {
                const processed = await this.compress(file);
                results.push(processed);
            }
            return results;
        },

        formatSize: function(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }
    };

    // ==========================================
    // REUSABLE FILE PREVIEW FACTORY
    // ==========================================
    const FilePreviewFactory = {
        createPreview: function(containerId, file) {
            const container = document.getElementById(containerId);
            if (!container) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.className = 'position-relative d-inline-block';
                div.style.cssText = 'width:60px;height:60px;margin:2px;';

                if (file.type.startsWith('image/')) {
                    div.innerHTML = `<img src="${e.target.result}" style="width:60px;height:60px;object-fit:cover;border-radius:6px;">`;
                } else {
                    let icon = 'fa-file';
                    if (file.type.includes('pdf')) icon = 'fa-file-pdf';
                    else if (file.type.includes('word')) icon = 'fa-file-word';
                    else if (file.type.includes('excel') || file.type.includes('sheet')) icon = 'fa-file-excel';
                    div.innerHTML = `<div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:60px;height:60px;"><i class="fas ${icon} fa-lg text-muted"></i></div>`;
                }
                div.innerHTML += `<button type="button" class="btn-remove-preview position-absolute bg-danger text-white rounded-circle"
                    style="top:-6px;right:-6px;width:18px;height:18px;font-size:10px;padding:0;line-height:1;border:none;cursor:pointer;">&times;</button>`;
                container.appendChild(div);
            };
            reader.readAsDataURL(file);
        },

        setupModalFileUpload: function(modalId, previewContainerId) {
            const modal = document.getElementById(modalId);
            if (!modal) return null;

            const input = modal.querySelector('input[type="file"]');
            const preview = document.getElementById(previewContainerId);
            if (!input || !preview) return null;

            let fileTransfer = new DataTransfer();

            input.addEventListener('change', async function() {
                const compressedFiles = await ImageCompressor.compressMultiple(this.files);

                compressedFiles.forEach(f => {
                    fileTransfer.items.add(f);
                    FilePreviewFactory.createPreview(previewContainerId, f);
                });
                this.value = '';
            });

            preview.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-remove-preview')) {
                    e.target.closest('.position-relative').remove();
                    const newDt = new DataTransfer();
                    Array.from(fileTransfer.files).forEach(f => {
                        newDt.items.add(f);
                    });
                    fileTransfer = newDt;
                }
            });

            return fileTransfer;
        }
    };

    // ==========================================
    // MENTION SUGGESTIONS
    // ==========================================
    let mentionUsers = [];
    let mentionPopup = null;
    let mentionStartIndex = -1;

    function loadMentionUsers() {
        fetch('/api/v1/ajax/users/mention-suggestions', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(r => r.json())
        .then(data => { if (data.success) mentionUsers = data.data; })
        .catch(() => {});
    }
    loadMentionUsers();

    function getOrCreateMentionPopup() {
        if (!mentionPopup) {
            mentionPopup = document.createElement('div');
            mentionPopup.className = 'mention-suggestions';
            mentionPopup.style.cssText = 'position:fixed;z-index:9999;background:white;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 10px 40px rgba(0,0,0,0.2);max-height:200px;overflow-y:auto;min-width:220px;display:none;';
            document.body.appendChild(mentionPopup);
            document.addEventListener('click', (e) => { if (!mentionPopup.contains(e.target)) hideMentionPopup(); });
        }
        return mentionPopup;
    }

    function showMentionPopup(users, textarea) {
        const popup = getOrCreateMentionPopup();
        const rect = textarea.getBoundingClientRect();
        popup.innerHTML = users.map((u, i) => `
            <div class="mention-item d-flex align-items-center gap-2 px-3 py-2 ${i === 0 ? 'active' : ''}"
                 style="cursor:pointer;" data-name="${u.name}">
                <img src="${u.avatar_url || '/images/default-avatar.png'}" class="rounded-circle" width="28" height="28">
                <div><div class="fw-medium" style="font-size:0.8125rem;">${u.name}</div><small class="text-muted">@${u.username}</small></div>
            </div>
        `).join('');
        popup.querySelectorAll('.mention-item').forEach(item => {
            item.addEventListener('click', function() {
                const name = this.dataset.name;
                if (mentionStartIndex >= 0 && textarea) {
                    const before = textarea.value.substring(0, mentionStartIndex);
                    const after = textarea.value.substring(textarea.selectionStart);
                    textarea.value = before + '@' + name + ' ' + after;
                    textarea.focus();
                    const newPos = mentionStartIndex + name.length + 2;
                    textarea.setSelectionRange(newPos, newPos);
                }
                hideMentionPopup();
            });
        });
        popup.style.display = 'block';
        popup.style.top = Math.min(rect.bottom + 4, window.innerHeight - 220) + 'px';
        popup.style.left = Math.min(rect.left, window.innerWidth - 240) + 'px';
        popup.style.maxWidth = Math.min(300, window.innerWidth - rect.left - 10) + 'px';
    }

    function hideMentionPopup() { if (mentionPopup) { mentionPopup.style.display = 'none'; mentionStartIndex = -1; } }

    document.addEventListener('input', function(e) {
        if (e.target && (e.target.id === 'commentContent' || e.target.classList.contains('reply-textarea'))) {
            const textarea = e.target;
            const cursorPos = textarea.selectionStart;
            const text = textarea.value;
            const lastAtIndex = text.lastIndexOf('@', cursorPos - 1);

            if (lastAtIndex !== -1 && (lastAtIndex === 0 || [' ', '\n'].includes(text[lastAtIndex - 1] || ''))) {
                const searchTerm = text.substring(lastAtIndex + 1, cursorPos).toLowerCase();
                mentionStartIndex = lastAtIndex;

                const filtered = mentionUsers.filter(u =>
                    u.name.toLowerCase().includes(searchTerm) ||
                    u.username.toLowerCase().includes(searchTerm)
                ).slice(0, 5);

                if (filtered.length > 0) {
                    showMentionPopup(filtered, textarea);
                } else {
                    hideMentionPopup();
                }
            } else {
                hideMentionPopup();
            }
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.target && (e.target.id === 'commentContent' || e.target.classList.contains('reply-textarea'))) {
            const popup = getOrCreateMentionPopup();

            if (popup && popup.style.display === 'block') {
                if (e.key === 'Escape') {
                    hideMentionPopup();
                    e.preventDefault();
                }

                if (['ArrowDown', 'ArrowUp', 'Enter', 'Tab'].includes(e.key)) {
                    e.preventDefault();
                    const items = popup.querySelectorAll('.mention-item');
                    if (items.length === 0) return;

                    let idx = Array.from(items).findIndex(i => i.classList.contains('active'));

                    if (e.key === 'ArrowDown') {
                        idx = (idx + 1) % items.length;
                    } else if (e.key === 'ArrowUp') {
                        idx = (idx - 1 + items.length) % items.length;
                    } else if (e.key === 'Enter' || e.key === 'Tab') {
                        if (idx >= 0) {
                            items[idx].click();
                        } else {
                            items[0].click();
                        }
                        return;
                    }

                    items.forEach(i => i.classList.remove('active'));
                    if (idx >= 0) items[idx].classList.add('active');
                }
            }
        }
    });

    // ==========================================
    // FILE ATTACHMENT FOR COMMENTS
    // ==========================================
    let commentFiles = new DataTransfer();
    const attachBtn = document.getElementById('attachCommentBtn');
    const commentFilePreview = document.getElementById('commentFilePreview');
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.multiple = true;
    fileInput.accept = 'image/*,.pdf,.doc,.docx,.xls,.xlsx';
    fileInput.style.display = 'none';
    document.body.appendChild(fileInput);

    if (attachBtn) attachBtn.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', async function() {
        const processedFiles = await ImageCompressor.compressMultiple(this.files);

        processedFiles.forEach(f => {
            commentFiles.items.add(f);
            FilePreviewFactory.createPreview('commentFilePreview', f);
        });
        this.value = '';
    });

    commentFilePreview.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove-preview')) {
            e.target.closest('.position-relative').remove();
        }
    });

    // ==========================================
    // COMMENT SUBMISSION
    // ==========================================
    const commentForm = document.getElementById('commentForm');
    const commentContent = document.getElementById('commentContent');

    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const content = commentContent?.value.trim() || '';
            if (!content && commentFiles.files.length === 0) {
                if (typeof toastr !== 'undefined') toastr.warning('Please enter a comment or attach a file.');
                return;
            }
            const btn = document.getElementById('commentSubmitBtn');
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>'; }

            const formData = new FormData();
            formData.append('content', content);
            Array.from(commentFiles.files).forEach(f => formData.append('files[]', f));

            fetch('{{ route("incidents.comments.store", $incident) }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(async r => {
                const ct = r.headers.get('content-type');
                if (ct && ct.includes('application/json')) return r.json();
                throw new Error('Server error');
            })
            .then(data => {
                if (data.success) {
                    if (commentContent) commentContent.value = '';
                    commentFilePreview.innerHTML = '';
                    commentFiles = new DataTransfer();
                    document.getElementById('noComments')?.remove();
                    const html = window.buildCommentHTML(data.data.comment);
                    document.getElementById('commentsList')?.insertAdjacentHTML('afterbegin', html);
                    document.getElementById('commentCount').textContent = data.data.comments_count;
                    if (typeof toastr !== 'undefined') toastr.success('Comment posted!');
                }
            })
            .catch(err => { console.error(err); if (typeof toastr !== 'undefined') toastr.error('Failed.'); })
            .finally(() => { if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Post'; } });
        });
    }

    // ==========================================
    // REPLY FUNCTIONALITY
    // ==========================================
    const replyFileTransfers = {};
    const replyFileInputs = {};

    function getReplyFileInput(commentId) {
        if (!replyFileInputs[commentId]) {
            const input = document.createElement('input');
            input.type = 'file';
            input.multiple = true;
            input.accept = 'image/*,.pdf,.doc,.docx,.xls,.xlsx';
            input.style.display = 'none';
            input.dataset.commentId = commentId;
            document.body.appendChild(input);

            input.addEventListener('change', async function() {
                const cid = this.dataset.commentId;
                if (!replyFileTransfers[cid]) replyFileTransfers[cid] = new DataTransfer();

                const processedFiles = await ImageCompressor.compressMultiple(this.files);

                processedFiles.forEach(f => {
                    replyFileTransfers[cid].items.add(f);
                    FilePreviewFactory.createPreview('reply-preview-' + cid, f);
                });
                this.value = '';
            });

            replyFileInputs[commentId] = input;
        }
        return replyFileInputs[commentId];
    }

    function setupReplyFileUpload(commentId) {
        getReplyFileInput(commentId);
    }

    function triggerReplyFileInput(commentId) {
        const input = getReplyFileInput(commentId);
        input.click();
    }

    document.addEventListener('click', function(e) {
        if (e.target.closest('.reply-toggle-btn')) {
            const btn = e.target.closest('.reply-toggle-btn');
            const commentId = btn.dataset.commentId;
            const username = btn.dataset.username;
            const container = document.getElementById('replies-' + commentId);
            if (!container) return;

            const existingForm = container.querySelector('.reply-form-inline');
            if (existingForm) {
                existingForm.remove();
                return;
            }

            document.querySelectorAll('.reply-form-inline').forEach(f => f.remove());

            const form = document.createElement('div');
            form.className = 'reply-form-inline mt-2';
            form.innerHTML = `
                <div class="d-flex gap-2">
                    <img src="{{ Auth::user()->avatar_url }}" class="rounded-circle flex-shrink-0" width="28" height="28" style="object-fit:cover;">
                    <div class="flex-grow-1">
                        <textarea class="form-control form-control-sm reply-textarea" rows="1" placeholder="Reply to ${username}..." id="replyCommentContent"></textarea>
                        <div class="reply-file-preview d-flex flex-wrap gap-2 mt-2" id="reply-preview-${commentId}"></div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <button type="button" class="btn btn-light btn-sm reply-attach-btn" data-comment-id="${commentId}" title="Attach files">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-light btn-sm cancel-reply-btn">Cancel</button>
                                <button type="button" class="btn btn-primary btn-sm submit-reply-btn" data-comment-id="${commentId}">Reply</button>
                            </div>
                        </div>
                    </div>
                </div>`;
            container.appendChild(form);
            form.querySelector('textarea').focus();

            const ta = form.querySelector('textarea');
            ta.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });

            setupReplyFileUpload(commentId);
        }

        if (e.target.closest('.cancel-reply-btn')) {
            e.target.closest('.reply-form-inline').remove();
        }

        if (e.target.closest('.reply-attach-btn')) {
            const commentId = e.target.closest('.reply-attach-btn').dataset.commentId;
            triggerReplyFileInput(commentId);
        }

        if (e.target.closest('.submit-reply-btn')) {
            const btn = e.target.closest('.submit-reply-btn');
            const commentId = btn.dataset.commentId;
            const form = btn.closest('.reply-form-inline');
            const textarea = form.querySelector('textarea');
            const content = textarea?.value.trim();
            const replyFiles = replyFileTransfers[commentId] || new DataTransfer();

            if (!content && replyFiles.files.length === 0) {
                if (typeof toastr !== 'undefined') toastr.warning('Please enter a reply or attach a file.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const formData = new FormData();
            formData.append('content', content);
            formData.append('parent_id', commentId);
            Array.from(replyFiles.files).forEach(f => formData.append('files[]', f));

            fetch('{{ route("incidents.comments.store", $incident) }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const container = document.getElementById('replies-' + commentId);
                    if (container) {
                        container.insertAdjacentHTML('beforeend', window.buildCommentHTML(data.data.comment, true));
                    }
                    document.getElementById('commentCount').textContent = data.data.comments_count;
                    form.remove();
                    delete replyFileTransfers[commentId];
                    if (typeof toastr !== 'undefined') toastr.success('Reply posted!');
                } else {
                    if (typeof toastr !== 'undefined') toastr.error(data.message || 'Failed');
                    btn.disabled = false;
                    btn.innerHTML = 'Reply';
                }
            })
            .catch(err => { console.error(err); btn.disabled = false; btn.innerHTML = 'Reply'; });
        }
    });

    // Submit reply on Enter
    document.addEventListener('keydown', function(e) {
        if (e.target.classList.contains('reply-textarea') && e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            e.target.closest('.reply-form-inline')?.querySelector('.submit-reply-btn')?.click();
        }
    });

    // ==========================================
    // EDIT & DELETE COMMENT
    // ==========================================
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-comment-btn')) {
            const commentId = e.target.closest('.edit-comment-btn').dataset.commentId;
            document.getElementById('comment-content-' + commentId)?.classList.add('d-none');
            document.getElementById('comment-edit-' + commentId)?.classList.remove('d-none');
        }
        if (e.target.closest('.cancel-edit-btn')) {
            const commentId = e.target.closest('.cancel-edit-btn').dataset.commentId;
            document.getElementById('comment-content-' + commentId)?.classList.remove('d-none');
            document.getElementById('comment-edit-' + commentId)?.classList.add('d-none');
        }
        if (e.target.closest('.save-edit-btn')) {
            const btn = e.target.closest('.save-edit-btn');
            const commentId = btn.dataset.commentId;
            const content = document.getElementById('edit-textarea-' + commentId)?.value.trim();
            if (!content) return;
            btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            fetch(`/incidents/{{ $incident->id }}/comments/${commentId}`, { 
                method: 'PUT', 
                headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json' }, 
                body: JSON.stringify({ content }) 
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    let text = escapeHtml(data.data.comment.content);
                    text = text.replace(/@(\w+)/g, '<span class="text-primary fw-medium">@$1</span>').replace(/#(\w+)/g, '<span class="text-success fw-medium">#$1</span>').replace(/\n/g, '<br>');
                    const ct = document.querySelector('#comment-content-' + commentId + ' .comment-text');
                    if (ct) ct.innerHTML = text;
                    document.getElementById('comment-content-' + commentId)?.classList.remove('d-none');
                    document.getElementById('comment-edit-' + commentId)?.classList.add('d-none');
                    if (typeof toastr !== 'undefined') toastr.success('Comment updated!');
                }
            }).catch(err => console.error(err)).finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save me-1"></i>Save'; });
        }
        if (e.target.closest('.delete-comment-btn')) {
            const commentId = e.target.closest('.delete-comment-btn').dataset.commentId;
            if (!confirm('Delete this comment?')) return;
            fetch(`/incidents/{{ $incident->id }}/comments/${commentId}`, { 
                method: 'DELETE', 
                headers: { 'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json' } 
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const el = document.getElementById('comment-' + commentId);
                    if (el) { el.style.transition = 'opacity 0.3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }
                    document.getElementById('commentCount').textContent = data.data.comments_count;
                    if (typeof toastr !== 'undefined') toastr.success('Comment deleted!');
                }
            }).catch(err => console.error(err));
        }
    });

    // ==========================================
    // MODAL FORM WITH FILE SUPPORT
    // ==========================================
    function setupModalForm(formId, url, successMsg, fileTransfer = null) {
        const form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const formData = new FormData(this);
            if (fileTransfer && fileTransfer.files.length > 0) {
                Array.from(fileTransfer.files).forEach(f => formData.append('files[]', f));
            }
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }
            fetch(url, { 
                method: 'POST', 
                headers: { 'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','X-Requested-With':'XMLHttpRequest' }, 
                body: formData 
            })
            .then(async r => { 
                const ct = r.headers.get('content-type'); 
                if (ct && ct.includes('application/json')) { 
                    const j = await r.json(); 
                    if (!r.ok) throw new Error(j.message||'Failed'); 
                    return j; 
                } 
                throw new Error('Server error'); 
            })
            .then(data => {
                if (data.success) {
                    const modalEl = document.getElementById(formId.replace('Form','Modal'));
                    if (modalEl) { 
                        const m = bootstrap.Modal.getInstance(modalEl); 
                        if (m) m.hide(); 
                        document.querySelectorAll('.modal-backdrop').forEach(b=>b.remove()); 
                        document.body.classList.remove('modal-open'); 
                        document.body.style.overflow=''; 
                    }
                    if (typeof toastr !== 'undefined') {
                        const msg = data.message || successMsg;
                        if (data.new_status) {
                            toastr.success(msg + ' | Status: ' + data.new_status.replace(/_/g, ' '));
                        } else {
                            toastr.success(msg);
                        }
                    }
                    setTimeout(()=>location.reload(),1200);
                }
            }).catch(err => { 
                console.error(err); 
                if (typeof toastr!=='undefined') toastr.error(err.message||'Failed'); 
                if (btn) { btn.disabled=false; btn.innerHTML='Submit'; } 
            });
        });
    }

    // ==========================================
    // ESCALATION - LOAD USERS BY DEPARTMENT
    // ==========================================
    const escalateDeptSelect = document.getElementById('escalateDeptSelect');
    const escalateUserSelect = document.getElementById('escalateUserSelect');
    if (escalateDeptSelect && escalateUserSelect) {
        escalateDeptSelect.addEventListener('change', function() {
            const deptId = this.value;
            if (!deptId) { escalateUserSelect.innerHTML = '<option value="">Select Department First</option>'; return; }
            escalateUserSelect.innerHTML = '<option value="">Loading...</option>';
            fetch(`/api/v1/departments/${deptId}/users`, { 
                headers: { 'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}' } 
            })
            .then(r=>r.json()).then(data=>{
                if (data.success) {
                    escalateUserSelect.innerHTML = '<option value="">Select User</option>';
                    data.data.forEach(u => escalateUserSelect.innerHTML += `<option value="${u.id}">${u.name} (${u.role_name||'Staff'})</option>`);
                    if (typeof jQuery!=='undefined' && jQuery.fn.select2) jQuery(escalateUserSelect).trigger('change');
                }
            });
        });
    }

    // ==========================================
    // FILE PREVIEW FOR MODALS
    // ==========================================
    const resolveFiles = FilePreviewFactory.setupModalFileUpload('resolveModal', 'resolveFilePreview');
    const closeFiles = FilePreviewFactory.setupModalFileUpload('closeModal', 'closeFilePreview');

    // Setup all modals
    setupModalForm('assignForm', '{{ route("incidents.assign", $incident) }}', 'Assigned!');
    setupModalForm('escalateForm', '{{ route("incidents.escalate", $incident) }}', 'Escalated!');
    setupModalForm('resolveForm', '{{ route("incidents.resolve", $incident) }}', 'Resolved!', resolveFiles);
    setupModalForm('rejectForm', '{{ route("incidents.reject", $incident) }}', 'Rejected!');
    setupModalForm('closeForm', '{{ route("incidents.close", $incident) }}', 'Closed!', closeFiles);
    setupModalForm('acceptEscalationForm', '{{ route("incidents.escalation.respond", $incident) }}', 'Escalation accepted!');
    setupModalForm('returnEscalationForm', '{{ route("incidents.escalation.respond", $incident) }}', 'Escalation returned!');
    setupModalForm('rejectEscalationForm', '{{ route("incidents.escalation.respond", $incident) }}', 'Escalation rejected!');

    // Quick actions
    window.closeIncident = function() { 
        if(!confirm('Close?'))return; 
        fetch('{{ route("incidents.close", $incident) }}',{
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
            body:JSON.stringify({closing_remarks:'Closed'})
        }).then(r=>r.json()).then(d=>{if(d.success){toastr.success('Closed!');setTimeout(()=>location.reload(),1000);}}); 
    };
    
    window.reopenIncident = function() { 
        if(!confirm('Reopen?'))return; 
        fetch('{{ route("incidents.reopen", $incident) }}',{
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
            body:'{}'
        }).then(r=>r.json()).then(d=>{if(d.success){toastr.success('Reopened!');setTimeout(()=>location.reload(),1000);}}); 
    };
    
    window.shareIncident = function() { 
        fetch('{{ route("incidents.share", $incident) }}')
        .then(r=>r.json())
        .then(d=>{if(d.success)window.open(d.whatsapp_url,'_blank');}); 
    };

    // Select2 in modals
    ['assignModal','escalateModal'].forEach(id=>{ 
        const m=document.getElementById(id); 
        if(m)m.addEventListener('shown.bs.modal',()=>{ 
            m.querySelectorAll('.select2').forEach(s=>{ 
                if(typeof jQuery!=='undefined'&&jQuery.fn.select2)
                    jQuery(s).select2({dropdownParent:jQuery(m),placeholder:'Search...',allowClear:true,width:'100%'}); 
            }); 
        }); 
    });

});

// Delete incident confirmation
function confirmDeleteIncident() {
    if (confirm('⚠️ Are you sure you want to PERMANENTLY DELETE this incident?\n\nThis action CANNOT be undone. All comments, attachments, and history will be lost.')) {
        if (confirm('FINAL WARNING: Delete incident #{{ $incident->incident_id }}?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("incidents.destroy", $incident) }}';
            form.innerHTML = `
                @csrf
                @method('DELETE')
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
}
</script>
@endpush