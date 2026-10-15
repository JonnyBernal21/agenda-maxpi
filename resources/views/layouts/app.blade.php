<!DOCTYPE html>
@php
    $themeKey = ($appSetting ?? null)?->theme() ?: 'maxpi';
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $themeKey }}" data-scheme="{{ \App\Support\AppTheme::scheme($themeKey) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Agenda MaxPi'))</title>
    @php
        $faviconUrl = ($appSetting ?? null)?->faviconUrl() ?: '/logo.png';
        $themeColor = ($appSetting ?? null)?->themeTokens()['nav'] ?? '#201e1f';
        $appName = ($appSetting ?? null)?->companyName() ?: config('app.name', 'Agenda MaxPi');
    @endphp
    <meta name="theme-color" content="{{ $themeColor }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ $appName }}">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" type="image/png" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $faviconUrl }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function () {
            try {
                if (window.innerWidth > 991.98 && window.localStorage.getItem('app-sidebar') === 'collapsed') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {}
        })();
    </script>
    @stack('styles')
</head>
<body class="@yield('body-class', 'bg-light')">
    @yield('body')
    @stack('scripts')
</body>
</html>
