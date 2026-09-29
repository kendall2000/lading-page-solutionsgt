@php $cfgSitio = \App\Support\Sitio::config(); @endphp
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ isset($titulo) ? $titulo.' · ' : '' }}{{ \App\Support\Sitio::nombre() }}</title>

@if ($cfgSitio->favicon)
    <link rel="icon" href="{{ $cfgSitio->url('favicon') }}">
    <link rel="apple-touch-icon" href="{{ $cfgSitio->url('favicon') }}">
@else
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/favicons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicons/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/favicons/favicon-16x16.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/img/favicons/favicon.ico') }}">
@endif
<meta name="theme-color" content="{{ $cfgSitio->color_primario ?: '#3874ff' }}">

<script src="{{ asset('vendors/imagesloaded/imagesloaded.pkgd.min.js') }}"></script>
<script src="{{ asset('vendors/simplebar/simplebar.min.js') }}"></script>
<script src="{{ asset('assets/js/config.js') }}"></script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
<link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&amp;display=swap" rel="stylesheet">
<link href="{{ asset('vendors/simplebar/simplebar.min.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
<link href="{{ asset('assets/css/theme.min.css') }}" type="text/css" rel="stylesheet" id="style-default">
<link href="{{ asset('assets/css/user.min.css') }}" type="text/css" rel="stylesheet" id="user-style-default">
@include('partials.colores')
@stack('estilos')
