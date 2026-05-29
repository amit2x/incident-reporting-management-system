{{-- resources/views/onboarding/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Department Onboarding - IRMSystem')

@push('styles')
<style>
    .onboarding-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Step Indicator */
    .step-indicator {
        display: flex;
        justify-content: space-between;
        margin-bottom: 40px;
        position: relative;
        padding: 0 10px;
    }

    .step-indicator::before {
        content: '';
        position: absolute;
        top: 17px;
        left: 8%;
        right: 8%;
        height: 2px;
        background: #e5e7eb;
        z-index: 0;
    }

    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 1;
        cursor: pointer;
    }

    .step-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #e5e7eb;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.875rem;
        margin-bottom: 8px;
        transition: all 0.3s;
        border: 2px solid transparent;
    }

    .step-circle.active {
        background: #1a56db;
        color: white;
        border-color: #1a56db;
        box-shadow: 0 0 0 4px rgba(26, 86, 219, 0.15);
    }

    .step-circle.completed {
        background: #10b981;
        color: white;
        border-color: #10b981;
    }

    .step-circle.has-error {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
    }

    .step-label {
        font-size: 0.6875rem;
        color: #6b7280;
        font-weight: 500;
        text-align: center;
        max-width: 80px;
    }

    .step-label.active {
        color: #1a56db;
        font-weight: 600;
    }

    /* Form Sections */
    .form-section-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 28px;
        margin-bottom: 24px;
        display: none;
    }

    .form-section-card.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .section-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 12px;
    }

    .section-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 4px;
        color: #1f2937;
    }

    .section-subtitle {
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 24px;
        line-height: 1.5;
    }

    /* User Entry Cards */
    .user-entry {
        background: #f9fafb;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
        position: relative;
        transition: all 0.2s;
    }

    .user-entry:hover {
        border-color: #d1d5db;
    }

    .user-entry.required {
        border-left: 3px solid #1a56db;
    }

    .user-entry .user-role-badge {
        position: absolute;
        top: -10px;
        left: 16px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .role-hod {
        background: #1a56db;
        color: white;
    }

    .role-supervisor {
        background: #f59e0b;
        color: #1f2937;
    }

    .role-staff {
        background: #10b981;
        color: white;
    }

    .remove-user {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #fee2e2;
        color: #ef4444;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 0.875rem;
        border: none;
        transition: all 0.2s;
    }

    .remove-user:hover {
        background: #ef4444;
        color: white;
    }

    /* Category Cards */
    .category-select-card {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
        height: 100%;
    }

    .category-select-card:hover {
        border-color: #3b82f6;
        background: #f0f5ff;
    }

    .category-select-card.selected {
        border-color: #1a56db;
        background: #eff6ff;
        box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.1);
    }

    .category-select-card .cat-icon {
        font-size: 1.5rem;
        margin-bottom: 8px;
        display: block;
    }

    .category-select-card .cat-name {
        font-weight: 600;
        font-size: 0.8125rem;
        margin-bottom: 2px;
    }

    .category-select-card .cat-sla {
        font-size: 0.6875rem;
        color: #6b7280;
    }

    /* Navigation Buttons */
    .nav-buttons {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    }

    /* Validation */
    .is-invalid {
        border-color: #ef4444 !important;
    }

    .invalid-feedback {
        display: block;
    }

    /* Info Box */
    .info-highlight {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        font-size: 0.875rem;
        color: #1e40af;
    }

    .info-highlight i {
        font-size: 1.25rem;
    }
</style>
@endpush

