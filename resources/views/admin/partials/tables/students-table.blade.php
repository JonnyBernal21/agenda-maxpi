<table class="table table-hover align-middle admin-datatable admin-datatable--scroll students-datatable mb-0">
    <thead>
        <tr>
            <th>Alumno</th>
            <th>Curso</th>
            <th>Primera clase</th>
            <th>Clases</th>
            <th>Pago</th>
            <th class="no-sort">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($students as $student)
            @php
                $whatsapp = \App\Support\WhatsAppNumber::digits($student->phone);
                $extraClassesForForm = $student->extraClasses->map(fn ($extra) => [
                    'type' => $extra->type,
                    'quantity' => $extra->quantity,
                    'notes' => $extra->notes,
                ])->values();
                $firstClass = $student->firstClassWhen();
                $paidAmount = $student->paidAmount();
                $balanceDue = $student->balanceDue();
                $paidInFull = $balanceDue <= 0;
                $addressParts = collect([
                    $student->address,
                    $student->city,
                    $student->state,
                    $student->zip,
                    $student->country,
                ])->filter()->implode(', ');
            @endphp
            <tr>
                <td class="students-table__name-cell">
                    <div class="students-table__person">
                        <button
                            type="button"
                            class="students-table__toggle"
                            aria-expanded="false"
                            aria-label="Ver más información de {{ $student->fullName() }}"
                        >
                            <i class="bi bi-chevron-right"></i>
                        </button>
                        <div class="students-table__person-body">
                            <span class="students-table__name">{{ $student->name }} {{ $student->last_name }}</span>
                            @if ($student->is_home_class)
                                <span class="table-badge table-badge--home">A domicilio</span>
                            @endif
                        </div>
                    </div>
                    <div class="d-none students-table__details">
                        <div class="student-details">
                            <section class="student-details__col">
                                <h4 class="student-details__heading">Contacto</h4>
                                <dl class="student-details__list">
                                    <div class="student-details__row">
                                        <dt>Correo</dt>
                                        <dd>{{ $student->email ?: '—' }}</dd>
                                    </div>
                                    <div class="student-details__row">
                                        <dt>Teléfono</dt>
                                        <dd>{{ $student->phone ?: '—' }}</dd>
                                    </div>
                                    <div class="student-details__row">
                                        <dt>Domicilio</dt>
                                        <dd>{{ $addressParts !== '' ? $addressParts : '—' }}</dd>
                                    </div>
                                    @if (filled($student->notes))
                                        <div class="student-details__row">
                                            <dt>Notas</dt>
                                            <dd>{{ $student->notes }}</dd>
                                        </div>
                                    @endif
                                    @if ($student->is_home_class && filled($student->meeting_point))
                                        <div class="student-details__row">
                                            <dt>Indicaciones</dt>
                                            <dd>{{ $student->meeting_point }}</dd>
                                        </div>
                                    @endif
                                    @if ($student->is_home_class && $student->meeting_lat && $student->meeting_lng)
                                        <div class="student-details__row">
                                            <dt>Encuentro</dt>
                                            <dd>
                                                <a
                                                    href="https://www.google.com/maps?q={{ $student->meeting_lat }},{{ $student->meeting_lng }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    Ver en mapa
                                                </a>
                                            </dd>
                                        </div>
                                    @endif
                                </dl>
                            </section>
                            <section class="student-details__col">
                                <h4 class="student-details__heading">Curso</h4>
                                <dl class="student-details__list">
                                    <div class="student-details__row">
                                        <dt>Nombre</dt>
                                        <dd>{{ $student->course?->name ?? 'Sin curso' }}</dd>
                                    </div>
                                    <div class="student-details__row">
                                        <dt>Modalidad</dt>
                                        <dd>{{ $student->is_home_class ? 'A domicilio' : 'En escuela' }}</dd>
                                    </div>
                                    @if ($student->is_home_class && (float) $student->home_fee_amount > 0)
                                        <div class="student-details__row">
                                            <dt>Tarifa envío</dt>
                                            <dd>
                                                {{ $student->homeFeeInput() }}
                                                · {{ $student->homeFeeModeLabel() }}
                                                @if ($student->homeFeeTimes() > 1)
                                                    · {{ $student->homeFeeTimes() }} × ${{ number_format((float) $student->home_fee_amount, 2) }}
                                                    = ${{ number_format($student->homeFeeAppliedAmount(), 2) }}
                                                @else
                                                    · ${{ number_format((float) $student->home_fee_amount, 2) }}
                                                @endif
                                            </dd>
                                        </div>
                                    @endif
                                    <div class="student-details__row">
                                        <dt>Primera clase</dt>
                                        <dd>{{ $firstClass ? $firstClass['date'].' · '.$firstClass['time'] : 'Sin asignar' }}</dd>
                                    </div>
                                    <div class="student-details__row">
                                        <dt>Clases</dt>
                                        <dd>{{ $student->completed_classes_count ?? 0 }} / {{ $student->allowedClassesCount() }} · {{ $student->remaining_classes }} restantes</dd>
                                    </div>
                                    @if ($student->extraClassesCount() > 0)
                                        <div class="student-details__row">
                                            <dt>Adicionales</dt>
                                            <dd>
                                                {{ $student->extraClassesCount() }}
                                                @foreach ($student->extraClasses as $extra)
                                                    · {{ \App\Models\StudentExtraClass::TYPES[$extra->type] ?? $extra->type }} ({{ $extra->quantity }})
                                                @endforeach
                                            </dd>
                                        </div>
                                    @endif
                                </dl>
                            </section>
                            <section class="student-details__col">
                                <h4 class="student-details__heading">Pago</h4>
                                <dl class="student-details__list">
                                    <div class="student-details__row">
                                        <dt>Método</dt>
                                        <dd>{{ $student->paymentMethodLabel() }}</dd>
                                    </div>
                                    <div class="student-details__row">
                                        <dt>Plan</dt>
                                        <dd>{{ $student->paymentPlanLabel() }}</dd>
                                    </div>
                                    @if ($student->isPerClassPlan())
                                        <div class="student-details__row">
                                            <dt>Por clase</dt>
                                            <dd>${{ number_format($student->amountPerClass(), 2) }}</dd>
                                        </div>
                                    @endif
                                    @if ($student->isInstallmentPlan() || ($student->isPerClassPlan() && (float) $student->payment_initial > 0) || $student->defersHomeFeeToAbonos())
                                        <div class="student-details__row">
                                            <dt>{{ $student->isPerClassPlan() ? 'Abono de hoy' : ($student->defersHomeFeeToAbonos() ? 'Hoy (curso)' : 'Abono inicial') }}</dt>
                                            <dd>${{ number_format((float) $student->payment_initial, 2) }}</dd>
                                        </div>
                                    @endif
                                    <div class="student-details__row">
                                        <dt>Subtotal</dt>
                                        <dd>${{ number_format((float) $student->payment_subtotal, 2) }}</dd>
                                    </div>
                                    @if ((float) $student->discount_amount > 0)
                                        <div class="student-details__row">
                                            <dt>Descuento</dt>
                                            <dd>${{ number_format((float) $student->discount_amount, 2) }}</dd>
                                        </div>
                                    @endif
                                    <div class="student-details__row">
                                        <dt>Total</dt>
                                        <dd>${{ number_format((float) $student->payment_total, 2) }}</dd>
                                    </div>
                                    <div class="student-details__row">
                                        <dt>Abonado</dt>
                                        <dd>${{ number_format($paidAmount, 2) }}</dd>
                                    </div>
                                    <div class="student-details__row">
                                        <dt>Estado</dt>
                                        <dd>
                                            @if ($paidInFull)
                                                <span class="table-badge table-badge--paid">Liquidado</span>
                                            @else
                                                <span class="table-badge table-badge--due">Debe ${{ number_format($balanceDue, 2) }}</span>
                                            @endif
                                        </dd>
                                    </div>
                                </dl>
                            </section>
                        </div>
                    </div>
                </td>
                <td>
                    @if ($student->course)
                        <span class="table-badge table-badge--course">{{ $student->course->name }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if ($firstClass)
                        <span class="students-table__date">{{ $firstClass['date'] }}</span>
                        <span class="students-table__time">{{ $firstClass['time'] }}</span>
                    @else
                        <span class="text-muted">Sin asignar</span>
                    @endif
                </td>
                <td>
                    <span class="students-table__progress">
                        {{ $student->completed_classes_count ?? 0 }} / {{ $student->allowedClassesCount() }}
                    </span>
                    <span class="text-muted small d-block">
                        {{ $student->remaining_classes }} restantes
                    </span>
                </td>
                <td>
                    <div class="student-pay-cell">
                        @if ($paidInFull)
                            <span class="table-badge table-badge--paid">Liquidado</span>
                        @else
                            <span class="table-badge table-badge--due">Debe ${{ number_format($balanceDue, 2) }}</span>
                            @can('students.edit')
                            <button
                                type="button"
                                class="btn student-abonar-btn js-student-pay"
                                data-student-id="{{ $student->id }}"
                                data-student-name="{{ $student->fullName() }}"
                                data-balance="{{ number_format($balanceDue, 2, '.', '') }}"
                                data-balance-label="${{ number_format($balanceDue, 2) }}"
                                data-payment-method="{{ $student->payment_method }}"
                                data-per-class-amount="{{ ($suggested = $student->suggestedAbonoAmount()) > 0 ? number_format($suggested, 2, '.', '') : '' }}"
                                data-pay-url="{{ route('admin.students.payments.store', $student) }}"
                            >
                                <i class="bi bi-cash-coin"></i>
                                Abonar
                            </button>
                            @endcan
                        @endif
                        <button
                            type="button"
                            class="btn student-history-btn js-payment-history"
                            title="Historial de pagos"
                            aria-label="Ver historial de pagos de {{ $student->fullName() }}"
                            data-history-url="{{ route('admin.students.payment-history', $student) }}"
                            data-student-name="{{ $student->fullName() }}"
                        >
                            <i class="bi bi-clock-history"></i>
                            Historial
                        </button>
                    </div>
                </td>
                <td>
                    <div class="table-actions">
                        <button
                            type="button"
                            class="btn btn-brand-outline js-view-student-schedule"
                            title="Ver horarios"
                            aria-label="Ver horarios de {{ $student->fullName() }}"
                            data-student-id="{{ $student->id }}"
                            data-student-name="{{ $student->fullName() }}"
                            data-schedule-url="{{ route('admin.students.schedule', $student) }}"
                        >
                            <i class="bi bi-calendar-week"></i>
                        </button>
                        <button
                            type="button"
                            class="btn btn-brand-outline js-student-whatsapp"
                            title="{{ $whatsapp ? 'Enviar horarios por WhatsApp' : 'El alumno no tiene un teléfono válido para WhatsApp' }}"
                            aria-label="Enviar horarios por WhatsApp a {{ $student->fullName() }}"
                            data-whatsapp="{{ $whatsapp }}"
                            data-student-name="{{ $student->fullName() }}"
                            data-course="{{ $student->course?->name }}"
                            data-schedule-url="{{ route('admin.students.schedule', $student) }}"
                            @disabled(! $whatsapp)
                        >
                            <i class="bi bi-whatsapp"></i>
                        </button>
                        <button
                            type="button"
                            class="btn btn-brand-outline js-student-email"
                            title="Enviar horarios por correo"
                            aria-label="Enviar horarios por correo a {{ $student->fullName() }}"
                            data-email="{{ $student->email }}"
                            data-student-name="{{ $student->fullName() }}"
                            data-send-url="{{ route('admin.students.schedule-email', $student) }}"
                            data-receipt-url="{{ route('admin.students.receipt', $student) }}"
                            data-receipt-send-url="{{ route('admin.students.receipt-email', $student) }}"
                            data-history-url="{{ route('admin.students.payment-history', $student) }}"
                            @disabled(! $student->email)
                        >
                            <i class="bi bi-envelope"></i>
                        </button>
                        @can('students.edit')
                        <button
                            type="button"
                            class="btn btn-brand-outline js-edit-student"
                            title="Editar información"
                            aria-label="Editar a {{ $student->fullName() }}"
                            data-id="{{ $student->id }}"
                            data-course-id="{{ $student->course_id }}"
                            data-name="{{ $student->name }}"
                            data-last-name="{{ $student->last_name }}"
                            data-email="{{ $student->email }}"
                            data-phone="{{ $student->phone }}"
                            data-address="{{ $student->address }}"
                            data-city="{{ $student->city }}"
                            data-state="{{ $student->state }}"
                            data-zip="{{ $student->zip }}"
                            data-country="{{ $student->country }}"
                            data-course-classes="{{ $student->course?->num_classes ?? 0 }}"
                            data-extra-classes='@json($extraClassesForForm)'
                            data-is-home-class="{{ $student->is_home_class ? '1' : '0' }}"
                            data-home-fee="{{ $student->homeFeeInput() }}"
                            data-home-fee-mode="{{ $student->home_fee_mode ?: 'total' }}"
                            data-meeting-point="{{ $student->meeting_point }}"
                            data-general-notes="{{ $student->notes }}"
                            data-meeting-lat="{{ $student->meeting_lat }}"
                            data-meeting-lng="{{ $student->meeting_lng }}"
                            data-discount="{{ $student->discountInput() }}"
                            data-payment-method="{{ $student->payment_method }}"
                            data-payment-plan="{{ $student->payment_plan }}"
                            data-payment-initial="{{ ($student->isInstallmentPlan() || $student->isPerClassPlan()) && (float) $student->payment_initial > 0 ? number_format((float) $student->payment_initial, 2, '.', '') : '' }}"
                        >
                            <i class="bi bi-pencil"></i>
                        </button>
                        @endcan
                        @can('students.delete')
                        <form
                            method="POST"
                            action="{{ route('admin.students.destroy', $student) }}"
                            class="js-soft-delete"
                            data-name="{{ $student->fullName() }}"
                            data-entity="alumno"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="btn btn-brand-outline btn-delete"
                                title="Eliminar"
                                aria-label="Eliminar a {{ $student->fullName() }}"
                            >
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endcan
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
