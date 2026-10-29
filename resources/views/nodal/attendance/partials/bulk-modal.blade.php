<div class="modal fade" id="bulkModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" action="{{ route('nodal.attendance.bulk') }}">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">

            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-users me-2"></i> Bulk Mark Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-5">
                        <label class="form-label small">Department</label>
                        <select id="bulkDept" class="form-select">
                            <option value="">— Select Department —</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="present" selected>Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                            <option value="half_day">Half Day</option>
                            <option value="on_leave">On Leave</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Remarks (optional)</label>
                        <input type="text" name="remarks" class="form-control">
                    </div>
                </div>

                <div id="bulkEmployees" class="border rounded p-2" style="max-height:400px; overflow:auto;">
                    <div class="text-muted text-center py-3 small">
                        Select a department to load employees.
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <span class="me-auto small text-muted">
                    <span id="selectedCount">0</span> selected
                </span>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="bulkSubmit" disabled>
                    <i class="fas fa-check-double me-1"></i> Save Attendance
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        (function() {
            const deptEl = document.getElementById('bulkDept');
            const listEl = document.getElementById('bulkEmployees');
            const countEl = document.getElementById('selectedCount');
            const submitEl = document.getElementById('bulkSubmit');
            const date = '{{ $date }}';

            function refreshCount() {
                const n = listEl.querySelectorAll('input[name="user_ids[]"]:checked').length;
                countEl.textContent = n;
                submitEl.disabled = n === 0;
            }

            deptEl.addEventListener('change', async () => {
                const deptId = deptEl.value;
                if (!deptId) {
                    listEl.innerHTML =
                        '<div class="text-muted text-center py-3 small">Select a department to load employees.</div>';
                    refreshCount();
                    return;
                }

                listEl.innerHTML =
                    '<div class="text-center py-3"><span class="spinner-border spinner-border-sm"></span> Loading…</div>';

                try {
                    const res = await fetch(
                        `{{ url('nodal/api/departments') }}/${deptId}/employees?date=${date}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                    const data = await res.json();

                    if (!data.employees.length) {
                        listEl.innerHTML =
                            '<div class="text-muted text-center py-3 small">No active employees in this department.</div>';
                        return;
                    }

                    listEl.innerHTML = data.employees.map(e => {
                        const badge = e.status ?
                            `<span class="badge bg-${e.marked_by_role === 'self' ? 'info' : 'primary'} ms-2">
                         ${e.status}${e.marked_by_role === 'self' ? ' (self)' : ''}
                       </span>` :
                            '<span class="badge bg-light text-muted ms-2">unmarked</span>';
                        return `
                    <label class="d-flex align-items-center p-2 border-bottom">
                        <input type="checkbox" name="user_ids[]" value="${e.id}" class="form-check-input me-2">
                        <span class="flex-grow-1">
                            <strong>${e.name}</strong>
                            <span class="text-muted small">${e.employee_id ?? ''} · ${e.designation ?? ''}</span>
                            ${badge}
                        </span>
                    </label>`;
                    }).join('');

                    listEl.querySelectorAll('input[type=checkbox]').forEach(cb =>
                        cb.addEventListener('change', refreshCount)
                    );
                    refreshCount();
                } catch (e) {
                    listEl.innerHTML =
                        '<div class="text-danger text-center py-3 small">Failed to load employees.</div>';
                }
            });

            document.getElementById('bulkModal').addEventListener('shown.bs.modal', refreshCount);
        })();
    </script>
@endpush
