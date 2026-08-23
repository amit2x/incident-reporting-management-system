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

            <!-- Same fields as create form but with values filled from $airport -->
            <!-- ... (use same form fields with value="{{ old('field', $airport->field) }}") -->

            <!-- Include all form groups from create view with old values populated -->

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