@section('content')
<div class="onboarding-container">

    {{-- Header --}}
    <div class="text-center mb-4">
        <h3 class="fw-bold mb-2">🚀 Department Onboarding</h3>
        <p class="text-muted">Complete all 5 steps to onboard your department to IRMSystem</p>
    </div>

    {{-- Step Indicator --}}
    <div class="step-indicator">
        <div class="step-item" onclick="goToStep(1)">
            <div class="step-circle active" id="stepCircle1">1</div>
            <div class="step-label active" id="stepLabel1">Department<br>Details</div>
        </div>
        <div class="step-item" onclick="goToStep(2)">
            <div class="step-circle" id="stepCircle2">2</div>
            <div class="step-label" id="stepLabel2">Team<br>Members</div>
        </div>
        <div class="step-item" onclick="goToStep(3)">
            <div class="step-circle" id="stepCircle3">3</div>
            <div class="step-label" id="stepLabel3">Categories<br>& SLA</div>
        </div>
        <div class="step-item" onclick="goToStep(4)">
            <div class="step-circle" id="stepCircle4">4</div>
            <div class="step-label" id="stepLabel4">Escalation<br>Matrix</div>
        </div>
        <div class="step-item" onclick="goToStep(5)">
            <div class="step-circle" id="stepCircle5">5</div>
            <div class="step-label" id="stepLabel5">Review &<br>Submit</div>
        </div>
    </div>

    <form action="{{ route('onboarding.submit') }}" method="POST" id="onboardingForm" novalidate>
        @csrf
        <input type="hidden" name="custom_categories" id="customCategoriesInput">

        {{-- ========================================== --}}
        {{-- STEP 1: DEPARTMENT DETAILS --}}
        {{-- ========================================== --}}
        <div class="form-section-card active" id="step1">
            <div class="d-flex align-items-start gap-3 mb-4">
                <div class="section-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-building"></i>
                </div>
                <div>
                    <div class="section-title">Department Information</div>
                    <div class="section-subtitle">
                        Provide your department's basic details. This information will be used to identify your
                        department in the system.
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label fw-semibold">
                        Department Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="department_name"
                        class="form-control @error('department_name') is-invalid @enderror"
                        value="{{ old('department_name') }}" required
                        placeholder="e.g., Engineering Civil, Electrical Maintenance, Housekeeping"
                        data-error="Please enter department name">
                    @error('department_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Full official name of your department</small>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">
                        Department Code <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="department_code"
                        class="form-control @error('department_code') is-invalid @enderror"
                        value="{{ old('department_code') }}" required placeholder="e.g., CIVIL, ELEC, HK" maxlength="10"
                        style="text-transform:uppercase"
                        data-error="Please enter a unique department code (max 10 chars)">
                    @error('department_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Short code for your department (max 10 characters)</small>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="department_description" class="form-control" rows="3"
                        placeholder="Briefly describe what your department does, its responsibilities, and the types of work handled...">{{ old('department_description') }}</textarea>
                    <small class="text-muted">Help others understand what your department is responsible for</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Department Email</label>
                    <input type="email" name="department_email"
                        class="form-control @error('department_email') is-invalid @enderror"
                        value="{{ old('department_email') }}" placeholder="dept@yourcompany.com">
                    @error('department_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Official email for department communications</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Contact Number</label>
                    <input type="text" name="department_phone"
                        class="form-control @error('department_phone') is-invalid @enderror"
                        value="{{ old('department_phone') }}" placeholder="+91-XXXXXXXXXX">
                    @error('department_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Department Color</label>
                    <input type="color" name="department_color" class="form-control form-control-color"
                        value="{{ old('department_color', '#3B82F6') }}" style="height:42px;width:100%;">
                    <small class="text-muted">Choose a color to represent your department</small>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Location / Office Address</label>
                    <input type="text" name="department_location"
                        class="form-control @error('department_location') is-invalid @enderror"
                        value="{{ old('department_location') }}" placeholder="e.g., Building A, 2nd Floor, Terminal 1">
                    @error('department_location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="nav-buttons">
                <div></div>
                <button type="button" class="btn btn-primary px-4" onclick="goToStep(2)">
                    Next: Team Members <i class="fas fa-arrow-right ms-1"></i>
                </button>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- STEP 2: TEAM MEMBERS --}}
        {{-- ========================================== --}}
        <div class="form-section-card" id="step2">
            <div class="d-flex align-items-start gap-3 mb-4">
                <div class="section-icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <div class="section-title">Team Members</div>
                    <div class="section-subtitle">
                        Add the people in your department who will use IRMSystem. Each person needs a unique email and
                        employee ID.
                    </div>
                </div>
            </div>

            <div class="info-highlight">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Important:</strong> All users will receive a default password <code>Welcome@123</code> which
                they should change on first login. Make sure email addresses are correct.
            </div>

            <div id="usersContainer">
                {{-- HOD --}}
                <div class="user-entry required">
                    <span class="user-role-badge role-hod">Department Head (HOD)</span>
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Full Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="users[0][name]" class="form-control form-control-sm"
                                value="{{ old('users.0.name') }}" placeholder="e.g., Rajesh Kumar" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Email Address <span
                                    class="text-danger">*</span></label>
                            <input type="email" name="users[0][email]" class="form-control form-control-sm"
                                value="{{ old('users.0.email') }}" placeholder="rajesh.kumar@company.com" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Employee ID <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="users[0][employee_id]" class="form-control form-control-sm"
                                value="{{ old('users.0.employee_id') }}" placeholder="EMP001" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Designation</label>
                            <input type="text" name="users[0][designation]" class="form-control form-control-sm"
                                value="{{ old('users.0.designation') }}" placeholder="HOD">
                        </div>
                    </div>
                    <input type="hidden" name="users[0][role]" value="hod">
                </div>

                {{-- Supervisor --}}
                <div class="user-entry required">
                    <span class="user-role-badge role-supervisor">Supervisor / Team Lead</span>
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Full Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="users[1][name]" class="form-control form-control-sm"
                                value="{{ old('users.1.name') }}" placeholder="e.g., Suresh Patel" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Email Address <span
                                    class="text-danger">*</span></label>
                            <input type="email" name="users[1][email]" class="form-control form-control-sm"
                                value="{{ old('users.1.email') }}" placeholder="suresh.patel@company.com" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Employee ID <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="users[1][employee_id]" class="form-control form-control-sm"
                                value="{{ old('users.1.employee_id') }}" placeholder="EMP002" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Designation</label>
                            <input type="text" name="users[1][designation]" class="form-control form-control-sm"
                                value="{{ old('users.1.designation') }}" placeholder="Sr. Supervisor">
                        </div>
                    </div>
                    <input type="hidden" name="users[1][role]" value="supervisor">
                </div>

                {{-- Staff Members --}}
                <div id="staffEntries">
                    @php $staffIndex = 2; @endphp
                    @if(old('users'))
                    @foreach(old('users') as $index => $user)
                    @if(($user['role'] ?? '') === 'staff')
                    <div class="user-entry">
                        <button type="button" class="remove-user"
                            onclick="this.closest('.user-entry').remove()">&times;</button>
                        <span class="user-role-badge role-staff">Staff Member {{ $loop->index + 1 }}</span>
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <input type="text" name="users[{{ $index }}][name]" class="form-control form-control-sm"
                                    value="{{ $user['name'] ?? '' }}" placeholder="Full Name" required>
                            </div>
                            <div class="col-md-4">
                                <input type="email" name="users[{{ $index }}][email]"
                                    class="form-control form-control-sm" value="{{ $user['email'] ?? '' }}"
                                    placeholder="Email" required>
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="users[{{ $index }}][employee_id]"
                                    class="form-control form-control-sm" value="{{ $user['employee_id'] ?? '' }}"
                                    placeholder="Emp ID" required>
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="users[{{ $index }}][designation]"
                                    class="form-control form-control-sm" value="{{ $user['designation'] ?? '' }}"
                                    placeholder="Designation">
                            </div>
                        </div>
                        <input type="hidden" name="users[{{ $index }}][role]" value="staff">
                    </div>
                    @php $staffIndex = $index + 1; @endphp
                    @endif
                    @endforeach
                    @else
                    <div class="user-entry">
                        <button type="button" class="remove-user"
                            onclick="this.closest('.user-entry').remove()">&times;</button>
                        <span class="user-role-badge role-staff">Staff Member 1</span>
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <input type="text" name="users[2][name]" class="form-control form-control-sm"
                                    placeholder="Full Name">
                            </div>
                            <div class="col-md-4">
                                <input type="email" name="users[2][email]" class="form-control form-control-sm"
                                    placeholder="Email">
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="users[2][employee_id]" class="form-control form-control-sm"
                                    placeholder="Emp ID">
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="users[2][designation]" class="form-control form-control-sm"
                                    placeholder="Designation">
                            </div>
                        </div>
                        <input type="hidden" name="users[2][role]" value="staff">
                    </div>
                    @endif
                </div>

                <div class="add-more-btn" onclick="addStaffEntry({{ $staffIndex }})">
                    <i class="fas fa-plus-circle me-1"></i> Add More Staff Members
                </div>
            </div>

            <div class="nav-buttons">
                <button type="button" class="btn btn-outline-secondary px-4" onclick="goToStep(1)">
                    <i class="fas fa-arrow-left me-1"></i> Previous
                </button>
                <button type="button" class="btn btn-primary px-4" onclick="goToStep(3)">
                    Next: Categories & SLA <i class="fas fa-arrow-right ms-1"></i>
                </button>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- STEP 3: CATEGORIES & SLA --}}
        {{-- ========================================== --}}
        <div class="form-section-card" id="step3">
            <div class="d-flex align-items-start gap-3 mb-4">
                <div class="section-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-tags"></i>
                </div>
                <div>
                    <div class="section-title">Incident Categories & SLA</div>
                    <div class="section-subtitle">
                        Select the types of incidents your department handles. Each category has a default SLA (Service
                        Level Agreement) time - the maximum time allowed to resolve such incidents.
                        <br><br>
                        <strong class="text-dark">💡 Note:</strong> Different units within the same department (e.g.,
                        Civil - Structural vs Civil - Plumbing) may handle different categories. Select ALL categories
                        that ANY unit in your department can handle.
                    </div>
                </div>
            </div>

            <div class="info-highlight">
                <i class="fas fa-clock me-2"></i>
                <strong>SLA Explained:</strong> If SLA is 120 minutes, the incident must be resolved within 2 hours. If
                it breaches, it will be escalated based on your escalation matrix in Step 4.
            </div>

            <div class="row g-3 mb-4">
                @php
                $defaultCategories = [
                'Safety Hazard' => ['icon' => 'fa-triangle-exclamation', 'color' => '#EF4444', 'sla' => 30, 'desc' =>
                'Workplace safety issues, hazards'],
                'Security Issue' => ['icon' => 'fa-shield-halved', 'color' => '#DC2626', 'sla' => 15, 'desc' =>
                'Security breaches, unauthorized access'],
                'Maintenance Required' => ['icon' => 'fa-screwdriver-wrench', 'color' => '#F59E0B', 'sla' => 240, 'desc'
                => 'General maintenance and repairs'],
                'IT/Network Issue' => ['icon' => 'fa-laptop-code', 'color' => '#8B5CF6', 'sla' => 120, 'desc' =>
                'Computer, network, software problems'],
                'Cleaning Issue' => ['icon' => 'fa-broom', 'color' => '#10B981', 'sla' => 120, 'desc' => 'Housekeeping,
                sanitation, waste'],
                'Electrical Issue' => ['icon' => 'fa-bolt', 'color' => '#6366F1', 'sla' => 60, 'desc' => 'Power, wiring,
                electrical faults'],
                'Water Leakage' => ['icon' => 'fa-droplet', 'color' => '#0EA5E9', 'sla' => 120, 'desc' => 'Water leaks,
                plumbing issues'],
                'Infrastructure Damage' => ['icon' => 'fa-building-columns', 'color' => '#6B7280', 'sla' => 480, 'desc'
                => 'Structural damage, cracks, wear'],
                'Fire Safety' => ['icon' => 'fa-fire-extinguisher', 'color' => '#EF4444', 'sla' => 15, 'desc' => 'Fire
                hazards, equipment issues'],
                'Vehicle Obstruction' => ['icon' => 'fa-truck', 'color' => '#F97316', 'sla' => 60, 'desc' => 'Vehicle
                blocking, parking issues'],
                ];
                @endphp
                @foreach($defaultCategories as $catName => $catData)
                <div class="col-md-6">
                    <div class="category-select-card {{ in_array($catName, old('categories', [])) ? 'selected' : '' }}"
                        onclick="toggleCategory(this)" data-category="{{ $catName }}">
                        <span class="cat-icon" style="color:{{ $catData['color'] }}">
                            <i class="fas {{ $catData['icon'] }}"></i>
                        </span>
                        <div class="cat-name">{{ $catName }}</div>
                        <div class="cat-sla">Default SLA: {{ $catData['sla'] }} min</div>
                        <small class="text-muted" style="font-size:0.65rem;">{{ $catData['desc'] }}</small>
                        <input type="checkbox" name="categories[]" value="{{ $catName }}" {{ in_array($catName,
                            old('categories', [])) ? 'checked' : '' }} style="display:none;">
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Custom Category --}}
            <div class="border rounded-3 p-3 bg-light">
                <label class="fw-semibold mb-2">➕ Add Custom Category (if not listed above)</label>
                <div class="row g-2">
                    <div class="col-md-5">
                        <input type="text" class="form-control form-control-sm" id="newCategoryName"
                            placeholder="Category name (e.g., Pest Control)">
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control form-control-sm" id="newCategorySla" value="120"
                            min="5" placeholder="SLA in minutes">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-primary btn-sm w-100"
                            onclick="addCustomCategory()">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                </div>
                <div id="customCategoriesList" class="mt-2 d-flex flex-wrap gap-1"></div>
            </div>

            <div class="nav-buttons">
                <button type="button" class="btn btn-outline-secondary px-4" onclick="goToStep(2)">
                    <i class="fas fa-arrow-left me-1"></i> Previous
                </button>
                <button type="button" class="btn btn-primary px-4" onclick="goToStep(4)">
                    Next: Escalation Matrix <i class="fas fa-arrow-right ms-1"></i>
                </button>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- STEP 4: ESCALATION MATRIX --}}
        {{-- ========================================== --}}
        <div class="form-section-card" id="step4">
            <div class="d-flex align-items-start gap-3 mb-4">
                <div class="section-icon bg-danger bg-opacity-10 text-danger">
                    <i class="fas fa-arrow-up-right-dots"></i>
                </div>
                <div>
                    <div class="section-title">Escalation Matrix</div>
                    <div class="section-subtitle">
                        Define the escalation chain. When an incident is not resolved within SLA time, it automatically
                        escalates to the next level.
                        <br><br>
                        <strong>Level 1</strong> → First responder (Supervisor) |
                        <strong>Level 2</strong> → Department Head (HOD) |
                        <strong>Level 3</strong> → Senior Management / Admin
                    </div>
                </div>
            </div>

            <div id="escalationEntries">
                <div class="row g-2 mb-3 fw-semibold small text-muted border-bottom pb-2">
                    <div class="col-md-2">Level</div>
                    <div class="col-md-2">Timeout (Min)</div>
                    <div class="col-md-4">Escalate To (Person Name)</div>
                    <div class="col-md-4">Notes</div>
                </div>

                @for($i = 1; $i <= 4; $i++) <div class="row g-2 mb-3 align-items-center">
                    <div class="col-md-2">
                        <span
                            class="badge bg-{{ $i <= 2 ? 'warning' : ($i == 3 ? 'danger' : 'dark') }} text-dark w-100 py-2">
                            Level {{ $i }} {{ $i == 1 ? '(Supervisor)' : ($i == 2 ? '(HOD)' : ($i == 3 ? '(Admin)' :
                            '(Director)')) }}
                        </span>
                        <input type="hidden" name="escalation[{{ $i-1 }}][level]" value="{{ $i }}">
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="escalation[{{ $i-1 }}][timeout_minutes]"
                            class="form-control form-control-sm"
                            value="{{ old('escalation.'.($i-1).'.timeout_minutes', [30, 60, 120, 240][$i-1]) }}" min="5"
                            required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="escalation[{{ $i-1 }}][escalate_to_name]"
                            class="form-control form-control-sm"
                            value="{{ old('escalation.'.($i-1).'.escalate_to_name') }}"
                            placeholder="e.g., {{ ['Shift Supervisor', 'HOD Name', 'Admin Manager', 'Director'][$i-1] }}"
                            required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="escalation[{{ $i-1 }}][target_department]"
                            class="form-control form-control-sm"
                            value="{{ old('escalation.'.($i-1).'.target_department', 'Same Department') }}"
                            placeholder="Target department if different">
                    </div>
            </div>
            @endfor
        </div>

        <div class="nav-buttons">
            <button type="button" class="btn btn-outline-secondary px-4" onclick="goToStep(3)">
                <i class="fas fa-arrow-left me-1"></i> Previous
            </button>
            <button type="button" class="btn btn-primary px-4" onclick="goToStep(5)">
                Review & Submit <i class="fas fa-arrow-right ms-1"></i>
            </button>
        </div>
</div>

{{-- ========================================== --}}
{{-- STEP 5: REVIEW & SUBMIT --}}
{{-- ========================================== --}}
<div class="form-section-card" id="step5">
    <div class="d-flex align-items-start gap-3 mb-4">
        <div class="section-icon bg-success bg-opacity-10 text-success">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <div>
            <div class="section-title">Review & Submit</div>
            <div class="section-subtitle">Please review all the information before submitting. You can go back to any
                step to make changes.</div>
        </div>
    </div>

    <div id="reviewSummary" class="bg-light rounded p-4 mb-4" style="font-size:0.875rem;">
        <div id="reviewContent">
            <p class="text-muted text-center mb-0">Complete all previous steps to see the summary here.</p>
        </div>
    </div>

    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" id="confirmAccuracy" required>
        <label class="form-check-label" for="confirmAccuracy">
            <strong>I confirm that all information provided is accurate and complete.</strong>
            <br>
            <small class="text-muted">By checking this, you confirm that the department details, team members,
                categories, and escalation matrix are correct.</small>
        </label>
    </div>

    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary px-4" onclick="goToStep(4)">
            <i class="fas fa-arrow-left me-1"></i> Previous
        </button>
        <button type="submit" class="btn btn-success btn-lg px-5" id="submitBtn">
            <i class="fas fa-paper-plane me-2"></i> Submit Onboarding Request
        </button>
    </div>
</div>
</form>
</div>
@endsection

@push('scripts')
<script>
    let currentStep = 1;
const totalSteps = 5;
const customCategories = [];
let staffCounter = {{ $staffIndex ?? 3 }};

// Store initial custom categories from old input
@if(old('custom_categories'))
    try {
        const oldCustom = JSON.parse('{!! old('custom_categories') !!}');
        oldCustom.forEach(c => customCategories.push(c));
        updateCustomCategoriesList();
    } catch(e) {}
@endif

// ==========================================
// STEP NAVIGATION
// ==========================================
function goToStep(step) {
    if (step < 1 || step > totalSteps) return;

    // Validate current step before moving forward
    if (step > currentStep && !validateStep(currentStep)) {
        return;
    }

    // Hide all steps
    document.querySelectorAll('.form-section-card').forEach(el => el.classList.remove('active'));
    // Show target step
    document.getElementById('step' + step).classList.add('active');

    // Update step indicator
    for (let i = 1; i <= totalSteps; i++) {
        const circle = document.getElementById('stepCircle' + i);
        const label = document.getElementById('stepLabel' + i);
        circle.classList.remove('active', 'completed');
        label.classList.remove('active');

        if (i < step) {
            circle.classList.add('completed');
        } else if (i === step) {
            circle.classList.add('active');
            label.classList.add('active');
        }
    }

    currentStep = step;

    // Update review summary if on step 5
    if (step === 5) {
        updateReviewSummary();
    }

    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ==========================================
// STEP VALIDATION
// ==========================================
function validateStep(step) {
    const stepEl = document.getElementById('step' + step);
    const requiredFields = stepEl.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    if (!isValid) {
        // Highlight step circle
        document.getElementById('stepCircle' + step).classList.add('has-error');
        setTimeout(() => {
            document.getElementById('stepCircle' + step).classList.remove('has-error');
        }, 2000);

        alert('Please fill in all required fields before proceeding.');
    }

    return isValid;
}

// ==========================================
// CATEGORY SELECTION
// ==========================================
function toggleCategory(card) {
    card.classList.toggle('selected');
    const checkbox = card.querySelector('input[type="checkbox"]');
    checkbox.checked = !checkbox.checked;
}

function addCustomCategory() {
    const nameInput = document.getElementById('newCategoryName');
    const slaInput = document.getElementById('newCategorySla');
    const name = nameInput.value.trim();
    const sla = slaInput.value || 120;

    if (!name) {
        alert('Please enter a category name.');
        return;
    }

    customCategories.push({ name, sla });
    nameInput.value = '';
    slaInput.value = '120';
    updateCustomCategoriesList();
    updateCustomCategoriesInput();
}

function updateCustomCategoriesList() {
    const list = document.getElementById('customCategoriesList');
    list.innerHTML = customCategories.map((c, i) => `
        <span class="badge bg-primary d-inline-flex align-items-center gap-1" style="font-size:0.75rem;padding:6px 10px;">
            ${c.name} (SLA: ${c.sla}min)
            <span style="cursor:pointer;font-size:0.875rem;" onclick="removeCustomCategory(${i})">&times;</span>
        </span>
    `).join('');
}

function removeCustomCategory(index) {
    customCategories.splice(index, 1);
    updateCustomCategoriesList();
    updateCustomCategoriesInput();
}

function updateCustomCategoriesInput() {
    document.getElementById('customCategoriesInput').value = JSON.stringify(customCategories);
}

// ==========================================
// STAFF ENTRY MANAGEMENT
// ==========================================
function addStaffEntry(startIndex) {
    const index = startIndex || staffCounter;
    const container = document.getElementById('staffEntries');
    const div = document.createElement('div');
    div.className = 'user-entry';
    div.innerHTML = `
        <button type="button" class="remove-user" onclick="this.closest('.user-entry').remove()">&times;</button>
        <span class="user-role-badge role-staff">Staff Member</span>
        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <input type="text" name="users[${index}][name]" class="form-control form-control-sm" placeholder="Full Name">
            </div>
            <div class="col-md-4">
                <input type="email" name="users[${index}][email]" class="form-control form-control-sm" placeholder="Email">
            </div>
            <div class="col-md-2">
                <input type="text" name="users[${index}][employee_id]" class="form-control form-control-sm" placeholder="Emp ID">
            </div>
            <div class="col-md-2">
                <input type="text" name="users[${index}][designation]" class="form-control form-control-sm" placeholder="Designation">
            </div>
        </div>
        <input type="hidden" name="users[${index}][role]" value="staff">
    `;
    container.appendChild(div);
    staffCounter = Math.max(staffCounter, index + 1);
}

// ==========================================
// REVIEW SUMMARY
// ==========================================
function updateReviewSummary() {
    const deptName = document.querySelector('[name="department_name"]')?.value || 'Not provided';
    const deptCode = document.querySelector('[name="department_code"]')?.value || 'Not provided';
    const userCount = document.querySelectorAll('#usersContainer .user-entry').length;

    const selectedCategories = [];
    document.querySelectorAll('.category-select-card.selected').forEach(card => {
        selectedCategories.push(card.dataset.category);
    });

    const html = `
        <h6 class="fw-bold mb-3"><i class="fas fa-building me-2"></i>Department</h6>
        <p><strong>Name:</strong> ${deptName} | <strong>Code:</strong> ${deptCode}</p>

        <h6 class="fw-bold mb-3 mt-3"><i class="fas fa-users me-2"></i>Team Members</h6>
        <p><strong>Total Users:</strong> ${userCount} (1 HOD + 1 Supervisor + ${userCount - 2} Staff)</p>

        <h6 class="fw-bold mb-3 mt-3"><i class="fas fa-tags me-2"></i>Categories</h6>
        <p>${selectedCategories.length > 0 ? selectedCategories.join(', ') : 'None selected'}</p>
        ${customCategories.length > 0 ? `<p><strong>Custom:</strong> ${customCategories.map(c => c.name).join(', ')}</p>` : ''}

        <h6 class="fw-bold mb-3 mt-3"><i class="fas fa-arrow-up-right-dots me-2"></i>Escalation Matrix</h6>
        <p>4 levels configured with timeouts from 30 to 240 minutes.</p>

        <div class="alert alert-info small mt-3 mb-0">
            <i class="fas fa-info-circle me-1"></i>
            Default password for all users: <strong>Welcome@123</strong> (change on first login)
        </div>
    `;

    document.getElementById('reviewContent').innerHTML = html;
}

// ==========================================
// FORM SUBMISSION
// ==========================================
document.getElementById('onboardingForm').addEventListener('submit', function(e) {
    // Final validation
    if (!validateStep(4)) {
        e.preventDefault();
        goToStep(4);
        return;
    }

    if (!document.getElementById('confirmAccuracy').checked) {
        e.preventDefault();
        alert('Please confirm the information is accurate before submitting.');
        return;
    }

    updateCustomCategoriesInput();

    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
});

// Initialize custom categories from old input
updateCustomCategoriesInput();
</script>
@endpush