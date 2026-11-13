{{-- Metronic reference: dist/index2.html body shell (metronic-notes.md §1) --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head-meta')
</head>
<body id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
    <div class="d-flex flex-column flex-root">
        <div class="page d-flex flex-row flex-column-fluid">
            <div id="kt_aside" class="aside aside-dark aside-hoverable" data-kt-drawer="true" data-kt-drawer-name="aside" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'200px', '300px': '250px'}" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_aside_mobile_toggle">
                @include('partials.sidebar')
            </div>
            <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
                @include('partials.topbar')
                <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
                    <div class="content d-flex flex-column flex-column-fluid" id="kt_content_inner">
                        <div class="container-xxl" id="kt_content_container">
                            @yield('page-title')
                            @yield('content')
                        </div>
                    </div>
                </div>
                <div class="footer py-4 d-flex flex-lg-column" id="kt_footer">
                    <div class="container-fluid d-flex flex-column flex-md-row align-items-center justify-content-between">
                        <div class="text-dark order-2 order-md-1">
                            <span class="text-muted fw-bold me-1">{{ date('Y') }}©</span>
                            <a href="{{ url('/admin') }}" class="text-gray-800 text-hover-primary">{{ config('app.name', 'Admin') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
        <span class="svg-icon svg-icon-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path opacity="0.5" d="M13.5448 13.5L12.4999 12.4551L11.455 13.5L12.4999 14.5449L13.5448 13.5ZM12.4999 5.99997C11.9476 5.99997 11.4999 6.44769 11.4999 6.99997L11.4999 12.4551H13.4999L13.4999 6.99997C13.4999 6.44769 13.0522 5.99997 12.4999 5.99997Z" fill="black" />
                <path d="M12.5 6.5L12.5 13.5L9.5 10.5L12.5 6.5Z" fill="black" />
                <path d="M12.5 6.5L12.5 13.5L15.5 10.5L12.5 6.5Z" fill="black" />
            </svg>
        </span>
    </div>
    @include('partials.footer-scripts')
</body>
</html>
