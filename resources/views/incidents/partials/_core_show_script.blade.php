@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ========================================
    // Helper function
    // ========================================
    function escapeHtml(text) {
        if (!text) return '';
        const map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
        return String(text).replace(/[&<>"']/g, m => map[m]);
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
                'X-Requested-With': 'XMLHttpRequest' }
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

    const commentContent = document.getElementById('commentContent');
    if (commentContent) {
        commentContent.addEventListener('input', function() {
            const cursorPos = this.selectionStart;
            const text = this.value;
            const lastAtIndex = text.lastIndexOf('@', cursorPos - 1);

            if (lastAtIndex !== -1 && (lastAtIndex === 0 || [' ', '\n'].includes(text[lastAtIndex - 1] || ''))) {
                const searchTerm = text.substring(lastAtIndex + 1, cursorPos).toLowerCase();
                mentionStartIndex = lastAtIndex;
                const filtered = mentionUsers.filter(u =>
                    u.name.toLowerCase().includes(searchTerm) || u.username.toLowerCase().includes(searchTerm)
                ).slice(0, 5);

                if (filtered.length > 0) showMentionPopup(filtered, this);
                else hideMentionPopup();
            } else {
                hideMentionPopup();
            }
        });

        commentContent.addEventListener('keydown', function(e) {
            const popup = getOrCreateMentionPopup();
            if (popup.style.display === 'block') {
                if (e.key === 'Escape') { hideMentionPopup(); e.preventDefault(); }
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter' || e.key === 'Tab') {
                    e.preventDefault();
                    const items = popup.querySelectorAll('.mention-item');
                    if (items.length === 0) return;
                    let idx = Array.from(items).findIndex(i => i.classList.contains('active'));
                    if (e.key === 'ArrowDown') idx = (idx + 1) % items.length;
                    else if (e.key === 'ArrowUp') idx = (idx - 1 + items.length) % items.length;
                    else if (e.key === 'Enter' || e.key === 'Tab') {
                        if (idx >= 0) items[idx].click();
                        else items[0].click();
                        return;
                    }
                    items.forEach(i => i.classList.remove('active'));
                    if (idx >= 0) items[idx].classList.add('active');
                }
            }
        });
    }

    function showMentionPopup(users, textarea) {
        const popup = getOrCreateMentionPopup();
        const rect = textarea.getBoundingClientRect();

        popup.innerHTML = users.map((u, i) => `
            <div class="mention-item d-flex align-items-center gap-2 px-3 py-2 ${i === 0 ? 'active' : ''}"
                 style="cursor:pointer;" data-username="${u.username}"
                 onmouseover="this.style.background='#f0f5ff';this.classList.add('active')"
                 onmouseout="this.style.background='';this.classList.remove('active')">
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

    function hideMentionPopup() {
        if (mentionPopup) { mentionPopup.style.display = 'none'; mentionStartIndex = -1; }
    }

    // ==========================================
    // FILE ATTACHMENT
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

    if (attachBtn) {
        attachBtn.addEventListener('click', () => fileInput.click());
    }

    fileInput.addEventListener('change', function() {
        Array.from(this.files).forEach(f => {
            commentFiles.items.add(f);
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'position-relative d-inline-block';
                div.style.cssText = 'width:60px;height:60px;margin:2px;';
                div.innerHTML = f.type.startsWith('image/')
                    ? `<img src="${e.target.result}" style="width:60px;height:60px;object-fit:cover;border-radius:6px;">`
                    : `<div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:60px;height:60px;"><i class="fas fa-file fa-lg text-muted"></i></div>`;
                div.innerHTML += `<button type="button" class="btn-remove-attachment position-absolute bg-danger text-white rounded-circle"
                    style="top:-6px;right:-6px;width:18px;height:18px;font-size:10px;padding:0;line-height:1;border:none;cursor:pointer;"
                    data-name="${f.name.replace(/'/g, "\\'")}">&times;</button>`;
                commentFilePreview.appendChild(div);
            };
            reader.readAsDataURL(f);
        });
        this.value = '';
    });

    commentFilePreview.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove-attachment')) {
            const name = e.target.dataset.name;
            const newDt = new DataTransfer();
            Array.from(commentFiles.files).forEach(f => { if (f.name !== name) newDt.items.add(f); });
            commentFiles = newDt;
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
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(async r => {
                // Check if response is JSON
                const contentType = r.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return r.json();
                }
                // If not JSON, get text and throw error
                const text = await r.text();
                console.error('Server returned non-JSON response:', text.substring(0, 200));
                throw new Error('Server error. Please try again.');
            })
            .then(data => {
                if (data.success) {
                    // Clear form
                    if (commentContent) commentContent.value = '';
                    commentFilePreview.innerHTML = '';
                    commentFiles = new DataTransfer();

                    // Remove no comments message
                    const noComments = document.getElementById('noComments');
                    if (noComments) noComments.remove();

                    // Add new comment
                    const html = buildCommentHTML(data.data.comment);
                    const list = document.getElementById('commentsList');
                    if (list) list.insertAdjacentHTML('afterbegin', html);

                    // Update count
                    const countEl = document.getElementById('commentCount');
                    if (countEl) countEl.textContent = data.data.comments_count;

                    // Success toast
                    if (typeof toastr !== 'undefined') toastr.success('Comment posted!');
                } else {
                    if (typeof toastr !== 'undefined') toastr.error(data.message || 'Failed to post comment.');
                }
            })
            .catch(err => {
                console.error('Comment error:', err);
                if (typeof toastr !== 'undefined') toastr.error(err.message || 'Failed to post comment.');
            })
            .finally(() => {
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Post'; }
            });
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

            if (images.length > 0) {
                attachmentsHtml += '<div class="d-flex flex-wrap gap-2 mb-2">' + images.map(img =>
                    `<a href="/storage/${img.path}" target="_blank"><img src="/storage/${img.path}" style="max-width:200px;max-height:200px;object-fit:cover;border-radius:8px;cursor:pointer;" class="border"></a>`
                ).join('') + '</div>';
            }
            if (files.length > 0) {
                attachmentsHtml += '<div class="d-flex flex-wrap gap-1">' + files.map(f =>
                    `<a href="/storage/${f.path}" target="_blank" class="badge bg-light text-dark text-decoration-none d-inline-flex align-items-center gap-1" style="font-size:0.7rem;padding:6px 10px;">
                        <i class="fas fa-file text-muted"></i>${f.name} <small>(${Math.round(f.size/1024)}KB)</small></a>`
                ).join('') + '</div>';
            }
        }

        const marginClass = isReply ? 'ms-4 mt-2' : 'mb-3 pb-3 border-bottom';

        const canEdit = {{ Auth::id() }} === (comment.user?.id || 0) || {{ Auth::user()->isAdmin() ? 'true' : 'false' }};

        // Add edit/delete buttons if user can modify
        const editButtons = canEdit ? `
            <button class="btn btn-link btn-sm text-muted p-0 edit-comment-btn" style="font-size:0.6875rem;" data-comment-id="${comment.id}">
                <i class="fas fa-edit me-1"></i>Edit
            </button>
            <button class="btn btn-link btn-sm text-muted p-0 delete-comment-btn" style="font-size:0.6875rem;" data-comment-id="${comment.id}">
                <i class="fas fa-trash me-1"></i>Delete
            </button>
        ` : '';

        return `
        <div class="comment-thread ${marginClass}" id="comment-${comment.id}">
            <div class="d-flex gap-2">
                <img src="${comment.user?.avatar_url || '/images/default-avatar.png'}" class="rounded-circle flex-shrink-0" width="36" height="36" style="object-fit:cover;">
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
    }

    // ==========================================
    // REPLY FUNCTIONALITY
    // ==========================================
    document.addEventListener('click', function(e) {
        // Toggle reply form
        if (e.target.closest('.reply-toggle-btn')) {
            const btn = e.target.closest('.reply-toggle-btn');
            const commentId = btn.dataset.commentId;
            const username = btn.dataset.username;
            const container = document.getElementById('replies-' + commentId);
            if (!container) return;

            // Remove any existing reply form
            const existingForm = container.querySelector('.reply-form-inline');
            if (existingForm) {
                existingForm.remove();
                return; // Toggle off
            }

            // Remove other reply forms
            document.querySelectorAll('.reply-form-inline').forEach(f => f.remove());

            // Create reply form
            const form = document.createElement('div');
            form.className = 'reply-form-inline mt-2';
            form.innerHTML = `
                <div class="d-flex gap-2">
                    <img src="{{ Auth::user()->avatar_url }}" class="rounded-circle flex-shrink-0" width="28" height="28" style="object-fit:cover;">
                    <div class="flex-grow-1">
                        <textarea class="form-control form-control-sm reply-textarea" rows="1" placeholder="Reply to ${username}..."></textarea>
                        <div class="d-flex justify-content-end gap-1 mt-1">
                            <button type="button" class="btn btn-light btn-sm cancel-reply-btn">Cancel</button>
                            <button type="button" class="btn btn-primary btn-sm submit-reply-btn" data-comment-id="${commentId}">Reply</button>
                        </div>
                    </div>
                </div>`;
            container.appendChild(form);
            form.querySelector('textarea').focus();

            // Auto-resize
            const ta = form.querySelector('textarea');
            ta.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
        }

        // Cancel reply
        if (e.target.closest('.cancel-reply-btn')) {
            e.target.closest('.reply-form-inline').remove();
        }

        // Submit reply
        if (e.target.closest('.submit-reply-btn')) {
            const btn = e.target.closest('.submit-reply-btn');
            const commentId = btn.dataset.commentId;
            const form = btn.closest('.reply-form-inline');
            const textarea = form.querySelector('textarea');
            const content = textarea?.value.trim();
            if (!content) return;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('{{ route("incidents.comments.store", $incident) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ content: content, parent_id: commentId })
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
// EDIT COMMENT
// ==========================================
document.addEventListener('click', function(e) {

    // Toggle edit mode
    if (e.target.closest('.edit-comment-btn')) {
        const commentId = e.target.closest('.edit-comment-btn').dataset.commentId;
        const contentWrapper = document.getElementById('comment-content-' + commentId);
        const editWrapper = document.getElementById('comment-edit-' + commentId);

        if (contentWrapper && editWrapper) {
            contentWrapper.classList.add('d-none');
            editWrapper.classList.remove('d-none');
            const textarea = editWrapper.querySelector('textarea');
            if (textarea) textarea.focus();
        }
    }

    // Cancel edit
    if (e.target.closest('.cancel-edit-btn')) {
        const commentId = e.target.closest('.cancel-edit-btn').dataset.commentId;
        const contentWrapper = document.getElementById('comment-content-' + commentId);
        const editWrapper = document.getElementById('comment-edit-' + commentId);

        if (contentWrapper && editWrapper) {
            contentWrapper.classList.remove('d-none');
            editWrapper.classList.add('d-none');
        }
    }

    // Save edit
    if (e.target.closest('.save-edit-btn')) {
        const btn = e.target.closest('.save-edit-btn');
        const commentId = btn.dataset.commentId;
        const textarea = document.getElementById('edit-textarea-' + commentId);
        const content = textarea?.value.trim();

        if (!content) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        // CORRECTED: Build URL properly instead of string replacement
        const incidentId = '{{ $incident->id }}';
        const url = `/incidents/${incidentId}/comments/${commentId}`;

        fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ content: content })
        })
        .then(async r => {
            const contentType = r.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const json = await r.json();
                if (!r.ok) throw new Error(json.message || 'Failed to update');
                return json;
            }
            throw new Error('Server error');
        })
        .then(data => {
            if (data.success) {
                const contentWrapper = document.getElementById('comment-content-' + commentId);
                const editWrapper = document.getElementById('comment-edit-' + commentId);

                if (contentWrapper && editWrapper) {
                    // Update displayed content
                    let formattedContent = escapeHtml(data.data.comment.content);
                    formattedContent = formattedContent.replace(/@(\w+)/g, '<span class="text-primary fw-medium">@$1</span>');
                    formattedContent = formattedContent.replace(/#(\w+)/g, '<span class="text-success fw-medium">#$1</span>');
                    formattedContent = formattedContent.replace(/\n/g, '<br>');

                    const commentText = contentWrapper.querySelector('.comment-text');
                    if (commentText) {
                        commentText.innerHTML = formattedContent;
                    }
                    contentWrapper.classList.remove('d-none');
                    editWrapper.classList.add('d-none');
                }

                // Show edited indicator
                const commentEl = document.getElementById('comment-' + commentId);
                if (commentEl) {
                    const headerSmall = commentEl.querySelector('.d-flex.gap-2 small.text-muted');
                    if (headerSmall && !commentEl.querySelector('.edited-badge')) {
                        const editedSpan = document.createElement('small');
                        editedSpan.className = 'text-muted edited-badge ms-1';
                        editedSpan.innerHTML = '<i class="fas fa-pencil-alt"></i> edited';
                        headerSmall.after(editedSpan);
                    }
                }

                if (typeof toastr !== 'undefined') toastr.success('Comment updated!');
            } else {
                if (typeof toastr !== 'undefined') toastr.error(data.message || 'Failed to update');
            }
        })
        .catch(err => {
            console.error('Edit error:', err);
            if (typeof toastr !== 'undefined') toastr.error(err.message || 'Failed to update comment');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i>Save';
        });
    }

    // Delete comment
    if (e.target.closest('.delete-comment-btn')) {
        const btn = e.target.closest('.delete-comment-btn');
        const commentId = btn.dataset.commentId;

        if (!confirm('Delete this comment? This cannot be undone.')) return;

        const incidentId = '{{ $incident->id }}';
        const url = `/incidents/${incidentId}/comments/${commentId}`;

        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(async r => {
            const contentType = r.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const json = await r.json();
                if (!r.ok) throw new Error(json.message || 'Failed to delete');
                return json;
            }
            throw new Error('Server error');
        })
        .then(data => {
            if (data.success) {
                // Remove comment from DOM with animation
                const commentEl = document.getElementById('comment-' + commentId);
                if (commentEl) {
                    commentEl.style.transition = 'opacity 0.3s, max-height 0.3s';
                    commentEl.style.opacity = '0';
                    commentEl.style.maxHeight = '0';
                    commentEl.style.overflow = 'hidden';
                    setTimeout(() => commentEl.remove(), 300);
                }

                // Update count
                const countEl = document.getElementById('commentCount');
                if (countEl && data.data.comments_count !== undefined) {
                    countEl.textContent = data.data.comments_count;
                }

                if (typeof toastr !== 'undefined') toastr.success('Comment deleted!');
            } else {
                if (typeof toastr !== 'undefined') toastr.error(data.message || 'Failed to delete');
            }
        })
        .catch(err => {
            console.error('Delete error:', err);
            if (typeof toastr !== 'undefined') toastr.error(err.message || 'Failed to delete comment');
        });
    }
});

    // ==========================================
    // MODAL FORMS
    // ==========================================

    function setupModalForm(formId, url, successMsg) {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            const btn = this.querySelector('button[type="submit"]');

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            })
            .then(async r => {
                // Check if response is JSON
                const contentType = r.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const json = await r.json();
                    if (!r.ok) {
                        // Handle validation errors
                        throw new Error(json.message || 'Validation failed');
                    }
                    return json;
                }
                // Not JSON - likely an error page
                const text = await r.text();
                console.error('Server returned non-JSON:', text.substring(0, 200));
                throw new Error('Server error. Please try again.');
            })
            .then(data => {
                if (data.success) {
                    // Hide modal
                    const modalEl = document.getElementById(formId.replace('Form', 'Modal'));
                    if (modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) {
                            modal.hide();
                            // Clean up backdrop
                            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                            document.body.classList.remove('modal-open');
                            document.body.style.overflow = '';
                            document.body.style.paddingRight = '';
                        }
                    }

                    // Show success
                    if (typeof toastr !== 'undefined') {
                        toastr.success(data.message || successMsg);
                    }

                    // Reload after delay
                    setTimeout(() => location.reload(), 1200);
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(data.message || 'Operation failed.');
                    }
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-check me-1"></i> Submit';
                    }
                }
            })
            .catch(err => {
                console.error('Modal submit error:', err);
                if (typeof toastr !== 'undefined') {
                    toastr.error(err.message || 'Failed. Please try again.');
                }
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check me-1"></i> Submit';
                }
            });
        });
    }

    // ==========================================
    // ESCALATION - LOAD USERS BY DEPARTMENT
    // ==========================================
    const escalateDeptSelect = document.getElementById('escalateDeptSelect');
    const escalateUserSelect = document.getElementById('escalateUserSelect');
    const escalateUserCount = document.getElementById('escalateUserCount');

    if (escalateDeptSelect && escalateUserSelect) {
        escalateDeptSelect.addEventListener('change', function() {
            const deptId = this.value;

            if (!deptId) {
                escalateUserSelect.innerHTML = '<option value="">Select Department First</option>';
                if (escalateUserCount) escalateUserCount.textContent = '';
                return;
            }

            // Show loading
            escalateUserSelect.innerHTML = '<option value="">Loading users...</option>';

            fetch(`/api/v1/departments/${deptId}/users`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    escalateUserSelect.innerHTML = '<option value="">Select User</option>';

                    if (data.data.length === 0) {
                        escalateUserSelect.innerHTML += '<option value="" disabled>No users in this department</option>';
                        if (escalateUserCount) escalateUserCount.textContent = 'No users found';
                    } else {
                        data.data.forEach(user => {
                            escalateUserSelect.innerHTML += `
                                <option value="${user.id}">${user.name} (${user.role_name || 'Staff'})</option>
                            `;
                        });
                        if (escalateUserCount) {
                            escalateUserCount.textContent = `${data.data.length} user(s) available`;
                        }
                    }

                    // Refresh Select2
                    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                        jQuery(escalateUserSelect).trigger('change');
                    }
                }
            })
            .catch(err => {
                console.error('Failed to load users:', err);
                escalateUserSelect.innerHTML = '<option value="">Error loading users</option>';
            });
        });
    }

    // ==========================================
    // FILE PREVIEW FOR RESOLVE AND CLOSE MODALS
    // ==========================================
    function setupFilePreview(inputId, previewContainerId) {
        const input = document.querySelector(`#${inputId} input[type="file"]`);
        const preview = document.getElementById(previewContainerId);
        if (!input || !preview) return;

        let files = new DataTransfer();

        input.addEventListener('change', function() {
            Array.from(this.files).forEach(f => {
                files.items.add(f);
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'position-relative d-inline-block';
                    div.style.cssText = 'width:60px;height:60px;margin:2px;';

                    if (f.type.startsWith('image/')) {
                        div.innerHTML = `<img src="${e.target.result}" style="width:60px;height:60px;object-fit:cover;border-radius:6px;">`;
                    } else {
                        let icon = 'fa-file';
                        if (f.type.includes('pdf')) icon = 'fa-file-pdf';
                        else if (f.type.includes('word')) icon = 'fa-file-word';
                        else if (f.type.includes('excel') || f.type.includes('sheet')) icon = 'fa-file-excel';
                        div.innerHTML = `<div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:60px;height:60px;"><i class="fas ${icon} fa-lg text-muted"></i></div>`;
                    }
                    div.innerHTML += `<button type="button" class="btn-remove position-absolute bg-danger text-white rounded-circle"
                        style="top:-6px;right:-6px;width:18px;height:18px;font-size:10px;padding:0;line-height:1;border:none;cursor:pointer;"
                        data-name="${f.name.replace(/'/g, "\\'")}">&times;</button>`;
                    preview.appendChild(div);
                };
                reader.readAsDataURL(f);
            });
            this.value = '';
        });

        preview.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-remove')) {
                const name = e.target.dataset.name;
                const newDt = new DataTransfer();
                Array.from(files.files).forEach(f => { if (f.name !== name) newDt.items.add(f); });
                files = newDt;
                e.target.closest('.position-relative').remove();
            }
        });

        // Return the files DataTransfer for form submission
        return files;
    }

    const resolveFiles = setupFilePreview('resolveModal', 'resolveFilePreview');
    const closeFiles = setupFilePreview('closeModal', 'closeFilePreview');

    // ==========================================
    // MODAL FORMS (Updated for file uploads)
    // ==========================================
    function setupModalForm(formId, url, successMsg, fileDataTransfer = null) {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');

            // Use FormData for file support
            const formData = new FormData(this);

            // Add files from DataTransfer if provided
            if (fileDataTransfer && fileDataTransfer.files.length > 0) {
                Array.from(fileDataTransfer.files).forEach(f => {
                    formData.append('files[]', f);
                });
            }

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(async r => {
                const contentType = r.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const json = await r.json();
                    if (!r.ok) throw new Error(json.message || 'Failed');
                    return json;
                }
                throw new Error('Server error');
            })
            .then(data => {
                if (data.success) {
                    const modalEl = document.getElementById(formId.replace('Form', 'Modal'));
                    if (modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                    }
                    if (typeof toastr !== 'undefined') toastr.success(data.message || successMsg);
                    setTimeout(() => location.reload(), 1200);
                }
            })
            .catch(err => {
                console.error(err);
                if (typeof toastr !== 'undefined') toastr.error(err.message || 'Failed.');
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check me-1"></i> Submit'; }
            });
        });
    }

    // Setup all modal forms
    setupModalForm('assignForm', '{{ route("incidents.assign", $incident) }}', 'Assigned!');
    setupModalForm('escalateForm', '{{ route("incidents.escalate", $incident) }}', 'Escalated!');
    setupModalForm('resolveForm', '{{ route("incidents.resolve", $incident) }}', 'Resolved!', resolveFiles);
    setupModalForm('rejectForm', '{{ route("incidents.reject", $incident) }}', 'Rejected!');
    setupModalForm('closeForm', '{{ route("incidents.close", $incident) }}', 'Closed!', closeFiles);

    window.closeIncident = function() {
        if (!confirm('Close this incident?')) return;
        fetch('{{ route("incidents.close", $incident) }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: JSON.stringify({}) })
        .then(r => r.json()).then(data => { if (data.success) { toastr.success('Closed!'); setTimeout(() => location.reload(), 1000); } });
    };
    window.reopenIncident = function() {
        if (!confirm('Reopen this incident?')) return;
        fetch('{{ route("incidents.reopen", $incident) }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: JSON.stringify({}) })
        .then(r => r.json()).then(data => { if (data.success) { toastr.success('Reopened!'); setTimeout(() => location.reload(), 1000); } });
    };
    window.shareIncident = function() {
        fetch('{{ route("incidents.share", $incident) }}').then(r => r.json()).then(data => {
            if (data.success) { window.open(data.whatsapp_url, '_blank'); }
        });
    };

    ['assignModal', 'escalateModal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal) modal.addEventListener('shown.bs.modal', () => {
            modal.querySelectorAll('.select2').forEach(s => {
                if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                    jQuery(s).select2({ dropdownParent: jQuery(modal), placeholder: 'Search...', allowClear: true, width: '100%' });
                }
            });
        });
    });

    function escapeHtml(text) {
        if (!text) return '';
        const map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

});
</script>
@endpush
