@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bullhorn me-2"></i> Publish Notification</h5>
            </div>
            <form method="POST" action="{{ route('nodal.notifications.store') }}">
                @csrf
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                            value="{{ old('title') }}" required maxlength="191">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea name="message" rows="5" class="form-control @error('message') is-invalid @enderror" required
                            maxlength="2000"
                            placeholder="e.g. Good Morning all. Today's DMG inspection will be carried out at domestic SHA area. All concerned department officials are requested to assemble at departure 3C gate by 11AM.">{{ old('message') }}</textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="send_email" id="sendEmail" value="1">
                        <label class="form-check-label" for="sendEmail">Also send via email</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="send_push" id="sendPush" value="1">
                        <label class="form-check-label" for="sendPush">Also send push notification</label>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Send Notification</button>
                </div>
            </form>
        </div>
    </div>
@endsection
