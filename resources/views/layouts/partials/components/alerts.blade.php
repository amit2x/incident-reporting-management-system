@if(session('success'))
<div class="alert alert-success d-flex align-items-center gap-2 border-0 shadow-sm mb-3 animate__animated animate__fadeInDown"
     style="border-radius: var(--radius-lg); background: var(--success-bg); color: var(--success);">
    <i class="fas fa-circle-check fs-5"></i>
    <div class="flex-grow-1">{{ session('success') }}</div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger d-flex align-items-center gap-2 border-0 shadow-sm mb-3 animate__animated animate__fadeInDown"
     style="border-radius: var(--radius-lg); background: var(--danger-bg); color: var(--danger);">
    <i class="fas fa-circle-xmark fs-5"></i>
    <div class="flex-grow-1">{{ session('error') }}</div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('warning'))
<div class="alert alert-warning d-flex align-items-center gap-2 border-0 shadow-sm mb-3 animate__animated animate__fadeInDown"
     style="border-radius: var(--radius-lg); background: var(--warning-bg); color: var(--warning);">
    <i class="fas fa-triangle-exclamation fs-5"></i>
    <div class="flex-grow-1">{{ session('warning') }}</div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('info'))
<div class="alert alert-info d-flex align-items-center gap-2 border-0 shadow-sm mb-3 animate__animated animate__fadeInDown"
     style="border-radius: var(--radius-lg); background: var(--info-bg); color: var(--info);">
    <i class="fas fa-circle-info fs-5"></i>
    <div class="flex-grow-1">{{ session('info') }}</div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger border-0 shadow-sm mb-3 animate__animated animate__fadeInDown"
     style="border-radius: var(--radius-lg); background: var(--danger-bg); color: var(--danger);">
    <div class="d-flex align-items-center gap-2 mb-2">
        <i class="fas fa-circle-xmark fs-5"></i>
        <strong>Please fix the following errors:</strong>
    </div>
    <ul class="mb-0 small ps-4">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
