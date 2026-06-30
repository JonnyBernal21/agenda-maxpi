<div
    class="modal fade"
    id="scheduleClassModal"
    tabindex="-1"
    aria-labelledby="scheduleClassModalLabel"
    aria-hidden="true"
    data-auto-open="{{ ($errors->any() && old('_form') === 'reserva') ? 'true' : 'false' }}"
    data-search-url="{{ route('admin.students.search') }}"
    data-options-url="{{ route('admin.reservas.options') }}"
    data-check-url="{{ route('admin.reservas.check') }}"
    @if(old('student_id') && old('_form') === 'reserva' && ($oldStudent = \App\Models\Student::find(old('student_id'))))
        data-old-student='@json(["id" => $oldStudent->id, "full_name" => trim($oldStudent->name." ".$oldStudent->last_name), "email" => $oldStudent->email])'
    @endif
>
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.reservas.store') }}" id="scheduleClassForm" class="modal-form-layout">
                @csrf
                <input type="hidden" name="_form" value="reserva">
                <input type="hidden" name="student_id" id="reserva_student_id" value="{{ old('student_id') }}">

                <div class="modal-header">
                    <h5 class="modal-title fw-semibold d-flex align-items-center" id="scheduleClassModalLabel">
                        <span class="modal-title-icon"><i class="bi bi-calendar-plus"></i></span>
                        Agendar clase
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div id="scheduleSlotSummary" class="book-slot-summary d-none mb-3">
                        <p class="book-slot-summary__label mb-1">
                            <i class="bi bi-calendar-check me-1"></i> Horario seleccionado
                        </p>
                        <p class="book-slot-summary__value mb-0" id="scheduleSlotSummaryText">—</p>
                    </div>

                    @if ($errors->any() && old('_form') === 'reserva')
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Búsqueda de alumno --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-search me-1"></i>Alumno
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted border-end-0">
                                <i class="bi bi-person"></i>
                            </span>
                            <input
                                type="text"
                                id="studentSearchInput"
                                class="form-control border-start-0 ps-0"
                                placeholder="Nombre o apellido del alumno"
                                autocomplete="off"
                                value="{{ old('_student_search') }}"
                            >
                            <button type="button" class="btn btn-brand-outline" id="studentSearchBtn">
                                Verificar
                            </button>
                        </div>
                        <div class="form-text">Escribe el nombre y verifica si el alumno está registrado.</div>

                        <div id="studentSearchResults" class="list-group mt-2 d-none"></div>

                        <div id="studentSelectedAlert" class="alert alert-success mt-3 d-none" role="alert">
                            <strong>Alumno seleccionado:</strong>
                            <span id="studentSelectedName"></span>
                            <div class="small mt-1" id="studentSelectedCourse"></div>
                            <button type="button" class="btn btn-sm btn-link p-0 ms-2" id="studentClearBtn">Cambiar</button>
                        </div>

                        <div id="studentNotFoundAlert" class="alert alert-warning mt-3 d-none" role="alert">
                            No se encontró ningún alumno con ese nombre.
                            <button
                                type="button"
                                class="btn btn-sm btn-brand-outline ms-1"
                                data-bs-dismiss="modal"
                                data-bs-toggle="modal"
                                data-bs-target="#addStudentModal"
                            >
                                <i class="bi bi-person-plus"></i> Agregar alumno
                            </button>
                        </div>

                        @error('student_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="scheduleFormFields" class="{{ old('student_id') ? '' : 'opacity-50' }}" style="{{ old('student_id') ? '' : 'pointer-events: none;' }}">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="reserva_instructor_id" class="form-label">Instructor</label>
                                <select
                                    id="reserva_instructor_id"
                                    name="instructor_id"
                                    class="form-select @error('instructor_id') is-invalid @enderror"
                                    required
                                >
                                    <option value="">Seleccionar instructor</option>
                                    @foreach ($instructors as $instructor)
                                        <option
                                            value="{{ $instructor->id }}"
                                            @selected(old('instructor_id') == $instructor->id)
                                        >
                                            {{ $instructor->name }} {{ $instructor->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('instructor_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="reserva_vehicle_id" class="form-label">Vehículo</label>
                                <select
                                    id="reserva_vehicle_id"
                                    name="vehicle_id"
                                    class="form-select @error('vehicle_id') is-invalid @enderror"
                                    required
                                >
                                    <option value="">Seleccionar vehículo</option>
                                    @foreach ($vehicles as $vehicle)
                                        <option
                                            value="{{ $vehicle->id }}"
                                            @selected(old('vehicle_id') == $vehicle->id)
                                        >
                                            {{ $vehicle->modelo }} — {{ $vehicle->plate }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('vehicle_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="reserva_date" class="form-label">Fecha</label>
                                <input
                                    type="date"
                                    id="reserva_date"
                                    name="date"
                                    value="{{ old('date') }}"
                                    min="{{ now()->format('Y-m-d') }}"
                                    class="form-control @error('date') is-invalid @enderror"
                                    required
                                >
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="reserva_time" class="form-label">Hora de inicio</label>
                                <select
                                    id="reserva_time"
                                    name="time"
                                    class="form-select @error('time') is-invalid @enderror"
                                    required
                                >
                                    <option value="">Seleccionar hora</option>
                                    @foreach ($timeSlots as $slot)
                                        <option value="{{ $slot }}" @selected(old('time') === $slot)>
                                            {{ $slot }} (2 horas)
                                        </option>
                                    @endforeach
                                </select>
                                @error('time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="reserva_status" class="form-label">Estado</label>
                                <select
                                    id="reserva_status"
                                    name="status"
                                    class="form-select @error('status') is-invalid @enderror"
                                    required
                                >
                                    <option value="pendiente" @selected(old('status', 'pendiente') === 'pendiente')>Pendiente</option>
                                    <option value="confirmada" @selected(old('status') === 'confirmada')>Confirmada</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand d-flex align-items-center gap-2" id="scheduleSubmitBtn" disabled>
                        <i class="bi bi-check-lg"></i>
                        Agendar clase
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
