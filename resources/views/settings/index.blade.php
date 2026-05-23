{{-- resources/views/settings/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Settings - IRMS')

@push('styles')
<style>
    .setting-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
        background: white;
        transition: all 0.2s;
    }
    .setting-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .setting-card .setting-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-3">

    <div class="mb-3">
        <h4 class="fw-bold mb-1">Settings</h4>
        <p class="text-muted small mb-0">Manage your account preferences</p>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Appearance --}}
                <div class="setting-card">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="setting-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-moon"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Dark Mode</h6>
                            <p class="text-muted small mb-0">Toggle between light and dark theme</p>
                        </div>
                        <div class="form-check form-switch">
                            <input type="checkbox" name="preferences[dark_mode]" class="form-check-input" value="1" id="darkMode"
                                   {{ ($user->preferences['dark_mode'] ?? false) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>

                {{-- Notifications --}}
                <div class="setting-card">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="setting-icon bg-info bg-opacity-10 text-info">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Email Notifications</h6>
                            <p class="text-muted small mb-0">Receive incident updates via email</p>
                        </div>
                        <div class="form-check form-switch">
                            <input type="checkbox" name="preferences[email_notifications]" class="form-check-input" value="1" id="emailNotif"
                                   {{ ($user->preferences['email_notifications'] ?? true) ? 'checked' : '' }}>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3">
                        <div class="setting-icon bg-success bg-opacity-10 text-success">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Push Notifications</h6>
                            <p class="text-muted small mb-0">Receive push notifications on your device</p>
                        </div>
                        <div class="form-check form-switch">
                            <input type="checkbox" name="preferences[push_notifications]" class="form-check-input" value="1" id="pushNotif"
                                   {{ ($user->preferences['push_notifications'] ?? true) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>

                {{-- Language --}}
                <div class="setting-card">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="setting-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-language"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Language</h6>
                            <p class="text-muted small mb-2">Select your preferred language</p>
                            <select name="preferences[language]" class="form-select" style="max-width: 200px;">
                                <option value="en" {{ ($user->preferences['language'] ?? 'en') == 'en' ? 'selected' : '' }}>English</option>
                                <option value="hi" {{ ($user->preferences['language'] ?? '') == 'hi' ? 'selected' : '' }}>हिन्दी (Hindi)</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Save --}}
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-1"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>

        {{-- Sidebar Info --}}
        <div class="col-lg-4 d-none d-lg-block">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><strong><i class="fas fa-shield-alt me-2"></i>Account Info</strong></div>
                <div class="card-body small">
                    <div class="mb-2">
                        <strong>Name:</strong> {{ $user->name }}
                    </div>
                    <div class="mb-2">
                        <strong>Email:</strong> {{ $user->email }}
                    </div>
                    <div class="mb-2">
                        <strong>Role:</strong> {{ $user->role_name }}
                    </div>
                    <div class="mb-2">
                        <strong>Department:</strong> {{ $user->department?->name ?? 'N/A' }}
                    </div>
                    <div class="mb-2">
                        <strong>Member Since:</strong> {{ $user->created_at->format('d M Y') }}
                    </div>
                    <div>
                        <strong>Last Login:</strong> {{ $user->last_login_at ? $user->last_login_at->format('d M Y, H:i') : 'N/A' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
