{{-- Metronic reference: dist/documentation/base/forms/controls.html (metronic-notes.md §4) --}}
@extends('layouts.admin')

@section('title', 'Edit User — ' . $user->name)
    @section('page-title')
    <div class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="fw-bolder my-2">Edit User — {{ $user->name }}</h1>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            @if (session('error'))
                <div class="alert alert-light-danger fs-6 mb-7">{{ session('error') }}</div>
            @endif
            <form class="form" method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                @method('PUT')
                <div class="fv-row mb-10">
                    <label class="required fw-bold fs-6 mb-2">Name</label>
                    <input class="form-control form-control-solid {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text" name="name" value="{{ old('name', $user->name) }}" />
                    @error('name')
                        <div class="text-danger fs-8 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="fv-row mb-10">
                    <label class="required fw-bold fs-6 mb-2">Email</label>
                    <input class="form-control form-control-solid {{ $errors->has('email') ? 'is-invalid' : '' }}" type="email" name="email" value="{{ old('email', $user->email) }}" />
                    @error('email')
                        <div class="text-danger fs-8 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="fv-row mb-10">
                    <label class="fw-bold fs-6 mb-2">Password <span class="text-muted fs-7">(leave blank to keep current)</span></label>
                    <input class="form-control form-control-solid {{ $errors->has('password') ? 'is-invalid' : '' }}" type="password" name="password" autocomplete="new-password" />
                    @error('password')
                        <div class="text-danger fs-8 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="fv-row mb-10">
                    <label class="fw-bold fs-6 mb-2">Confirm Password</label>
                    <input class="form-control form-control-solid" type="password" name="password_confirmation" autocomplete="new-password" />
                </div>
                <div class="fv-row mb-10">
                    <label class="fw-bold fs-6 mb-2">Roles</label>
                    <select class="form-select form-select-solid" name="roles[]" multiple size="{{ count($roles) }}">
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" {{ in_array($role->name, old('roles', $user->roles->pluck('name')->all())) ? 'selected' : '' }}>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('roles')
                        <div class="text-danger fs-8 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="d-flex justify-content-end">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-light me-3">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <span class="indicator-label">Submit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
