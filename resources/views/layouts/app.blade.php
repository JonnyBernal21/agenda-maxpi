<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Agenda MaxPi'))</title>
    @php
        $faviconUrl = ($appSetting ?? null)?->faviconUrl() ?: asset('logo.png');
    @endphp
    <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" type="image/png" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function () {
            var compact = window.matchMedia('(max-width: 1199.98px)');
            var sync = function () {
                document.documentElement.classList.toggle('nav-compact', compact.matches);
            };
            sync();
            if (compact.addEventListener) {
                compact.addEventListener('change', sync);
            }
        })();
    </script>
    <style>
        html.nav-compact .admin-navbar .navbar-collapse {
            flex-basis: 100% !important;
            flex-grow: 1 !important;
            width: 100% !important;
            max-width: 100% !important;
            align-items: stretch !important;
            order: 4;
        }
        html.nav-compact .admin-navbar .navbar-nav.admin-navbar__nav {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            align-items: stretch !important;
            justify-content: stretch !important;
            width: 100% !important;
            margin: 0 !important;
            gap: 0.5rem;
            padding: 0.35rem 0 0.9rem;
        }
        html.nav-compact .admin-navbar .navbar-nav:has(> :only-child) {
            grid-template-columns: 1fr !important;
        }
        html.nav-compact .admin-navbar .navbar-nav .nav-item {
            display: flex;
            min-width: 0;
        }
        html.nav-compact .admin-navbar .nav-link {
            display: flex !important;
            width: 100% !important;
            min-height: 2.85rem;
            justify-content: flex-start;
            align-items: center;
            padding: 0.7rem 0.85rem !important;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        html.nav-compact .admin-navbar .nav-link.active {
            background: rgba(255, 255, 255, 0.14);
            border-color: rgba(255, 255, 255, 0.12);
        }
        html.nav-compact .admin-navbar__split {
            grid-column: 1 / -1;
            width: 100% !important;
            height: 1px !important;
            margin: 0.1rem 0 !important;
        }
        html.nav-compact .admin-navbar__brand {
            margin-right: auto;
            order: 1;
        }
        html.nav-compact .admin-navbar__actions {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: flex-end !important;
            width: auto !important;
            margin: 0 0.15rem 0 0.25rem !important;
            padding: 0 !important;
            border: 0 !important;
            gap: 0.2rem;
            order: 2;
        }
        html.nav-compact .admin-navbar__tools {
            display: flex !important;
            gap: 0.15rem;
            width: auto;
        }
        html.nav-compact .admin-navbar__icon-link {
            width: 2.15rem !important;
            height: 2.15rem !important;
        }
        html.nav-compact .admin-navbar__icon-label {
            display: none !important;
        }
        html.nav-compact .admin-navbar,
        html.nav-compact .admin-navbar__inner,
        html.nav-compact .admin-navbar__actions,
        html.nav-compact .admin-navbar__tools {
            overflow: visible !important;
        }
        html.nav-compact .admin-navbar__account,
        html.nav-compact .admin-navbar__user,
        html.nav-compact .admin-notifications {
            overflow: visible !important;
            width: auto !important;
            max-width: none !important;
        }
        html.nav-compact .admin-navbar .navbar-toggler {
            order: 3;
        }
        html.nav-compact .admin-notifications .admin-notifications__menu {
            position: fixed !important;
            top: 4.35rem !important;
            right: 0.7rem !important;
            left: auto !important;
            width: min(24.5rem, calc(100vw - 1.4rem)) !important;
            max-width: calc(100vw - 1.4rem) !important;
            max-height: calc(100dvh - 5.2rem);
            margin: 0 !important;
            transform: none !important;
            z-index: 1080;
        }
        @media (max-width: 575.98px) {
            html.nav-compact .admin-navbar__user-meta {
                display: none;
            }
            html.nav-compact .admin-navbar__user {
                padding: 0.12rem;
            }
            html.nav-compact .admin-navbar__user::after {
                display: none;
            }
            html.nav-compact .admin-notifications .admin-notifications__menu {
                top: 4.2rem !important;
                right: 0.55rem !important;
                left: 0.55rem !important;
                width: auto !important;
                max-width: none !important;
            }
        }
        @media (min-width: 1200px) {
            .admin-navbar .navbar-collapse {
                order: 2 !important;
                flex-grow: 1 !important;
            }
            .admin-navbar__actions {
                order: 3 !important;
                margin-left: 0.35rem !important;
            }
            .admin-navbar__brand {
                order: 1 !important;
                margin-right: 1.25rem;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="@yield('body-class', 'bg-light')">
    @yield('body')
    @stack('scripts')
</body>
</html>
