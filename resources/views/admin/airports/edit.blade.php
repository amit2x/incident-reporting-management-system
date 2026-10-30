{{-- resources/views/admin/airports/edit.blade.php --}}

@extends('layouts.app')

@section('title', 'Edit Airport')
@section('page-title', 'Edit Airport')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-edit me-2"></i> Edit Airport: {{ $airport->full_name }}
        </h5>
    </div>

    <div class="card-body">
        <form action="{{ route('admin.airports.update', $airport) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <!-- Basic Information -->
                <div class="col-md-12">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-info-circle me-2"></i> Basic Information
                    </h6>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="code" class="form-label">Airport Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code"
                        value="{{ old('code', $airport->code) }}" placeholder="e.g., CCU" required>
                    @error('code')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Unique identifier (e.g., CCU, DEL, BOM)</div>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="iata_code" class="form-label">IATA Code</label>
                    <input type="text" class="form-control @error('iata_code') is-invalid @enderror" id="iata_code"
                        name="iata_code" value="{{ old('iata_code', $airport->iata_code) }}" placeholder="e.g., CCU">
                    @error('iata_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">3-letter IATA airport code</div>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="icao_code" class="form-label">ICAO Code</label>
                    <input type="text" class="form-control @error('icao_code') is-invalid @enderror" id="icao_code"
                        name="icao_code" value="{{ old('icao_code', $airport->icao_code) }}" placeholder="e.g., VECC">
                    @error('icao_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">4-letter ICAO airport code</div>
                </div>

                <div class="col-md-12 mb-3">
                    <label for="name" class="form-label">Airport Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                        value="{{ old('name', $airport->name) }}" placeholder="Full airport name" required>
                    @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12 mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                        name="description" rows="3">{{ old('description', $airport->description) }}</textarea>
                    @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Location Information -->
                <div class="col-md-12 mt-3">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-map-marker-alt me-2"></i> Location Information
                    </h6>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control @error('address') is-invalid @enderror" id="address"
                        name="address" value="{{ old('address', $airport->address) }}" placeholder="Street address">
                    @error('address')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city"
                        value="{{ old('city', $airport->city) }}" placeholder="City" required>
                    @error('city')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="state" class="form-label">State/Province</label>
                    <input type="text" class="form-control @error('state') is-invalid @enderror" id="state" name="state"
                        value="{{ old('state', $airport->state) }}" placeholder="State/Province">
                    @error('state')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="country" class="form-label">Country <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('country') is-invalid @enderror" id="country"
                        name="country" value="{{ old('country', $airport->country ?? 'India') }}" placeholder="Country" required>
                    @error('country')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="timezone" class="form-label">Timezone <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('timezone') is-invalid @enderror" id="timezone"
                        name="timezone" value="{{ old('timezone', $airport->timezone ?? 'Asia/Kolkata') }}" placeholder="e.g., Asia/Kolkata">
                    @error('timezone')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Use standard timezone names (e.g., Asia/Kolkata, America/New_York)</div>
                </div>

                <!-- Coordinates -->
                <div class="col-md-6 mb-3">
                    <label for="latitude" class="form-label">Latitude</label>
                    <input type="number" step="0.00000001" class="form-control @error('latitude') is-invalid @enderror"
                        id="latitude" name="latitude" value="{{ old('latitude', $airport->latitude) }}" placeholder="e.g., 22.6547">
                    @error('latitude')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="longitude" class="form-label">Longitude</label>
                    <input type="number" step="0.00000001" class="form-control @error('longitude') is-invalid @enderror"
                        id="longitude" name="longitude" value="{{ old('longitude', $airport->longitude) }}" placeholder="e.g., 88.4467">
                    @error('longitude')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Contact Information -->
                <div class="col-md-12 mt-3">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-phone me-2"></i> Contact Information
                    </h6>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="contact_email" class="form-label">Contact Email</label>
                    <input type="email" class="form-control @error('contact_email') is-invalid @enderror"
                        id="contact_email" name="contact_email" value="{{ old('contact_email', $airport->contact_email) }}"
                        placeholder="email@airport.com">
                    @error('contact_email')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="contact_phone" class="form-label">Contact Phone</label>
                    <input type="text" class="form-control @error('contact_phone') is-invalid @enderror"
                        id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $airport->contact_phone) }}"
                        placeholder="e.g., +91-33-12345678">
                    @error('contact_phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12 mb-3">
                    <label for="website" class="form-label">Website</label>
                    <input type="url" class="form-control @error('website') is-invalid @enderror" id="website"
                        name="website" value="{{ old('website', $airport->website) }}" placeholder="https://www.airport.com">
                    @error('website')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Settings -->
                <div class="col-md-12 mt-3">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-cog me-2"></i> Settings
                    </h6>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="primary_color" class="form-label">Primary Color</label>
                    <div class="input-group">
                        <span class="input-group-text" id="color-preview"
                            style="background-color: {{ old('primary_color', $airport->primary_color ?? '#3B82F6') }}; width: 40px;"></span>
                        <input type="color"
                            class="form-control form-control-color @error('primary_color') is-invalid @enderror"
                            id="primary_color" name="primary_color" value="{{ old('primary_color', $airport->primary_color ?? '#3B82F6') }}">
                    </div>
                    @error('primary_color')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order"
                        name="sort_order" value="{{ old('sort_order', $airport->sort_order ?? 0) }}" min="0">
                    @error('sort_order')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Lower numbers appear first</div>
                </div>

                <div class="col-md-12 mb-3">
                    <div class="form-check form-switch">
                        {{-- Hidden input ensures 0 is submitted if unchecked --}}
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                            {{ old('is_active', $airport->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            <i class="fas fa-check-circle text-success me-1"></i> Active
                        </label>
                        <div class="form-text">Active airports can be used by users and system</div>
                    </div>
                </div>
            </div>

            @include('admin.airports.partials.attendance-settings', ['airport' => $airport])
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i> Update Airport
                </button>
                <a href="{{ route('admin.airports.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection