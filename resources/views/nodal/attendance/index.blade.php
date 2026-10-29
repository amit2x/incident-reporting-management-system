@extends('layouts.app')

@section('content')
    <div class="container-fluid">

        {{-- Filters --}}
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">Date</label>
                        <input type="date" name="date" value="{{ $date }}" max="{{ now()->toDateString() }}"
                            min="{{ now()->subDays(7)->toDateString() }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">All Departments</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>
                                    {{ $d->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Search</label>
                        <input type="text" name="search" value="{{ $search }}"
                            placeholder="Name, employee ID, email" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filter</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Stats --}}
        <div class="row g-2 mb-3">
            <div class="col">
                <div class="card text-center p-2">
                    <div class="text-muted small">Total</div>
                    <div class="h4 mb-0">{{ $stats['total'] }}</div>
                </div>
            </div>
            <div class="col">
                <div class="card text-center p-2 border-success">
                    <div class="text-muted small">Present</div>
                    <div class="h4 mb-0 text-success">{{ $stats['present'] }}</div>
                </div>
            </div>
            <div class="col">
                <div class="card text-center p-2 border-danger">
                    <div class="text-muted small">Absent</div>
                    <div class="h4 mb-0 text-danger">{{ $stats['absent'] }}</div>
                </div>
            </div>
            <div class="col">
                <div class="card text-center p-2 border-warning">
                    <div class="text-muted small">Unmarked</div>
                    <div class="h4 mb-0 text-warning">{{ $stats['unmarked'] }}</div>
                </div>
            </div>
            <div class="col">
                <div class="card text-center p-2 border-info">
                    <div class="text-muted small">Self-marked</div>
                    <div class="h4 mb-0 text-info">{{ $stats['self'] }}</div>
                </div>
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="mb-2">
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bulkModal">
                <i class="fas fa-users me-1"></i> Bulk Mark
            </button>
            <a href="{{ route('nodal.attendance.review', ['date' => $date]) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-history me-1"></i> Review & Override
            </a>
        </div>

        {{-- Employee table --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Marked By</th>
                            <th>Time</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            @php $att = $u->todayAttendance ?? null; @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $u->name }}</div>
                                    <div class="text-muted small">{{ $u->employee_id ?? '—' }}</div>
                                </td>
                                <td class="small">{{ $u->department?->name ?? '—' }}</td>
                                <td>
                                    @if ($att)
                                        <span class="badge bg-{{ $att->status_badge }}">
                                            {{ ucfirst(str_replace('_', ' ', $att->status)) }}
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted">Unmarked</span>
                                    @endif
                                </td>
                                <td class="small">
                                    @if ($att)
                                        {{ $att->marked_by_label }}
                                        @if ($att->is_modified)
                                            <i class="fas fa-pen text-warning ms-1" title="Modified"></i>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="small">{{ $att?->marked_at?->format('h:i A') ?? '—' }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('nodal.attendance.single') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $u->id }}">
                                        <input type="hidden" name="date" value="{{ $date }}">
                                        <input type="hidden" name="status" value="present">
                                        <button class="btn btn-sm btn-success" title="Mark Present">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('nodal.attendance.single') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $u->id }}">
                                        <input type="hidden" name="date" value="{{ $date }}">
                                        <input type="hidden" name="status" value="absent">
                                        <button class="btn btn-sm btn-outline-danger" title="Mark Absent">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted p-4">No employees found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $users->links() }}</div>
        </div>
    </div>

    {{-- Bulk modal --}}
    @include('nodal.attendance.partials.bulk-modal', ['departments' => $departments, 'date' => $date])
@endsection
