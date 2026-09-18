<div class="balance-report mb-4">
    <div class="balance-report__header">
        <h2 class="balance-report__title">
            <i class="bi bi-bar-chart-line"></i>
            Reporte de ventas y gastos por mes
        </h2>
        <form
            id="balanceYearForm"
            method="GET"
            action="{{ route('admin.reports.index') }}"
            class="balance-report__year"
        >
            <input type="hidden" name="from" value="{{ $from }}">
            <input type="hidden" name="to" value="{{ $to }}">
            <label for="balanceYear" class="mb-0">Año</label>
            <select id="balanceYear" name="year" class="form-select form-select-sm" aria-label="Año del reporte">
                @foreach ($balance['years'] as $optionYear)
                    <option value="{{ $optionYear }}" @selected($optionYear === $balance['year'])>{{ $optionYear }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="balance-report__body">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-6">
                <div class="table-responsive">
                    <table class="table align-middle balance-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Mes</th>
                                <th class="text-end">Ventas</th>
                                <th class="text-end">Gastos</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($balance['months'] as $month)
                                @php
                                    $balanceClass = $month['balance'] < 0 ? 'is-negative' : 'is-positive';
                                @endphp
                                <tr>
                                    <td>{{ $month['number'] }}</td>
                                    <td>{{ $month['name'] }}</td>
                                    <td class="text-end">{{ '$'.number_format($month['sales'], 2) }}</td>
                                    <td class="text-end">{{ '$'.number_format($month['expenses'], 2) }}</td>
                                    <td class="text-end {{ $balanceClass }}">{{ '$'.number_format($month['balance'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-6 d-flex flex-column">
                <h3 class="balance-report__chart-title">Estadísticas mensuales</h3>
                <div class="balance-report__chart">
                    <canvas
                        id="monthlyBalanceChart"
                        aria-label="Gráfica de ingresos y gastos por mes"
                        data-chart='@json($balance['chart'])'
                    ></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
