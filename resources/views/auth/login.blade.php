@extends('layouts.app')

@section('title', 'Login - IRMSystem')

@push('styles')
<style>
    .login-container {
        min-height: calc(90vh - var(--topbar-height));
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
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

    /* CAPTCHA Styles */
    .captcha-section {
        margin-bottom: 20px;
    }

    .captcha-row {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .captcha-image-wrapper {
        flex-shrink: 0;
        position: relative;
        cursor: pointer;
        border-radius: 10px;
        overflow: hidden;
        border: 2px solid #e5e7eb;
        transition: all 0.2s;
    }

    .captcha-image-wrapper:hover {
        border-color: #1a56db;
        box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.08);
    }

    .captcha-image-wrapper img {
        display: block;
        height: 46px;
    }

    .captcha-refresh-hint {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.75rem;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .captcha-image-wrapper:hover .captcha-refresh-hint {
        opacity: 1;
    }

    .captcha-input-group {
        flex: 1;
        position: relative;
    }

    .captcha-input-group .form-control {
        height: 46px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px 14px 10px 40px;
        font-size: 0.875rem;
        transition: all 0.2s ease;
    }

    .captcha-input-group .form-control:focus {
        border-color: #1a56db;
        box-shadow: 0 0 0 4px rgba(26, 86, 219, 0.08);
    }

    .captcha-input-group .captcha-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 0.875rem;
    }

    .captcha-loading {
        opacity: 0.5;
        pointer-events: none;
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

    .btn-login:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
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

    /* Alert styles */
    .alert-captcha {
        background: #fef3c7;
        border: 1px solid #fcd34d;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 0.75rem;
        color: #92400e;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .alert-captcha i {
        font-size: 1rem;
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
        .captcha-row {
            flex-direction: column;
            align-items: stretch;
        }
        .captcha-image-wrapper {
            align-self: center;
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
                <img src="{{ asset('images/logo.png') }}" alt="IRMSystem Logo"
                     onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'fas fa-shield-halved\' style=\'font-size: 28px; color: #1a56db;\'></i>';">
            </div>
            <h4>Welcome Back</h4>
            <p>Sign in to your IRMSystem account</p>
        </div>

        {{-- Form --}}
        <div class="login-body">
            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf

                {{-- CAPTCHA Notice --}}
                @if($showCaptcha ?? false)
                    <div class="alert-captcha">
                        <i class="fas fa-shield-halved"></i>
                        <span>Additional verification required due to multiple login attempts.</span>
                    </div>
                @endif

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

                {{-- CAPTCHA (Conditionally shown) --}}
                <div class="captcha-section" id="captchaSection" style="{{ ($showCaptcha ?? false) ? '' : 'display: none;' }}">
                    <label class="form-label small fw-semibold mb-2">Security Verification</label>
                    <div class="captcha-row" id="captchaRow">
                        <div class="captcha-image-wrapper" onclick="refreshCaptcha()" title="Click to refresh">
                            <img src="{{ captcha_src('math') }}" alt="CAPTCHA" id="captchaImage">
                            <div class="captcha-refresh-hint">
                                <i class="fas fa-redo me-1"></i> Refresh
                            </div>
                        </div>
                        <div class="captcha-input-group">
                            <i class="fas fa-puzzle-piece captcha-icon"></i>
                            <input type="text"
                                   name="captcha"
                                   class="form-control @error('captcha') is-invalid @enderror"
                                   placeholder="Enter the answer"
                                   id="captchaInput"
                                   autocomplete="off">
                        </div>
                    </div>
                    @error('captcha')
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
                <button type="submit" class="btn btn-login" id="loginBtn">
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ==========================================
    // CAPTCHA REFRESH
    // ==========================================
    window.refreshCaptcha = function() {
        var img = document.getElementById('captchaImage');
        var row = document.getElementById('captchaRow');
        var input = document.getElementById('captchaInput');

        if (!img || !row) return;

        // Add loading state
        row.classList.add('captcha-loading');

        fetch('{{ route("captcha.refresh") }}', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                img.src = data.captcha_url + '?t=' + new Date().getTime();
                if (input) {
                    input.value = '';
                    input.focus();
                }
            }
        })
        .catch(function(error) {
            // Fallback: reload with timestamp
            var currentSrc = img.src.split('?')[0];
            img.src = currentSrc + '?t=' + new Date().getTime();
        })
        .finally(function() {
            row.classList.remove('captcha-loading');
        });
    };

    // ==========================================
    // FORM SUBMISSION - Loading State
    // ==========================================
    var form = document.getElementById('loginForm');
    var btn = document.getElementById('loginBtn');

    if (form && btn) {
        form.addEventListener('submit', function() {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Signing in...';
        });
    }

});
</script>
@endpush
