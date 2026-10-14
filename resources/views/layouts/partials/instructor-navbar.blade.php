@php
    $instructor = auth('instructor')->user();
    $photoUrl = $instructor?->photoUrl();
    $initials = collect(preg_split('/\s+/', trim((string) $instructor?->fullName())))
        ->filter()
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

<aside class="app-sidebar" id="appSidebar">
    <div class="app-sidebar__brand">
        <a class="app-sidebar__brand-link" href="{{ route('instructor.dashboard') }}">
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
        <a class="app-sidebar__link @if(request()->routeIs('instructor.dashboard')) is-active @endif" href="{{ route('instructor.dashboard') }}" title="Mis clases">
            <i class="bi bi-calendar3"></i>
            <span>Mis clases</span>
        </a>
    </nav>

    <div class="dropdown dropup app-sidebar__account">
        <button
            class="app-sidebar__user"
            type="button"
            data-bs-toggle="dropdown"
            data-bs-offset="0,10"
            aria-expanded="false"
            title="{{ $instructor->fullName() }}"
        >
            <span class="app-sidebar__avatar">
                @if ($photoUrl)
                    <img src="{{ $photoUrl }}" alt="">
                @else
                    {{ $initials ?: 'I' }}
                @endif
            </span>
            <span class="app-sidebar__user-meta">
                <span class="app-sidebar__user-name">{{ $instructor->fullName() }}</span>
                <span class="app-sidebar__user-role">Instructor</span>
            </span>
            <i class="bi bi-chevron-up app-sidebar__user-caret" aria-hidden="true"></i>
        </button>
        <ul class="dropdown-menu app-sidebar__menu">
            <li>
                <form method="POST" action="{{ route('instructor.logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item">
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
        <a class="app-topbar__brand d-lg-none" href="{{ route('instructor.dashboard') }}">
            @include('layouts.partials.brand-mark')
        </a>
        <div class="admin-navbar__actions ms-auto">
            <div class="dropdown admin-navbar__account">
                <button
                    class="admin-navbar__user dropdown-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >
                    <span class="admin-navbar__avatar">
                        @if ($photoUrl)
                            <img src="{{ $photoUrl }}" alt="">
                        @else
                            {{ $initials ?: 'I' }}
                        @endif
                    </span>
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
</header>
