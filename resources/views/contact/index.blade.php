{{-- resources/views/contact/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Contact Us - IRMSystem')

@push('styles')
<style>
    .contact-container {
        min-height: calc(100vh - var(--topbar-height));
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: linear-gradient(135deg, #f0f5ff 0%, #e8f0fe 50%, #f5f3ff 100%);
    }

    .contact-card {
        width: 100%;
        max-width: 600px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .contact-header {
        background: linear-gradient(135deg, #1a56db 0%, #1e40af 100%);
        padding: 28px 24px;
        text-align: center;
        color: white;
    }

    .contact-header h4 {
        color: white;
        margin-bottom: 4px;
        font-weight: 700;
    }

    .contact-header p {
        color: rgba(255, 255, 255, 0.85);
        font-size: 0.8125rem;
        margin: 0;
    }

    .contact-body {
        padding: 28px 24px;
    }

    .category-selector {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 8px;
    }

    .category-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 8px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 0.8125rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        background: white;
        color: #6b7280;
    }

    .category-chip:hover {
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .category-chip.selected {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #1a56db;
        font-weight: 600;
    }

    .category-chip i {
        font-size: 1rem;
    }

    .btn-submit {
        width: 100%;
        height: 48px;
        background: linear-gradient(135deg, #1a56db 0%, #1e40af 100%);
        border: none;
        border-radius: 12px;
        color: white;
        font-weight: 600;
        font-size: 0.9375rem;
        transition: all 0.3s;
        box-shadow: 0 4px 16px rgba(26, 86, 219, 0.3);
    }

    .btn-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 24px rgba(26, 86, 219, 0.4);
    }

    .btn-submit:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }

    @media (max-width: 480px) {
        .contact-card {
            border-radius: 16px;
        }

        .contact-body {
            padding: 20px 16px;
        }

        .contact-header {
            padding: 24px 16px;
        }
    }
</style>
@endpush

@section('content')
<div class="contact-container">
    <div class="contact-card">
        <div class="contact-header">
            <h4><i class="fas fa-headset me-2"></i>Contact Us</h4>
            <p>Need help? Send us a message and we'll get back to you.</p>
        </div>
        <div class="contact-body">
            <form id="contactForm" action="{{ route('contact.submit') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Name --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Your Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', auth()->user()->name ?? '') }}" required
                        placeholder="Enter your full name">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Email --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', auth()->user()->email ?? '') }}" required placeholder="Enter your email">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Category --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                    <div class="category-selector" id="categorySelector">
                        <span class="category-chip {{ old('category') == 'account' ? 'selected' : '' }}"
                            data-category="account">
                            <i class="fas fa-user-circle"></i> Account
                        </span>
                        <span class="category-chip {{ old('category') == 'incident' ? 'selected' : '' }}"
                            data-category="incident">
                            <i class="fas fa-exclamation-triangle"></i> Incident
                        </span>
                        <span class="category-chip {{ old('category') == 'technical' ? 'selected' : '' }}"
                            data-category="technical">
                            <i class="fas fa-laptop-code"></i> Technical
                        </span>
                        <span class="category-chip {{ old('category') == 'access' ? 'selected' : '' }}"
                            data-category="access">
                            <i class="fas fa-lock"></i> Access
                        </span>
                        <span class="category-chip {{ old('category', 'other') == 'other' ? 'selected' : '' }}"
                            data-category="other">
                            <i class="fas fa-ellipsis-h"></i> Other
                        </span>
                    </div>
                    <input type="hidden" name="category" id="categoryInput" value="{{ old('category', 'other') }}">
                    @error('category')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

                {{-- Subject --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
                    <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                        value="{{ old('subject') }}" required placeholder="Brief subject of your message">
                    @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Message --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                    <textarea name="message" class="form-control @error('message') is-invalid @enderror" rows="5"
                        required
                        placeholder="Describe your issue or question in detail...">{{ old('message') }}</textarea>
                    @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Attachment --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold">Attachment (Optional)</label>
                    <input type="file" name="attachment" class="form-control @error('attachment') is-invalid @enderror"
                        accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                    <small class="text-muted">Max 10MB. Supported: Images, PDF, DOC, XLS, TXT</small>
                    @error('attachment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn btn-submit" id="submitBtn">
                    <i class="fas fa-paper-plane me-2"></i> Send Message
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

    // Category selector
    const categorySelector = document.getElementById('categorySelector');
    const categoryInput = document.getElementById('categoryInput');

    if (categorySelector && categoryInput) {
        categorySelector.addEventListener('click', function(e) {
            const chip = e.target.closest('.category-chip');
            if (!chip) return;

            // Remove selected from all
            categorySelector.querySelectorAll('.category-chip').forEach(c => c.classList.remove('selected'));
            // Add selected to clicked
            chip.classList.add('selected');
            // Update hidden input
            categoryInput.value = chip.dataset.category;
        });
    }

    // Form submission loading state
    const form = document.getElementById('contactForm');
    const btn = document.getElementById('submitBtn');

    if (form && btn) {
        form.addEventListener('submit', function() {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sending...';
        });
    }

});
</script>
@endpush