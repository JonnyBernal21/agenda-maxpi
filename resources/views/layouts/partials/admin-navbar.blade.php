@php
    $user = auth()->user();
    $initials = collect(preg_split('/\s+/', trim((string) $user?->name)))
        ->filter()
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');
    $settingsUrl = match (true) {
        $user?->can('settings.edit') => route('admin.settings.index'),
        $user?->can('users.manage') => route('admin.settings.users'),
        $user?->can('emails.view') => route('admin.emails.index'),
        $user?->can('permissions.manage') => route('admin.settings.permissions'),
        default => null,
    };
    $settingsActive = request()->routeIs('admin.settings.*')
        || request()->routeIs('admin.emails.*')
        || request()->routeIs('admin.users.*');
@endphp

<nav class="navbar navbar-expand-lg navbar-dark admin-navbar sticky-top">
    <div class="container-fluid admin-navbar__inner">
        <a class="navbar-brand admin-navbar__brand" href="{{ route('admin.dashboard') }}">
            @include('layouts.partials.brand-mark')
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
            <ul class="navbar-nav admin-navbar__nav me-auto">
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('admin.dashboard')) active @endif" href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-grid-1x2"></i>
                        <span>Panel</span>
                    </a>
                </li>
                @can('students.manage')
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.students.*')) active @endif" href="{{ route('admin.students.index') }}">
                            <i class="bi bi-people"></i>
                            <span>Alumnos</span>
                        </a>
                    </li>
                @endcan
                @can('instructors.manage')
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.instructors.*')) active @endif" href="{{ route('admin.instructors.index') }}">
                            <i class="bi bi-person-badge"></i>
                            <span>Instructores</span>
                        </a>
                    </li>
                @endcan
                @can('courses.manage')
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.courses.*')) active @endif" href="{{ route('admin.courses.index') }}">
                            <i class="bi bi-journal-text"></i>
                            <span>Cursos</span>
                        </a>
                    </li>
                @endcan
                @can('vehicles.manage')
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.vehicles.*')) active @endif" href="{{ route('admin.vehicles.index') }}">
                            <i class="bi bi-car-front"></i>
                            <span>Vehículos</span>
                        </a>
                    </li>
                @endcan
                @if (auth()->user()?->can('expenses.manage') || auth()->user()?->can('reports.view'))
                    <li class="nav-item admin-navbar__split" aria-hidden="true"></li>
                @endif
                @can('expenses.manage')
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.expenses.*')) active @endif" href="{{ route('admin.expenses.index') }}">
                            <i class="bi bi-receipt"></i>
                            <span>Gastos</span>
                        </a>
                    </li>
                @endcan
                @can('reports.view')
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.reports.*')) active @endif" href="{{ route('admin.reports.index') }}">
                            <i class="bi bi-bar-chart-line"></i>
                            <span>Reportes</span>
                        </a>
                    </li>
                @endcan
            </ul>

            <div class="admin-navbar__actions">
                @if ($settingsUrl)
                    <a
                        class="admin-navbar__icon-link @if($settingsActive) is-active @endif"
                        href="{{ $settingsUrl }}"
                        title="Ajustes"
                        aria-label="Ajustes"
                    >
                        <i class="bi bi-sliders"></i>
                    </a>
                @endif

                <div class="dropdown">
                    <button
                        class="admin-navbar__user dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        data-bs-display="static"
                        aria-expanded="false"
                    >
                        <span class="admin-navbar__avatar">{{ $initials ?: 'U' }}</span>
                        <span class="admin-navbar__user-meta">
                            <span class="admin-navbar__user-name">{{ $user->name }}</span>
                            @if ($user->role?->name)
                                <span class="admin-navbar__user-role">{{ $user->role->name }}</span>
                            @endif
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end admin-navbar__menu">
                        @if ($settingsUrl)
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ $settingsUrl }}">
                                    <i class="bi bi-sliders"></i>
                                    Ajustes
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                        @endif
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
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
