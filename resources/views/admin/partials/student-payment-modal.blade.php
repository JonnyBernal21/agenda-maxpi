@php
    $payStudent = old('_form') === 'student-payment' && old('student_id')
        ? \App\Models\Student::query()->withSum('payments as paid_amount', 'amount')->find(old('student_id'))
        : null;
    $payBalance = $payStudent?->balanceDue() ?? 0;
@endphp

<div
    class="modal fade"
    id="studentPayModal"
    tabindex="-1"
    aria-labelledby="studentPayModalLabel"
    aria-hidden="true"
    data-auto-open="{{ old('_form') === 'student-payment' ? 'true' : 'false' }}"
    data-today-label="{{ now()->timezone(config('app.timezone'))->format('d/m/Y') }}"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content student-pay-modal">
            <form method="POST" action="{{ $payStudent ? route('admin.students.payments.store', $payStudent) : '' }}" class="modal-form-layout" id="studentPayForm">
                @csrf
                <input type="hidden" name="_form" value="student-payment">
                <input type="hidden" name="student_id" id="studentPayStudentId" value="{{ old('student_id', $payStudent?->id) }}">

                <div class="modal-header student-pay-modal__header">
                    <div>
                        <p class="student-pay-modal__kicker">Registrar abono</p>
                        <h5 class="modal-title fw-semibold d-flex align-items-center" id="studentPayModalLabel">
                            <span class="modal-title-icon student-pay-modal__title-icon"><i class="bi bi-cash-coin"></i></span>
                            Abonar
                        </h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    @if ($errors->any() && old('_form') === 'student-payment')
                        <div class="alert alert-danger" role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="student-pay-modal__hero">
                        <p class="student-pay-modal__name mb-1" id="studentPayName">{{ $payStudent?->fullName() ?? 'Alumno' }}</p>
                        <p class="small text-muted mb-0">Saldo pendiente: <strong id="studentPayBalance">${{ number_format($payBalance, 2) }}</strong></p>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="student_pay_amount" class="form-label">Cantidad a abonar</label>
                            <div class="assign-field">
                                <i class="bi bi-currency-dollar assign-field__icon"></i>
                                <input
                                    type="number"
                                    id="student_pay_amount"
                                    name="amount"
                                    value="{{ old('amount') }}"
                                    class="form-control @error('amount') is-invalid @enderror"
                                    placeholder="0.00"
                                    inputmode="decimal"
                                    step="0.01"
                                    min="0.01"
                                    max="{{ $payBalance > 0 ? number_format($payBalance, 2, '.', '') : '' }}"
                                    required
                                >
                            </div>
                            @error('amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="student_pay_method" class="form-label">Método de pago</label>
                            <select
                                id="student_pay_method"
                                name="payment_method"
                                class="form-select @error('payment_method') is-invalid @enderror"
                                required
                            >
                                <option value="">Seleccionar</option>
                                @foreach (\App\Models\Student::PAYMENT_METHODS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('payment_method', $payStudent?->payment_method) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('payment_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="student_pay_date" class="form-label">Fecha</label>
                            <div class="assign-field">
                                <i class="bi bi-calendar3 assign-field__icon"></i>
                                <input
                                    type="text"
                                    id="student_pay_date"
                                    value="{{ now()->timezone(config('app.timezone'))->format('d/m/Y') }}"
                                    class="form-control student-pay-modal__date"
                                    readonly
                                    tabindex="-1"
                                    aria-readonly="true"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer student-pay-modal__footer">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand d-flex align-items-center gap-2">
                        <i class="bi bi-check-lg"></i>
                        Registrar abono
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
