{{-- resources/views/notifications/broadcast.blade.php --}}

@extends('layouts.app')

@section('content')
    <div class="container">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><i class="fas fa-bullhorn me-2"></i> Publish Notification</h4>
            <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>

        <div class="alert alert-info d-flex align-items-center">
            <i class="fas fa-users me-2 fa-lg"></i>
            This message will be sent to <strong class="mx-1">{{ $recipientCount }}</strong> active user(s) in your
            airport.
        </div>

        <div class="card">
            <form method="POST" action="{{ route('notifications.broadcast.send') }}">
                @csrf
                <div class="card-body">

                    <div class="mb-3">
                        <label for="title" class="form-label">
                            Title <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="title" name="title"
                            class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}"
                            placeholder="e.g. DMG Inspection – Domestic SHA Area" required maxlength="191">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label">
                            Message <span class="text-danger">*</span>
                        </label>
                        <textarea id="message" name="message" rows="6" class="form-control @error('message') is-invalid @enderror"
                            placeholder="Good Morning all. Today's DMG inspection will be carried out at domestic SHA area. All concerned department officials are requested to assemble at departure 3C gate by 11 AM."
                            required maxlength="2000">{{ old('message') }}</textarea>
                        <div class="form-text">
                            <span id="charCount">0</span> / 2000 characters
                        </div>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr>

                    <div class="mb-2">
                        <strong>Delivery Channels</strong>
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="ch_database" checked disabled>
                        <label class="form-check-label" for="ch_database">
                            <i class="fas fa-bell me-1"></i> In-App (always on)
                        </label>
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="send_push" name="send_push" value="1"
                            @checked(old('send_push', true))>
                        <label class="form-check-label" for="send_push">
                            <i class="fas fa-mobile-alt me-1"></i> Push Notification (FCM)
                        </label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="send_email" name="send_email" value="1"
                            @checked(old('send_email'))>
                        <label class="form-check-label" for="send_email">
                            <i class="fas fa-envelope me-1"></i> Email
                        </label>
                    </div>

                </div>
                <div class="card-footer d-flex justify-content-end">
                    <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i> Send Notification
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const textarea = document.getElementById('message');
        const counter = document.getElementById('charCount');

        function updateCount() {
            counter.textContent = textarea.value.length;
        }
        textarea.addEventListener('input', updateCount);
        updateCount();
    </script>
@endpush
