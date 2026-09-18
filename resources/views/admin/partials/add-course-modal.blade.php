<div
    class="modal fade"
    id="addCourseModal"
    tabindex="-1"
    aria-labelledby="addCourseModalLabel"
    aria-hidden="true"
    data-store-url="{{ route('admin.courses.store') }}"
    data-update-base="{{ url('admin/courses') }}"
    data-editing-id="{{ old('_form') === 'course-edit' ? old('editing_id') : '' }}"
    data-auto-open="{{ ($errors->any() && in_array(old('_form'), ['course', 'course-edit'], true)) ? 'true' : 'false' }}"
>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.courses.store') }}" class="modal-form-layout" id="courseAdminForm">
                @csrf
                <input type="hidden" name="_method" id="courseFormSpoofMethod" value="PUT" disabled>
                <input type="hidden" name="_form" id="courseFormType" value="{{ old('_form', 'course') }}">
                <input type="hidden" name="editing_id" id="courseEditingId" value="{{ old('editing_id') }}">

                <div class="modal-header">
                    <h5 class="modal-title fw-semibold d-flex align-items-center" id="addCourseModalLabel">
                        <span class="modal-title-icon"><i class="bi bi-journal-plus" id="courseFormIcon"></i></span>
                        <span id="courseFormTitle">Agregar curso</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    @if ($errors->any() && in_array(old('_form'), ['course', 'course-edit'], true))
                        <div class="alert alert-danger" role="alert" id="courseFormErrorAlert">
                            <p class="mb-1 fw-semibold">No se pudo guardar. Motivo:</p>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <p class="small text-muted mb-3 d-none" id="courseFormHint">
                        El número de clases se usa para agendar el horario de cada alumno inscrito.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="course_name" class="form-label">Nombre</label>
                            <input
                                type="text"
                                id="course_name"
                                name="name"
                                value="{{ old('name') }}"
                                class="form-control @error('name') is-invalid @enderror"
                                placeholder="Ej. Curso básico"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="course_num_classes" class="form-label">Número de clases</label>
                            <input
                                type="number"
                                id="course_num_classes"
                                name="num_classes"
                                value="{{ old('num_classes') }}"
                                class="form-control @error('num_classes') is-invalid @enderror"
                                placeholder="8"
                                min="1"
                                max="50"
                                required
                            >
                            @error('num_classes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="course_cost" class="form-label">Costo</label>
                            <input
                                type="number"
                                id="course_cost"
                                name="cost"
                                value="{{ old('cost') }}"
                                class="form-control @error('cost') is-invalid @enderror"
                                placeholder="3500.00"
                                min="0"
                                max="999999.99"
                                step="0.01"
                                required
                            >
                            @error('cost')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-8">
                            <label for="course_description" class="form-label">Descripción</label>
                            <textarea
                                id="course_description"
                                name="description"
                                rows="2"
                                class="form-control @error('description') is-invalid @enderror"
                                placeholder="Resumen del programa y el nivel del alumno."
                                required
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="course_temario" class="form-label">Temario</label>
                            <textarea
                                id="course_temario"
                                name="temario"
                                rows="4"
                                class="form-control @error('temario') is-invalid @enderror"
                                placeholder="Temas que se cubren en el curso."
                                required
                            >{{ old('temario') }}</textarea>
                            @error('temario')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand d-flex align-items-center gap-2" id="courseFormSubmit">
                        <i class="bi bi-check-lg"></i>
                        <span id="courseFormSubmitLabel">Guardar curso</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
