{{-- resources/views/incidents/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Report New Incident - IRMS')

@push('styles')
<style>
    /* Form Section */
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

    /* Form Controls */
    .form-control {
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 0.875rem;
        transition: all 0.2s ease;
    }
    .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .form-control::placeholder {
        color: #9ca3af;
    }

    /* Select2 Custom */
    .select2-container--default .select2-selection--single {
        height: 42px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 42px;
        padding-left: 14px;
        font-size: 0.875rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    /* File Upload */
    .file-upload-zone {
        border: 2px dashed #d1d5db;
        border-radius: 12px;
        padding: 32px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #fafbfc;
    }
    .file-upload-zone:hover {
        border-color: #3b82f6;
        background: #f0f5ff;
    }
    .file-upload-zone .upload-icon {
        font-size: 2.5rem;
        color: #3b82f6;
        margin-bottom: 12px;
        display: block;
    }
    .file-upload-zone .upload-title {
        font-weight: 600;
        color: #374151;
        margin-bottom: 4px;
    }
    .file-upload-zone .upload-subtitle {
        font-size: 0.8125rem;
        color: #6b7280;
    }

    /* Preview Grid */
    .preview-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 16px;
    }
    .preview-item {
        width: 90px;
        height: 90px;
        border-radius: 10px;
        position: relative;
        border: 2px solid #e5e7eb;
        overflow: hidden;
        background: #f9fafb;
        transition: all 0.2s;
    }
    .preview-item:hover {
        border-color: #3b82f6;
    }
    .preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .preview-item .file-icon {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    .preview-item .file-icon i {
        font-size: 1.8rem;
        color: #6b7280;
    }
    .preview-item .file-icon span {
        font-size: 0.55rem;
        color: #9ca3af;
        text-align: center;
        padding: 0 4px;
        word-break: break-all;
        line-height: 1.2;
    }
    .preview-item .btn-remove {
        position: absolute;
        top: -4px;
        right: -4px;
        width: 22px;
        height: 22px;
        background: #ef4444;
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
    .preview-item .btn-remove:hover {
        background: #dc2626;
        transform: scale(1.1);
    }

    /* Anonymouse Toggle */
    .anonymous-toggle {
        background: #f9fafb;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px 20px;
        transition: all 0.2s;
    }
    .anonymous-toggle:hover {
        border-color: #d1d5db;
    }
    .anonymous-toggle:has(input:checked) {
        border-color: #3b82f6;
        background: #eff6ff;
    }

    /* Tips Card */
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
<div class="container-fluid px-3 py-3">

    {{-- Page Header --}}
    <div class="mb-4">
        <a href="{{ route('incidents.index') }}" class="text-muted text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i> Back to Incidents
        </a>
        <h4 class="fw-bold mt-1 mb-1">Report New Incident</h4>
        <p class="text-muted small mb-0">
            Fill in the details below. Fields marked <span class="text-danger fw-bold">*</span> are required.
        </p>
    </div>

    <div class="row g-4">
        {{-- Main Form --}}
        <div class="col-lg-8">
            <form action="{{ route('incidents.store') }}" method="POST" enctype="multipart/form-data" id="incidentForm">
                @csrf

                {{-- Section 1: Basic Information --}}
                <div class="form-section">
                    <div class="section-heading">
                        <div class="section-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div>
                            <div class="section-title">Basic Information</div>
                            <small class="text-muted">Provide the core details of the incident</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Incident Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title"
                                   class="form-control form-control-lg @error('title') is-invalid @enderror"
                                   value="{{ old('title') }}"
                                   placeholder="e.g., Water leakage spotted in Terminal 1 lobby area"
                                   required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Category <span class="text-danger">*</span>
                            </label>
                            <select name="category_id" class="form-select  @error('category_id') is-invalid @enderror"
                                    data-placeholder="Select category..." required>
                                <option value=""></option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Department <span class="text-danger">*</span>
                            </label>
                            <select name="department_id" class="form-select @error('department_id') is-invalid @enderror"
                                    data-placeholder="Select department..." required>
                                <option value=""></option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                        {{ old('department_id', auth()->user()->department_id) == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }} ({{ $dept->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Location</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-map-marker-alt text-muted"></i>
                                </span>
                                <input type="text" name="location"
                                       class="form-control @error('location') is-invalid @enderror"
                                       value="{{ old('location') }}"
                                       placeholder="e.g., Terminal 1, Gate A, Near Check-in Counter">
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
                            <small class="text-muted">Assess the urgency and impact level</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Severity Level <span class="text-danger">*</span></label>
                            <select name="severity" class="form-select @error('severity') is-invalid @enderror" required>
                                <option value="low" {{ old('severity') == 'low' ? 'selected' : '' }}>
                                    🟢 Low - Minor issue, no immediate action needed
                                </option>
                                <option value="medium" {{ old('severity', 'medium') == 'medium' ? 'selected' : '' }}>
                                    🟡 Medium - Moderate impact, attention required
                                </option>
                                <option value="high" {{ old('severity') == 'high' ? 'selected' : '' }}>
                                    🟠 High - Significant impact, urgent action needed
                                </option>
                                <option value="critical" {{ old('severity') == 'critical' ? 'selected' : '' }}>
                                    🔴 Critical - Severe impact, immediate action required
                                </option>
                            </select>
                            @error('severity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                <option value="critical" {{ old('priority') == 'critical' ? 'selected' : '' }}>Critical</option>
                            </select>
                            @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Section 3: Description --}}
                <div class="form-section">
                    <div class="section-heading">
                        <div class="section-icon bg-info bg-opacity-10 text-info">
                            <i class="fas fa-align-left"></i>
                        </div>
                        <div>
                            <div class="section-title">Description</div>
                            <small class="text-muted">Provide detailed information about the incident</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Detailed Description <span class="text-danger">*</span>
                        </label>
                        <textarea name="description"
                                  class="form-control @error('description') is-invalid @enderror"
                                  rows="6"
                                  placeholder="Please describe the incident in detail...&#10;&#10;• What did you observe?&#10;• When did it happen?&#10;• Where exactly is the location?&#10;• Who or what is affected?&#10;• Any immediate actions already taken?"
                                  required>{{ old('description') }}</textarea>
                        <small class="text-muted float-end mt-1">
                            <span id="charCount">0</span>/5000 characters
                        </small>
                        @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Tags</label>
                        <input type="text" name="tags" class="form-control"
                               value="{{ old('tags') }}"
                               placeholder="e.g., urgent, safety, maintenance (separate with commas)">
                        <small class="text-muted">Help categorize this incident with relevant tags</small>
                    </div>
                </div>

                {{-- Section 4: Attachments --}}
                <div class="form-section">
                    <div class="section-heading">
                        <div class="section-icon bg-success bg-opacity-10 text-success">
                            <i class="fas fa-paperclip"></i>
                        </div>
                        <div>
                            <div class="section-title">Attachments</div>
                            <small class="text-muted">Upload photos, videos, or documents</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Choose Files</label>
                        <input type="file" id="fileInput" name="files[]" multiple
                               accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx"
                               class="form-control" style="padding: 8px;">
                        <small class="text-muted">Accepted: Images, Videos, PDF, DOC, XLS (Max 20MB each)</small>
                    </div>

                    <div class="file-upload-zone" id="dropZone">
                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                        <div class="upload-title">Drag & Drop Files Here</div>
                        <div class="upload-subtitle">or click the browse button above</div>
                    </div>

                    <div class="preview-grid" id="previewContainer"></div>
                </div>

                {{-- Section 5: Options --}}
                <div class="form-section">
                    <div class="section-heading">
                        <div class="section-icon bg-secondary bg-opacity-10 text-secondary">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div>
                            <div class="section-title">Additional Options</div>
                            <small class="text-muted">Configure reporting preferences</small>
                        </div>
                    </div>
                    <div class="anonymous-toggle">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_anonymous" name="is_anonymous" value="1"
                                   {{ old('is_anonymous') ? 'checked' : '' }}
                                   style="width: 20px; height: 20px; margin-top: 2px;">
                            <label class="form-check-label ms-2" for="is_anonymous">
                                <strong>Report Anonymously</strong>
                                <br>
                                <small class="text-muted">Your identity will be hidden from other users viewing this incident</small>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Submit Buttons --}}
                <div class="d-flex gap-3 justify-content-end mb-5">
                    <a href="{{ route('incidents.index') }}" class="btn btn-light btn-lg px-4">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg px-5" id="submitBtn">
                        <i class="fas fa-paper-plane me-2"></i>Submit Incident
                    </button>
                </div>

            </form>
        </div>

        {{-- Tips Sidebar --}}
        <div class="col-lg-4">
            <div class="tips-card">
                <h6><i class="fas fa-lightbulb me-2"></i>Tips for Good Reporting</h6>
                <ul class="list-unstyled mb-0">
                    <li><i class="fas fa-check-circle me-2"></i>Be specific about the exact location</li>
                    <li><i class="fas fa-check-circle me-2"></i>Include the time when you observed it</li>
                    <li><i class="fas fa-check-circle me-2"></i>Add clear photos or videos if possible</li>
                    <li><i class="fas fa-check-circle me-2"></i>Select the most appropriate category</li>
                    <li><i class="fas fa-check-circle me-2"></i>Choose severity based on actual impact</li>
                    <li><i class="fas fa-check-circle me-2"></i>Describe what you observed, not assumptions</li>
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
        textarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }

    // ==========================================
    // FILE UPLOAD
    // ==========================================
    if (typeof window.initializeFileUpload === 'function') {
        window.initializeFileUpload({
            fileInputId: 'fileInput',
            dropZoneId: 'dropZone',
            previewContainerId: 'previewContainer'
        });
    }

    // ==========================================
    // FORM SUBMISSION - LOADING STATE
    // ==========================================
    const form = document.getElementById('incidentForm');
    const submitBtn = document.getElementById('submitBtn');

    if (form && submitBtn) {
        form.addEventListener('submit', function(e) {
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
        });
    }

});
</script>
@endpush
