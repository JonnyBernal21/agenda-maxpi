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
    $studentInitial = $scheduleStudentName !== '' ? mb_strtoupper(mb_substr($scheduleStudentName, 0, 1)) : 'A';
@endphp

<div
    class="modal fade"
    id="assignScheduleModal"
    tabindex="-1"
    aria-labelledby="assignScheduleModalLabel"
    aria-hidden="true"
    data-bs-focus="false"
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
        <div class="modal-content assign-schedule-modal modal-content--stack">
            <form method="POST" action="{{ route('admin.reservas.schedule') }}" class="modal-form-layout" id="assignScheduleForm">
                @csrf
                <input type="hidden" name="_form" value="schedule">
                <input type="hidden" name="student_id" id="schedule_student_id" value="{{ $scheduleStudentId }}">

                <div class="modal-header assign-schedule-modal__header">
                    <div>
                        <p class="assign-schedule-modal__kicker">Agenda el curso en 4 pasos</p>
                        <h5 class="modal-title fw-semibold d-flex align-items-center" id="assignScheduleModalLabel">
                            <span class="modal-title-icon assign-schedule-modal__title-icon"><i class="bi bi-calendar2-week"></i></span>
                            Asignar horarios
                        </h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body assign-schedule-modal__body">
                    @if ($errors->any() && (old('_form') === 'schedule' || $schedule))
                        <div class="alert alert-danger" role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="assign-hero">
                        <div class="assign-hero__avatar" id="scheduleStudentInitial" aria-hidden="true">{{ $studentInitial }}</div>
                        <div class="assign-hero__copy">
                            <p class="assign-hero__name mb-1" id="scheduleStudentName">{{ $scheduleStudentName }}</p>
                            <div class="assign-hero__meta">
                                <span class="assign-chip assign-chip--course">
                                    <i class="bi bi-journal-text"></i>
                                    <span id="scheduleCourseName">{{ $scheduleCourseName !== '' ? $scheduleCourseName : 'Sin curso' }}</span>
                                </span>
                                <span class="assign-chip assign-chip--classes">
                                    <i class="bi bi-hash"></i>
                                    <span id="scheduleNumClassesLabel">{{ $scheduleNumClasses }}</span> clases a asignar
                                </span>
                            </div>
                        </div>
                    </div>

                    <section class="assign-step assign-step--when">
                        <header class="assign-step__head">
                            <span class="assign-step__num">1</span>
                            <div>
                                <h3 class="assign-step__title">Cuándo empieza</h3>
                                <p class="assign-step__help">Elige la primera fecha y la hora de las clases.</p>
                            </div>
                        </header>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="schedule_start_date_display" class="form-label">Fecha de inicio</label>
                                <div
                                    class="assign-field js-single-date"
                                    data-min-date="{{ $minBookableDate }}"
                                >
                                    <i class="bi bi-calendar-event assign-field__icon"></i>
                                    <input
                                        type="hidden"
                                        id="schedule_start_date"
                                        name="start_date"
                                        class="js-single-date-value"
                                        value="{{ old('start_date') }}"
                                    >
                                    <input
                                        type="text"
                                        id="schedule_start_date_display"
                                        class="form-control js-single-date-display @error('start_date') is-invalid @enderror"
                                        value="{{ old('start_date') ? \Illuminate\Support\Carbon::parse(old('start_date'))->format('d/m/Y') : '' }}"
                                        placeholder="Selecciona un día"
                                        autocomplete="off"
                                        readonly
                                        required
                                    >
                                </div>
                                <p class="small text-muted mt-2 mb-0" id="scheduleStartHint">
                                    @if ($sameDayScheduleBlocked)
                                        A partir de las 9:00 AM no se agenda el día de hoy. Elige desde mañana.
                                    @else
                                        Elige lunes o viernes para detectar el día de arranque.
                                    @endif
                                </p>
                                @error('start_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="schedule_time" class="form-label">Hora de clase</label>
                                <div class="assign-field">
                                    <i class="bi bi-clock assign-field__icon"></i>
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
                                </div>
                                @error('time')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <p class="small text-muted mt-2 mb-0">De 7:00 AM a 7:00 PM, cada 30 min. Cada clase dura 2 horas. Puedes ajustarla por fila.</p>
                            </div>
                        </div>
                    </section>

                    <section class="assign-step assign-step--days">
                        <header class="assign-step__head">
                            <span class="assign-step__num">2</span>
                            <div>
                                <h3 class="assign-step__title">Días de la semana</h3>
                                <p class="assign-step__help">Marca los días que se repetirán. El día de inicio queda resaltado.</p>
                            </div>
                        </header>
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
                    </section>

                    <section class="assign-step assign-step--people">
                        <header class="assign-step__head">
                            <span class="assign-step__num">3</span>
                            <div>
                                <h3 class="assign-step__title">Instructor y vehículo</h3>
                                <p class="assign-step__help">Se aplican a todas las clases. El vehículo se puede cambiar por fila.</p>
                            </div>
                        </header>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="schedule_instructor_id" class="form-label">Instructor</label>
                                <div class="assign-field">
                                    <i class="bi bi-person-badge assign-field__icon"></i>
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
                                </div>
                                @error('instructor_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="schedule_vehicle_id" class="form-label">Vehículo</label>
                                <div class="assign-field">
                                    <i class="bi bi-car-front assign-field__icon"></i>
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
                                </div>
                                @error('vehicle_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <div id="scheduleConflictAlert" class="alert alert-warning d-none mb-0" role="alert"></div>

                    <section class="assign-step assign-step--preview">
                        <header class="assign-step__head">
                            <span class="assign-step__num">4</span>
                            <div class="flex-grow-1 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <h3 class="assign-step__title">Vista previa</h3>
                                    <p class="assign-step__help mb-0" id="schedulePreviewSummary">Completa los pasos de arriba para armar la tabla.</p>
                                </div>
                            </div>
                        </header>
                        <div class="schedule-preview">
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
                                            <td colspan="6">
                                                <div class="schedule-preview-empty__box">
                                                    <i class="bi bi-calendar-week"></i>
                                                    <p>Selecciona fecha, días, hora e instructor para ver las clases.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="modal-footer assign-schedule-modal__footer">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Más tarde</button>
                    <button type="submit" class="btn btn-brand d-flex align-items-center gap-2" id="assignScheduleSubmit">
                        <i class="bi bi-check2-circle"></i>
                        Asignar horarios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
