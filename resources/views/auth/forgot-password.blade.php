{{-- Metronic reference: dist/authentication/layouts/basic/password-reset.html (metronic-notes.md §7) --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head-meta')
    <title>{{ config('app.name', 'Admin') }} — Forgot Password</title>
</head>
<body id="kt_body" class="bg-body">
    <div class="d-flex flex-column flex-root">
        <div class="d-flex flex-center flex-column flex-column-fluid p-10 pb-lg-20">
            <a href="{{ url('/') }}" class="mb-12">
                <img alt="Logo" src="{{ asset('assets/media/logos/logo-1.svg') }}" class="h-40px" />
            </a>
            <div class="w-lg-500px bg-body rounded shadow-sm p-10 p-lg-15 mx-auto">
                <form class="form w-100" method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="text-center mb-10">
                        <h1 class="text-dark mb-3">Forgot Password</h1>
                        <div class="text-gray-400 fw-bold fs-4">Enter your email to reset your password</div>
                    </div>
                    @if (session('status'))
                        <div class="alert alert-light-success fs-6 mb-10">{{ session('status') }}</div>
                    @endif
                    @if (session('errors') || $errors->any())
                        <div class="alert alert-light-danger fs-6 mb-10">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif
                    <div class="fv-row mb-10">
                        <label class="form-label fw-bolder text-gray-900 fs-6">Email</label>
                        <input class="form-control form-control-solid" type="email" placeholder="" name="email" value="{{ old('email') }}" autocomplete="off" />
                    </div>
                    <div class="d-flex flex-wrap justify-content-center pb-lg-0">
                        <button type="submit" class="btn btn-lg btn-primary fw-bolder me-4">
                            <span class="indicator-label">Submit</span>
                        </button>
                        <a href="{{ route('login') }}" class="btn btn-lg btn-light-primary fw-bolder">Cancel</a>
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
