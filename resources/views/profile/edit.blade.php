@extends('layouts.app')

@section('title', 'My Profile - IRMS')

@push('styles')
<style>
    .profile-avatar-wrapper {
        position: relative;
        display: inline-block;
        cursor: pointer;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .avatar-overlay {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s;
        color: white;
        font-size: 1.5rem;
    }

    .profile-avatar-wrapper:hover .avatar-overlay {
        opacity: 1;
    }

    .profile-avatar-wrapper:hover .profile-avatar {
        transform: scale(1.02);
    }

    .avatar-processing {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.6);
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        color: white;
        gap: 8px;
        z-index: 10;
    }

    .avatar-processing.active {
        display: flex;
    }

    .avatar-processing .spinner {
        width: 30px;
        height: 30px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    .avatar-processing .text {
        font-size: 0.7rem;
        opacity: 0.8;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .nav-pills .nav-link {
        border-radius: 8px;
        padding: 10px 16px;
        color: #6b7280;
        font-weight: 500;
        font-size: 0.875rem;
    }

    .nav-pills .nav-link.active {
        background: #eff6ff;
        color: #1a56db;
    }

    .tab-content {
        padding: 20px 0;
    }
</style>
@endpush

@section('content')
<div class="py-3">

    <div class="mb-3">
        <h4 class="fw-bold mb-1">My Profile</h4>
        <p class="text-muted small mb-0">Manage your account information and preferences</p>
    </div>

    <div class="row g-3">
        {{-- Avatar & Info Sidebar --}}
        <div class="col-lg-4">
            <div class="card shadow-sm text-center">
                <div class="card-body p-4">
                    {{-- Avatar Upload Section --}}
                    <div class="mb-3">
                        <form action="{{ route('profile.avatar') }}" method="POST" enctype="multipart/form-data" id="avatarForm">
                            @csrf
                            
                            <div class="profile-avatar-wrapper position-relative d-inline-block" id="avatarWrapper">
                                {{-- Avatar Image --}}
                                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="profile-avatar" id="avatarImage">
                                
                                {{-- Camera Hover Icon --}}
                                <div class="avatar-overlay">
                                    <i class="fas fa-camera"></i>
                                </div>

                                {{-- Processing Overlay --}}
                                <div class="avatar-processing" id="avatarProcessing">
                                    <div class="spinner"></div>
                                    <span class="text">Processing...</span>
                                </div>
                            </div>
                            
                            {{-- Hidden File Input --}}
                            <input type="file" id="avatarInput" name="avatar" accept="image/*" class="d-none">
                            <input type="hidden" name="exif_data" id="exifDataInput" value="">
                        </form>
                        
                        <div class="mt-2">
                            <small class="text-muted d-block">Click on avatar to change</small>
                            <small class="text-muted" style="font-size: 0.65rem;">Supported: JPG, PNG, WebP (Max 5MB)</small>
                        </div>
                    </div>

                    <h5 class="mt-3 mb-1">{{ $user->name }}</h5>
                    <p class="text-muted small mb-2">{{ $user->email }}</p>
                    <span class="badge bg-primary">{{ $user->role_name }}</span>

                    @if($user->department)
                    <div class="mt-2">
                        <span class="badge"
                            style="background: {{ $user->department->color }}20; color: {{ $user->department->color }};">
                            <i class="fas fa-building me-1"></i>{{ $user->department->name }}
                        </span>
                    </div>
                    @endif

                    <hr>

                    <div class="row text-center small">
                        <div class="col-6">
                            <div class="fw-bold">{{ $user->reportedIncidents()->count() }}</div>
                            <div class="text-muted">Reported</div>
                        </div>
                        <div class="col-6">
                            <div class="fw-bold">{{ $user->assignedIncidents()->count() }}</div>
                            <div class="text-muted">Assigned</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Edit Forms --}}
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    {{-- Tabs --}}
                    <ul class="nav nav-pills mb-3" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#profileInfo"
                                type="button">
                                <i class="fas fa-user me-1"></i> Profile Info
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#changePassword"
                                type="button">
                                <i class="fas fa-lock me-1"></i> Change Password
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        {{-- Profile Info Tab --}}
                        <div class="tab-pane fade show active" id="profileInfo">
                            <form action="{{ route('profile.update') }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name"
                                            class="form-control @error('name') is-invalid @enderror"
                                            value="{{ old('name', $user->name) }}" required>
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Username <span class="text-danger">*</span></label>
                                        <input type="text" name="username"
                                            class="form-control @error('username') is-invalid @enderror"
                                            value="{{ old('username', $user->username) }}" required>
                                        @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" name="email"
                                            class="form-control @error('email') is-invalid @enderror"
                                            value="{{ old('email', $user->email) }}" required>
                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone"
                                            class="form-control @error('phone') is-invalid @enderror"
                                            value="{{ old('phone', $user->phone) }}">
                                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Designation</label>
                                        <input type="text" name="designation"
                                            class="form-control @error('designation') is-invalid @enderror"
                                            value="{{ old('designation', $user->designation) }}">
                                        @error('designation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Employee ID</label>
                                        <input type="text" class="form-control" value="{{ $user->employee_id }}"
                                            disabled>
                                        <small class="text-muted">Contact admin to change employee ID</small>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Department</label>
                                        <input type="text" class="form-control"
                                            value="{{ $user->department?->name ?? 'N/A' }}" disabled>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- Change Password Tab --}}
                        <div class="tab-pane fade" id="changePassword">
                            <form action="{{ route('profile.password') }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Current Password <span
                                                class="text-danger">*</span></label>
                                        <input type="password" name="current_password"
                                            class="form-control @error('current_password') is-invalid @enderror"
                                            required>
                                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">New Password <span
                                                class="text-danger">*</span></label>
                                        <input type="password" name="password"
                                            class="form-control @error('password') is-invalid @enderror" required>
                                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirm New Password <span
                                                class="text-danger">*</span></label>
                                        <input type="password" name="password_confirmation" class="form-control"
                                            required>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-key me-1"></i> Change Password
                                    </button>
                                </div>
                            </form>
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
document.addEventListener('DOMContentLoaded', function() {
    // ==========================================
    // PROFILE AVATAR UPLOAD WITH IMAGE UPLOAD HANDLER
    // ==========================================
    
    var avatarWrapper = document.getElementById('avatarWrapper');
    var avatarInput = document.getElementById('avatarInput');
    var avatarImage = document.getElementById('avatarImage');
    var avatarProcessing = document.getElementById('avatarProcessing');
    var avatarForm = document.getElementById('avatarForm');
    var exifDataInput = document.getElementById('exifDataInput');
    var processedFileData = null;
    
    // Initialize the upload handler for avatar
    var avatarUploader = new ImageUploadHandler({
        maxSize: 5 * 1024 * 1024, // 5MB for avatar
        maxDimension: 800,
        webpQuality: 0.85,
        preserveExif: true, // No need for EXIF on avatar , true for testing
        maxFiles: 1, // Only one file
        debug: true,
        onFileProcessed: function(file, metadata, index) {
            // Store the processed file
            processedFileData = file;
            
            // Show preview immediately
            var reader = new FileReader();
            reader.onload = function(e) {
                avatarImage.src = e.target.result;
            };
            reader.readAsDataURL(file);
            
            // Store EXIF data if available
            if (metadata && metadata.exif) {
                exifDataInput.value = JSON.stringify(metadata.exif);
            }
            
            // Update processing status
            avatarProcessing.classList.remove('active');
            
            // IMPORTANT: Replace the file input with the processed file
            var dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            avatarInput.files = dataTransfer.files;
            
            // Submit the form
            avatarForm.submit();
        },
        onError: function(message) {
            avatarProcessing.classList.remove('active');
            alert(message || 'Failed to process image. Please try again.');
        },
        onProgress: function(progress) {
            if (progress.status === 'processing') {
                avatarProcessing.classList.add('active');
            }
        },
    });

    // Click on avatar wrapper triggers file input
    if (avatarWrapper) {
        avatarWrapper.addEventListener('click', function(e) {
            // Don't trigger if processing
            if (avatarProcessing.classList.contains('active')) {
                return;
            }
            avatarInput.click();
        });
    }

    // Handle file selection
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            var files = this.files;
            if (files.length === 0) return;

            // Show processing
            avatarProcessing.classList.add('active');

            // Process the file using the handler
            avatarUploader.processFiles(files).then(function(result) {
                if (!result.success || result.files.length === 0) {
                    avatarProcessing.classList.remove('active');
                    alert('Failed to process image. Please try again.');
                }
                // The onFileProcessed callback will handle form submission
            });
        });
    }

    // ==========================================
    // OPTIONAL: Add drag and drop for avatar
    // ==========================================
    
    if (avatarWrapper) {
        // Add drag and drop support
        avatarWrapper.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#3b82f6';
        });

        avatarWrapper.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
        });

        avatarWrapper.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            
            var files = e.dataTransfer.files;
            if (files.length > 0) {
                // Check if it's an image
                var file = files[0];
                if (!file.type.startsWith('image/')) {
                    alert('Please drop an image file.');
                    return;
                }
                
                // Trigger file input
                var dt = new DataTransfer();
                dt.items.add(file);
                avatarInput.files = dt.files;
                
                // Trigger change event
                var event = new Event('change', { bubbles: true });
                avatarInput.dispatchEvent(event);
            }
        });
    }

});
</script>
@endpush