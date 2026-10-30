@extends('layouts.app')

@section('content')
   <div class="containers mt-4">
    <div class="card shadow-sm border-0">
        <!-- Top Visual Indicator Panel -->
        <div class="card-header bg-light border-bottom-0 pt-4 pb-2 text-center">
            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                <i class="fas fa-fingerprint fs-2"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-1 text-info">Mark My Attendance</h3>
            <p class="text-muted small mb-0">
                <i class="far fa-calendar-alt me-1"></i> {{ now($settings['timezone'])->format('l, d M Y') }}
            </p>
            </div>
        </div>

        <!-- Interactive Context Area -->
        <div class="card-body text-center pt-2 pb-5 px-4">
            <span class="badge bg-secondary bg-opacity-10 text-secondary mb-4 py-2 px-3 fs-10 fw-bold">
                <i class="far fa-clock me-1"></i> Window: <strong>{{ $settings['self_mark_start_time'] }}</strong> to <strong>{{ $settings['self_mark_end_time'] }}</strong>
            </span>

            <div class="my-2">
                @if ($todayAttendance)
                    <div class="alert alert-{{ $todayAttendance->status_badge }} d-inline-flex align-items-center justify-content-center px-4 py-3 border-0 shadow-sm rounded-3">
                        <i class="fas fa-check-circle me-2 fs-5"></i>
                        <div>
                            <span class="fw-bold">{{ ucfirst(str_replace('_', ' ', $todayAttendance->status)) }}</span>
                            <span class="mx-1">&middot;</span> 
                            <small class="text-dark opacity-75">Marked by {{ $todayAttendance->marked_by_label }} at {{ $todayAttendance->marked_at->format('h:i A') }}</small>
                        </div>
                    </div>
                @else
                    <button id="btnMark" class="btn btn-primary btn-lg px-5 py-3 fw-bold shadow-sm rounded-pill transition-all">
                        <i class="fas fa-map-marker-alt me-2 animate-bounce"></i> Mark Attendance Now
                    </button>
                    <div id="statusMsg" class="mt-3"></div>
                @endif
            </div>
        </div>
        
        <!-- Bottom Action Router -->
        <div class="card-footer bg-transparent border-top p-0">
            <a href="{{ route('attendance.my') }}" class="btn btn-light w-100 py-3 text-primary fw-semibold rounded-0 rounded-bottom d-flex align-items-center justify-content-center gap-2 border-0">
                <i class="fas fa-history"></i> View My Attendance History
            </a>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script>
        document.getElementById('btnMark')?.addEventListener('click', async () => {
            const btn = document.getElementById('btnMark');
            const msg = document.getElementById('statusMsg');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Fetching location…';

            if (!navigator.geolocation) {
                msg.innerHTML = '<div class="alert alert-danger">Geolocation not supported.</div>';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-map-marker-alt me-2"></i> Retry';
                return;
            }

            navigator.geolocation.getCurrentPosition(async (pos) => {
                btn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2"></span> Marking…';

                try {
                    const res = await fetch('{{ route('attendance.mark-self') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            latitude: pos.coords.latitude,
                            longitude: pos.coords.longitude,
                        }),
                    });

                    const data = await res.json();

                    if (res.ok) {
                        msg.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                        btn.remove();
                    } else {
                        const errs = data.errors ?
                            Object.values(data.errors).flat().join('<br>') :
                            (data.message || 'Failed to mark attendance.');
                        msg.innerHTML = `<div class="alert alert-danger">${errs}</div>`;
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-map-marker-alt me-2"></i> Try Again';
                    }
                } catch (e) {
                    msg.innerHTML =
                        `<div class="alert alert-danger">Network error. Try again.</div>`;
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-map-marker-alt me-2"></i> Try Again';
                }
            }, (err) => {
                msg.innerHTML = `<div class="alert alert-danger">
            Location access is required: ${err.message}
        </div>`;
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-map-marker-alt me-2"></i> Try Again';
            }, {
                enableHighAccuracy: true,
                timeout: 15000
            });
        });
    </script>
@endpush
