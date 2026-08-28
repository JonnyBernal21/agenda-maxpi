<div
    class="modal fade"
    id="addStudentModal"
    tabindex="-1"
    aria-labelledby="addStudentModalLabel"
    aria-hidden="true"
    data-store-url="{{ route('admin.students.store') }}"
    data-update-base="{{ url('admin/students') }}"
    data-editing-id="{{ old('_form') === 'student-edit' ? old('editing_id') : '' }}"
    data-auto-open="{{ ($errors->any() && in_array(old('_form'), ['student', 'student-edit'], true)) ? 'true' : 'false' }}"
    data-old-extras='@json(old('extra_classes', []))'
>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.students.store') }}" class="modal-form-layout" id="studentAdminForm">
                @csrf
                <input type="hidden" name="_method" id="studentFormSpoofMethod" value="PUT" disabled>
                <input type="hidden" name="_form" id="studentFormType" value="{{ old('_form', 'student') }}">
                <input type="hidden" name="editing_id" id="studentEditingId" value="{{ old('editing_id') }}">

                <div class="modal-header">
                    <h5 class="modal-title fw-semibold d-flex align-items-center" id="addStudentModalLabel">
                        <span class="modal-title-icon"><i class="bi bi-person-plus" id="studentFormIcon"></i></span>
                        <span id="studentFormTitle">Agregar alumno</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <p class="small text-muted mb-3" id="studentFormHint">
                        Después de guardar se abrirá un segundo paso para asignar <strong>fecha de inicio, días y hora</strong> de sus clases.
                    </p>

                    @if ($errors->any() && in_array(old('_form'), ['student', 'student-edit'], true))
                        <div class="alert alert-danger" role="alert" id="studentFormErrorAlert">
                            Revisa los campos marcados e intenta de nuevo.
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="student_course_id" class="form-label">Curso</label>
                            <select
                                id="student_course_id"
                                name="course_id"
                                class="form-select @error('course_id') is-invalid @enderror"
                                required
                            >
                                <option value="">Seleccionar curso</option>
                                @foreach ($courses as $course)
                                    <option
                                        value="{{ $course->id }}"
                                        data-num-classes="{{ $course->num_classes }}"
                                        @selected(old('course_id') == $course->id)
                                    >
                                        {{ $course->name }} — {{ $course->num_classes }} clases — ${{ number_format($course->cost, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('course_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="student_name" class="form-label">Nombre</label>
                            <input
                                type="text"
                                id="student_name"
                                name="name"
                                value="{{ old('name') }}"
                                class="form-control input-uppercase @error('name') is-invalid @enderror"
                                placeholder="EJ. JUAN"
                                autocapitalize="characters"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="student_last_name" class="form-label">Apellido</label>
                            <input
                                type="text"
                                id="student_last_name"
                                name="last_name"
                                value="{{ old('last_name') }}"
                                class="form-control input-uppercase @error('last_name') is-invalid @enderror"
                                placeholder="EJ. PÉREZ GARCÍA"
                                autocapitalize="characters"
                                required
                            >
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="student_email" class="form-label">Correo electrónico</label>
                            <input
                                type="email"
                                id="student_email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="alumno@correo.com"
                                required
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="student_phone" class="form-label">Teléfono</label>
                            <input
                                type="text"
                                id="student_phone"
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
                            <label for="student_address" class="form-label">Dirección</label>
                            <input
                                type="text"
                                id="student_address"
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
                            <label for="student_city" class="form-label">Ciudad</label>
                            <input
                                type="text"
                                id="student_city"
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
                            <label for="student_state" class="form-label">Estado</label>
                            <input
                                type="text"
                                id="student_state"
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
                            <label for="student_zip" class="form-label">Código postal</label>
                            <input
                                type="text"
                                id="student_zip"
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
                            <label for="student_country" class="form-label">País</label>
                            <input
                                type="text"
                                id="student_country"
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

                        <div
                            class="col-12 d-none"
                            id="studentExtraClassesSection"
                            data-extra-types='@json(\App\Models\StudentExtraClass::TYPES)'
                        >
                            <hr class="my-1">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <div>
                                    <p class="fw-semibold mb-0">Clases adicionales</p>
                                    <p class="small text-muted mb-0">Reposiciones, clases extra o cortesías. Suman al cupo del curso.</p>
                                </div>
                                <button type="button" class="btn btn-brand-outline btn-sm d-inline-flex align-items-center gap-1" id="studentExtraClassAdd">
                                    <i class="bi bi-plus-lg"></i>
                                    Agregar
                                </button>
                            </div>
                            <p class="small mb-2" id="studentExtraClassesSummary"></p>
                            <div class="student-extra-head" aria-hidden="true">
                                <span>Tipo</span>
                                <span>Cant.</span>
                                <span>Motivo</span>
                                <span></span>
                            </div>
                            <div class="student-extra-list" id="studentExtraClassesList"></div>
                            @error('extra_classes')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand d-flex align-items-center gap-2" id="studentFormSubmit">
                        <i class="bi bi-check-lg"></i>
                        <span id="studentFormSubmitLabel">Guardar alumno</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="studentExtraClassRowTemplate">
    <div class="student-extra-row">
        <select class="form-select" data-extra-type aria-label="Tipo de clase adicional" required>
            <option value="">Tipo</option>
        </select>
        <input
            type="number"
            class="form-control"
            data-extra-quantity
            min="1"
            max="20"
            value="1"
            title="Cantidad"
            aria-label="Cantidad"
            required
        >
        <input
            type="text"
            class="form-control"
            data-extra-notes
            maxlength="255"
            placeholder="Motivo (opcional)"
            aria-label="Motivo"
        >
        <button type="button" class="btn btn-brand-outline btn-delete" data-extra-remove title="Quitar" aria-label="Quitar clase adicional">
            <i class="bi bi-trash"></i>
        </button>
    </div>
</template>
