{{-- resources/views/incidents/partials/modals.blade.php --}}

{{-- ========================================== --}}
{{-- ASSIGN MODAL --}}
{{-- ========================================== --}}
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="assignForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>{{ $incident->assignedTo ? 'Reassign' :
                        'Assign' }} Incident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ $incident->assignedTo ? 'Reassign To' : 'Assign To' }} <span
                                class="text-danger">*</span></label>
                        <select name="assigned_to" class="form-select select2" required>
                            <option value="">{{ $incident->assignedTo ? 'Select New User' : 'Select User' }}</option>
                            @php
                            $availableUsers = \App\Models\User::where('department_id', $incident->department_id)
                            ->active()
                            ->when($incident->assignedTo, function($query) use ($incident) {
                            return $query->where('id', '!=', $incident->assigned_to);
                            })
                            ->get();
                            @endphp
                            @foreach($availableUsers as $user)
                            <option value="{{ $user->id }}">
                                {{ $user->name }} ({{ $user->getFirstRoleName() }})
                            </option>
                            @endforeach
                        </select>
                        @if($incident->assignedTo)
                        <small class="text-muted">Currently assigned to: <strong>{{ $incident->assignedTo->name
                                }}</strong></small>
                        @endif
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i> {{ $incident->assignedTo ? 'Reassign' : 'Assign' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- REJECT MODAL --}}
{{-- ========================================== --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="rejectForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-times-circle text-danger me-2"></i>Reject Incident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-0">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3"
                            placeholder="Why is this incident being rejected?" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times-circle me-1"></i>
                        Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- ESCALATE MODAL --}}
{{-- ========================================== --}}
<div class="modal fade" id="escalateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="escalateForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-arrow-up text-warning me-2"></i>Escalate Incident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Target Department <span class="text-danger">*</span></label>
                        <select name="to_department_id" id="escalateDeptSelect" class="form-select select2" required>
                            <option value="">Select Department First</option>
                            @foreach(\App\Models\Department::active()->ordered()->get() as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->code }})</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Select department to see available users</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Escalate To <span class="text-danger">*</span></label>
                        <select name="escalated_to" id="escalateUserSelect" class="form-select select2" required>
                            <option value="">Select Department First</option>
                        </select>
                        <small class="text-muted" id="escalateUserCount"></small>
                        @if($incident->escalations && $incident->escalations->count() > 0)
                        <div class="mt-1">
                            <small class="text-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Already escalated to:
                                @foreach($incident->escalations->unique('escalated_to') as $esc)
                                <span class="badge bg-warning text-dark">{{ $esc->escalatedTo?->name ?? 'N/A' }}</span>
                                @endforeach
                            </small>
                        </div>
                        @endif
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3"
                            placeholder="Why are you escalating this incident?" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-arrow-up me-1"></i> Escalate</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- RESOLVE MODAL --}}
{{-- ========================================== --}}
<div class="modal fade" id="resolveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="resolveForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-check-circle text-success me-2"></i>Resolve Incident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Resolution Notes <span class="text-danger">*</span></label>
                        <textarea name="resolution_notes" class="form-control" rows="4"
                            placeholder="Describe how the incident was resolved..." required></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Attach Files (Optional)</label>
                        <input type="file" name="files[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"
                            class="form-control">
                        <small class="text-muted">Attach resolution evidence, photos, or documents</small>
                        <div id="resolveFilePreview" class="d-flex flex-wrap gap-2 mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i> Resolve</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- CLOSE MODAL --}}
{{-- ========================================== --}}
<div class="modal fade" id="closeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="closeForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-lock text-dark me-2"></i>Close Incident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Closing Remarks <span class="text-danger">*</span></label>
                        <textarea name="closing_remarks" class="form-control" rows="3"
                            placeholder="Add final remarks before closing this incident..." required></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Attach Files (Optional)</label>
                        <input type="file" name="files[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"
                            class="form-control">
                        <small class="text-muted">Attach final evidence or closing documents</small>
                        <div id="closeFilePreview" class="d-flex flex-wrap gap-2 mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark"><i class="fas fa-lock me-1"></i> Close Incident</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- ACCEPT ESCALATION MODAL --}}
{{-- ========================================== --}}
@if($incident->status === 'escalated' && Auth::id() === $incident->escalated_to)
<div class="modal fade" id="acceptEscalationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="acceptEscalationForm">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Accept Escalation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">You are accepting responsibility for this incident. It will be assigned
                        to you.</p>
                    <div class="mb-0">
                        <label class="form-label">Note (Optional)</label>
                        <textarea name="response_note" class="form-control" rows="2"
                            placeholder="Add a note..."></textarea>
                    </div>
                    <input type="hidden" name="response_type" value="accept">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i> Accept</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- RETURN ESCALATION MODAL --}}
{{-- ========================================== --}}
<div class="modal fade" id="returnEscalationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="returnEscalationForm">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-undo me-2"></i>Return Escalation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">This will return the incident to the previous escalation level or
                        original assignee.</p>
                    <div class="mb-0">
                        <label class="form-label">Reason for Returning</label>
                        <textarea name="response_note" class="form-control" rows="3"
                            placeholder="Why are you returning this?"></textarea>
                    </div>
                    <input type="hidden" name="response_type" value="return">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-undo me-1"></i> Return</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- REJECT ESCALATION MODAL --}}
{{-- ========================================== --}}
<div class="modal fade" id="rejectEscalationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="rejectEscalationForm">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-times-circle me-2"></i>Reject Escalation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Reject this escalation. The incident will go back to open status.</p>
                    <div class="mb-0">
                        <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="response_note" class="form-control" rows="3"
                            placeholder="Why are you rejecting this escalation?" required></textarea>
                    </div>
                    <input type="hidden" name="response_type" value="reject">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times-circle me-1"></i>
                        Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif