<table class="table table-hover align-middle admin-datatable w-100 mb-0">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Concepto</th>
            <th>Categoría</th>
            <th>Monto</th>
            <th>Pago</th>
            <th>Notas</th>
            <th class="no-sort">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($expenses as $expense)
            <tr>
                <td>{{ $expense->dateLabel() }}</td>
                <td>
                    <span class="fw-semibold">{{ $expense->concept }}</span>
                </td>
                <td><span class="table-badge">{{ $expense->categoryLabel() }}</span></td>
                <td>{{ $expense->amountLabel() }}</td>
                <td>{{ $expense->paymentMethodLabel() }}</td>
                <td>{{ $expense->notes ? \Illuminate\Support\Str::limit($expense->notes, 60) : '—' }}</td>
                <td>
                    <div class="table-actions">
                        <button
                            type="button"
                            class="btn btn-brand-outline js-edit-expense"
                            title="Editar información"
                            aria-label="Editar {{ $expense->concept }}"
                            data-id="{{ $expense->id }}"
                            data-date="{{ $expense->date?->toDateString() }}"
                            data-concept="{{ $expense->concept }}"
                            data-category="{{ $expense->category }}"
                            data-amount="{{ $expense->amount }}"
                            data-payment-method="{{ $expense->payment_method }}"
                            data-notes="{{ $expense->notes }}"
                        >
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form
                            method="POST"
                            action="{{ route('admin.expenses.destroy', $expense) }}"
                            class="js-soft-delete"
                            data-name="{{ $expense->concept }}"
                            data-entity="gasto"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="btn btn-brand-outline btn-delete"
                                title="Eliminar"
                                aria-label="Eliminar {{ $expense->concept }}"
                            >
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
