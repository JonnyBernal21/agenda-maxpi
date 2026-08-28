@php
    $schedule = session('assign_schedule');
    $scheduleStudentId = old('student_id', $schedule['student_id'] ?? '');
    $scheduleStudentName = $schedule['student_name'] ?? '';
    $scheduleCourseName = $schedule['course_name'] ?? '';
    $scheduleNumClasses = $schedule['num_classes'] ?? 0;
    $selectedWeekdays = collect(old('weekdays', []))->map(fn ($day) => (int) $day)->all();
    $weekdayOptions = [
        1 => ['letter' => 'L', 'name' => 'Lunes'],
        2 => ['letter' => 'M', 'name' => 'Martes'],
        3 => ['letter' => 'M', 'name' => 'Miércoles'],
        4 => ['letter' => 'J', 'name' => 'Jueves'],
        5 => ['letter' => 'V', 'name' => 'Viernes'],
        6 => ['letter' => 'S', 'name' => 'Sábado'],
    ];
    $hourSlots = \App\Models\Reservas::halfHourTimes();
    $scheduleVehicles = collect($vehicles ?? [])->map(fn ($vehicle) => [
        'id' => (string) $vehicle->id,
        'modelo' => $vehicle->modelo,
        'plate' => $vehicle->plate,
        'type' => $vehicle->type,
        'type_label' => $vehicle->typeLabel(),
    ])->values();
@endphp

<div
    class="modal fade"
    id="assignScheduleModal"
    tabindex="-1"
    aria-labelledby="assignScheduleModalLabel"
    aria-hidden="true"
    data-auto-open="{{ $schedule ? 'true' : 'false' }}"
    data-num-classes="{{ $scheduleNumClasses }}"
    data-conflicts-url="{{ route('admin.reservas.instructor-conflicts') }}"
    data-hour-slots='@json($hourSlots)'
    data-vehicles='@json($scheduleVehicles)'
    data-min-start-date="{{ $minBookableDate }}"
    data-same-day-blocked="{{ $sameDayScheduleBlocked ? 'true' : 'false' }}"
    data-same-day-message="{{ $sameDayScheduleMessage }}"
>
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.reservas.schedule') }}" class="modal-form-layout" id="assignScheduleForm">
                @csrf
                <input type="hidden" name="_form" value="schedule">
                <input type="hidden" name="student_id" id="schedule_student_id" value="{{ $scheduleStudentId }}">

                <div class="modal-header">
                    <h5 class="modal-title fw-semibold d-flex align-items-center" id="assignScheduleModalLabel">
                        <span class="modal-title-icon"><i class="bi bi-clock-history"></i></span>
                        Asignar horarios
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    @if ($errors->any() && (old('_form') === 'schedule' || $schedule))
                        <div class="alert alert-danger" role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="alert alert-light border mb-3">
                        <p class="fw-semibold mb-1" id="scheduleStudentName">{{ $scheduleStudentName }}</p>
                        <p class="small text-muted mb-0">
                            Curso: <strong id="scheduleCourseName">{{ $scheduleCourseName }}</strong>
                            · <span id="scheduleNumClassesLabel">{{ $scheduleNumClasses }}</span> clases a asignar
                        </p>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="schedule_start_date" class="form-label">Fecha de inicio de clases</label>
                            <input
                                type="date"
                                id="schedule_start_date"
                                name="start_date"
                                value="{{ old('start_date') }}"
                                min="{{ $minBookableDate }}"
                                class="form-control @error('start_date') is-invalid @enderror"
                                required
                            >
                            <p class="small text-muted mt-2 mb-0" id="scheduleStartHint">
                                @if ($sameDayScheduleBlocked)
                                    A partir de las 9:00 AM no se agenda el día de hoy. Elige desde mañana.
                                @else
                                    Elige lunes o viernes para detectar el día de arranque.
                                @endif
                            </p>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="schedule_time" class="form-label">Hora de clase</label>
                            <select
                                id="schedule_time"
                                name="time"
                                class="form-select @error('time') is-invalid @enderror"
                                required
                            >
                                <option value="">Seleccionar hora</option>
                                @foreach ($hourSlots as $slot)
                                    <option value="{{ $slot }}" @selected(old('time') === $slot)>
                                        {{ \Illuminate\Support\Carbon::createFromFormat('H:i', $slot)->format('g:i A') }}
                                    </option>
                                @endforeach
                            </select>
                            @error('time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <p class="small text-muted mt-2 mb-0">De 7:00 AM a 7:00 PM, cada 30 minutos. Puedes cambiarla después en cada fila de la tabla. Cada clase dura 2 horas.</p>
                        </div>

                        <div class="col-12">
                            <label class="form-label d-block">Días de la semana</label>
                            <div class="weekday-picker" id="scheduleWeekdays">
                                @foreach ($weekdayOptions as $iso => $day)
                                    <label class="weekday-chip">
                                        <input
                                            type="checkbox"
                                            name="weekdays[]"
                                            value="{{ $iso }}"
                                            class="weekday-chip__input"
                                            data-iso="{{ $iso }}"
                                            @checked(in_array($iso, $selectedWeekdays, true))
                                        >
                                        <span class="weekday-chip__box">
                                            <span class="weekday-chip__letter">{{ $day['letter'] }}</span>
                                            <span class="weekday-chip__name">{{ $day['name'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('weekdays')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="schedule_instructor_id" class="form-label">Instructor</label>
                            <select
                                id="schedule_instructor_id"
                                name="instructor_id"
                                class="form-select @error('instructor_id') is-invalid @enderror"
                                required
                            >
                                <option value="">Seleccionar instructor</option>
                                @foreach ($instructors as $instructor)
                                    <option value="{{ $instructor->id }}" @selected(old('instructor_id') == $instructor->id)>
                                        {{ trim($instructor->name.' '.$instructor->last_name) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('instructor_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="schedule_vehicle_id" class="form-label">Vehículo</label>
                            <select
                                id="schedule_vehicle_id"
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
                            <p class="small text-muted mt-2 mb-0">Puedes cambiarlo después en cada fila de la tabla.</p>
                        </div>

                        <div class="col-12">
                            <div id="scheduleConflictAlert" class="alert alert-warning d-none mb-0" role="alert"></div>
                        </div>

                        <div class="col-12">
                            <div class="schedule-preview">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                    <p class="small fw-semibold mb-0">Vista previa</p>
                                    <p class="small text-muted mb-0" id="schedulePreviewSummary">Marca o quita días para actualizar la tabla.</p>
                                </div>
                                <div class="table-responsive schedule-preview__table-wrap">
                                    <table class="table table-sm align-middle mb-0 schedule-preview-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Día</th>
                                                <th>Fecha</th>
                                                <th>Hora</th>
                                                <th>Vehículo</th>
                                                <th>Cupo</th>
                                            </tr>
                                        </thead>
                                        <tbody id="schedulePreviewBody">
                                            <tr class="schedule-preview-empty">
                                                <td colspan="6" class="text-muted text-center py-3">Selecciona fecha, días, hora e instructor.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Más tarde</button>
                    <button type="submit" class="btn btn-brand d-flex align-items-center gap-2" id="assignScheduleSubmit">
                        <i class="bi bi-calendar-check"></i>
                        Asignar horarios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
