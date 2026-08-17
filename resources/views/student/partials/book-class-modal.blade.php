<div
    class="modal fade"
    id="bookClassModal"
    tabindex="-1"
    aria-labelledby="bookClassModalLabel"
    aria-hidden="true"
    data-auto-open="{{ ($errors->has('instructor_id') || $errors->has('vehicle_id') || $errors->has('date') || $errors->has('time') || $errors->has('reserva') || $errors->has('availability')) ? 'true' : 'false' }}"
    data-check-url="{{ route('student.reservas.check') }}"
    data-options-url="{{ route('student.reservas.options') }}"
    data-student-name="{{ auth('student')->user()->fullName() }}"
>
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('student.reservas.store') }}" class="modal-form-layout">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title fw-semibold d-flex align-items-center" id="bookClassModalLabel">
                        <span class="modal-title-icon"><i class="bi bi-calendar-plus"></i></span>
                        <span id="bookModalTitleText">Reservar clase</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div id="bookSlotSummary" class="book-slot-summary d-none mb-3">
                        <p class="book-slot-summary__label mb-1">
                            <i class="bi bi-calendar-check me-1"></i> Horario seleccionado
                        </p>
                        <p class="book-slot-summary__value mb-0" id="bookSlotSummaryText">—</p>
                    </div>

                    <p id="bookModalHint" class="text-muted small mb-3">
                        Elige un bloque <strong>verde</strong> en el calendario o completa el formulario manualmente.
                    </p>

                    <div id="bookingModalFeedback" class="alert d-none mb-3"></div>

                    @if ($errors->has('availability'))
                        <div class="alert alert-danger mb-3">
                            <i class="bi bi-exclamation-triangle me-1"></i>{{ $errors->first('availability') }}
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="book_instructor_id" class="form-label">Instructor</label>
                            <select
                                id="book_instructor_id"
                                name="instructor_id"
                                class="form-select @error('instructor_id') is-invalid @enderror"
                                required
                            >
                                <option value="">Seleccionar instructor</option>
                                @foreach ($instructors as $instructor)
                                    <option value="{{ $instructor->id }}" @selected(old('instructor_id') == $instructor->id)>
                                        {{ $instructor->name }} {{ $instructor->last_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('instructor_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="book_vehicle_id" class="form-label">Vehículo</label>
                            <select
                                id="book_vehicle_id"
                                name="vehicle_id"
                                class="form-select @error('vehicle_id') is-invalid @enderror"
                                required
                            >
                                <option value="">Seleccionar vehículo</option>
                                @foreach ($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>
                                        {{ $vehicle->optionLabel() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('vehicle_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="book_date" class="form-label">Fecha</label>
                            <input
                                type="date"
                                id="book_date"
                                name="date"
                                value="{{ old('date') }}"
                                min="{{ $minBookableDate }}"
                                class="form-control @error('date') is-invalid @enderror"
                                required
                            >
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="book_time" class="form-label">Hora de inicio</label>
                            <select
                                id="book_time"
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
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand d-flex align-items-center gap-2" id="bookSubmitBtn">
                        <i class="bi bi-check-lg"></i>
                        Confirmar reserva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
