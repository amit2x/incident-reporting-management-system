{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.app')

@section('title', 'Login - IRMSystem')

@push('styles')
<style>
    .login-container {
        min-height: calc(90vh - var(--topbar-height));
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
        background: linear-gradient(135deg, #f0f5ff 0%, #e8f0fe 50%, #f5f3ff 100%);
    }

    .login-card {
        width: 100%;
        max-width: 440px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        animation: fadeInUp 0.5s ease;
    }

    .login-header {
        background: linear-gradient(135deg, #1a56db 0%, #1e40af 100%);
        padding: 32px 24px;
        text-align: center;
        color: white;
    }

    .login-logo {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        background: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }

    .login-logo img {
        width: 40px;
        height: 40px;
        object-fit: contain;
    }

    .login-header h4 {
        color: white;
        margin-bottom: 4px;
        font-weight: 700;
    }

    .login-header p {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.8125rem;
        margin: 0;
    }

    .login-body {
        padding: 32px 28px;
    }

    .form-floating-custom {
        position: relative;
        margin-bottom: 20px;
    }

    .form-floating-custom .form-control {
        height: 52px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px 16px 12px 44px;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        background: #fafbfc;
    }

    .form-floating-custom .form-control:focus {
        border-color: #1a56db;
        background: white;
        box-shadow: 0 0 0 4px rgba(26, 86, 219, 0.08);
    }

    .form-floating-custom .form-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 1rem;
        transition: color 0.2s;
        pointer-events: none;
    }

    .form-floating-custom .form-control:focus ~ .form-icon {
        color: #1a56db;
    }

    .form-floating-custom .form-label-floating {
        position: absolute;
        left: 44px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 0.875rem;
        pointer-events: none;
        transition: all 0.2s ease;
        background: transparent;
        padding: 0 4px;
    }

    .form-floating-custom .form-control:focus ~ .form-label-floating,
    .form-floating-custom .form-control:not(:placeholder-shown) ~ .form-label-floating {
        top: 0;
        left: 40px;
        font-size: 0.6875rem;
        color: #1a56db;
        background: white;
        font-weight: 600;
    }

    .btn-login {
        width: 100%;
        height: 48px;
        background: linear-gradient(135deg, #1a56db 0%, #1e40af 100%);
        border: none;
        border-radius: 12px;
        color: white;
        font-weight: 600;
        font-size: 0.9375rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 16px rgba(26, 86, 219, 0.3);
    }

    .btn-login:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 24px rgba(26, 86, 219, 0.4);
    }

    .btn-login:active {
        transform: translateY(0);
    }

    .divider {
        display: flex;
        align-items: center;
        text-align: center;
        margin: 20px 0;
        color: #9ca3af;
        font-size: 0.75rem;
    }

    .divider::before,
    .divider::after {
        content: '';
        flex: 1;
        border-bottom: 1px solid #e5e7eb;
    }

    .divider span {
        padding: 0 12px;
    }

    .register-link {
        text-align: center;
        font-size: 0.8125rem;
        color: #6b7280;
    }

    .register-link a {
        color: #1a56db;
        font-weight: 600;
        text-decoration: none;
    }

    .register-link a:hover {
        text-decoration: underline;
    }

    .forgot-link {
        text-align: right;
        margin-bottom: 16px;
    }

    .forgot-link a {
        font-size: 0.75rem;
        color: #6b7280;
        text-decoration: none;
        transition: color 0.2s;
    }

    .forgot-link a:hover {
        color: #1a56db;
    }

    .remember-check {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.8125rem;
        color: #6b7280;
        margin-bottom: 20px;
    }

    .remember-check input[type="checkbox"] {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        border: 2px solid #d1d5db;
        cursor: pointer;
        accent-color: #1a56db;
    }

    @media (max-width: 480px) {
        .login-card {
            border-radius: 16px;
        }
        .login-body {
            padding: 24px 20px;
        }
        .login-header {
            padding: 24px 20px;
        }
    }
</style>
@endpush

@section('content')
<div class="login-container">
    <div class="login-card">
        {{-- Header --}}
        <div class="login-header">
            <div class="login-logo">
                <img src="{{ asset('images/logo.png') }}" alt="IRMSystem Logo" onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'fas fa-shield-halved\' style=\'font-size: 28px; color: #1a56db;\'></i>';">
            </div>
            <h4>Welcome Back</h4>
            <p>Sign in to your IRMSystem account</p>
        </div>

        {{-- Form --}}
        <div class="login-body">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Email --}}
                <div class="form-floating-custom">
                    <i class="fas fa-envelope form-icon"></i>
                    <input id="email"
                           type="email"
                           class="form-control @error('email') is-invalid @enderror"
                           name="email"
                           value="{{ old('email') }}"
                           placeholder=" "
                           required
                           autocomplete="email"
                           autofocus>
                    <label class="form-label-floating" for="email">Email Address</label>
                    @error('email')
                        <small class="text-danger" style="font-size: 0.6875rem;">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="form-floating-custom">
                    <i class="fas fa-lock form-icon"></i>
                    <input id="password"
                           type="password"
                           class="form-control @error('password') is-invalid @enderror"
                           name="password"
                           placeholder=" "
                           required
                           autocomplete="current-password">
                    <label class="form-label-floating" for="password">Password</label>
                    @error('password')
                        <small class="text-danger" style="font-size: 0.6875rem;">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Remember & Forgot --}}
                <div class="d-flex justify-content-between align-items-center">
                    <div class="remember-check">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label for="remember">Remember me</label>
                    </div>
                    @if (Route::has('password.request'))
                        <div class="forgot-link">
                            <a href="{{ route('password.request') }}">Forgot password?</a>
                        </div>
                    @endif
                </div>

                {{-- Login Button --}}
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i> Sign In
                </button>

                {{-- Divider --}}
                <div class="divider">
                    <span>or</span>
                </div>

                {{-- Register Link --}}
                <div class="register-link">
                    Don't have an account?
                    <a href="{{ route('register') }}">Create one now</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
