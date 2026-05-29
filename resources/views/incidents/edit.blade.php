{{-- resources/views/incidents/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Incident #' . $incident->incident_id . ' - IRMS')

@push('styles')
<style>
    .form-section {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
        transition: all 0.2s ease;
    }

    .form-section:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .form-section .section-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid #f3f4f6;
    }

    .form-section .section-heading .section-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .form-section .section-heading .section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1f2937;
    }

    .form-control,
    .form-select {
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 0.875rem;
        transition: all 0.2s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    /* Tag Selector */
    .tag-selector {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
    }

    .tag-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 14px;
        border: 2px solid #e5e7eb;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        background: white;
        color: #6b7280;
        user-select: none;
    }

    .tag-chip:hover {
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .tag-chip.selected {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #1a56db;
        font-weight: 600;
    }

    .selected-tags-display {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        min-height: 32px;
        padding: 4px;
        margin-bottom: 4px;
    }

    .selected-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 16px;
        font-size: 0.6875rem;
        font-weight: 500;
        color: #1a56db;
    }

    .selected-tag .remove-tag {
        cursor: pointer;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #93c5fd;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 8px;
        transition: all 0.2s;
    }

    .selected-tag .remove-tag:hover {
        background: #ef4444;
    }

    /* Existing Media */
    .existing-media-item {
        position: relative;
        width: 100px;
        height: 100px;
        border-radius: 10px;
        overflow: hidden;
        border: 2px solid #e5e7eb;
        display: inline-block;
        margin: 4px;
    }

    .existing-media-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .existing-media-item .delete-media-btn {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 24px;
        height: 24px;
        background: rgba(239, 68, 68, 0.9);
        color: white;
        border: 2px solid white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 11px;
        font-weight: 700;
        z-index: 2;
        padding: 0;
        line-height: 1;
        transition: all 0.2s;
    }

    .existing-media-item .delete-media-btn:hover {
        background: #dc2626;
        transform: scale(1.1);
    }

    .tips-card {
        background: linear-gradient(135deg, #1e3a5f 0%, #1a56db 100%);
        border-radius: 16px;
        padding: 24px;
        color: white;
        position: sticky;
        top: 84px;
    }

    .tips-card h6 {
        color: white;
        margin-bottom: 16px;
    }

    .tips-card ul li {
        padding: 8px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 0.8125rem;
    }

    .tips-card ul li:last-child {
        border-bottom: none;
    }

    .tips-card ul li i {
        color: #34d399;
    }
</style>
@endpush

@section('content')
<div class="py-3">

    <div class="mb-4">
        <a href="{{ route('incidents.show', $incident) }}" class="text-muted text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i> Back to Incident
        </a>
        <h4 class="fw-bold mt-1 mb-1">
            Edit Incident <span class="badge bg-light text-dark ms-2">#{{ $incident->incident_id }}</span>
        </h4>
        <p class="text-muted small mb-0">
            Update the incident details below. Fields marked <span class="text-danger fw-bold">*</span> are required.
        </p>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <form action="{{ route('incidents.update', $incident) }}" method="POST" enctype="multipart/form-data"
                id="editIncidentForm">
                @csrf
                @method('PUT')

                {{-- Section 1: Basic Information --}}
                <div class="form-section">
                    <div class="section-heading">
                        <div class="section-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div>
                            <div class="section-title">Basic Information</div>
                            <small class="text-muted">Update the core details of the incident</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Incident Title <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="title"
                                class="form-control form-control-lg @error('title') is-invalid @enderror"
                                value="{{ old('title', $incident->title) }}" required maxlength="255">
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select @error('category_id') is-invalid @enderror"
                                required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $incident->category_id) == $cat->id
                                    ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                            <select name="department_id"
                                class="form-select @error('department_id') is-invalid @enderror" required>
                                <option value="">Select Department</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id', $incident->department_id) ==
                                    $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }} ({{ $dept->code }})
                                </option>
                                @endforeach
                            </select>
                            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Location</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i
                                        class="fas fa-map-marker-alt text-muted"></i></span>
                                <input type="text" name="location"
                                    class="form-control @error('location') is-invalid @enderror"
                                    value="{{ old('location', $incident->location) }}"
                                    placeholder="e.g., Terminal 1, Gate A">
                            </div>
                            @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Section 2: Severity & Priority --}}
                <div class="form-section">
                    <div class="section-heading">
                        <div class="section-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <div class="section-title">Severity & Priority</div>
                            <small class="text-muted">Update the urgency and impact level</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Severity Level <span
                                    class="text-danger">*</span></label>
                            <select name="severity" class="form-select @error('severity') is-invalid @enderror"
                                required>
                                <option value="low" {{ old('severity', $incident->severity) == 'low' ? 'selected' : ''
                                    }}>🟢 Low - Minor issue</option>
                                <option value="medium" {{ old('severity', $incident->severity) == 'medium' ? 'selected'
                                    : '' }}>🟡 Medium - Attention required</option>
                                <option value="high" {{ old('severity', $incident->severity) == 'high' ? 'selected' : ''
                                    }}>🟠 High - Urgent action needed</option>
                                <option value="critical" {{ old('severity', $incident->severity) == 'critical' ?
                                    'selected' : '' }}>🔴 Critical - Immediate action</option>
                            </select>
                            @error('severity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select @error('priority') is-invalid @enderror"
                                required>
                                <option value="low" {{ old('priority', $incident->priority) == 'low' ? 'selected' : ''
                                    }}>Low</option>
                                <option value="medium" {{ old('priority', $incident->priority) == 'medium' ? 'selected'
                                    : '' }}>Medium</option>
                                <option value="high" {{ old('priority', $incident->priority) == 'high' ? 'selected' : ''
                                    }}>High</option>
                                <option value="critical" {{ old('priority', $incident->priority) == 'critical' ?
                                    'selected' : '' }}>Critical</option>
                            </select>
                            @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Section 3: Description & Tags --}}
                <div class="form-section">
                    <div class="section-heading">
                        <div class="section-icon bg-info bg-opacity-10 text-info">
                            <i class="fas fa-align-left"></i>
                        </div>
                        <div>
                            <div class="section-title">Description & Tags</div>
                            <small class="text-muted">Update the incident description and categorization</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Detailed Description <span
                                class="text-danger">*</span></label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                            rows="5" placeholder="Please describe the incident in detail..."
                            required>{{ old('description', $incident->description) }}</textarea>
                        <small class="text-muted float-end mt-1"><span id="charCount">0</span>/5000</small>
                        @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    {{-- Tags --}}
                    <div>
                        <label class="form-label fw-semibold">Quick Tags</label>
                        <div class="tag-selector" id="tagSelector">
                            <span class="tag-chip" data-tag="urgent"><span class="tag-emoji">🔴</span> Urgent</span>
                            <span class="tag-chip" data-tag="safety"><span class="tag-emoji">🦺</span> Safety</span>
                            <span class="tag-chip" data-tag="maintenance"><span class="tag-emoji">🔧</span>
                                Maintenance</span>
                            <span class="tag-chip" data-tag="security"><span class="tag-emoji">🔒</span> Security</span>
                            <span class="tag-chip" data-tag="electrical"><span class="tag-emoji">⚡</span>
                                Electrical</span>
                            <span class="tag-chip" data-tag="water-leak"><span class="tag-emoji">💧</span> Water
                                Leak</span>
                            <span class="tag-chip" data-tag="cleaning"><span class="tag-emoji">🧹</span> Cleaning</span>
                            <span class="tag-chip" data-tag="it-issue"><span class="tag-emoji">💻</span> IT Issue</span>
                            <span class="tag-chip" data-tag="fire"><span class="tag-emoji">🔥</span> Fire Hazard</span>
                            <span class="tag-chip" data-tag="structural"><span class="tag-emoji">🏗️</span>
                                Structural</span>
                            <span class="tag-chip" data-tag="vehicle"><span class="tag-emoji">🚗</span> Vehicle</span>
                            <span class="tag-chip" data-tag="noise"><span class="tag-emoji">🔊</span> Noise</span>
                        </div>
                        <div class="selected-tags-display" id="selectedTagsDisplay"></div>
                        <div class="tags-input-wrapper">
                            <input type="text" id="customTagInput" class="form-control form-control-sm"
                                placeholder="Type custom tag and press Enter or comma...">
                        </div>
                        <input type="hidden" name="tags" id="tagsHidden"
                            value="{{ old('tags', is_array($incident->tags) ? implode(',', $incident->tags) : '') }}">
                        <small class="text-muted">Click predefined tags or type your own.</small>
                    </div>
                </div>

                {{-- Section 4: Existing Media --}}
                @if($incident->media->count() > 0)
                <div class="form-section">
                    <div class="section-heading">
                        <div class="section-icon bg-info bg-opacity-10 text-info">
                            <i class="fas fa-images"></i>
                        </div>
                        <div>
                            <div class="section-title">Existing Attachments</div>
                            <small class="text-muted">Click × to remove an attachment</small>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap" id="existingMedia">
                        @foreach($incident->media as $media)
                        <div class="existing-media-item" data-media-id="{{ $media->id }}">
                            @if($media->isImage())
                            <img src="{{ $media->url }}" alt="{{ $media->original_name }}">
                            @else
                            <div class="d-flex align-items-center justify-content-center bg-light"
                                style="width:100%;height:100%;">
                                <div class="text-center">
                                    <i class="fas fa-file fa-2x text-muted"></i>
                                    <small class="d-block text-truncate" style="max-width:80px;font-size:0.6rem;">{{
                                        $media->original_name }}</small>
                                </div>
                            </div>
                            @endif
                            <button type="button" class="delete-media-btn" data-media-id="{{ $media->id }}"
                                onclick="deleteExistingMedia({{ $media->id }}, this)">×</button>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Section 5: Add New Attachments --}}
                <div class="form-section">
                    <div class="section-heading">
                        <div class="section-icon bg-success bg-opacity-10 text-success">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div>
                            <div class="section-title">Add New Attachments</div>
                            <small class="text-muted">Upload additional photos, videos, or documents (Max 20MB
                                each)</small>
                        </div>
                    </div>
                    <input type="file" id="fileInput" name="files[]" multiple
                        accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx" style="display: none;">
                    <div class="file-upload-wrapper" id="uploadZone"
                        style="border: 2px dashed #d1d5db; border-radius: 16px; padding: 30px 20px; text-align: center; cursor: pointer; transition: all 0.3s; background: #fafbfc;">
                        <i class="fas fa-cloud-upload-alt upload-icon-main"
                            style="font-size: 2.5rem; color: #3b82f6; margin-bottom: 8px;"></i>
                        <div class="upload-text" style="font-weight: 600; color: #374151;">Click or Drop Files Here
                        </div>
                        <div class="upload-hint" style="font-size: 0.8125rem; color: #6b7280;">Add more attachments
                        </div>
                    </div>
                    <div class="preview-grid" id="previewContainer"
                        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 16px;">
                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-flex flex-column flex-sm-row gap-2 gap-sm-3 justify-content-sm-end mb-4 mb-sm-5">
                    <a href="{{ route('incidents.show', $incident) }}"
                        class="btn btn-light btn-lg px-4 order-2 order-sm-1">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg px-5 order-1 order-sm-2" id="submitBtn">
                        <i class="fas fa-save me-2"></i>Update Incident
                    </button>
                </div>
            </form>
        </div>

        {{-- Tips Sidebar --}}
        <div class="col-lg-4">
            <div class="tips-card">
                <h6><i class="fas fa-lightbulb me-2"></i>Editing Tips</h6>
                <ul class="list-unstyled mb-0">
                    <li><i class="fas fa-check-circle me-2"></i>Update the description with new findings</li>
                    <li><i class="fas fa-check-circle me-2"></i>Change severity if the situation has changed</li>
                    <li><i class="fas fa-check-circle me-2"></i>Add new photos or documents as evidence</li>
                    <li><i class="fas fa-check-circle me-2"></i>Remove outdated or incorrect attachments</li>
                    <li><i class="fas fa-check-circle me-2"></i>Update location if it was incorrect</li>
                    <li><i class="fas fa-check-circle me-2"></i>Add relevant tags for better categorization</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

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

    // Initialize from existing tags
    if (tagsHidden && tagsHidden.value) {
        selectedTags = tagsHidden.value.split(',').map(t => t.trim()).filter(t => t);
        updateTagsDisplay();
        updateTagChips();
    }

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
            <span class="selected-tag">${escapeHtml(tag)}<span class="remove-tag" data-tag="${escapeHtml(tag)}">×</span></span>
        `).join('');
    }

    function updateTagChips() {
        if (!tagSelector) return;
        tagSelector.querySelectorAll('.tag-chip').forEach(chip => {
            chip.classList.toggle('selected', selectedTags.includes(chip.dataset.tag));
        });
    }

    function updateHiddenInput() {
        if (tagsHidden) tagsHidden.value = selectedTags.join(',');
    }

    // ==========================================
    // DELETE EXISTING MEDIA - FIXED
    // ==========================================
    window.deleteExistingMedia = function(mediaId, btn) {
        if (!confirm('Remove this attachment? This action cannot be undone.')) return;

        // Show loading state on button
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        const incidentId = '{{ $incident->id }}';
        const url = `/incidents/${incidentId}/media/${mediaId}`;

        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(async response => {
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Failed to delete');
                }
                return data;
            }
            // If not JSON, get text for debugging
            const text = await response.text();
            console.error('Server returned non-JSON response:', text.substring(0, 200));
            throw new Error('Server error. Please try again.');
        })
        .then(data => {
            if (data.success) {
                // Remove the media element with animation
                const mediaItem = btn.closest('.existing-media-item');
                if (mediaItem) {
                    mediaItem.style.transition = 'all 0.3s ease';
                    mediaItem.style.opacity = '0';
                    mediaItem.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        mediaItem.remove();

                        // Check if there are any media items left
                        const remainingMedia = document.querySelectorAll('.existing-media-item');
                        if (remainingMedia.length === 0) {
                            // Optionally hide the entire existing media section
                            const existingMediaSection = document.getElementById('existingMedia')?.closest('.form-section');
                            if (existingMediaSection) {
                                existingMediaSection.style.transition = 'all 0.3s ease';
                                existingMediaSection.style.opacity = '0';
                                setTimeout(() => existingMediaSection.remove(), 300);
                            }
                        }
                    }, 300);
                }

                // Show success toast
                if (typeof toastr !== 'undefined') {
                    toastr.success(data.message || 'Attachment removed successfully!');
                } else {
                    alert('Attachment removed successfully!');
                }
            } else {
                // Show error
                if (typeof toastr !== 'undefined') {
                    toastr.error(data.message || 'Failed to remove attachment.');
                }
                // Restore button
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Delete media error:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error(error.message || 'Failed to remove attachment. Please try again.');
            } else {
                alert('Failed to remove attachment. Please try again.');
            }
            // Restore button
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
    };

    // ==========================================
    // FILE UPLOAD FOR NEW ATTACHMENTS
    // ==========================================
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');
    const previewContainer = document.getElementById('previewContainer');
    const MAX_FILE_SIZE = 20 * 1024 * 1024;
    let selectedFiles = new DataTransfer();

    if (uploadZone) {
        uploadZone.addEventListener('click', () => fileInput.click());
        uploadZone.addEventListener('dragover', (e) => { e.preventDefault(); uploadZone.classList.add('drag-active'); });
        uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-active'));
        uploadZone.addEventListener('drop', (e) => { e.preventDefault(); uploadZone.classList.remove('drag-active'); handleFiles(e.dataTransfer.files); });
    }

    if (fileInput) {
        fileInput.addEventListener('change', () => handleFiles(fileInput.files));
    }

    async function handleFiles(files) {
        for (const file of Array.from(files)) {
            if (file.size > MAX_FILE_SIZE) {
                alert(`File "${file.name}" is too large (${formatSize(file.size)}). Max 20MB.`);
                continue;
            }
            let processedFile = file;
            if (file.type.startsWith('image/') && file.size > 200 * 1024) {
                processedFile = await compressImageNative(file);
            }
            selectedFiles.items.add(processedFile);
            createPreview(processedFile);
        }
        fileInput.files = selectedFiles.files;
    }

    function compressImageNative(file) {
        return new Promise((resolve) => {
            if (!file.type.startsWith('image/') || file.size < 200 * 1024) { resolve(file); return; }
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    let { width, height } = img;
                    const MAX = 1920;
                    if (width > MAX || height > MAX) {
                        if (width > height) { height = Math.round((height * MAX) / width); width = MAX; }
                        else { width = Math.round((width * MAX) / height); height = MAX; }
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = width; canvas.height = height;
                    canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                    canvas.toBlob((blob) => {
                        if (blob && blob.size < file.size) {
                            resolve(new File([blob], file.name, { type: 'image/jpeg', lastModified: Date.now() }));
                        } else { resolve(file); }
                    }, 'image/jpeg', 0.7);
                };
                img.onerror = () => resolve(file);
                img.src = e.target.result;
            };
            reader.onerror = () => resolve(file);
            reader.readAsDataURL(file);
        });
    }

    function createPreview(file) {
        const div = document.createElement('div');
        div.className = 'preview-item';
        const reader = new FileReader();
        reader.onload = function(e) {
            div.innerHTML = file.type.startsWith('image/')
                ? `<img src="${e.target.result}" alt=""><button type="button" class="btn-remove">×</button>`
                : `<div class="file-preview-fallback"><i class="fas fa-file"></i><span class="file-name">${escapeHtml(file.name)}</span></div><button type="button" class="btn-remove">×</button>`;
            previewContainer.appendChild(div);
        };
        reader.readAsDataURL(file);
    }

    previewContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove')) {
            const filename = e.target.closest('.preview-item')?.querySelector('.file-name')?.textContent;
            const newDt = new DataTransfer();
            Array.from(selectedFiles.files).forEach(f => { if (f.name !== filename) newDt.items.add(f); });
            selectedFiles = newDt;
            fileInput.files = selectedFiles.files;
            e.target.closest('.preview-item').remove();
        }
    });

    function formatSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024, sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    function escapeHtml(text) {
        const map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    // ==========================================
    // FORM SUBMISSION
    // ==========================================
    const form = document.getElementById('editIncidentForm');
    const submitBtn = document.getElementById('submitBtn');
    if (form && submitBtn) {
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
        });
    }

});
</script>
@endpush