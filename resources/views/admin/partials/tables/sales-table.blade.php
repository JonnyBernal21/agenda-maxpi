<table class="table table-hover align-middle admin-datatable w-100 mb-0">
    <thead>
        <tr>
            <th>Cliente</th>
            <th>Concepto</th>
            <th>Método de pago</th>
            <th>Monto</th>
            <th>Registrado por</th>
            <th>Fecha y hora</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($sales as $sale)
            <tr>
                <td>
                    <span class="fw-semibold">{{ $sale->student?->fullName() ?? 'Cliente eliminado' }}</span>
                </td>
                <td>{{ $sale->conceptLabel() }}</td>
                <td>{{ $sale->paymentMethodLabel() }}</td>
                <td>{{ $sale->amountLabel() }}</td>
                <td>{{ $sale->creatorName() }}</td>
                <td data-order="{{ $sale->created_at?->timestamp ?? 0 }}">{{ $sale->recordedAtLabel() }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
