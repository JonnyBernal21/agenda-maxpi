@extends('layouts.student')

@section('title', 'Mis clases — ' . config('app.name'))

@section('content')
    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
        <div>
            <h1 class="page-title mb-1">Hola, {{ $student->name }}</h1>
            <p class="page-subtitle mb-0">
                Curso: <strong>{{ $student->course?->name ?? 'Sin curso' }}</strong>
                · {{ $completedClasses }} de {{ $allowedClasses }} clases completadas
            </p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-card__icon"><i class="bi bi-journal-bookmark"></i></div>
                <p class="stat-card__label">Curso</p>
                <p class="stat-card__value fs-5">{{ $student->course?->name ?? '—' }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-card__icon"><i class="bi bi-calendar-check"></i></div>
                <p class="stat-card__label">Clases completadas</p>
                <p class="stat-card__value">{{ $completedClasses }} / {{ $allowedClasses }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-card__icon"><i class="bi bi-hourglass-split"></i></div>
                <p class="stat-card__label">Disponibles</p>
                <p class="stat-card__value">{{ $remainingClasses }}</p>
            </div>
        </div>
    </div>

  <div class="action-bar d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <p class="fw-semibold mb-0 text-dark">Reservar clase</p>
            <p class="small text-muted mb-0">
                @if ($canReserve)
                    Tienes {{ $remainingClasses }} {{ $remainingClasses === 1 ? 'clase disponible' : 'clases disponibles' }}
                @else
                    Ya utilizaste todas las clases de tu curso
                @endif
            </p>
        </div>
        @if ($canReserve)
            <button
                type="button"
                class="btn btn-brand d-flex align-items-center gap-2"
                data-bs-toggle="modal"
                data-bs-target="#bookClassModal"
            >
                <i class="bi bi-calendar-plus"></i>
                Reservar clase
            </button>
        @endif
    </div>

    <div class="panel-card">
        <div class="panel-card__header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="stat-card__icon mb-0 mt-1"><i class="bi bi-calendar3"></i></div>
                    <div>
                        <h2 class="h5 fw-semibold mb-1">Horarios disponibles</h2>
                        <p class="text-muted small mb-0">
                            @if ($canReserve)
                                Solo verás espacios libres para reservar y tus clases ya agendadas
                            @else
                                Tus clases agendadas
                            @endif
                        </p>
                    </div>
                </div>
                @include('student.partials.calendar-legend')
            </div>
        </div>
        <div class="panel-card__body">
            <div
                id="student-calendar"
                data-events-url="{{ route('student.calendar.events') }}"
                data-can-reserve="{{ $canReserve ? 'true' : 'false' }}"
            ></div>
            <div class="px-3 pb-3 pt-2 border-top">
                <div id="calendarAvailabilityAlert" class="alert {{ $errors->has('availability') || $errors->has('time') ? 'alert-danger' : 'alert-info' }} mb-0">
                    @if ($errors->has('availability'))
                        <i class="bi bi-exclamation-triangle me-1"></i>{{ $errors->first('availability') }}
                    @elseif ($errors->has('time'))
                        <i class="bi bi-exclamation-triangle me-1"></i>{{ $errors->first('time') }}
                    @else
                        <i class="bi bi-hand-index-thumb me-1"></i>
                        @if ($canReserve)
                            Los bloques <strong>verdes</strong> son horarios libres. Haz clic en uno para reservar.
                        @else
                            Aquí aparecen únicamente tus clases ya reservadas.
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="eventDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content--stack">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold d-flex align-items-center">
                        <span class="modal-title-icon"><i class="bi bi-info-circle"></i></span>
                        <span id="eventModalTitle">Detalle de clase</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0 detail-list">
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/student-booking.js', 'resources/js/student-calendar.js', 'resources/js/flash-alerts.js'])
@endpush
