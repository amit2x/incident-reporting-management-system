{{-- resources/views/incidents/partials/core_show_script.blade.php --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Initialize all tooltips on the page
const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="modal"][title]')
const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

    // ==========================================
    // REUSABLE IMAGE COMPRESSION UTILITY
    // ==========================================
    const ImageCompressor = {
        MAX_DIMENSION: 1920,
        QUALITY: 0.7,
        MIN_SIZE_TO_COMPRESS: 200 * 1024, // 200KB

        /**
         * Compress an image file using Canvas API
         * @param {File} file - The image file to compress
         * @returns {Promise<File>} - Compressed file
         */
        compress: function(file) {
            return new Promise((resolve) => {
                // Skip non-images or small files
                if (!file.type.startsWith('image/') || file.size < this.MIN_SIZE_TO_COMPRESS) {
                    resolve(file);
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = new Image();
                    img.onload = () => {
                        let { width, height } = img;

                        // Resize if too large
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
                                console.log(`📸 Compressed: ${this.formatSize(file.size)} → ${this.formatSize(compressed.size)} (${Math.round((1 - compressed.size/file.size)*100)}% smaller)`);
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

        /**
         * Compress multiple files
         * @param {FileList|Array} files - Files to compress
         * @returns {Promise<File[]>} - Array of processed files
         */
        compressMultiple: async function(files) {
            const results = [];
            for (const file of Array.from(files)) {
                const processed = await this.compress(file);
                results.push(processed);
            }
            return results;
        },

        /**
         * Format file size to human readable
         */
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
        /**
         * Create file preview for modal forms (resolve, close, etc.)
         * @param {string} containerId - Preview container element ID
         * @param {File} file - File to preview
         */
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

        /**
         * Setup file input with preview and compression for modals
         * @param {string} modalId - Modal element ID (e.g., 'resolveModal')
         * @param {string} previewContainerId - Preview container ID
         * @returns {DataTransfer} - DataTransfer object containing selected files
         */
        setupModalFileUpload: function(modalId, previewContainerId) {
            const modal = document.getElementById(modalId);
            if (!modal) return null;

            const input = modal.querySelector('input[type="file"]');
            const preview = document.getElementById(previewContainerId);
            if (!input || !preview) return null;

            let fileTransfer = new DataTransfer();
            const self = this;

            input.addEventListener('change', async function() {
                const compressedFiles = await ImageCompressor.compressMultiple(this.files);

                compressedFiles.forEach(f => {
                    fileTransfer.items.add(f);
                    self.createPreview(previewContainerId, f);
                });
                this.value = '';
            });

            // Remove file on click
            preview.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-remove-preview')) {
                    e.target.closest('.position-relative').remove();
                    // Rebuild DataTransfer from remaining previews
                    const newDt = new DataTransfer();
                    Array.from(fileTransfer.files).forEach(f => {
                        // Keep file if its preview still exists (simplified)
                        newDt.items.add(f);
                    });
                    fileTransfer = newDt;
                }
            });

            return fileTransfer;
        }
    };

    // ==========================================
    // HELPER FUNCTIONS
    // ==========================================
    function escapeHtml(text) {
        if (!text) return '';
        const map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    function formatSize(bytes) {
        return ImageCompressor.formatSize(bytes);
    }

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

    // Unified Event Delegation for 'input' (Handles @ detection)
    document.addEventListener('input', function (e) {
        // Match either the standard static box or the dynamic reply element class
        if (e.target && (e.target.id === 'commentContent' || e.target.classList.contains('reply-textarea'))) {
            const textarea = e.target;
            const cursorPos = textarea.selectionStart;
            const text = textarea.value;
            const lastAtIndex = text.lastIndexOf('@', cursorPos - 1);

            if (lastAtIndex !== -1 && (lastAtIndex === 0 || [' ', '\n'].includes(text[lastAtIndex - 1] || ''))) {
                const searchTerm = text.substring(lastAtIndex + 1, cursorPos).toLowerCase();
                mentionStartIndex = lastAtIndex;

                // Filter your dataset
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

    //Unified Event Delegation for 'keydown' (Handles keyboard selection)
    document.addEventListener('keydown', function (e) {
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

    function showMentionPopup(users, textarea) {
        const popup = getOrCreateMentionPopup();
        const rect = textarea.getBoundingClientRect();
        popup.innerHTML = users.map((u, i) => `
            <div class="mention-item d-flex align-items-center gap-2 px-3 py-2 ${i === 0 ? 'active' : ''}"
                 style="cursor:pointer;" data-username="${u.username}">
                <img src="${u.avatar_url || '/images/default-avatar.png'}" class="rounded-circle" width="28" height="28">
                <div><div class="fw-medium" style="font-size:0.8125rem;">${u.name}</div><small class="text-muted">@${u.username}</small></div>
            </div>
        `).join('');
        popup.querySelectorAll('.mention-item').forEach(item => {
            item.addEventListener('click', function() {
                const username = this.dataset.username;
                if (mentionStartIndex >= 0 && textarea) {
                    const before = textarea.value.substring(0, mentionStartIndex);
                    const after = textarea.value.substring(textarea.selectionStart);
                    textarea.value = before + '@' + username + ' ' + after;
                    textarea.focus();
                    const newPos = mentionStartIndex + username.length + 2;
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

    // ==========================================
    // FILE ATTACHMENT FOR COMMENTS (with compression)
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
        // Compress images before adding
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
                    const html = buildCommentHTML(data.data.comment);
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
    // BUILD COMMENT HTML
    // ==========================================
    function buildCommentHTML(comment, isReply = false) {
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
            if (images.length > 0) attachmentsHtml += '<div class="d-flex flex-wrap gap-2 mb-2">' + images.map(img => `<a href="/storage/${img.path}" target="_blank"><img src="/storage/${img.path}" style="max-width:200px;max-height:200px;object-fit:cover;border-radius:8px;cursor:pointer;" class="border"></a>`).join('') + '</div>';
            if (files.length > 0) attachmentsHtml += '<div class="d-flex flex-wrap gap-1">' + files.map(f => `<a href="/storage/${f.path}" target="_blank" class="badge bg-light text-dark text-decoration-none" style="font-size:0.7rem;padding:6px 10px;"><i class="fas fa-file text-muted"></i>${f.name}</a>`).join('') + '</div>';
        }
        const marginClass = isReply ? 'ms-4 mt-2' : 'mb-3 pb-3 border-bottom';
        return `<div class="comment-thread ${marginClass}" id="comment-${comment.id}"><div class="d-flex gap-2"><img src="${comment.user?.avatar_url || '/images/default-avatar.png'}" class="rounded-circle flex-shrink-0" width="36" height="36"><div class="flex-grow-1 min-width-0"><div class="d-flex gap-2 align-items-center mb-1"><strong style="font-size:0.8125rem;">${comment.user?.name || 'Unknown'}</strong><small class="text-muted">${comment.created_at_diff || ''}</small>${comment.is_internal ? '<span class="badge bg-warning text-dark" style="font-size:0.6rem;">Internal</span>' : ''}</div>${contentHtml}${attachmentsHtml}<div class="d-flex gap-2 mt-1"><button class="btn btn-link btn-sm text-muted p-0 reply-toggle-btn" style="font-size:0.6875rem;" data-comment-id="${comment.id}" data-username="${comment.user?.name || 'User'}"><i class="fas fa-reply me-1"></i>Reply</button></div><div class="replies-container mt-2" id="replies-${comment.id}"></div></div></div></div>`;
    }


    // ==========================================
    // REPLY FUNCTIONALITY (with file attachment)
    // ==========================================
    document.addEventListener('click', function(e) {
        // Toggle reply form
        if (e.target.closest('.reply-toggle-btn')) {
            const btn = e.target.closest('.reply-toggle-btn');
            const commentId = btn.dataset.commentId;
            const username = btn.dataset.username;
            const container = document.getElementById('replies-' + commentId);
            if (!container) return;

            // Remove any existing reply form (toggle off)
            const existingForm = container.querySelector('.reply-form-inline');
            if (existingForm) {
                existingForm.remove();
                return;
            }

            // Remove other reply forms
            document.querySelectorAll('.reply-form-inline').forEach(f => f.remove());

            // Create reply form with file attachment support
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

            // Auto-resize textarea
            const ta = form.querySelector('textarea');
            ta.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });

            // Setup file attachment for this reply
            setupReplyFileUpload(commentId);
        }

        // Cancel reply
        if (e.target.closest('.cancel-reply-btn')) {
            e.target.closest('.reply-form-inline').remove();
        }

        // Attach file button in reply
        if (e.target.closest('.reply-attach-btn')) {
            const commentId = e.target.closest('.reply-attach-btn').dataset.commentId;
            triggerReplyFileInput(commentId);
        }

        // Submit reply
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
                        container.insertAdjacentHTML('beforeend', buildCommentHTML(data.data.comment, true));
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

    // ==========================================
    // REPLY FILE UPLOAD MANAGEMENT
    // ==========================================
    // Store file transfers per reply
    const replyFileTransfers = {};
    const replyFileInputs = {};

    /**
     * Create hidden file input for a reply
     */
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

                // Compress images before adding
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

    /**
     * Setup file upload for a reply
     */
    function setupReplyFileUpload(commentId) {
        getReplyFileInput(commentId);
    }

    /**
     * Trigger file input for a reply
     */
    function triggerReplyFileInput(commentId) {
        const input = getReplyFileInput(commentId);
        input.click();
    }

    // Submit reply on Enter (not Shift+Enter)
    document.addEventListener('keydown', function(e) {
        if (e.target.classList.contains('reply-textarea') && e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            e.target.closest('.reply-form-inline')?.querySelector('.submit-reply-btn')?.click();
        }
    });

    // Remove reply file transfer when reply form is removed
    const replyFormObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.removedNodes.forEach(function(node) {
                if (node.classList && node.classList.contains('reply-form-inline')) {
                    // Clean up file transfer for removed reply
                    const btn = node.querySelector('.submit-reply-btn');
                    if (btn) {
                        const commentId = btn.dataset.commentId;
                        // Keep files for 30 seconds in case of re-open, then clean
                        setTimeout(() => {
                            delete replyFileTransfers[commentId];
                        }, 30000);
                    }
                }
            });
        });
    });

    // Observe the comments list for removed reply forms
    const commentsList = document.getElementById('commentsList');
    if (commentsList) {
        replyFormObserver.observe(commentsList, { childList: true, subtree: true });
    }

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
            fetch(`/incidents/{{ $incident->id }}/comments/${commentId}`, { method: 'PUT', headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json' }, body: JSON.stringify({ content }) })
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
            fetch(`/incidents/{{ $incident->id }}/comments/${commentId}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json' } })
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
            fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','X-Requested-With':'XMLHttpRequest' }, body: formData })
            .then(async r => { const ct = r.headers.get('content-type'); if (ct && ct.includes('application/json')) { const j = await r.json(); if (!r.ok) throw new Error(j.message||'Failed'); return j; } throw new Error('Server error'); })
            .then(data => {
                if (data.success) {
                    const modalEl = document.getElementById(formId.replace('Form','Modal'));
                    if (modalEl) { const m = bootstrap.Modal.getInstance(modalEl); if (m) m.hide(); document.querySelectorAll('.modal-backdrop').forEach(b=>b.remove()); document.body.classList.remove('modal-open'); document.body.style.overflow=''; }
                    // if (typeof toastr !== 'undefined') toastr.success(data.message||successMsg);
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
            }).catch(err => { console.error(err); if (typeof toastr!=='undefined') toastr.error(err.message||'Failed'); if (btn) { btn.disabled=false; btn.innerHTML='Submit'; } });
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
            fetch(`/api/v1/departments/${deptId}/users`, { headers: { 'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}' } })
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
    // FILE PREVIEW FOR MODALS (with compression)
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
    window.closeIncident = function() { if(!confirm('Close?'))return; fetch('{{ route("incidents.close", $incident) }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:JSON.stringify({closing_remarks:'Closed'})}).then(r=>r.json()).then(d=>{if(d.success){toastr.success('Closed!');setTimeout(()=>location.reload(),1000);}}); };
    window.reopenIncident = function() { if(!confirm('Reopen?'))return; fetch('{{ route("incidents.reopen", $incident) }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:'{}'}).then(r=>r.json()).then(d=>{if(d.success){toastr.success('Reopened!');setTimeout(()=>location.reload(),1000);}}); };
    window.shareIncident = function() { fetch('{{ route("incidents.share", $incident) }}').then(r=>r.json()).then(d=>{if(d.success)window.open(d.whatsapp_url,'_blank');}); };

    // Select2 in modals
    ['assignModal','escalateModal'].forEach(id=>{ const m=document.getElementById(id); if(m)m.addEventListener('shown.bs.modal',()=>{ m.querySelectorAll('.select2').forEach(s=>{ if(typeof jQuery!=='undefined'&&jQuery.fn.select2)jQuery(s).select2({dropdownParent:jQuery(m),placeholder:'Search...',allowClear:true,width:'100%'}); }); }); });

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