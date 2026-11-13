{{-- Metronic reference: dist/authentication/layouts/basic/sign-in.html (metronic-notes.md §7) --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head-meta')
    <title>{{ config('app.name', 'Admin') }} — Sign In</title>
</head>
<body id="kt_body" class="bg-body">
    <div class="d-flex flex-column flex-root">
        <div class="d-flex flex-center flex-column flex-column-fluid p-10 pb-lg-20">
            <a href="{{ url('/') }}" class="mb-12">
                <img alt="Logo" src="{{ asset('assets/media/logos/logo-1.svg') }}" class="h-40px" />
            </a>
            <div class="w-lg-500px bg-body rounded shadow-sm p-10 p-lg-15 mx-auto">
                <form class="form w-100" method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="text-center mb-10">
                        <h1 class="text-dark mb-3">Sign In</h1>
                        <div class="text-gray-400 fw-bold fs-4">Admin Panel</div>
                    </div>
                    @if (session('status'))
                        <div class="alert alert-light-success fs-6 mb-10">{{ session('status') }}</div>
                    @endif
                    <div class="fv-row mb-10">
                        <label class="form-label fs-6 fw-bolder text-dark">Email</label>
                        <input class="form-control form-control-lg form-control-solid {{ $errors->has('email') ? 'is-invalid' : '' }}" type="email" name="email" value="{{ old('email') }}" autocomplete="off" />
                        @error('email')
                            <div class="text-danger fs-8 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="fv-row mb-10">
                        <div class="d-flex flex-stack mb-2">
                            <label class="form-label fw-bolder text-dark fs-6 mb-0">Password</label>
                            <a href="{{ route('password.request') }}" class="link-primary fs-6 fw-bolder">Forgot Password ?</a>
                        </div>
                        <input class="form-control form-control-lg form-control-solid {{ $errors->has('password') ? 'is-invalid' : '' }}" type="password" name="password" autocomplete="off" />
                        @error('password')
                            <div class="text-danger fs-8 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="fv-row mb-10">
                        <label class="form-check form-check-custom form-check-solid form-check-inline">
                            <input class="form-check-input" type="checkbox" name="remember" value="1" />
                            <span class="form-check-label fw-bold text-gray-700 fs-6">Remember me</span>
                        </label>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-lg btn-primary w-100 mb-5">
                            <span class="indicator-label">Continue</span>
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
