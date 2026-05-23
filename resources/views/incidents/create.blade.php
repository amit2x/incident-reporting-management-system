@extends('layouts.app')

@section('title', 'Report New Incident - IRMS')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('incidents.index') }}">Incidents</a></li>
    <li class="breadcrumb-item active">Report New</li>
@endsection

@section('content')
<div class="container-fluid p-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2 text-primary"></i>
                        Report New Incident
                    </h5>
                </div>
                <div class="card-body">
                    <form id="incidentForm" action="{{ route('incidents.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        {{-- Incident ID Preview --}}
                        <div class="alert alert-info d-flex align-items-center" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <div>
                                Incident ID will be auto-generated: <strong>INC-{{ date('Y') }}-XXXX</strong>
                            </div>
                        </div>

                        <div class="row g-3">
                            {{-- Title --}}
                            <div class="col-12">
                                <label for="title" class="form-label required">Incident Title</label>
                                <input type="text" class="form-control form-control-lg" id="title" name="title"
                                       placeholder="Brief title describing the incident" required maxlength="255"
                                       value="{{ old('title') }}">
                                <div class="invalid-feedback"></div>
                            </div>

                            {{-- Category --}}
                            <div class="col-md-6">
                                <label for="category_id" class="form-label required">Category</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}"
                                                data-icon="{{ $category->icon }}"
                                                data-color="{{ $category->color }}"
                                                data-priority="{{ $category->default_priority }}"
                                                {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                            @if($category->sla_minutes)
                                                (SLA: {{ $category->sla_minutes }} min)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Department --}}
                            <div class="col-md-6">
                                <label for="department_id" class="form-label required">Department</label>
                                <select class="form-select" id="department_id" name="department_id" required>
                                    <option value="">Select Department</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}"
                                                {{ old('department_id') == $department->id ? 'selected' : '' }}
                                                {{ Auth::user()->department_id == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }} ({{ $department->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Severity --}}
                            <div class="col-md-4">
                                <label for="severity" class="form-label required">Severity</label>
                                <select class="form-select" id="severity" name="severity" required>
                                    <option value="">Select Severity</option>
                                    <option value="low" {{ old('severity') == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('severity') == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('severity') == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="critical" {{ old('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
                                </select>
                            </div>

                            {{-- Priority --}}
                            <div class="col-md-4">
                                <label for="priority" class="form-label required">Priority</label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="">Select Priority</option>
                                    <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="critical" {{ old('priority') == 'critical' ? 'selected' : '' }}>Critical</option>
                                </select>
                            </div>

                            {{-- Location --}}
                            <div class="col-md-4">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location"
                                       placeholder="e.g., Terminal 1, Gate A" value="{{ old('location') }}"
                                       list="locationSuggestions">
                                <datalist id="locationSuggestions">
                                    <option value="Terminal 1 - Gate A">
                                    <option value="Terminal 2 - Security Check">
                                    <option value="Parking Area - Level 1">
                                    <option value="Administrative Building">
                                </datalist>
                            </div>

                            {{-- Description --}}
                            <div class="col-12">
                                <label for="description" class="form-label required">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="6"
                                          placeholder="Provide detailed description of the incident..."
                                          required maxlength="5000">{{ old('description') }}</textarea>
                                <div class="form-text">
                                    <span id="charCount">0</span>/5000 characters
                                </div>
                            </div>

                            {{-- Tags --}}
                            <div class="col-12">
                                <label for="tags" class="form-label">Tags</label>
                                <input type="text" class="form-control" id="tags" name="tags[]"
                                       placeholder="Type tag and press Enter" data-role="tagsinput">
                            </div>

                            {{-- File Uploads --}}
                            <div class="col-12">
                                <label class="form-label">Attachments</label>
                                <div class="file-upload-area" id="fileUploadArea">
                                    <div class="upload-placeholder">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                        <p class="mb-2">Drag & drop files here or click to browse</p>
                                        <small class="text-muted">
                                            Supported: Images (JPG, PNG, GIF), Videos (MP4, AVI), Documents (PDF, DOC, XLS)
                                            Max size: 20MB per file
                                        </small>
                                    </div>
                                    <input type="file" class="file-input" id="files" name="files[]"
                                           multiple accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx"
                                           style="display: none;">
                                </div>
                                <div class="file-preview-container mt-3" id="filePreview"></div>
                            </div>

                            {{-- Anonymous Reporting --}}
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_anonymous" name="is_anonymous" value="1"
                                           {{ old('is_anonymous') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_anonymous">
                                        Report Anonymously
                                        <small class="text-muted d-block">Your identity will be hidden from other users</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Submit Buttons --}}
                        <div class="mt-4 d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-light" onclick="saveDraft()">
                                <i class="far fa-save me-2"></i>Save as Draft
                            </button>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Submit Incident
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Quick Tips Sidebar --}}
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 80px;">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        Reporting Tips
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Be Specific:</strong> Include exact location and time
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Add Photos:</strong> Visual evidence helps faster resolution
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Right Category:</strong> Select accurate category for proper routing
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Clear Title:</strong> Use descriptive titles for quick identification
                        </li>
                    </ul>

                    <hr>

                    <div class="sla-info">
                        <h6>SLA Information:</h6>
                        <div id="slaInfo" class="small text-muted">
                            Select a category to view SLA details
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Character counter
        $('#description').on('input', function() {
            $('#charCount').text($(this).val().length);
        });

        // File upload handling
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('files');
        const filePreview = document.getElementById('filePreview');

        fileUploadArea.addEventListener('click', () => fileInput.click());
        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });
        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.classList.remove('dragover');
        });
        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });

        fileInput.addEventListener('change', (e) => handleFiles(e.target.files));

        function handleFiles(files) {
            filePreview.innerHTML = '';
            Array.from(files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = createPreviewElement(file, e.target.result, index);
                    filePreview.appendChild(preview);
                };
                reader.readAsDataURL(file);
            });
        }

        function createPreviewElement(file, dataUrl, index) {
            const div = document.createElement('div');
            div.className = 'file-preview-item';
            div.innerHTML = `
                ${file.type.startsWith('image/') ?
                    `<img src="${dataUrl}" alt="Preview">` :
                    `<i class="fas fa-file fa-3x text-muted"></i>`}
                <div class="file-info">
                    <small class="d-block text-truncate" style="max-width: 120px;">${file.name}</small>
                    <small class="text-muted">${formatFileSize(file.size)}</small>
                </div>
                <button type="button" class="btn btn-sm btn-danger remove-file" data-index="${index}">
                    <i class="fas fa-times"></i>
                </button>
            `;
            return div;
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Category change handler for SLA info
        $('#category_id').on('change', function() {
            const selected = $(this).find('option:selected');
            const slaMinutes = selected.text().match(/SLA: (\d+) min/);
            if (slaMinutes) {
                $('#slaInfo').html(`
                    <strong>SLA: ${slaMinutes[1]} minutes</strong><br>
                    Expected response within ${slaMinutes[1]} minutes
                `);
            }
        });

        // Form submission with AJAX
        $('#incidentForm').on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = $(this).find('button[type="submit"]');

            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Submitting...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(() => {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    }
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>Submit Incident');

                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(key => {
                            toastr.error(errors[key][0]);
                        });
                    } else {
                        toastr.error('Failed to submit incident. Please try again.');
                    }
                }
            });
        });
    });

    function saveDraft() {
        const formData = new FormData(document.getElementById('incidentForm'));
        formData.append('is_draft', '1');

        // Save to localStorage as backup
        const draftData = {};
        formData.forEach((value, key) => {
            if (key !== 'files[]') {
                draftData[key] = value;
            }
        });
        localStorage.setItem('incidentDraft', JSON.stringify(draftData));

        toastr.info('Draft saved locally');
    }
</script>
@endpush
