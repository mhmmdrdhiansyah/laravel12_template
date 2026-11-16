{{-- Metronic reference: metronic-notes.md §2 (index2.html stat cards) — auth-only view --}}
@extends('layouts.admin')

@section('title', 'Dashboard')
    @section('page-title')
    <div class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="fw-bolder my-2">Dashboard</h1>
    </div>
@endsection

@section('content')
    <div class="row g-5 g-xl-8">
        <div class="col-xl-6">
            <div class="card mb-5 mb-xxl-8">
                <div class="card-body pt-9 pb-6">
                    <div class="d-flex flex-wrap align-items-center">
                        <div class="me-7">
                            <span class="svg-icon svg-icon-2x svg-icon-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M17.5 11H6.5C4 11 2 9 2 6.5C2 4 4 2 6.5 2H17.5C20 2 22 4 22 6.5C22 9 20 11 17.5 11ZM15 6.5C15 7.9 16.1 9 17.5 9C18.9 9 20 7.9 20 6.5C20 5.1 18.9 4 17.5 4C16.1 4 15 5.1 15 6.5Z" fill="black" />
                                    <path opacity="0.3" d="M17.5 22H6.5C4 22 2 20 2 17.5C2 15 4 13 6.5 13H17.5C20 13 22 15 22 17.5C22 20 20 22 17.5 22ZM4 17.5C4 18.9 5.1 20 6.5 20C7.9 20 9 18.9 9 17.5C9 16.1 7.9 15 6.5 15C5.1 15 4 16.1 4 17.5Z" fill="black" />
                                </svg>
                            </span>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fs-2 fw-bolder text-dark">{{ $userCount }}</span>
                            <span class="fs-6 text-muted fw-bold">Users</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card mb-5 mb-xxl-8">
                <div class="card-body pt-9 pb-6">
                    <div class="d-flex flex-wrap align-items-center">
                        <div class="me-7">
                            <span class="svg-icon svg-icon-2x svg-icon-success">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <rect opacity="0.3" x="2" y="2" width="20" height="20" rx="10" fill="black" />
                                    <rect x="11" y="14" width="7" height="2" rx="1" transform="rotate(-90 11 14)" fill="black" />
                                    <rect x="11" y="17" width="2" height="2" rx="1" transform="rotate(-90 11 17)" fill="black" />
                                </svg>
                            </span>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fs-2 fw-bolder text-dark">{{ $roleCount }}</span>
                            <span class="fs-6 text-muted fw-bold">Roles</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
