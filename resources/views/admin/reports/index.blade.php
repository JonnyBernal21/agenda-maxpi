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
        <form
            id="reportRangeForm"
            method="GET"
            action="{{ route('admin.reports.index') }}"
            class="report-range d-flex flex-wrap align-items-end gap-2"
        >
            <div>
                <label for="report_range" class="form-label mb-0 small text-muted">Periodo</label>
                <div class="report-range__field">
                    <i class="bi bi-calendar-range report-range__icon"></i>
                    <input type="hidden" id="report_from" name="from" value="{{ $from }}">
                    <input type="hidden" id="report_to" name="to" value="{{ $to }}">
                    <input
                        type="text"
                        id="report_range"
                        class="form-control form-control-sm"
                        value="{{ $from === $to ? \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') : \Illuminate\Support\Carbon::parse($from)->format('d/m/Y').' – '.\Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }}"
                        placeholder="Selecciona un rango"
                        autocomplete="off"
                        readonly
                    >
                </div>
            </div>
            @if (! $report['is_today'])
                <a href="{{ route('admin.reports.index') }}" class="btn btn-brand-outline btn-sm">Hoy</a>
            @endif
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4 col-xl">
            <div class="kpi-card kpi-card--total">
                <div class="kpi-card__icon"><i class="bi bi-calendar-range"></i></div>
                <p class="kpi-card__label">{{ $report['is_single_day'] ? 'Total del día' : 'Total del periodo' }}</p>
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
        'title' => $report['is_single_day'] ? 'Clases del día' : 'Clases del periodo',
        'subtitle' => $report['counts']['total'].' registros · '.$report['date_label'],
        'table' => view('admin.partials.tables.daily-reservas-table', [
            'reservas' => $report['reservas'],
            'showDate' => ! $report['is_single_day'],
        ])->render(),
    ])
@endsection

@push('scripts')
    @vite('resources/js/admin-reports.js')
@endpush
