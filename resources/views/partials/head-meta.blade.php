{{-- Metronic reference: dist/index2.html <head> (asset order per metronic-notes.md §0) --}}
<meta charset="utf-8" />
<title>@yield('title', config('app.name', 'Admin'))</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
<link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
