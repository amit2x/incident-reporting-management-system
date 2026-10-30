@extends('layouts.app')

@section('content')
<div class="container-fluid pb-4">

    {{-- Filters (Unchanged) --}}
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Period</label>
                    <select name="period" class="form-select form-select-sm" onchange="toggleCustom(this)">
                        <option value="month" @selected(request('period', 'month') === 'month')>This Month</option>
                        <option value="quarter" @selected(request('period') === 'quarter')>This Quarter</option>
                        <option value="year" @selected(request('period') === 'year')>This Year</option>
                        <option value="custom" @selected(request('period') === 'custom')>Custom…</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 custom-date" style="display:none">
                    <label class="form-label small text-muted mb-1">From</label>
                    <input type="date" name="date_from" value="{{ request('date_from', $from) }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2 custom-date" style="display:none">
                    <label class="form-label small text-muted mb-1">To</label>
                    <input type="date" name="date_to" value="{{ request('date_to', $to) }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Department</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 mt-2 mt-md-0">
                    <button class="btn btn-primary btn-sm w-100"><i class="fas fa-filter me-1"></i> Apply Filter</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Stats Overview (Unchanged) --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-primary text-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small opacity-75">Total Departments</div>
                            <div class="h3 mb-0 fw-bold">{{ count($deptStats) }}</div>
                        </div>
                        <i class="fas fa-building fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-success text-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small opacity-75">Participated Depts</div>
                            <div class="h3 mb-0 fw-bold">{{ collect($deptStats)->where('present', '>', 0)->count() }}</div>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-danger text-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small opacity-75">Non-Participating</div>
                            <div class="h3 mb-0 fw-bold">{{ collect($deptStats)->where('present', 0)->count() }}</div>
                        </div>
                        <i class="fas fa-times-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-info text-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small opacity-75">Total Users</div>
                            <div class="h3 mb-0 fw-bold">{{ $rows->count() }}</div>
                        </div>
                        <i class="fas fa-users fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Analytics Graphs (Unchanged) --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold border-bottom-0 pt-3 pb-0">
                    <i class="fas fa-chart-pie text-primary me-2"></i> Department Participation
                </div>
                <div class="card-body">
                    <div style="height: 250px; position: relative;">
                        <canvas id="deptParticipationChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold border-bottom-0 pt-3 pb-0">
                    <i class="fas fa-chart-bar text-success me-2"></i> Overall Attendance Breakdown
                </div>
                <div class="card-body">
                    <div style="height: 250px; position: relative;">
                        <canvas id="attendanceBreakdownChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detailed Table --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between align-items-md-center py-3 gap-2">
            <div>
                <h5 class="mb-0 fw-bold"><i class="fas fa-list-alt text-secondary me-2"></i> Detailed Report</h5>
                <span class="text-muted small">{{ $label }} ({{ \Carbon\Carbon::parse($from)->format('d M Y') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }})</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('reports.attendance.export', array_merge(request()->all(), ['format' => 'csv'])) }}"
                    class="btn btn-sm btn-outline-success"><i class="fas fa-file-csv me-1"></i> CSV</a>
                <a href="{{ route('reports.attendance.export', array_merge(request()->all(), ['format' => 'excel'])) }}"
                    class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel me-1"></i> Excel</a>
                <a href="{{ route('reports.attendance.export', array_merge(request()->all(), ['format' => 'pdf'])) }}"
                    class="btn btn-sm btn-outline-danger"><i class="fas fa-file-pdf me-1"></i> PDF</a>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 200px;">Employee / Department</th>
                        <th class="text-center">Total Days</th>
                        <th class="text-center text-success">Present</th>
                        <th class="text-center text-danger">Absent</th>
                        <th class="text-center">Late</th>
                        <th class="text-center">Half Day</th>
                        <th class="text-center">Leave</th>
                        <th class="text-center text-info">Self Marked</th>
                        <th class="text-center text-primary">Nodal Marked</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows->groupBy('user.department_id') as $deptId => $deptUsers)
                        @php
                            $deptTotalPresent = $deptUsers->sum('present');
                            $deptTotalAbsent = $deptUsers->sum('absent');
                            $deptTotalLate = $deptUsers->sum('late');
                            $deptTotalHalfDay = $deptUsers->sum('half_day');
                            $deptTotalLeave = $deptUsers->sum('on_leave');
                            $deptTotalSelf = $deptUsers->sum('self_marked');
                            $deptTotalNodal = $deptUsers->sum('nodal_marked');
                            $deptTotalDays = $deptUsers->sum('total');
                            $isDeptPresent = $deptTotalPresent > 0;
                            
                            $firstUser = $deptUsers->first();
                            $department = $firstUser['user']->department ?? null;
                            $deptName = $department ? $department->name : 'No Department Assigned';
                            $deptParam = $deptId ?? 'unassigned';
                        @endphp
                        
                        {{-- Department Summary Row --}}
                        <tr class="table-primary" style="border-top: 2px solid #dee2e6;">
                            <td class="fw-bold">
                                <a href="javascript:void(0)" class="text-dark text-decoration-none hover-underline" 
                                   onclick="showDetails('department', '{{ $deptParam }}')">
                                    <i class="fas fa-building me-2"></i> {{ $deptName }}
                                    <i class="fas fa-external-link-alt ms-1 small opacity-50"></i>
                                </a>
                                @if($isDeptPresent)
                                    <span class="badge bg-success ms-2"><i class="fas fa-check me-1"></i> Participated</span>
                                @else
                                    <span class="badge bg-danger ms-2"><i class="fas fa-times me-1"></i> Absent</span>
                                @endif
                            </td>
                            <td class="text-center fw-bold">{{ $deptTotalDays }}</td>
                            <td class="text-center fw-bold text-success">{{ $deptTotalPresent }}</td>
                            <td class="text-center fw-bold text-danger">{{ $deptTotalAbsent }}</td>
                            <td class="text-center fw-bold">{{ $deptTotalLate }}</td>
                            <td class="text-center fw-bold">{{ $deptTotalHalfDay }}</td>
                            <td class="text-center fw-bold">{{ $deptTotalLeave }}</td>
                            <td class="text-center fw-bold text-info">{{ $deptTotalSelf }}</td>
                            <td class="text-center fw-bold text-primary">{{ $deptTotalNodal }}</td>
                        </tr>

                        {{-- Individual Employees --}}
                        @foreach($deptUsers as $r)
                            <tr>
                                <td class="ps-4">
                                    <a href="javascript:void(0)" class="text-dark text-decoration-none hover-underline"
                                       onclick="showDetails('user', '{{ $r['user']->id }}')">
                                        <i class="fas fa-user text-muted me-2 small"></i>
                                        {{ $r['user']->name }} 
                                        <span class="text-muted small">({{ $r['user']->designation }})</span>
                                        <i class="fas fa-external-link-alt ms-1 small opacity-50"></i>
                                    </a>
                                </td>
                                <td class="text-center">{{ $r['total'] }}</td>
                                <td class="text-center text-success">{{ $r['present'] }}</td>
                                <td class="text-center text-danger">{{ $r['absent'] }}</td>
                                <td class="text-center">{{ $r['late'] }}</td>
                                <td class="text-center">{{ $r['half_day'] }}</td>
                                <td class="text-center">{{ $r['on_leave'] }}</td>
                                <td class="text-center text-info">{{ $r['self_marked'] }}</td>
                                <td class="text-center text-primary">{{ $r['nodal_marked'] }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted p-5">
                                <i class="fas fa-folder-open fa-3x mb-3 text-light"></i>
                                <h5>No attendance records found for this period.</h5>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Details Modal --}}
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-0">
                <div>
                    <h5 class="modal-title fw-bold" id="modalTitle">Details</h5>
                    <div class="text-muted small" id="modalPeriod"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="modalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">Loading details...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-underline:hover { text-decoration: underline !important; color: #0d6efd !important; }
</style>
@endsection

@push('scripts')

<script>
    function toggleCustom(sel) {
        document.querySelectorAll('.custom-date').forEach(el => {
            el.style.display = sel.value === 'custom' ? 'block' : 'none';
        });
    }
    toggleCustom(document.querySelector('select[name=period]'));

        function showDetails(type, id) {
        const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
        const titleEl = document.getElementById('modalTitle');
        const periodEl = document.getElementById('modalPeriod');
        const bodyEl = document.getElementById('modalBody');

        titleEl.textContent = 'Loading...';
        periodEl.textContent = '';
        bodyEl.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Loading details...</div></div>';
        
        modal.show();

        // Build URL safely as a string
        let urlString = '{{ route("reports.attendance.details") }}';
        
        // Create a standard URL object
        const urlObj = new URL(urlString, window.location.origin);
        
        urlObj.searchParams.append('type', type);
        urlObj.searchParams.append('id', id);
        
        const period = document.querySelector('select[name=period]').value;
        urlObj.searchParams.append('period', period);
        
        if(period === 'custom') {
            urlObj.searchParams.append('date_from', document.querySelector('input[name=date_from]').value);
            urlObj.searchParams.append('date_to', document.querySelector('input[name=date_to]').value);
        }

        // Convert to string before passing to fetch()
        const finalUrl = urlObj.toString();

        fetch(finalUrl, { 
            headers: { 
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            } 
        })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.error || 'Failed to fetch details');
                }
                return data;
            })
            .then(data => {
                titleEl.textContent = data.title;
                periodEl.textContent = data.period;

                if (!data.records || data.records.length === 0) {
                    bodyEl.innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-folder-open fa-3x mb-3 text-light"></i><h5>No records found for this period.</h5></div>';
                    return;
                }

                let html = '<div class="table-responsive"><table class="table table-hover align-middle mb-0">';
                html += '<thead class="table-light"><tr><th>Date</th>';
                if (type === 'department') html += '<th>Employee</th>';
                html += '<th>Status</th><th>Marked By</th><th>Time</th><th>Remarks</th></tr></thead><tbody>';

                data.records.forEach(rec => {
                    html += `<tr>
                        <td class="text-nowrap fw-semibold">${rec.date}</td>`;
                    if (type === 'department') html += `<td>${rec.employee}</td>`;
                    html += `<td><span class="badge bg-${rec.status_badge}">${rec.status}</span></td>
                        <td class="small">${rec.marked_by}</td>
                        <td class="small text-nowrap">${rec.time}</td>
                        <td class="small text-muted">${rec.remarks}</td>
                    </tr>`;
                });

                html += '</tbody></table></div>';
                bodyEl.innerHTML = html;
            })
            .catch(err => {
                bodyEl.innerHTML = `<div class="text-center py-5 text-danger">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h5>Error</h5>
                    <p class="mb-0">${err.message}</p>
                </div>`;
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // --- Chart 1: Department Participation ---
        const deptCtx = document.getElementById('deptParticipationChart').getContext('2d');
        new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: ['Participated', 'Did Not Participate'],
                datasets: [{
                    data: [{{ collect($deptStats)->where('present', '>', 0)->count() }}, {{ collect($deptStats)->where('present', 0)->count() }}],
                    backgroundColor: ['#10B981', '#EF4444'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // --- Chart 2: Overall Attendance Breakdown ---
        const attendanceCtx = document.getElementById('attendanceBreakdownChart').getContext('2d');
        new Chart(attendanceCtx, {
            type: 'bar',
            data: {
                labels: ['Present', 'Absent', 'Late', 'Half Day', 'Leave'],
                datasets: [{
                    label: 'Total Records',
                    data: [
                        {{ $rows->sum('present') }}, 
                        {{ $rows->sum('absent') }}, 
                        {{ $rows->sum('late') }}, 
                        {{ $rows->sum('half_day') }}, 
                        {{ $rows->sum('on_leave') }}
                    ],
                    backgroundColor: ['#10B981', '#EF4444', '#F59E0B', '#3B82F6', '#6B7280'],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    });
</script>
@endpush