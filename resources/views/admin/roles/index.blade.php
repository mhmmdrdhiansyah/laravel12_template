{{-- Metronic reference: dist/apps/user-management/users/list.html (metronic-notes.md §3) --}}
@extends('layouts.admin')

@section('title', 'Roles')
    @section('page-title')
    <div class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="fw-bolder my-2">Roles</h1>
    </div>
@endsection

@section('content')
    @can('roles.create')
    <div class="d-flex flex-wrap mb-7">
        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
            <span class="svg-icon svg-icon-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <rect opacity="0.5" x="11" y="4" width="2" height="16" rx="1" fill="black" />
                    <rect opacity="0.5" x="4" y="11" width="16" height="2" rx="1" fill="black" />
                </svg>
            </span>
            Add Role
        </a>
    </div>
    @endcan

    @if (session('success'))
        <div class="alert alert-light-success fs-6 mb-7">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-light-danger fs-6 mb-7">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-header border-0 pt-6 d-flex flex-stack flex-wrap gap-2">
            <div class="card-title m-0">
                <form method="GET" action="{{ route('admin.roles.index') }}" class="d-flex align-items-center position-relative my-1">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-solid w-100 w-md-250px ps-14" placeholder="Search role" />
                </form>
            </div>
            <div class="card-toolbar m-0"></div>
        </div>
        <div id="kt_roles_table_container">
            @include('admin.roles._table', ['roles' => $roles])
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.querySelector('.card-title form');
    var input = form.querySelector('input[name="search"]');
    var container = document.getElementById('kt_roles_table_container');
    var controller = null;
    var timer = null;

    function refresh() {
        if (controller) controller.abort();
        controller = new AbortController();
        var params = new URLSearchParams(window.location.search);
        if (input.value) { params.set('search', input.value); } else { params.delete('search'); }
        params.delete('page');
        params.set('partial', '1');

        container.style.opacity = '0.5';
        fetch(form.action + '?' + params.toString(), { signal: controller.signal })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
        .then(function (html) {
            container.innerHTML = html;
            container.style.opacity = '';
            var url = new URL(window.location);
            if (input.value) { url.searchParams.set('search', input.value); } else { url.searchParams.delete('search'); }
            url.searchParams.delete('partial');
            url.searchParams.delete('page');
            window.history.replaceState({}, '', url);
        })
        .catch(function (e) { if (e.name !== 'AbortError') { container.style.opacity = ''; } });
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(refresh, 300);
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearTimeout(timer);
        refresh();
    });
})();
</script>
@endpush

