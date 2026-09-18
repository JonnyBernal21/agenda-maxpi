@php
    $isAdminRole = $selected?->isAdmin() ?? false;
@endphp

<form method="POST" action="{{ route('admin.settings.permissions.update') }}" id="rolePermissionsForm">
    @csrf
    @method('PUT')
    <input type="hidden" name="role_id" value="{{ $selected?->id }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <p class="small text-muted mb-0">Los cambios se aplican al rol seleccionado.</p>
        <button type="submit" class="btn btn-brand d-inline-flex align-items-center gap-2" @disabled($isAdminRole)>
            <i class="bi bi-check-lg"></i>
            Guardar permisos
        </button>
    </div>

    <div class="row g-4">
        <div class="col-lg-4 col-xl-3">
            <div class="permission-roles">
                <p class="fw-semibold mb-1">Roles</p>
                <p class="small text-muted mb-3">Selecciona un rol para editar sus permisos.</p>
                <div class="list-group">
                    @foreach ($roles as $role)
                        <a
                            href="{{ route('admin.settings.permissions', ['role' => $role->slug]) }}"
                            class="list-group-item list-group-item-action permission-role @if ($selected?->id === $role->id) is-active @endif"
                        >
                            <span>{{ $role->name }}</span>
                            <span class="permission-role__count">{{ $role->users_count }}</span>
                        </a>
                    @endforeach
                </div>
                <p class="small text-muted mt-3 mb-0">
                    El rol Admin siempre tiene acceso completo en el sistema.
                </p>
            </div>
        </div>

        <div class="col-lg-8 col-xl-9">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h6 fw-semibold mb-0">
                    Permisos — <span class="text-primary">{{ $selected?->name }}</span>
                </h2>
                @unless ($isAdminRole)
                    <div class="btn-group">
                        <button type="button" class="btn btn-brand-outline btn-sm" id="permissionCheckAll">Marcar todos</button>
                        <button type="button" class="btn btn-brand-outline btn-sm" id="permissionClearAll">Quitar todos</button>
                    </div>
                @endunless
            </div>

            @foreach ($groups as $group => $permissions)
                @php
                    $groupKeys = array_keys($permissions);
                    $groupChecked = collect($groupKeys)->every(fn ($key) => in_array($key, $selectedKeys, true));
                @endphp
                <div class="permission-group mb-3">
                    <label class="permission-group__header">
                        <input
                            type="checkbox"
                            class="form-check-input js-permission-group"
                            @checked($groupChecked || $isAdminRole)
                            @disabled($isAdminRole)
                        >
                        <span>{{ $group }}</span>
                    </label>
                    <div class="permission-group__body">
                        @foreach ($permissions as $key => $label)
                            <label class="permission-item">
                                <span class="d-flex align-items-center gap-2">
                                    <input
                                        type="checkbox"
                                        class="form-check-input js-permission-item"
                                        name="permissions[]"
                                        value="{{ $key }}"
                                        @checked(in_array($key, $selectedKeys, true) || $isAdminRole)
                                        @disabled($isAdminRole)
                                    >
                                    {{ $label }}
                                </span>
                                <span class="permission-item__key">{{ $key }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</form>
