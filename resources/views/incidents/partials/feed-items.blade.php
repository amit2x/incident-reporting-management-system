@forelse($incidents as $incident)
    <x-incident-card :incident="$incident" />
@empty
    <div class="text-center py-5">
        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
        <h6 class="text-muted">No incidents found</h6>
        <p class="text-muted small">Incidents will appear here once reported</p>
        <a href="{{ route('incidents.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Report Incident
        </a>
    </div>
@endforelse

@if($incidents->hasMorePages())
    <div class="text-center py-3" id="loadMoreContainer">
        <button class="btn btn-light" onclick="loadMoreIncidents(this)" data-page="1">
            <i class="fas fa-spinner fa-spin me-2 d-none" id="loadMoreSpinner"></i>
            Load More
        </button>
    </div>
@endif