{{-- resources/views/incidents/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Report New Incident - IRMS')

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

    /* File Upload Area */
    .file-upload-wrapper {
        border: 2px dashed #d1d5db;
        border-radius: 16px;
        padding: 40px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #fafbfc;
        position: relative;
    }

    .file-upload-wrapper:hover,
    .file-upload-wrapper.drag-active {
        border-color: #3b82f6;
        background: #f0f5ff;
    }

    .file-upload-wrapper .upload-icon-main {
        font-size: 3rem;
        color: #3b82f6;
        margin-bottom: 12px;
    }

    .file-upload-wrapper .upload-text {
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
    }

    .file-upload-wrapper .upload-hint {
        font-size: 0.8125rem;
        color: #6b7280;
    }

    .file-upload-wrapper .upload-btn {
        display: inline-block;
        margin-top: 16px;
        padding: 10px 24px;
        background: #3b82f6;
        color: white;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .file-upload-wrapper .upload-btn:hover {
        background: #2563eb;
    }

    /* Preview Grid */
    .preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 10px;
        margin-top: 16px;
    }

    .preview-item {
        position: relative;
        aspect-ratio: 1;
        border-radius: 10px;
        overflow: hidden;
        border: 2px solid #e5e7eb;
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

    .preview-item .file-preview-fallback {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        padding: 8px;
    }

    .preview-item .file-preview-fallback i {
        font-size: 2rem;
        color: #6b7280;
    }

    .preview-item .file-preview-fallback .file-name {
        font-size: 0.6rem;
        color: #9ca3af;
        text-align: center;
        word-break: break-all;
        line-height: 1.2;
        max-height: 2.4em;
        overflow: hidden;
    }

    .preview-item .file-preview-fallback .file-size {
        font-size: 0.55rem;
        color: #d1d5db;
    }

    .preview-item .btn-remove {
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

    .preview-item .btn-remove:hover {
        background: #dc2626;
        transform: scale(1.1);
    }

    .preview-item .compressing-badge {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.75rem;
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

    .tag-chip .tag-emoji {
        font-size: 1rem;
    }

    .tags-input-wrapper {
        position: relative;
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

    @media (max-width: 767.98px) {
        .file-upload-wrapper {
            padding: 24px 16px;
        }

        .preview-grid {
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-3">

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
                            <label class="form-label fw-semibold">Incident Title <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="title"
                                class="form-control form-control-lg @error('title') is-invalid @enderror"
                                value="{{ old('title') }}"
                                placeholder="e.g., Water leakage spotted in Terminal 1 lobby area" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select @error('category_id') is-invalid @enderror"
                                required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id')==$cat->id ? 'selected' : '' }}>{{
                                    $cat->name }}</option>
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
                                <option value="{{ $dept->id }}" {{ old('department_id', auth()->user()->department_id)
                                    == $dept->id ? 'selected' : '' }}>
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
                                    value="{{ old('location') }}" placeholder="e.g., Terminal 1, Gate A">
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
                            <label class="form-label fw-semibold">Severity Level <span
                                    class="text-danger">*</span></label>
                            <select name="severity" class="form-select @error('severity') is-invalid @enderror"
                                required>
                                <option value="low" {{ old('severity')=='low' ? 'selected' : '' }}>🟢 Low - Minor issue
                                </option>
                                <option value="medium" {{ old('severity', 'medium' )=='medium' ? 'selected' : '' }}>🟡
                                    Medium - Attention required</option>
                                <option value="high" {{ old('severity')=='high' ? 'selected' : '' }}>🟠 High - Urgent
                                    action needed</option>
                                <option value="critical" {{ old('severity')=='critical' ? 'selected' : '' }}>🔴 Critical
                                    - Immediate action</option>
                            </select>
                            @error('severity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select @error('priority') is-invalid @enderror"
                                required>
                                <option value="low" {{ old('priority')=='low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ old('priority', 'medium' )=='medium' ? 'selected' : '' }}>
                                    Medium</option>
                                <option value="high" {{ old('priority')=='high' ? 'selected' : '' }}>High</option>
                                <option value="critical" {{ old('priority')=='critical' ? 'selected' : '' }}>Critical
                                </option>
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
                            <small class="text-muted">Provide detailed information and categorize the incident</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Detailed Description <span
                                class="text-danger">*</span></label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                            rows="5" placeholder="Please describe the incident in detail..."
                            required>{{ old('description') }}</textarea>
                        <small class="text-muted float-end mt-1"><span id="charCount">0</span>/5000</small>
                        @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    {{-- Predefined Tags --}}
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
                        <input type="hidden" name="tags" id="tagsHidden" value="{{ old('tags') }}">
                        <small class="text-muted">Click predefined tags or type your own. Press Enter or comma to
                            add.</small>
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
                            <small class="text-muted">Upload photos, videos, or documents (Max 20MB each - images
                                auto-compressed)</small>
                        </div>
                    </div>

                    {{-- Hidden file input for multiple files --}}
                    <input type="file" id="fileInput" name="files[]" multiple
                        accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx" style="display: none;">

                    {{-- Upload Zone --}}
                    <div class="file-upload-wrapper" id="uploadZone">
                        <i class="fas fa-cloud-upload-alt upload-icon-main"></i>
                        <div class="upload-text">Tap to Select Files</div>
                        <div class="upload-hint">Photos, Videos, Documents (Max 20MB each)</div>
                        <div class="upload-hint" style="font-size:0.7rem; margin-top:4px;">
                            📸 Images will be compressed for faster upload
                        </div>
                        <span class="upload-btn">
                            <i class="fas fa-plus me-1"></i> Choose Files
                        </span>
                    </div>

                    {{-- Preview Grid --}}
                    <div class="preview-grid" id="previewContainer"></div>
                </div>

                {{-- Section 5: Options -- no need --}}
                <div class="form-section d-none">
                    <div class="section-heading">
                        <div class="section-icon bg-secondary bg-opacity-10 text-secondary">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div>
                            <div class="section-title">Additional Options</div>
                            <small class="text-muted">Configure reporting preferences</small>
                        </div>
                    </div>
                    <div class="anonymous-toggle"
                        style="background: #f9fafb; border: 2px solid #e5e7eb; border-radius: 12px; padding: 16px 20px;">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_anonymous" name="is_anonymous"
                                value="1" {{ old('is_anonymous') ? 'checked' : '' }}
                                style="width: 20px; height: 20px; margin-top: 2px;">
                            <label class="form-check-label ms-2" for="is_anonymous">
                                <strong>Report Anonymously</strong>
                                <br><small class="text-muted">Your identity will be hidden from other users</small>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-flex flex-column flex-sm-row gap-2 gap-sm-3 justify-content-sm-end mb-4 mb-sm-5">
                    <a href="{{ route('incidents.index') }}" class="btn btn-light btn-lg px-4 order-2 order-sm-1">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg px-5 id=" submitBtn" order-1 order-sm-2">
                        <i class="fas fa-paper-plane me-2"></i>Submit Incident
                    </button>
                </div>

            </form>
        </div>

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

{{-- Core Scripts --}}
@include('incidents.partials.core_create_script')