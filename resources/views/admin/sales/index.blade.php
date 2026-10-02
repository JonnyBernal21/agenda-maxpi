@extends('layouts.admin')

@section('title', 'Ingresos — ' . config('app.name'))

@section('content')
    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
        <div>
            <h1 class="page-title mb-1">Ingresos</h1>
            <p class="page-subtitle mb-0">
                Pagos de inscripción y abonos
                @if ($is_today)
                    de hoy
                @endif
                — {{ $date_label }}
            </p>
        </div>
        <form
            id="saleRangeForm"
            method="GET"
            action="{{ route('admin.sales.index') }}"
            class="report-range js-date-range d-flex flex-wrap align-items-end gap-2"
        >
            <div>
                <label for="sale_range" class="form-label mb-0 small text-muted">Periodo</label>
                <div class="report-range__field">
                    <i class="bi bi-calendar-range report-range__icon"></i>
                    <input type="hidden" id="sale_from" name="from" value="{{ $from }}">
                    <input type="hidden" id="sale_to" name="to" value="{{ $to }}">
                    <input
                        type="text"
                        id="sale_range"
                        class="form-control form-control-sm js-date-range-display"
                        value="{{ $from === $to ? \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $from)->format('d/m/Y') : \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $from)->format('d/m/Y').' – '.\Illuminate\Support\Carbon::createFromFormat('Y-m-d', $to)->format('d/m/Y') }}"
                        placeholder="Selecciona un rango"
                        autocomplete="off"
                        readonly
                    >
                </div>
            </div>
            @if (! $is_today)
                <a href="{{ route('admin.sales.index') }}" class="btn btn-brand-outline btn-sm">Hoy</a>
            @endif
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="kpi-card kpi-card--sales">
                <div class="kpi-card__icon"><i class="bi bi-cash-stack"></i></div>
                <p class="kpi-card__label">{{ $is_single_day ? 'Total del día' : 'Total del periodo' }}</p>
                <p class="kpi-card__value kpi-card__value--money">{{ '$'.number_format($kpis['total'], 2) }}</p>
                <p class="kpi-card__hint">{{ $kpis['total_count'] }} {{ $kpis['total_count'] === 1 ? 'movimiento' : 'movimientos' }}</p>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="kpi-card kpi-card--full">
                <div class="kpi-card__icon"><i class="bi bi-check2-circle"></i></div>
                <p class="kpi-card__label">Pago completo</p>
                <p class="kpi-card__value kpi-card__value--money">{{ '$'.number_format($kpis['full'], 2) }}</p>
                <p class="kpi-card__hint">{{ $kpis['full_count'] }} {{ $kpis['full_count'] === 1 ? 'pago' : 'pagos' }}</p>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="kpi-card kpi-card--abonos">
                <div class="kpi-card__icon"><i class="bi bi-wallet2"></i></div>
                <p class="kpi-card__label">Abonos</p>
                <p class="kpi-card__value kpi-card__value--money">{{ '$'.number_format($kpis['installments'], 2) }}</p>
                <p class="kpi-card__hint">{{ $kpis['installment_count'] }} {{ $kpis['installment_count'] === 1 ? 'abono' : 'abonos' }}</p>
            </div>
        </div>
    </div>

    @include('admin.partials.panel-table', [
        'icon' => 'bi-cash-stack',
        'title' => 'Registro de ingresos',
        'subtitle' => count($sales).' '.(count($sales) === 1 ? 'ingreso' : 'ingresos').' en total',
        'table' => view('admin.partials.tables.sales-table', compact('sales'))->render(),
    ])
@endsection

@push('scripts')
    @vite(['resources/js/admin-datatables.js', 'resources/js/admin-sales.js'])
@endpush
