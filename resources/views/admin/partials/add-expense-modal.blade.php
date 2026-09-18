<div
    class="modal fade"
    id="addExpenseModal"
    tabindex="-1"
    aria-labelledby="addExpenseModalLabel"
    aria-hidden="true"
    data-store-url="{{ route('admin.expenses.store') }}"
    data-update-base="{{ url('admin/expenses') }}"
    data-today="{{ now()->toDateString() }}"
    data-editing-id="{{ old('_form') === 'expense-edit' ? old('editing_id') : '' }}"
    data-auto-open="{{ ($errors->any() && in_array(old('_form'), ['expense', 'expense-edit'], true)) ? 'true' : 'false' }}"
>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.expenses.store') }}" class="modal-form-layout" id="expenseAdminForm">
                @csrf
                <input type="hidden" name="_method" id="expenseFormSpoofMethod" value="PUT" disabled>
                <input type="hidden" name="_form" id="expenseFormType" value="{{ old('_form', 'expense') }}">
                <input type="hidden" name="editing_id" id="expenseEditingId" value="{{ old('editing_id') }}">

                <div class="modal-header">
                    <h5 class="modal-title fw-semibold d-flex align-items-center" id="addExpenseModalLabel">
                        <span class="modal-title-icon"><i class="bi bi-receipt" id="expenseFormIcon"></i></span>
                        <span id="expenseFormTitle">Agregar gasto</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    @if ($errors->any() && in_array(old('_form'), ['expense', 'expense-edit'], true))
                        <div class="alert alert-danger" role="alert" id="expenseFormErrorAlert">
                            <p class="mb-1 fw-semibold">No se pudo guardar. Motivo:</p>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <p class="small text-muted mb-3 d-none" id="expenseFormHint">
                        Los cambios se reflejan de inmediato en el listado de gastos.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="expense_date" class="form-label">Fecha</label>
                            <input
                                type="date"
                                id="expense_date"
                                name="date"
                                value="{{ old('date', now()->toDateString()) }}"
                                class="form-control @error('date') is-invalid @enderror"
                                required
                            >
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-8">
                            <label for="expense_concept" class="form-label">Concepto</label>
                            <input
                                type="text"
                                id="expense_concept"
                                name="concept"
                                value="{{ old('concept') }}"
                                class="form-control @error('concept') is-invalid @enderror"
                                placeholder="Ej. Carga de gasolina"
                                required
                            >
                            @error('concept')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="expense_category" class="form-label">Categoría</label>
                            <select
                                id="expense_category"
                                name="category"
                                class="form-select @error('category') is-invalid @enderror"
                                required
                            >
                                <option value="">Seleccionar</option>
                                @foreach (\App\Models\Expense::CATEGORIES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="expense_amount" class="form-label">Monto</label>
                            <input
                                type="number"
                                id="expense_amount"
                                name="amount"
                                value="{{ old('amount') }}"
                                class="form-control @error('amount') is-invalid @enderror"
                                placeholder="1850.00"
                                min="0.01"
                                max="999999.99"
                                step="0.01"
                                required
                            >
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="expense_payment_method" class="form-label">Método de pago</label>
                            <select
                                id="expense_payment_method"
                                name="payment_method"
                                class="form-select @error('payment_method') is-invalid @enderror"
                                required
                            >
                                <option value="">Seleccionar</option>
                                @foreach (\App\Models\Expense::PAYMENT_METHODS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('payment_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="expense_notes" class="form-label">Notas</label>
                            <textarea
                                id="expense_notes"
                                name="notes"
                                rows="3"
                                class="form-control @error('notes') is-invalid @enderror"
                                placeholder="Detalle opcional del gasto."
                            >{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand d-flex align-items-center gap-2" id="expenseFormSubmit">
                        <i class="bi bi-check-lg"></i>
                        <span id="expenseFormSubmitLabel">Guardar gasto</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
