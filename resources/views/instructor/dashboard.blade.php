@extends('layouts.instructor')

@section('title', 'Mis clases — ' . config('app.name'))

@section('content')
    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
        <div>
            <h1 class="page-title mb-1">Hola, {{ $instructor->name }}</h1>
            <p class="page-subtitle mb-0">
                Revisa tus clases asignadas y horarios de la semana.
            </p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-card__icon"><i class="bi bi-calendar-day"></i></div>
                <p class="stat-card__label">Hoy</p>
                <p class="stat-card__value">{{ $classesToday }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-card__icon"><i class="bi bi-calendar-week"></i></div>
                <p class="stat-card__label">Esta semana</p>
                <p class="stat-card__value">{{ $classesThisWeek }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-card__icon"><i class="bi bi-hourglass-split"></i></div>
                <p class="stat-card__label">Pendientes</p>
                <p class="stat-card__value">{{ $pendingCount }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-card__icon"><i class="bi bi-check2-circle"></i></div>
                <p class="stat-card__label">Confirmadas</p>
                <p class="stat-card__value">{{ $confirmedCount }}</p>
            </div>
        </div>
    </div>

    <div class="panel-card">
        <div class="panel-card__header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="stat-card__icon mb-0 mt-1"><i class="bi bi-calendar3"></i></div>
                    <div>
                        <h2 class="h5 fw-semibold mb-1">Calendario de clases</h2>
                        <p class="text-muted small mb-0">Solo se muestran las clases asignadas a ti</p>
                    </div>
                </div>
                @include('instructor.partials.calendar-legend')
            </div>
        </div>
        <div class="panel-card__body">
            <div
                id="instructor-calendar"
                data-events-url="{{ route('instructor.calendar.events') }}"
            ></div>
            <div class="px-3 pb-3 pt-2 border-top">
                <div id="instructorCalendarHint" class="alert alert-info mb-0">
                    <i class="bi bi-hand-index-thumb me-1"></i>
                    Haz clic en una clase para ver el detalle del alumno y el vehículo.
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
                        <dt class="col-sm-4"><i class="bi bi-person"></i>Alumno</dt>
                        <dd class="col-sm-8" id="eventModalStudent">—</dd>

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
    @vite(['resources/js/instructor-calendar.js', 'resources/js/flash-alerts.js'])
@endpush
