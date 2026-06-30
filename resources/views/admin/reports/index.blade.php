@extends('layouts.admin')

@section('title', 'Reportes — ' . config('app.name'))

@section('content')
    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
        <div>
            <h1 class="page-title mb-1">Reportes</h1>
            <p class="page-subtitle mb-0">
                Resumen de clases
                @if ($report['is_today'])
                    de hoy
                @endif
                — {{ $report['date_label'] }}
            </p>
        </div>
        <form method="GET" action="{{ route('admin.reports.index') }}" class="d-flex flex-wrap align-items-center gap-2">
            <label for="report_date" class="form-label mb-0 small text-muted">Fecha</label>
            <input
                type="date"
                id="report_date"
                name="date"
                value="{{ $selectedDate }}"
                class="form-control form-control-sm"
                style="max-width: 11rem;"
                onchange="this.form.submit()"
            >
            @if ($selectedDate !== now()->toDateString())
                <a href="{{ route('admin.reports.index') }}" class="btn btn-brand-outline btn-sm">Hoy</a>
            @endif
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4 col-xl">
            <div class="kpi-card kpi-card--total">
                <div class="kpi-card__icon"><i class="bi bi-calendar-day"></i></div>
                <p class="kpi-card__label">Total del día</p>
                <p class="kpi-card__value">{{ $report['counts']['total'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-4 col-xl">
            <div class="kpi-card kpi-card--completada">
                <div class="kpi-card__icon"><i class="bi bi-check2-all"></i></div>
                <p class="kpi-card__label">Completadas</p>
                <p class="kpi-card__value">{{ $report['counts']['completadas'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-4 col-xl">
            <div class="kpi-card kpi-card--confirmada">
                <div class="kpi-card__icon"><i class="bi bi-check-circle"></i></div>
                <p class="kpi-card__label">Confirmadas</p>
                <p class="kpi-card__value">{{ $report['counts']['confirmadas'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-4 col-xl">
            <div class="kpi-card kpi-card--pendiente">
                <div class="kpi-card__icon"><i class="bi bi-hourglass-split"></i></div>
                <p class="kpi-card__label">Pendientes</p>
                <p class="kpi-card__value">{{ $report['counts']['pendientes'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-4 col-xl">
            <div class="kpi-card kpi-card--cancelada">
                <div class="kpi-card__icon"><i class="bi bi-x-circle"></i></div>
                <p class="kpi-card__label">Canceladas</p>
                <p class="kpi-card__value">{{ $report['counts']['canceladas'] }}</p>
            </div>
        </div>
    </div>

    @include('admin.partials.panel-table', [
        'icon' => 'bi-list-check',
        'title' => 'Clases del día',
        'subtitle' => $report['counts']['total'] . ' registros para esta fecha',
        'table' => view('admin.partials.tables.daily-reservas-table', ['reservas' => $report['reservas']])->render(),
    ])
@endsection
