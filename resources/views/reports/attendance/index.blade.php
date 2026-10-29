@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small">Period</label>
                        <select name="period" class="form-select" onchange="toggleCustom(this)">
                            <option value="month" @selected(request('period', 'month') === 'month')>This Month</option>
                            <option value="quarter" @selected(request('period') === 'quarter')>This Quarter</option>
                            <option value="year" @selected(request('period') === 'year')>This Year</option>
                            <option value="custom" @selected(request('period') === 'custom')>Custom…</option>
                        </select>
                    </div>
                    <div class="col-md-2 custom-date" style="display:none">
                        <label class="form-label small">From</label>
                        <input type="date" name="date_from" value="{{ request('date_from', $from) }}"
                            class="form-control">
                    </div>
                    <div class="col-md-2 custom-date" style="display:none">
                        <label class="form-label small">To</label>
                        <input type="date" name="date_to" value="{{ request('date_to', $to) }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">All</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Apply</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span><strong>{{ $label }}</strong> ({{ Carbon\Carbon::parse($from)->format('d M Y') }} –
                    {{ Carbon\Carbon::parse($to)->format('d M Y') }})</span>
                <div>
                    <a href="{{ route('reports.attendance.export', array_merge(request()->all(), ['format' => 'csv'])) }}"
                        class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i> CSV</a>
                    <a href="{{ route('reports.attendance.export', array_merge(request()->all(), ['format' => 'excel'])) }}"
                        class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel me-1"></i> Excel</a>
                    <a href="{{ route('reports.attendance.export', array_merge(request()->all(), ['format' => 'pdf'])) }}"
                        class="btn btn-sm btn-outline-danger"><i class="fas fa-file-pdf me-1"></i> PDF</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th class="text-center">Total</th>
                            <th class="text-center text-success">Present</th>
                            <th class="text-center text-danger">Absent</th>
                            <th class="text-center">Late</th>
                            <th class="text-center">Half Day</th>
                            <th class="text-center">Leave</th>
                            <th class="text-center text-info">Self</th>
                            <th class="text-center text-primary">Nodal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $r)
                            <tr>
                                <td>{{ $r['user']->name }} <span
                                        class="text-muted small">{{ $r['user']->employee_id }}</span></td>
                                <td class="small">{{ $r['user']->department?->name }}</td>
                                <td class="text-center">{{ $r['total'] }}</td>
                                <td class="text-center text-success fw-bold">{{ $r['present'] }}</td>
                                <td class="text-center text-danger">{{ $r['absent'] }}</td>
                                <td class="text-center">{{ $r['late'] }}</td>
                                <td class="text-center">{{ $r['half_day'] }}</td>
                                <td class="text-center">{{ $r['on_leave'] }}</td>
                                <td class="text-center text-info">{{ $r['self_marked'] }}</td>
                                <td class="text-center text-primary">{{ $r['nodal_marked'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted p-4">No attendance records.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleCustom(sel) {
            document.querySelectorAll('.custom-date').forEach(el => {
                el.style.display = sel.value === 'custom' ? 'block' : 'none';
            });
        }
        toggleCustom(document.querySelector('select[name=period]'));
    </script>
@endpush
