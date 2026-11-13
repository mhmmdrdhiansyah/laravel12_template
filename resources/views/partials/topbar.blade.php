{{-- Metronic reference: dist/index2.html #kt_header + user menu (metronic-notes.md §1) --}}
<div id="kt_header" class="header align-items-stretch">
    <div class="container-fluid d-flex align-items-stretch justify-content-between">
        <div class="d-flex align-items-center d-lg-none ms-n3 me-1" title="Show aside menu">
            <div class="btn btn-icon btn-active-light-primary w-30px h-30px w-md-40px h-md-40px" id="kt_aside_mobile_toggle">
                <span class="svg-icon svg-icon-2x mt-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M21 7H3C2.4 7 2 6.6 2 6V4C2 3.4 2.4 3 3 3H21C21.6 3 22 3.4 22 4V6C22 6.6 21.6 7 21 7Z" fill="black" />
                        <path opacity="0.3" d="M21 14H3C2.4 14 2 13.6 2 13V11C2 10.4 2.4 10 3 10H21C21.6 10 22 10.4 22 11V13C22 13.6 21.6 14 21 14ZM22 20V18C22 17.4 21.6 17 21 17H3C2.4 17 2 17.4 2 18V20C2 20.6 2.4 21 3 21H21C21.6 21 22 20.6 22 20Z" fill="black" />
                    </svg>
                </span>
            </div>
        </div>
        <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1">
            <div class="d-flex align-items-stretch" id="kt_header_nav">
                <div class="header-menu align-items-stretch" data-kt-drawer="true" data-kt-drawer-name="header-menu" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'200px', '300px': '250px'}" data-kt-drawer-direction="end" data-kt-drawer-toggle="#kt_header_menu_mobile_toggle">
                </div>
            </div>
            <div class="d-flex align-items-center ms-1 ms-lg-3" id="kt_header_user_menu_toggle">
                <div class="cursor-pointer symbol symbol-30px symbol-md-40px" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                    @if (auth()->user()->avatar_path)
                        <img src="{{ Storage::disk('public')->url(auth()->user()->avatar_path) }}" alt="{{ auth()->user()->name }}" />
                    @else
                        <img src="{{ asset('assets/media/avatars/blank.png') }}" alt="{{ auth()->user()->name }}" />
                    @endif
                </div>
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-primary fw-bold py-4 fs-6 w-275px" data-kt-menu="true">
                    <div class="menu-item px-3">
                        <div class="menu-content d-flex align-items-center px-3">
                            <div class="symbol symbol-50px me-5">
                                @if (auth()->user()->avatar_path)
                                    <img alt="{{ auth()->user()->name }}" src="{{ Storage::disk('public')->url(auth()->user()->avatar_path) }}" />
                                @else
                                    <img alt="{{ auth()->user()->name }}" src="{{ asset('assets/media/avatars/blank.png') }}" />
                                @endif
                            </div>
                            <div class="d-flex flex-column">
                                <div class="fw-bolder d-flex align-items-center fs-5">{{ auth()->user()->name }}</div>
                                <a href="{{ route('profile') }}" class="fw-bold text-muted text-hover-primary fs-7">{{ auth()->user()->email }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="separator my-2"></div>
                    <div class="menu-item px-5">
                        <a href="{{ route('profile') }}" class="menu-link px-5">My Profile</a>
                    </div>
                    <div class="menu-item px-5">
                        <form method="POST" action="{{ route('logout') }}" id="kt_logout_form">
                            @csrf
                            <a href="{{ route('logout') }}" class="menu-link px-5" onclick="event.preventDefault(); document.getElementById('kt_logout_form').submit();">Sign Out</a>
                        </form>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center d-lg-none ms-2 me-n3" title="Show header menu">
                <div class="btn btn-icon btn-active-light-primary w-30px h-30px w-md-40px h-md-40px" id="kt_header_menu_mobile_toggle">
                    <span class="svg-icon svg-icon-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M13.5 8H6.5C5.7 8 5 7.3 5 6.5C5 5.7 5.7 5 6.5 5H13.5C14.3 5 15 5.7 15 6.5C15 7.3 14.3 8 13.5 8Z" fill="black" />
                            <path opacity="0.3" d="M17.5 13.5H6.5C5.7 13.5 5 12.8 5 12C5 11.2 5.7 10.5 6.5 10.5H17.5C18.3 10.5 19 11.2 19 12C19 12.8 18.3 13.5 17.5 13.5Z" fill="black" />
                            <path d="M17.5 19H6.5C5.7 19 5 18.3 5 17.5C5 16.7 5.7 16 6.5 16H17.5C18.3 16 19 16.7 19 17.5C19 18.3 18.3 19 17.5 19Z" fill="black" />
                        </svg>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
