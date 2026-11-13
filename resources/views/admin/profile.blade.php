{{-- Metronic reference: dist/pages/profile/overview.html (3 sections) + documentation/forms/image-input.html (metronic-notes.md §6) --}}
@extends('layouts.admin')

@section('page-title')
    <div class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="fw-bolder my-2">My Profile</h1>
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-light-success fs-6 mb-7">{{ session('success') }}</div>
    @endif

    <div class="row g-5 g-xl-8">
        <div class="col-xl-4">
            <div class="card mb-5 mb-xxl-8">
                <div class="card-body p-9">
                    <div class="d-flex align-items-center mb-7">
                        {{-- Metronic image-input: data-kt-image-input auto-initialized by scripts.bundle.js --}}
                        <div class="image-input image-input-outline image-input-placeholder" data-kt-image-input="true">
                            @php
    $avatarUrl = $user->avatar_path
        ? Storage::disk('public')->url($user->avatar_path)
        : asset('assets/media/avatars/blank.png');
@endphp
<div class="image-input-wrapper w-100px h-100px" style="background-image:url('{{ $avatarUrl }}')"></div>
                            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                                <i class="bi bi-pencil-fill fs-7"></i>
                                <input type="file" name="photo" accept=".png,.jpg,.jpeg" hidden form="kt_profile_form" />
                                <input type="hidden" name="avatar_remove" value="0" form="kt_profile_form" />
                            </label>
                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar change">
                                <i class="bi bi-x fs-2"></i>
                            </span>
                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
                                <i class="bi bi-x fs-2"></i>
                            </span>
                        </div>
                        <div class="ms-5">
                            <div class="fw-bolder fs-4 text-dark">{{ $user->name }}</div>
                            <div class="text-muted fw-bold">{{ $user->email }}</div>
                        </div>
                    </div>
                    <div class="separator separator-dashed my-7"></div>
                    <div class="fw-bolder fs-5 text-dark mb-4">Photo</div>
                    <p class="text-gray-600 fs-7 mb-6">jpg/jpeg/png, max 2MB. Uploading replaces the previous photo.</p>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card mb-5 mb-xxl-8">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h3 class="fw-bolder">Personal Data</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form class="form" method="POST" action="{{ route('profile.update') }}" id="kt_profile_form" enctype="multipart/form-data">
                        @csrf
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
                        @error('photo')
                            <div class="alert alert-light-danger fs-6">{{ $message }}</div>
                        @enderror
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <span class="indicator-label">Save Changes</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-5 mb-xxl-8">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h3 class="fw-bolder">Change Password</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form class="form" method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        <div class="fv-row mb-10">
                            <label class="required fw-bold fs-6 mb-2">Current Password</label>
                            <input class="form-control form-control-solid {{ $errors->has('current_password') ? 'is-invalid' : '' }}" type="password" name="current_password" autocomplete="current-password" />
                            @error('current_password')
                                <div class="text-danger fs-8 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="fv-row mb-10">
                            <label class="required fw-bold fs-6 mb-2">New Password</label>
                            <input class="form-control form-control-solid {{ $errors->has('password') ? 'is-invalid' : '' }}" type="password" name="password" autocomplete="new-password" />
                            @error('password')
                                <div class="text-danger fs-8 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="fv-row mb-10">
                            <label class="required fw-bold fs-6 mb-2">Confirm New Password</label>
                            <input class="form-control form-control-solid" type="password" name="password_confirmation" autocomplete="new-password" />
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <span class="indicator-label">Update Password</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
