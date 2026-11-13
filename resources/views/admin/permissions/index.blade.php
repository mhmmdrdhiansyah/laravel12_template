{{-- Metronic reference: dist/apps/user-management/users/list.html (metronic-notes.md §3) — read-only, no Add button --}}
@extends('layouts.admin')

@section('page-title')
    <div class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="fw-bolder my-2">Permissions</h1>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <input type="text" data-kt-permission-table-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search permission" />
                </div>
            </div>
        </div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_permissions">
                <thead>
                    <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0">
                        <th class="min-w-125px">Name</th>
                        <th class="min-w-125px">Guard</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-bold">
                    @foreach ($permissions as $permission)
                    <tr>
                        <td>{{ $permission->name }}</td>
                        <td>
                            <div class="badge badge-light fw-bolder">{{ $permission->guard_name }}</div>
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
    $(document.getElementById('kt_table_permissions')).DataTable({info:!1,order:[],pageLength:10,lengthChange:!1,columnDefs:[{orderable:!1,targets:0}]});
}));
</script>
@endpush
