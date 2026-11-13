{{-- Metronic reference: dist/apps/user-management/users/list.html (metronic-notes.md §3) --}}
@extends('layouts.admin')

@section('page-title')
    <div class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="fw-bolder my-2">Users</h1>
    </div>
@endsection

@section('content')
    @can('users.create')
    <div class="d-flex flex-wrap mb-7">
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <span class="svg-icon svg-icon-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <rect opacity="0.5" x="11" y="4" width="2" height="16" rx="1" fill="black" />
                    <rect opacity="0.5" x="4" y="11" width="16" height="2" rx="1" fill="black" />
                </svg>
            </span>
            Add User
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
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <input type="text" data-kt-user-table-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search user" />
                </div>
            </div>
        </div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
                <thead>
                    <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0">
                        <th class="min-w-125px">User</th>
                        <th class="min-w-125px">Roles</th>
                        <th class="min-w-125px">Joined Date</th>
                        <th class="text-end min-w-100px">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-bold">
                    @foreach ($users as $user)
                    <tr>
                        <td class="d-flex align-items-center">
                            <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                <div class="symbol-label">
                                    @if ($user->avatar_path)
                                        <img src="{{ Storage::disk('public')->url($user->avatar_path) }}" alt="{{ $user->name }}" class="w-100" />
                                    @else
                                        <img src="{{ asset('assets/media/avatars/blank.png') }}" alt="{{ $user->name }}" class="w-100" />
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex flex-column">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-gray-800 text-hover-primary mb-1">{{ $user->name }}</a>
                                <span>{{ $user->email }}</span>
                            </div>
                        </td>
                        <td>
                            @foreach ($user->roles as $role)
                                <div class="badge badge-light fw-bolder">{{ $role->name }}</div>
                            @endforeach
                        </td>
                        <td>{{ $user->created_at->format('d M Y, H:i') }}</td>
                        <td class="text-end">
                            <a href="#" class="btn btn-light btn-active-light-primary btn-sm" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                <span class="svg-icon svg-icon-5 m-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M11.4343 12.7344L7.25 8.55005C6.83579 8.13583 6.16421 8.13584 5.75 8.55005C5.33579 8.96426 5.33579 9.63583 5.75 10.05L11.2929 15.5929C11.6834 15.9835 12.3166 15.9835 12.7071 15.5929L18.25 10.05C18.6642 9.63584 18.6642 8.96426 18.25 8.55005C17.8358 8.13584 17.1642 8.13584 16.75 8.55005L12.5657 12.7344C12.2533 13.0468 11.7467 13.0468 11.4343 12.7344Z" fill="black" />
                                    </svg>
                                </span>
                            </a>
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-bold fs-7 w-125px py-4" data-kt-menu="true">
                                <div class="menu-item px-3">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="menu-link px-3">Edit</a>
                                </div>
                                <div class="menu-item px-3">
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="menu-link px-3 border-0 bg-transparent">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('vendor-scripts')
<script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('scripts')
<script>
KTUtil.onDOMContentLoaded((function() {
    $(document.getElementById('kt_table_users')).DataTable({info:!1,order:[],pageLength:10,lengthChange:!1,columnDefs:[{orderable:!1,targets:0}]});
}));
</script>
@endpush
