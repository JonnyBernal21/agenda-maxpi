@php
    $instructor = auth('instructor')->user();
    $initials = collect(preg_split('/\s+/', trim((string) $instructor?->fullName())))
        ->filter()
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

<nav class="navbar navbar-expand-lg navbar-dark admin-navbar sticky-top">
    <div class="container-fluid admin-navbar__inner">
        <a class="navbar-brand admin-navbar__brand" href="{{ route('instructor.dashboard') }}">
            @include('layouts.partials.brand-mark')
        </a>
        <button
            class="navbar-toggler border-0"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#instructorNavbar"
            aria-controls="instructorNavbar"
            aria-expanded="false"
            aria-label="Alternar navegación"
        >
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="instructorNavbar">
            <ul class="navbar-nav admin-navbar__nav me-auto">
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('instructor.dashboard')) active @endif" href="{{ route('instructor.dashboard') }}">
                        <i class="bi bi-calendar3"></i>
                        <span>Mis clases</span>
                    </a>
                </li>
            </ul>
            <div class="admin-navbar__actions">
                <div class="dropdown">
                    <button
                        class="admin-navbar__user dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        data-bs-display="static"
                        aria-expanded="false"
                    >
                        <span class="admin-navbar__avatar">{{ $initials ?: 'I' }}</span>
                        <span class="admin-navbar__user-meta">
                            <span class="admin-navbar__user-name">{{ $instructor->fullName() }}</span>
                            <span class="admin-navbar__user-role">Instructor</span>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end admin-navbar__menu">
                        <li>
                            <form method="POST" action="{{ route('instructor.logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2">
                                    <i class="bi bi-box-arrow-right"></i>
                                    Cerrar sesión
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
