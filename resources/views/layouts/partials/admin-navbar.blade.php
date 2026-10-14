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
    $showFinance = $user?->can('sales.view') || $user?->can('expenses.manage') || $user?->can('reports.view');
@endphp

<aside class="app-sidebar" id="appSidebar">
    <div class="app-sidebar__brand">
        <a class="app-sidebar__brand-link" href="{{ route('admin.dashboard') }}">
            @include('layouts.partials.brand-mark')
        </a>
        <button
            type="button"
            class="btn-close btn-close-white d-lg-none"
            data-sidebar-close
            aria-label="Cerrar menú"
        ></button>
    </div>
    <nav class="app-sidebar__nav d-flex flex-column" aria-label="Menú principal">
        <a class="app-sidebar__link @if(request()->routeIs('admin.dashboard')) is-active @endif" href="{{ route('admin.dashboard') }}" title="Panel">
            <i class="bi bi-grid-1x2"></i>
            <span>Panel</span>
        </a>
        @canany(['students.view', 'students.manage', 'students.edit', 'students.delete'])
            <a class="app-sidebar__link @if(request()->routeIs('admin.students.*')) is-active @endif" href="{{ route('admin.students.index') }}" title="Alumnos">
                <i class="bi bi-people"></i>
                <span>Alumnos</span>
            </a>
        @endcanany
        @can('instructors.manage')
            <a class="app-sidebar__link @if(request()->routeIs('admin.instructors.*')) is-active @endif" href="{{ route('admin.instructors.index') }}" title="Instructores">
                <i class="bi bi-person-badge"></i>
                <span>Instructores</span>
            </a>
        @endcan
        @can('courses.manage')
            <a class="app-sidebar__link @if(request()->routeIs('admin.courses.*')) is-active @endif" href="{{ route('admin.courses.index') }}" title="Cursos">
                <i class="bi bi-journal-text"></i>
                <span>Cursos</span>
            </a>
        @endcan
        @can('vehicles.manage')
            <a class="app-sidebar__link @if(request()->routeIs('admin.vehicles.*')) is-active @endif" href="{{ route('admin.vehicles.index') }}" title="Vehículos">
                <i class="bi bi-car-front"></i>
                <span>Vehículos</span>
            </a>
        @endcan
        @if ($showFinance)
            <span class="app-sidebar__split" aria-hidden="true"></span>
        @endif
        @can('sales.view')
            <a class="app-sidebar__link @if(request()->routeIs('admin.sales.*')) is-active @endif" href="{{ route('admin.sales.index') }}" title="Ingresos">
                <i class="bi bi-cash-stack"></i>
                <span>Ingresos</span>
            </a>
        @endcan
        @can('expenses.manage')
            <a class="app-sidebar__link @if(request()->routeIs('admin.expenses.*')) is-active @endif" href="{{ route('admin.expenses.index') }}" title="Gastos">
                <i class="bi bi-receipt"></i>
                <span>Gastos</span>
            </a>
        @endcan
        @can('reports.view')
            <a class="app-sidebar__link @if(request()->routeIs('admin.reports.*')) is-active @endif" href="{{ route('admin.reports.index') }}" title="Reportes">
                <i class="bi bi-bar-chart-line"></i>
                <span>Reportes</span>
            </a>
        @endcan
    </nav>

    <div class="dropdown dropup app-sidebar__account">
        <button
            class="app-sidebar__user"
            type="button"
            data-bs-toggle="dropdown"
            data-bs-offset="0,10"
            aria-expanded="false"
            title="{{ $user->name }}"
        >
            <span class="app-sidebar__avatar">{{ $initials ?: 'U' }}</span>
            <span class="app-sidebar__user-meta">
                <span class="app-sidebar__user-name">{{ $user->name }}</span>
                @if ($user->role?->name)
                    <span class="app-sidebar__user-role">{{ $user->role->name }}</span>
                @endif
            </span>
            <i class="bi bi-chevron-up app-sidebar__user-caret" aria-hidden="true"></i>
        </button>
        <ul class="dropdown-menu app-sidebar__menu">
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
</aside>
<div class="app-sidebar-backdrop d-lg-none" data-sidebar-close></div>

<header class="app-topbar navbar navbar-dark">
    <div class="container-fluid flex-nowrap gap-2">
        <button
            type="button"
            class="app-topbar__toggle"
            data-sidebar-toggle
            aria-controls="appSidebar"
            aria-expanded="false"
            aria-label="Abrir o cerrar menú"
            title="Menú"
        >
            <i class="bi bi-list"></i>
        </button>
        <a class="app-topbar__brand d-lg-none" href="{{ route('admin.dashboard') }}">
            @include('layouts.partials.brand-mark')
        </a>
        <div class="admin-navbar__actions ms-auto">
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
</header>
