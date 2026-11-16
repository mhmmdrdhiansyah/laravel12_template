{{-- Metronic reference: dist/authentication/layouts/basic/sign-up.html (metronic-notes.md §7) --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head-meta')
    <title>{{ config('app.name', 'Admin') }} — Register</title>
</head>
<body id="kt_body" class="bg-body">
    <div class="d-flex flex-column flex-root">
        <div class="d-flex flex-center flex-column flex-column-fluid p-10 pb-lg-20">
            <a href="{{ url('/') }}" class="mb-12">
                <img alt="Logo" src="{{ asset('assets/media/logos/logo-1.svg') }}" class="h-40px" />
            </a>
            <div class="w-lg-500px bg-body rounded shadow-sm p-10 p-lg-15 mx-auto">
                <form class="form w-100" method="POST" action="{{ route('register') }}">
                    @csrf
                    <div class="text-center mb-10">
                        <h1 class="text-dark mb-3">Create an Account</h1>
                        <div class="text-gray-400 fw-bold fs-4">Already have an account?
                            <a href="{{ route('login') }}" class="link-primary fw-bolder">Sign in here</a>
                        </div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bolder text-dark fs-6">Name</label>
                        <input class="form-control form-control-lg form-control-solid {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text" name="name" value="{{ old('name') }}" autocomplete="off" />
                        @error('name')
                            <div class="text-danger fs-8 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bolder text-dark fs-6">Email</label>
                        <input class="form-control form-control-lg form-control-solid {{ $errors->has('email') ? 'is-invalid' : '' }}" type="email" name="email" value="{{ old('email') }}" autocomplete="off" />
                        @error('email')
                            <div class="text-danger fs-8 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bolder text-dark fs-6">Password</label>
                        <input class="form-control form-control-lg form-control-solid {{ $errors->has('password') ? 'is-invalid' : '' }}" type="password" name="password" autocomplete="off" />
                        @error('password')
                            <div class="text-danger fs-8 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="fv-row mb-10">
                        <label class="form-label fw-bolder text-dark fs-6">Confirm Password</label>
                        <input class="form-control form-control-lg form-control-solid" type="password" name="password_confirmation" autocomplete="off" />
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-lg btn-primary w-100 mb-5">
                            <span class="indicator-label">Sign Up</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="d-flex flex-center flex-column-auto p-10">
            <div class="d-flex align-items-center fw-bold fs-6">
                <span class="text-muted px-2">{{ date('Y') }}© {{ config('app.name', 'Admin') }}</span>
            </div>
        </div>
    </div>
    @include('partials.footer-scripts')
</body>
</html>
