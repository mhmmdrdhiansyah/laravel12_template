{{-- Metronic reference: dist/documentation/base/forms/controls.html + list.html checkbox pattern (metronic-notes.md §4/§5) --}}
@extends('layouts.admin')

@section('page-title')
    <div class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="fw-bolder my-2">Edit Role — {{ $role->name }}</h1>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            @if (session('error'))
                <div class="alert alert-light-danger fs-6 mb-7">{{ session('error') }}</div>
            @endif
            <form class="form" method="POST" action="{{ route('admin.roles.update', $role) }}">
                @csrf
                @method('PUT')
                <div class="fv-row mb-10">
                    <label class="required fw-bold fs-6 mb-2">Name</label>
                    <input class="form-control form-control-solid {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text" name="name" value="{{ old('name', $role->name) }}" {{ in_array($role->name, ['super-admin', 'user']) ? 'readonly' : '' }} />
                    @error('name')
                        <div class="text-danger fs-8 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="fv-row mb-10">
                    <label class="fw-bold fs-6 mb-2">Permissions</label>
                    <div class="row">
                        @foreach ($permissions as $permission)
                        <div class="col-6 col-lg-3">
                            <label class="form-check form-check-custom form-check-solid mb-4">
                                <input class="form-check-input me-3" type="checkbox" name="permissions[]" value="{{ $permission->name }}" {{ in_array($permission->name, old('permissions', $role->permissions->pluck('name')->all())) ? 'checked' : '' }} />
                                <span class="form-check-label fw-bold text-gray-700">{{ $permission->name }}</span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="d-flex justify-content-end">
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-light me-3">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <span class="indicator-label">Submit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
