@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <h3 class="mb-2"><i class="fas fa-fingerprint text-primary"></i> Mark My Attendance</h3>
                <p class="text-muted mb-4">
                    {{ now($settings['timezone'])->format('l, d M Y') }}
                    &middot; Window: {{ $settings['self_mark_start_time'] }}–{{ $settings['self_mark_end_time'] }}
                </p>

                @if ($todayAttendance)
                    <div class="alert alert-{{ $todayAttendance->status_badge }} d-inline-block px-4">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>{{ ucfirst(str_replace('_', ' ', $todayAttendance->status)) }}</strong>
                        — marked by {{ $todayAttendance->marked_by_label }}
                        at {{ $todayAttendance->marked_at->format('h:i A') }}
                    </div>
                @else
                    <button id="btnMark" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-map-marker-alt me-2"></i> Mark Attendance Now
                    </button>
                    <div id="statusMsg" class="mt-3"></div>
                @endif
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
