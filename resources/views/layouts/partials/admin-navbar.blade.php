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

<nav class="navbar navbar-expand-xl navbar-dark admin-navbar sticky-top">
    <div class="container-fluid admin-navbar__inner">
        <a class="navbar-brand admin-navbar__brand" href="{{ route('admin.dashboard') }}">
            @include('layouts.partials.brand-mark')
        </a>

        <div class="admin-navbar__actions order-2 order-xl-2">
            @if (!empty($canSeeNotifications) || $settingsUrl)
                <div class="admin-navbar__tools">
                    @if (!empty($canSeeNotifications))
                        <div
                            class="dropdown admin-notifications"
                            data-notifications
                            data-index-url="{{ $notificationsIndexUrl }}"
                            data-read-url="{{ $notificationsReadUrl }}"
                            data-destroy-url="{{ $notificationsDestroyUrl }}"
                        >
                            <button
                                class="admin-navbar__icon-link admin-notifications__toggle"
                                type="button"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                data-bs-display="static"
                                aria-expanded="false"
                                title="Notificaciones"
                                aria-label="Notificaciones"
                            >
                                <i class="bi bi-bell"></i>
                                <span class="admin-navbar__icon-label">Avisos</span>
                                <span
                                    class="admin-notifications__badge"
                                    data-notifications-badge
                                    @if(($notificationUnread ?? 0) < 1) hidden @endif
                                >{{ ($notificationUnread ?? 0) > 9 ? '9+' : ($notificationUnread ?? 0) }}</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end admin-notifications__menu">
                                <div class="admin-notifications__head">
                                    <div>
                                        <strong>Notificaciones</strong>
                                        <span class="admin-notifications__kicker">Las leídas se borran a las 24 h</span>
                                    </div>
                                    <div class="admin-notifications__head-actions">
                                        <button type="button" class="btn btn-link btn-sm admin-notifications__read-all js-notifications-read-all">
                                            Marcar leídas
                                        </button>
                                        <button type="button" class="btn btn-link btn-sm admin-notifications__clear-read js-notifications-clear-read" hidden>
                                            Borrar leídas
                                        </button>
                                    </div>
                                </div>
                                <div class="admin-notifications__list js-notifications-list">
                                    <div class="admin-notifications__empty">
                                        <span class="spinner-border spinner-border-sm"></span>
                                        Cargando...
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($settingsUrl)
                        <a
                            class="admin-navbar__icon-link @if($settingsActive) is-active @endif"
                            href="{{ $settingsUrl }}"
                            title="Ajustes"
                            aria-label="Ajustes"
                        >
                            <i class="bi bi-sliders"></i>
                            <span class="admin-navbar__icon-label">Ajustes</span>
                        </a>
                    @endif
                </div>
            @endif

            <div class="dropdown admin-navbar__account">
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

        <button
            class="navbar-toggler border-0 order-3"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#adminNavbar"
            aria-controls="adminNavbar"
            aria-expanded="false"
            aria-label="Alternar navegación"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse order-4 order-xl-1" id="adminNavbar">
            <ul class="navbar-nav admin-navbar__nav me-xl-auto">
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('admin.dashboard')) active @endif" href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-grid-1x2"></i>
                        <span>Panel</span>
                    </a>
                </li>
                @canany(['students.view', 'students.manage', 'students.edit', 'students.delete'])
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.students.*')) active @endif" href="{{ route('admin.students.index') }}">
                            <i class="bi bi-people"></i>
                            <span>Alumnos</span>
                        </a>
                    </li>
                @endcanany
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
                @if (auth()->user()?->can('sales.view') || auth()->user()?->can('expenses.manage') || auth()->user()?->can('reports.view'))
                    <li class="nav-item admin-navbar__split" aria-hidden="true"></li>
                @endif
                @can('sales.view')
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.sales.*')) active @endif" href="{{ route('admin.sales.index') }}">
                            <i class="bi bi-cash-stack"></i>
                            <span>Ingresos</span>
                        </a>
                    </li>
                @endcan
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
        </div>
    </div>
</nav>
