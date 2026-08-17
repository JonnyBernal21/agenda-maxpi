@extends('layouts.admin')

@section('title', 'Panel administrador — ' . config('app.name'))

@section('content')
    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
        <div>
            <h1 class="page-title mb-1">Panel administrador</h1>
            <p class="page-subtitle mb-0">
                Hola, {{ auth()->user()->name }}. Gestiona alumnos, clases y reservas.
            </p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <a href="{{ route('admin.students.index') }}" class="text-decoration-none">
                <div class="stat-card">
                    <div class="stat-card__icon"><i class="bi bi-people"></i></div>
                    <p class="stat-card__label">Alumnos</p>
                    <p class="stat-card__value">{{ $studentsCount }}</p>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ route('admin.instructors.index') }}" class="text-decoration-none">
                <div class="stat-card">
                    <div class="stat-card__icon"><i class="bi bi-person-badge"></i></div>
                    <p class="stat-card__label">Instructores</p>
                    <p class="stat-card__value">{{ $instructorsCount }}</p>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ route('admin.vehicles.index') }}" class="text-decoration-none">
                <div class="stat-card">
                    <div class="stat-card__icon"><i class="bi bi-car-front"></i></div>
                    <p class="stat-card__label">Vehículos</p>
                    <p class="stat-card__value">{{ $vehiclesCount }}</p>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-card__icon"><i class="bi bi-calendar-check"></i></div>
                <p class="stat-card__label">Reservas</p>
                <p class="stat-card__value">{{ $reservasCount }}</p>
            </div>
        </div>
    </div>

    <div class="action-bar d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <p class="fw-semibold mb-0 text-dark">Acciones rápidas</p>
            <p class="small text-muted mb-0">Registra alumnos, instructores, vehículos o agenda clases</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button
                type="button"
                class="btn btn-brand d-flex align-items-center gap-2"
                data-bs-toggle="modal"
                data-bs-target="#addStudentModal"
            >
                <i class="bi bi-person-plus"></i>
                Agregar alumno
            </button>
            <button
                type="button"
                class="btn btn-brand-outline d-flex align-items-center gap-2"
                data-bs-toggle="modal"
                data-bs-target="#addInstructorModal"
            >
                <i class="bi bi-person-badge"></i>
                Agregar instructor
            </button>
            <button
                type="button"
                class="btn btn-brand-outline d-flex align-items-center gap-2"
                data-bs-toggle="modal"
                data-bs-target="#addVehicleModal"
            >
                <i class="bi bi-car-front"></i>
                Agregar vehículo
            </button>
            <button
                type="button"
                class="btn btn-brand-outline d-flex align-items-center gap-2"
                data-bs-toggle="modal"
                data-bs-target="#scheduleClassModal"
                id="openScheduleManualBtn"
            >
                <i class="bi bi-calendar-plus"></i>
                Agendar clase
            </button>
        </div>
    </div>

    <div class="panel-card">
        <div class="panel-card__header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="stat-card__icon mb-0 mt-1"><i class="bi bi-calendar3"></i></div>
                    <div>
                        <h2 class="h5 fw-semibold mb-1">Calendario de clases</h2>
                        <p class="text-muted small mb-0">Bloques verdes = horarios libres. Arrastra una clase para cambiar fecha y hora.</p>
                    </div>
                </div>
                @include('admin.partials.calendar-legend')
            </div>
        </div>
        <div class="panel-card__body">
            <div
                id="admin-calendar"
                data-events-url="{{ route('admin.calendar.events') }}"
                data-confirm-url="{{ url('admin/reservas') }}"
                data-min-date="{{ $minBookableDate }}"
                data-same-day-message="{{ $sameDayScheduleMessage }}"
            ></div>
            <div class="px-3 pb-3 pt-2 border-top">
                <div id="adminCalendarHint" class="alert alert-info mb-0">
                    <i class="bi bi-hand-index-thumb me-1"></i>
                    Haz clic en un bloque <strong>verde</strong> para agendar. Arrastra una clase para cambiar su fecha u horario.
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="eventDetailModal" tabindex="-1" aria-labelledby="eventDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content--stack">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold d-flex align-items-center">
                        <span class="modal-title-icon"><i class="bi bi-info-circle"></i></span>
                        <span id="eventModalTitle">Detalle de reserva</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0 detail-list">
                        <dt class="col-sm-4"><i class="bi bi-person"></i>Alumno</dt>
                        <dd class="col-sm-8" id="eventModalStudent">—</dd>

                        <dt class="col-sm-4"><i class="bi bi-person-badge"></i>Instructor</dt>
                        <dd class="col-sm-8" id="eventModalInstructor">—</dd>

                        <dt class="col-sm-4"><i class="bi bi-car-front"></i>Vehículo</dt>
                        <dd class="col-sm-8" id="eventModalVehicle">—</dd>

                        <dt class="col-sm-4"><i class="bi bi-calendar-event"></i>Fecha</dt>
                        <dd class="col-sm-8" id="eventModalDate">—</dd>

                        <dt class="col-sm-4"><i class="bi bi-clock"></i>Horario</dt>
                        <dd class="col-sm-8" id="eventModalTime">—</dd>

                        <dt class="col-sm-4"><i class="bi bi-flag"></i>Estado</dt>
                        <dd class="col-sm-8" id="eventModalStatus">—</dd>
                    </dl>
                </div>
                <div class="modal-footer flex-wrap gap-2">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Cerrar</button>
                    <button
                        type="button"
                        id="confirmReservaBtn"
                        class="btn btn-success d-none align-items-center gap-2"
                    >
                        <i class="bi bi-check-circle"></i>
                        Confirmar cita
                    </button>
                    <button
                        type="button"
                        id="completeReservaBtn"
                        class="btn btn-primary d-none align-items-center gap-2"
                    >
                        <i class="bi bi-check2-all"></i>
                        Completada
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div
        class="modal fade"
        id="rescheduleClassModal"
        tabindex="-1"
        aria-labelledby="rescheduleClassModalLabel"
        aria-hidden="true"
        data-bs-backdrop="static"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content--stack">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold d-flex align-items-center" id="rescheduleClassModalLabel">
                        <span class="modal-title-icon"><i class="bi bi-arrows-move"></i></span>
                        Confirmar cambio de horario
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        ¿Mover la clase de
                        <strong id="rescheduleModalStudent">este alumno</strong>?
                    </p>
                    <div class="reschedule-compare">
                        <div class="reschedule-compare__col">
                            <span class="reschedule-compare__label">Actual</span>
                            <span class="reschedule-compare__date" id="rescheduleFromDate">—</span>
                            <span class="reschedule-compare__time" id="rescheduleFromTime">—</span>
                        </div>
                        <div class="reschedule-compare__arrow" aria-hidden="true">
                            <i class="bi bi-arrow-right"></i>
                        </div>
                        <div class="reschedule-compare__col reschedule-compare__col--next">
                            <span class="reschedule-compare__label">Nuevo</span>
                            <span class="reschedule-compare__date" id="rescheduleToDate">—</span>
                            <span class="reschedule-compare__time" id="rescheduleToTime">—</span>
                        </div>
                    </div>
                    <div id="rescheduleModalError" class="alert alert-danger mt-3 mb-0 d-none" role="alert"></div>
                </div>
                <div class="modal-footer flex-wrap gap-2">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="confirmRescheduleBtn" class="btn btn-brand d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle"></i>
                        Confirmar cambio
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/admin-reservas.js', 'resources/js/admin-calendar.js'])
@endpush
