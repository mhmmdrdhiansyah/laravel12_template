{{-- Metronic reference: dist/index2.html script order (metronic-notes.md §0) --}}
<script>var hostUrl = "{{ asset('assets/') }}";</script>
<script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
@stack('vendor-scripts')
<script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
@stack('scripts')
