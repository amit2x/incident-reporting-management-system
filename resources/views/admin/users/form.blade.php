{{-- resources/views/admin/users/form.blade.php --}}
@extends('layouts.app')

@section('title', isset($user) ? 'Edit User - IRMS' : 'Create User - IRMS')

@section('content')
<div class="container-fluid px-3 py-3">
    
    <div class="mb-3">
        <a href="{{ route('admin.users.index') }}" class="text-muted text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i> Back to Users
        </a>
        <h4 class="fw-bold mt-1 mb-0">{{ isset($user) ? 'Edit User' : 'Create User' }}</h4>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}" method="POST">
                        @csrf
                        @if(isset($user)) @method('PUT') @endif
                        
                        <div class="row g-3">
                            {{-- Name --}}
                            <div class="col-md-6">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                       value="{{ old('name', $user->name ?? '') }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            
                            {{-- Username --}}
                            <div class="col-md-6">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" 
                                       value="{{ old('username', $user->username ?? '') }}" required>
                                @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            
                            {{-- Email --}}
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                       value="{{ old('email', $user->email ?? '') }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            
                            {{-- Phone --}}
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                                       value="{{ old('phone', $user->phone ?? '') }}">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            
                            {{-- Password --}}
                            <div class="col-md-6">
                                <label class="form-label">
                                    Password {{ isset($user) ? '' : '<span class="text-danger">*</span>' }}
                                </label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" 
                                       {{ isset($user) ? '' : 'required' }}>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @if(isset($user))
                                    <small class="text-muted">Leave blank to keep current password</small>
                                @endif
                            </div>
                            
                            {{-- Confirm Password --}}
                            <div class="col-md-6">
                                <label class="form-label">
                                    Confirm Password {{ isset($user) ? '' : '<span class="text-danger">*</span>' }}
                                </label>
                                <input type="password" name="password_confirmation" class="form-control" 
                                       {{ isset($user) ? '' : 'required' }}>
                            </div>
                            
                            {{-- Employee ID --}}
                            <div class="col-md-6">
                                <label class="form-label">Employee ID</label>
                                <input type="text" name="employee_id" class="form-control @error('employee_id') is-invalid @enderror" 
                                       value="{{ old('employee_id', $user->employee_id ?? '') }}">
                                @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            
                            {{-- Designation --}}
                            <div class="col-md-6">
                                <label class="form-label">Designation</label>
                                <input type="text" name="designation" class="form-control @error('designation') is-invalid @enderror" 
                                       value="{{ old('designation', $user->designation ?? '') }}">
                                @error('designation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            
                            {{-- Department --}}
                            <div class="col-md-6">
                                <label class="form-label">Department <span class="text-danger">*</span></label>
                                <select name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id', $user->department_id ?? '') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }} ({{ $dept->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            
                            {{-- Status --}}
                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="active" {{ old('status', $user->status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            
                            {{-- Roles --}}
                            <div class="col-12">
                                <label class="form-label">Roles <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    @foreach($roles as $role)
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-check">
                                                <input type="checkbox" name="roles[]" value="{{ $role->name }}" 
                                                       class="form-check-input" id="role_{{ $role->id }}"
                                                       {{ in_array($role->name, old('roles', isset($user) ? $user->roles->pluck('name')->toArray() : [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="role_{{ $role->id }}">
                                                    {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('roles')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                        </div>
                        
                        <div class="mt-4 d-flex gap-2">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-light px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-1"></i> {{ isset($user) ? 'Update User' : 'Create User' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection