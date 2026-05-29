{{-- resources/views/profile/edit.blade.php --}}
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
                    <form action="{{ route('profile.avatar') }}" method="POST" enctype="multipart/form-data"
                        id="avatarForm">
                        @csrf
                        <label class="profile-avatar-wrapper" for="avatarInput">
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="profile-avatar">
                            <div class="avatar-overlay">
                                <i class="fas fa-camera"></i>
                            </div>
                        </label>
                        <input type="file" id="avatarInput" name="avatar" accept="image/*" class="d-none"
                            onchange="document.getElementById('avatarForm').submit();">
                    </form>

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