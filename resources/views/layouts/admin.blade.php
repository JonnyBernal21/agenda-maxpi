@extends('layouts.app')

@section('body-class', 'admin-body')

@section('body')
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="{{ route('admin.dashboard') }}">
                <i class="bi bi-calendar2-week"></i>
                Agenda MaxPi
            </a>
            <button
                class="navbar-toggler border-0"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#adminNavbar"
                aria-controls="adminNavbar"
                aria-expanded="false"
                aria-label="Alternar navegación"
            >
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2 @if(request()->routeIs('admin.dashboard')) active @endif" href="{{ route('admin.dashboard') }}">
                            <i class="bi bi-grid-1x2"></i>
                            Panel
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2 @if(request()->routeIs('admin.students.*')) active @endif" href="{{ route('admin.students.index') }}">
                            <i class="bi bi-people"></i>
                            Alumnos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2 @if(request()->routeIs('admin.instructors.*')) active @endif" href="{{ route('admin.instructors.index') }}">
                            <i class="bi bi-person-badge"></i>
                            Instructores
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2 @if(request()->routeIs('admin.vehicles.*')) active @endif" href="{{ route('admin.vehicles.index') }}">
                            <i class="bi bi-car-front"></i>
                            Vehículos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2 @if(request()->routeIs('admin.reports.*')) active @endif" href="{{ route('admin.reports.index') }}">
                            <i class="bi bi-bar-chart-line"></i>
                            Reportes
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <span class="navbar-text d-none d-sm-inline d-flex align-items-center gap-2">
                        <i class="bi bi-person-circle"></i>
                        {{ auth()->user()->name }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-light d-flex align-items-center gap-1">
                            <i class="bi bi-box-arrow-right"></i>
                            Salir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4 py-md-5">
        @if (session('success'))
            <div
                id="app-flash"
                data-type="success"
                data-message="{{ session('success') }}"
                hidden
            ></div>
        @endif

        @yield('content')
    </main>

    @include('admin.partials.add-student-modal')
    @include('admin.partials.add-instructor-modal')
    @include('admin.partials.add-vehicle-modal')
    @include('admin.partials.schedule-class-modal')
@endsection

@push('scripts')
    @vite(['resources/js/admin-panel.js', 'resources/js/admin-reservas.js', 'resources/js/flash-alerts.js'])
@endpush
