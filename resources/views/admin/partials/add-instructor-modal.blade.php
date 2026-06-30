<div
    class="modal fade"
    id="addInstructorModal"
    tabindex="-1"
    aria-labelledby="addInstructorModalLabel"
    aria-hidden="true"
    data-auto-open="{{ ($errors->any() && old('_form') === 'instructor') ? 'true' : 'false' }}"
>
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.instructors.store') }}" class="modal-form-layout">
                @csrf
                <input type="hidden" name="_form" value="instructor">

                <div class="modal-header">
                    <h5 class="modal-title fw-semibold d-flex align-items-center" id="addInstructorModalLabel">
                        <span class="modal-title-icon"><i class="bi bi-person-badge"></i></span>
                        Agregar instructor
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    @if ($errors->any() && old('_form') === 'instructor')
                        <div class="alert alert-danger" role="alert">
                            Revisa los campos marcados e intenta de nuevo.
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="instructor_name" class="form-label">Nombre</label>
                            <input
                                type="text"
                                id="instructor_name"
                                name="name"
                                value="{{ old('name') }}"
                                class="form-control @error('name') is-invalid @enderror"
                                placeholder="Ej. Carlos"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="instructor_last_name" class="form-label">Apellido</label>
                            <input
                                type="text"
                                id="instructor_last_name"
                                name="last_name"
                                value="{{ old('last_name') }}"
                                class="form-control @error('last_name') is-invalid @enderror"
                                placeholder="Ej. Méndez López"
                                required
                            >
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="instructor_email" class="form-label">Correo electrónico</label>
                            <input
                                type="email"
                                id="instructor_email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="instructor@correo.com"
                                required
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="instructor_phone" class="form-label">Teléfono</label>
                            <input
                                type="text"
                                id="instructor_phone"
                                name="phone"
                                value="{{ old('phone') }}"
                                class="form-control @error('phone') is-invalid @enderror"
                                placeholder="+52 55 1234 5678"
                                required
                            >
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="instructor_address" class="form-label">Dirección</label>
                            <input
                                type="text"
                                id="instructor_address"
                                name="address"
                                value="{{ old('address') }}"
                                class="form-control @error('address') is-invalid @enderror"
                                placeholder="Calle, número, colonia"
                                required
                            >
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="instructor_city" class="form-label">Ciudad</label>
                            <input
                                type="text"
                                id="instructor_city"
                                name="city"
                                value="{{ old('city') }}"
                                class="form-control @error('city') is-invalid @enderror"
                                placeholder="Ciudad de México"
                                required
                            >
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="instructor_state" class="form-label">Estado</label>
                            <input
                                type="text"
                                id="instructor_state"
                                name="state"
                                value="{{ old('state') }}"
                                class="form-control @error('state') is-invalid @enderror"
                                placeholder="CDMX"
                                required
                            >
                            @error('state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="instructor_zip" class="form-label">Código postal</label>
                            <input
                                type="text"
                                id="instructor_zip"
                                name="zip"
                                value="{{ old('zip') }}"
                                class="form-control @error('zip') is-invalid @enderror"
                                placeholder="01000"
                                required
                            >
                            @error('zip')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="instructor_country" class="form-label">País</label>
                            <input
                                type="text"
                                id="instructor_country"
                                name="country"
                                value="{{ old('country', 'México') }}"
                                class="form-control @error('country') is-invalid @enderror"
                                placeholder="México"
                                required
                            >
                            @error('country')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand d-flex align-items-center gap-2">
                        <i class="bi bi-check-lg"></i>
                        Guardar instructor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
