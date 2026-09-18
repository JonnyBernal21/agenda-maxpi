@php
    $tab = $tab ?? 'ajustes';
    $setting = $appSetting ?? null;
@endphp

<div class="settings-shell mb-4">
    <div class="settings-shell__header">
        <h1 class="settings-shell__title">
            <i class="bi bi-sliders"></i>
            Ajustes
        </h1>
        <span class="settings-shell__user">
            <i class="bi bi-person-circle"></i>
            {{ auth()->user()->name }}
        </span>
    </div>

    <div class="settings-shell__tabs" role="tablist">
        @can('settings.edit')
            <a
                href="{{ route('admin.settings.index') }}"
                class="settings-shell__tab @if ($tab === 'ajustes') is-active @endif"
            >
                <i class="bi bi-sliders"></i>
                Ajustes
            </a>
        @endcan
        @can('users.manage')
            <a
                href="{{ route('admin.settings.users') }}"
                class="settings-shell__tab @if ($tab === 'usuarios') is-active @endif"
            >
                <i class="bi bi-people"></i>
                Usuarios
            </a>
        @endcan
        @can('emails.view')
            <a
                href="{{ route('admin.emails.index') }}"
                class="settings-shell__tab @if ($tab === 'correos') is-active @endif"
            >
                <i class="bi bi-envelope"></i>
                Correos
            </a>
        @endcan
        @can('permissions.manage')
            <a
                href="{{ route('admin.settings.permissions') }}"
                class="settings-shell__tab @if ($tab === 'permisos') is-active @endif"
            >
                <i class="bi bi-shield-check"></i>
                Permisos por Rol
            </a>
        @endcan
    </div>

    @isset($slot)
        <div class="settings-shell__body">
            {!! $slot !!}
        </div>
    @endisset
</div>
