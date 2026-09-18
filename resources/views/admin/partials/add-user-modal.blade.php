<div
    class="modal fade"
    id="addUserModal"
    tabindex="-1"
    aria-labelledby="addUserModalLabel"
    aria-hidden="true"
    data-store-url="{{ route('admin.users.store') }}"
    data-update-base="{{ url('admin/users') }}"
    data-editing-id="{{ old('_form') === 'user-edit' ? old('editing_id') : '' }}"
    data-auto-open="{{ ($errors->any() && in_array(old('_form'), ['user', 'user-edit'], true)) ? 'true' : 'false' }}"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.users.store') }}" class="modal-form-layout" id="userAdminForm">
                @csrf
                <input type="hidden" name="_method" id="userFormSpoofMethod" value="PUT" disabled>
                <input type="hidden" name="_form" id="userFormType" value="{{ old('_form', 'user') }}">
                <input type="hidden" name="editing_id" id="userEditingId" value="{{ old('editing_id') }}">

                <div class="modal-header">
                    <h5 class="modal-title fw-semibold d-flex align-items-center" id="addUserModalLabel">
                        <span class="modal-title-icon"><i class="bi bi-person-plus" id="userFormIcon"></i></span>
                        <span id="userFormTitle">Agregar usuario</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    @if ($errors->any() && in_array(old('_form'), ['user', 'user-edit'], true))
                        <div class="alert alert-danger" role="alert" id="userFormErrorAlert">
                            Revisa los campos marcados e intenta de nuevo.
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="user_name" class="form-label">Nombre</label>
                            <input
                                type="text"
                                id="user_name"
                                name="name"
                                value="{{ old('name') }}"
                                class="form-control @error('name') is-invalid @enderror"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="user_email" class="form-label">Correo electrónico</label>
                            <input
                                type="email"
                                id="user_email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control @error('email') is-invalid @enderror"
                                required
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="user_role_id" class="form-label">Rol</label>
                            <select
                                id="user_role_id"
                                name="role_id"
                                class="form-select @error('role_id') is-invalid @enderror"
                                required
                            >
                                <option value="">Seleccionar rol</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>{{ $role->name }}</option>
                                @endforeach
                            </select>
                            @error('role_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="user_password" class="form-label">Contraseña</label>
                            <input
                                type="password"
                                id="user_password"
                                name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                autocomplete="new-password"
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <p class="small text-muted mt-1 mb-0 d-none" id="userPasswordHint">Déjala vacía para no cambiarla.</p>
                        </div>

                        <div class="col-md-6">
                            <label for="user_password_confirmation" class="form-label">Confirmar</label>
                            <input
                                type="password"
                                id="user_password_confirmation"
                                name="password_confirmation"
                                class="form-control"
                                autocomplete="new-password"
                            >
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand d-flex align-items-center gap-2" id="userFormSubmit">
                        <i class="bi bi-check-lg"></i>
                        <span id="userFormSubmitLabel">Guardar usuario</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
