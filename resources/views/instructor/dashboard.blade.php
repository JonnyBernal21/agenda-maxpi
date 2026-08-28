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

    @include('partials.event-detail-modal')
@endsection

@push('scripts')
    @vite(['resources/js/instructor-calendar.js'])
@endpush
