@extends('layouts.app')

@section('title', 'Create Permission')

@section('content')
<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-plus-circle text-primary me-2"></i>Create Permission
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.permissions.index') }}">Permissions</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Create Single Permission -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-plus me-2"></i>Create Single Permission
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.permissions.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Permission Name <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-tag"></i>
                                </span>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                    name="name" value="{{ old('name') }}"
                                    placeholder="e.g., create-incident, view-reports, edit-user" required
                                    pattern="[a-zA-Z0-9-]+">
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Use kebab-case format: action-resource (e.g., create-post, view-dashboard, edit-profile)
                            </div>
                        </div>

                        <!-- Common Permission Patterns -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-lightbulb text-warning me-1"></i>Common Patterns:
                            </label>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    onclick="document.getElementById('name').value = 'create-'">
                                    <i class="fas fa-plus-circle text-success me-1"></i>create-
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    onclick="document.getElementById('name').value = 'view-'">
                                    <i class="fas fa-eye text-info me-1"></i>view-
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    onclick="document.getElementById('name').value = 'edit-'">
                                    <i class="fas fa-edit text-warning me-1"></i>edit-
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    onclick="document.getElementById('name').value = 'delete-'">
                                    <i class="fas fa-trash text-danger me-1"></i>delete-
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    onclick="document.getElementById('name').value = 'manage-'">
                                    <i class="fas fa-cogs text-primary me-1"></i>manage-
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    onclick="document.getElementById('name').value = 'approve-'">
                                    <i class="fas fa-check-circle me-1"></i>approve-
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create Permission
                        </button>
                    </form>
                </div>
            </div>

            <!-- Bulk Create Permissions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-layer-group me-2"></i>Bulk Create Permissions
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.permissions.bulk-create') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="permissions" class="form-label">
                                Permission Names <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('permissions') is-invalid @enderror" id="permissions"
                                name="permissions" rows="8" placeholder="Enter one permission per line:
create-post
view-post
edit-post
delete-post
manage-users
view-dashboard" required>{{ old('permissions') }}</textarea>
                            @error('permissions')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Enter one permission per line. Duplicates will be skipped.
                            </div>
                        </div>

                        <!-- Quick Templates -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-magic text-warning me-1"></i>Quick Templates:
                            </label>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="generateCRUD()">
                                    <i class="fas fa-database me-1"></i>CRUD Template
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm"
                                    onclick="generateWorkflow()">
                                    <i class="fas fa-tasks me-1"></i>Workflow Template
                                </button>
                            </div>
                            <div class="mt-2">
                                <input type="text" id="resourceName" class="form-control form-control-sm"
                                    placeholder="Enter resource name (e.g., post, user, incident)"
                                    style="width: 300px;">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Bulk Create
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Existing Groups Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-folder me-2"></i>Existing Groups
                    </h5>
                </div>
                <div class="card-body">
                    @if($existingGroups->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($existingGroups as $group)
                        <span class="badge bg-secondary">{{ $group }}</span>
                        @endforeach
                    </div>
                    @else
                    <p class="text-muted mb-0">No existing permission groups</p>
                    @endif
                </div>
            </div>

            <!-- Tips Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-lightbulb text-warning me-2"></i>Naming Tips
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Use <strong>verb-noun</strong> format
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Be consistent with naming
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Use lowercase and hyphens
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-times text-danger me-2"></i>
                            Avoid spaces and special chars
                        </li>
                        <li>
                            <i class="fas fa-times text-danger me-2"></i>
                            Don't use camelCase or underscores
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function generateCRUD() {
    const resource = document.getElementById('resourceName').value || 'resource';
    const textarea = document.getElementById('permissions');

    const crudPermissions = [
        `create-${resource}`,
        `view-${resource}`,
        `edit-${resource}`,
        `delete-${resource}`,
        `list-${resource}`,
        `manage-${resource}`
    ];

    textarea.value = crudPermissions.join('\n');
}

function generateWorkflow() {
    const resource = document.getElementById('resourceName').value || 'resource';
    const textarea = document.getElementById('permissions');

    const workflowPermissions = [
        `create-${resource}`,
        `view-${resource}`,
        `edit-${resource}`,
        `submit-${resource}`,
        `approve-${resource}`,
        `reject-${resource}`,
        `review-${resource}`,
        `assign-${resource}`,
        `resolve-${resource}`
    ];

    textarea.value = workflowPermissions.join('\n');
}
</script>
@endpush