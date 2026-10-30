<!-- Attendance Settings -->
<div class="col-md-12 mt-4">
    <h6 class="border-bottom pb-2 mb-3">
        <i class="fas fa-fingerprint me-2"></i> Attendance Settings
    </h6>
</div>

@php
    $att = $airport->settings['attendance'] ?? [];
    $allowSelfMark = old('settings.attendance.allow_self_mark', $att['allow_self_mark'] ?? true);
    $locationCheck = old('settings.attendance.location_check_required', $att['location_check_required'] ?? true);
    $geoLat = old('settings.attendance.geofence_lat', $att['geofence_lat'] ?? $airport->latitude);
    $geoLng = old('settings.attendance.geofence_lng', $att['geofence_lng'] ?? $airport->longitude);
    $geoRadius = old('settings.attendance.geofence_radius_meters', $att['geofence_radius_meters'] ?? 100);
    $startTime = old('settings.attendance.self_mark_start_time', $att['self_mark_start_time'] ?? '11:00');
    $endTime = old('settings.attendance.self_mark_end_time', $att['self_mark_end_time'] ?? '18:00');
@endphp

<div class="col-md-6 mb-3">
    <div class="form-check form-switch">
        <input type="hidden" name="settings[attendance][allow_self_mark]" value="0">
        <input class="form-check-input" type="checkbox" name="settings[attendance][allow_self_mark]" value="1" 
               id="allow_self_mark" {{ $allowSelfMark ? 'checked' : '' }}>
        <label class="form-check-label" for="allow_self_mark">
            Allow User Self-Marking
        </label>
        <div class="form-text">If disabled, only Nodal Officers can mark attendance.</div>
    </div>
</div>

<div class="col-md-6 mb-3">
    <div class="form-check form-switch">
        <input type="hidden" name="settings[attendance][location_check_required]" value="0">
        <input class="form-check-input" type="checkbox" name="settings[attendance][location_check_required]" value="1" 
               id="location_check_required" {{ $locationCheck ? 'checked' : '' }}>
        <label class="form-check-label" for="location_check_required">
            Require Location Verification (Geofence)
        </label>
        <div class="form-text">If enabled, users must be within the geofence to mark attendance.</div>
    </div>
</div>

<div class="col-md-6 mb-3">
    <label class="form-label">Geofence Latitude</label>
    <input type="number" step="0.00000001" class="form-control" name="settings[attendance][geofence_lat]" 
           value="{{ $geoLat }}" placeholder="e.g., 22.64076300">
</div>

<div class="col-md-6 mb-3">
    <label class="form-label">Geofence Longitude</label>
    <input type="number" step="0.00000001" class="form-control" name="settings[attendance][geofence_lng]" 
           value="{{ $geoLng }}" placeholder="e.g., 88.43851300">
</div>

<div class="col-md-4 mb-3">
    <label class="form-label">Geofence Radius (Meters)</label>
    <input type="number" class="form-control" name="settings[attendance][geofence_radius_meters]" 
           value="{{ $geoRadius }}" min="10" max="5000">
</div>

<div class="col-md-4 mb-3">
    <label class="form-label">Self-Mark Start Time</label>
    <input type="time" class="form-control" name="settings[attendance][self_mark_start_time]" 
           value="{{ $startTime }}">
</div>

<div class="col-md-4 mb-3">
    <label class="form-label">Self-Mark End Time</label>
    <input type="time" class="form-control" name="settings[attendance][self_mark_end_time]" 
           value="{{ $endTime }}">
</div>