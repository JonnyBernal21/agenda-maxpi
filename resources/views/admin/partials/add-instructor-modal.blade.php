<div
    class="modal fade"
    id="addInstructorModal"
    tabindex="-1"
    aria-labelledby="addInstructorModalLabel"
    aria-hidden="true"
    data-store-url="{{ route('admin.instructors.store') }}"
    data-update-base="{{ url('admin/instructors') }}"
    data-editing-id="{{ old('_form') === 'instructor-edit' ? old('editing_id') : '' }}"
    data-auto-open="{{ ($errors->any() && in_array(old('_form'), ['instructor', 'instructor-edit'], true)) ? 'true' : 'false' }}"
>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.instructors.store') }}" class="modal-form-layout" enctype="multipart/form-data" id="instructorAdminForm">
                @csrf
                <input type="hidden" name="_method" id="instructorFormSpoofMethod" value="PUT" disabled>
                <input type="hidden" name="_form" id="instructorFormType" value="{{ old('_form', 'instructor') }}">
                <input type="hidden" name="editing_id" id="instructorEditingId" value="{{ old('editing_id') }}">

                <div class="modal-header">
                    <h5 class="modal-title fw-semibold d-flex align-items-center" id="addInstructorModalLabel">
                        <span class="modal-title-icon"><i class="bi bi-person-badge" id="instructorFormIcon"></i></span>
                        <span id="instructorFormTitle">Agregar instructor</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    @if ($errors->any() && in_array(old('_form'), ['instructor', 'instructor-edit'], true))
                        <div class="alert alert-danger" role="alert" id="instructorFormErrorAlert">
                            <p class="mb-1 fw-semibold">No se pudo guardar. Motivo:</p>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <p class="small text-muted mb-3 d-none" id="instructorFormHint">
                        En edición los documentos actuales se conservan si no subes uno nuevo.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="instructor_name" class="form-label">Nombre</label>
                            <input
                                type="text"
                                id="instructor_name"
                                name="name"
                                value="{{ old('name') }}"
                                class="form-control input-uppercase @error('name') is-invalid @enderror"
                                placeholder="EJ. CARLOS"
                                autocapitalize="characters"
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
                                class="form-control input-uppercase @error('last_name') is-invalid @enderror"
                                placeholder="EJ. MÉNDEZ LÓPEZ"
                                autocapitalize="characters"
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
                                class="form-control input-uppercase @error('address') is-invalid @enderror"
                                placeholder="CALLE, NÚMERO, COLONIA"
                                autocapitalize="characters"
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
                                class="form-control input-uppercase @error('city') is-invalid @enderror"
                                placeholder="CIUDAD DE MÉXICO"
                                autocapitalize="characters"
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
                                class="form-control input-uppercase @error('state') is-invalid @enderror"
                                placeholder="CDMX"
                                autocapitalize="characters"
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

                        <div class="col-12">
                            <hr class="my-1">
                            <p class="fw-semibold mb-1">Documentos</p>
                            <p class="small text-muted mb-0">Toma una foto con la cámara o sube un archivo. El DNI y el comprobante aceptan imagen o PDF.</p>
                        </div>

                        <div class="col-md-6">
                            @include('admin.partials.document-upload-field', [
                                'id' => 'instructor_photo',
                                'name' => 'photo',
                                'label' => 'Fotografía',
                                'help' => 'Foto de frente del instructor.',
                                'accept' => 'image/*',
                                'capture' => 'user',
                            ])
                        </div>

                        <div class="col-md-6">
                            @include('admin.partials.document-upload-field', [
                                'id' => 'instructor_dni_front',
                                'name' => 'dni_front',
                                'label' => 'DNI frente',
                                'help' => 'Anverso de la identificación.',
                                'accept' => 'image/*,application/pdf',
                                'capture' => 'environment',
                            ])
                        </div>

                        <div class="col-md-6">
                            @include('admin.partials.document-upload-field', [
                                'id' => 'instructor_dni_back',
                                'name' => 'dni_back',
                                'label' => 'DNI reverso',
                                'help' => 'Reverso de la identificación.',
                                'accept' => 'image/*,application/pdf',
                                'capture' => 'environment',
                            ])
                        </div>

                        <div class="col-md-6">
                            @include('admin.partials.document-upload-field', [
                                'id' => 'instructor_address_proof',
                                'name' => 'address_proof',
                                'label' => 'Comprobante de domicilio',
                                'help' => 'Recibo o constancia de domicilio.',
                                'accept' => 'image/*,application/pdf',
                                'capture' => 'environment',
                            ])
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand d-flex align-items-center gap-2" id="instructorFormSubmit">
                        <i class="bi bi-check-lg"></i>
                        <span id="instructorFormSubmitLabel">Guardar instructor</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
