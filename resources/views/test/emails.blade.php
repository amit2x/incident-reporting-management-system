@extends('layouts.app')

@section('title', 'Email Testing Dashboard')

@push('styles')
<style>
    .email-card {
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .email-card:hover {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }

    .email-card .icon {
        font-size: 2rem;
    }

    .email-card.preview-mode {
        border-color: #0d47a1;
        background: #f0f7ff;
    }

    .email-card .badge-status {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.65rem;
        font-weight: 600;
    }

    .badge-assigned {
        background: #bfdbfe;
        color: #1e40af;
    }

    .badge-escalated {
        background: #fde68a;
        color: #78350f;
    }

    .badge-resolved {
        background: #d1fae5;
        color: #065f46;
    }

    .badge-closed {
        background: #e5e7eb;
        color: #374151;
    }

    .badge-rejected {
        background: #fecaca;
        color: #991b1b;
    }

    .badge-created {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-mention {
        background: #ede9fe;
        color: #5b21b6;
    }

    .badge-comment {
        background: #cffafe;
        color: #0e7490;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="fas fa-envelope text-primary me-2"></i>Email Testing Dashboard</h4>
            <p class="text-muted small">Test all SAMANVAY email notifications</p>
        </div>
        <div>
            <button class="btn btn-success btn-sm" onclick="sendAllEmails()">
                <i class="fas fa-paper-plane me-1"></i> Send All
            </button>
        </div>
    </div>

    {{-- Configuration --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">Recipient Email</label>
                    <input type="email" id="testEmail" class="form-control" value="{{ auth()->user()?->email ?? '' }}"
                        placeholder="Enter email to receive tests">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Format</label>
                    <select id="testFormat" class="form-select">
                        <option value="html">HTML Preview</option>
                        <option value="send">Send Email</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Test User</label>
                    <select id="testUserId" class="form-select">
                        @foreach(\App\Models\User::take(10)->get() as $user)
                        <option value="{{ $user->id }}" {{ $user->id === auth()->id() ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" onclick="refreshEmails()">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Email Cards --}}
    <div class="row g-3" id="emailGrid">
        @php
        $emails = [
        ['type' => 'assigned', 'icon' => 'fa-user-plus', 'label' => 'Incident Assigned', 'color' => '#0d47a1'],
        ['type' => 'escalated', 'icon' => 'fa-arrow-up', 'label' => 'Incident Escalated', 'color' => '#ff8f00'],
        ['type' => 'resolved', 'icon' => 'fa-check-circle', 'label' => 'Incident Resolved', 'color' => '#2e7d32'],
        ['type' => 'closed', 'icon' => 'fa-lock', 'label' => 'Incident Closed', 'color' => '#374151'],
        ['type' => 'rejected', 'icon' => 'fa-times-circle', 'label' => 'Incident Rejected', 'color' => '#dc2626'],
        ['type' => 'created', 'icon' => 'fa-plus-circle', 'label' => 'New Incident', 'color' => '#0d47a1'],
        ['type' => 'mention', 'icon' => 'fa-at', 'label' => 'Mention', 'color' => '#7c3aed'],
        ['type' => 'comment', 'icon' => 'fa-comment', 'label' => 'New Comment', 'color' => '#0891b2'],
        ];
        @endphp

        @foreach($emails as $email)
        <div class="col-md-3 col-6">
            <div class="card email-card" data-type="{{ $email['type'] }}" onclick="testEmail('{{ $email['type'] }}')">
                <div class="card-body text-center">
                    <div class="icon" style="color: {{ $email['color'] }}">
                        <i class="fas {{ $email['icon'] }}"></i>
                    </div>
                    <h6 class="mt-2 mb-1">{{ $email['label'] }}</h6>
                    <span class="badge-status badge-{{ $email['type'] }}">
                        {{ ucfirst($email['type']) }}
                    </span>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-outline-primary"
                            onclick="event.stopPropagation(); testEmail('{{ $email['type'] }}')">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-success"
                            onclick="event.stopPropagation(); sendEmail('{{ $email['type'] }}')">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Email Preview Modal --}}
    <div class="modal fade" id="emailPreviewModal" tabindex="-1" data-bs-size="xl">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="emailPreviewTitle">
                        <i class="fas fa-envelope me-2"></i>Email Preview
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" id="emailPreviewBody">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading email preview...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Notification Test Modal --}}
    <div class="modal fade" id="notificationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-bell me-2"></i>Notification Test</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="notificationResult">
                        <p class="text-muted text-center">Select a notification type to test.</p>
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
    // Initialize with default email
    if ($('#testEmail').val() === '') {
        $('#testEmail').val('{{ auth()->user()?->email ?? "" }}');
    }
});

/**
 * Test a single email.
 */
function testEmail(type) {
    const email = $('#testEmail').val();
    const format = $('#testFormat').val();

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('emailPreviewModal'));
    modal.show();

    // Update title
    document.getElementById('emailPreviewTitle').textContent =
        'Email Preview: ' + type.charAt(0).toUpperCase() + type.slice(1);

    // Show loading
    document.getElementById('emailPreviewBody').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading ${type} email preview...</p>
        </div>
    `;

    // Fetch email preview
    $.ajax({
        url: '{{ route("test.emails.test") }}',
        method: 'GET',
        data: {
            type: type,
            format: format,
            email: email
        },
        success: function(response) {
            if (typeof response === 'string') {
                document.getElementById('emailPreviewBody').innerHTML = response;
            } else if (response.success && response.data) {
                document.getElementById('emailPreviewBody').innerHTML = response.data;
            } else {
                document.getElementById('emailPreviewBody').innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                        <p class="text-danger">${response.message || 'Failed to load email'}</p>
                    </div>
                `;
            }
        },
        error: function() {
            document.getElementById('emailPreviewBody').innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                    <p class="text-danger">Failed to load email preview. Please try again.</p>
                </div>
            `;
        }
    });
}

/**
 * Send a single email.
 */
function sendEmail(type) {
    const email = $('#testEmail').val();

    if (!email) {
        toastr.error('Please enter a recipient email address.');
        return;
    }

    if (!confirm(`Send ${type} email to ${email}?`)) {
        return;
    }

    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    $.ajax({
        url: '{{ route("test.emails.test.post") }}',
        method: 'POST',
        data: {
            type: type,
            format: 'send',
            email: email,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
            } else {
                toastr.error(response.message || 'Failed to send email');
            }
        },
        error: function(xhr) {
            toastr.error('Failed to send email: ' + (xhr.responseJSON?.message || 'Server error'));
        },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
}

/**
 * Send all emails.
 */
function sendAllEmails() {
    const email = $('#testEmail').val();

    if (!email) {
        toastr.error('Please enter a recipient email address.');
        return;
    }

    if (!confirm(`Send all test emails to ${email}? This may take a moment.`)) {
        return;
    }

    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sending...';

    $.ajax({
        url: '{{ route("test.emails.all") }}',
        method: 'POST',
        data: {
            email: email,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                if (response.sent) {
                    toastr.info('Sent: ' + response.sent.join(', '));
                }
                if (response.failed && response.failed.length > 0) {
                    toastr.error('Failed: ' + response.failed.map(f => f.type).join(', '));
                }
            } else {
                toastr.error(response.message || 'Failed to send emails');
            }
        },
        error: function() {
            toastr.error('Failed to send emails');
        },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Send All';
        }
    });
}

/**
 * Test notification.
 */
function testNotification(type) {
    const userId = $('#testUserId').val();

    if (!userId) {
        toastr.warning('Please select a user.');
        return;
    }

    const modal = new bootstrap.Modal(document.getElementById('notificationModal'));
    modal.show();

    document.getElementById('notificationResult').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Sending ${type} notification...</p>
        </div>
    `;

    $.ajax({
        url: '{{ route("test.emails.notification") }}',
        method: 'POST',
        data: {
            type: type,
            user_id: userId,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                document.getElementById('notificationResult').innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="fw-bold">${response.message}</p>
                        <div class="text-start bg-light p-3 rounded mt-3">
                            <pre class="mb-0 small">${JSON.stringify(response.data, null, 2)}</pre>
                        </div>
                    </div>
                `;
                toastr.success(response.message);
            } else {
                document.getElementById('notificationResult').innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                        <p class="text-danger">${response.message || 'Failed to send notification'}</p>
                    </div>
                `;
            }
        },
        error: function() {
            document.getElementById('notificationResult').innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                    <p class="text-danger">Failed to send notification</p>
                </div>
            `;
        }
    });
}

/**
 * Refresh emails.
 */
function refreshEmails() {
    toastr.info('Refreshing...');
    // Simply reload the page
    location.reload();
}
</script>
@endpush
