@extends('layouts.admin')

@section('title', 'Panel administrador — ' . config('app.name'))

@section('content')
    @php
        $hasQuickActions = collect([
            'students.manage',
            'instructors.manage',
            'vehicles.manage',
            'courses.manage',
            'expenses.manage',
            'reservas.manage',
        ])->contains(fn ($permission) => auth()->user()?->can($permission));
        $canSeePayments = auth()->user()?->can('students.view')
            || auth()->user()?->can('students.manage')
            || auth()->user()?->can('students.edit')
            || auth()->user()?->can('students.pay')
            || auth()->user()?->can('sales.view');
        $canRegisterPayment = auth()->user()?->can('students.pay');
        $featuredAction = match (true) {
            auth()->user()?->can('students.manage') => 'student',
            auth()->user()?->can('reservas.manage') => 'schedule',
            default => null,
        };
    @endphp

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        @include('partials.page-greeting', [
            'name' => auth()->user()->name,
            'subtitle' => 'Aquí tienes el resumen de tu operación de hoy.',
        ])
        <div class="dashboard-toolbar">
            <span class="dashboard-toolbar__date">
                <i class="bi bi-calendar3"></i>
                <span class="dashboard-toolbar__date-full">{{ \App\Support\DayGreeting::todayLong() }}</span>
                <span class="dashboard-toolbar__date-short">{{ \App\Support\DayGreeting::todayShort() }}</span>
            </span>
            @can('reservas.manage')
                <button
                    type="button"
                    class="btn btn-brand dashboard-toolbar__cta"
                    data-bs-toggle="modal"
                    data-bs-target="#scheduleClassModal"
                >
                    <i class="bi bi-plus-lg"></i>
                    Agendar clase
                </button>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            @can('reports.view')
                <a href="{{ route('admin.reports.index') }}" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="stat-card__icon"><i class="bi bi-calendar-day"></i></div>
                        <p class="stat-card__label">Clases de hoy</p>
                        <p class="stat-card__value">{{ $classesTodayCount }}</p>
                    </div>
                </a>
            @else
                <div class="stat-card">
                    <div class="stat-card__icon"><i class="bi bi-calendar-day"></i></div>
                    <p class="stat-card__label">Clases de hoy</p>
                    <p class="stat-card__value">{{ $classesTodayCount }}</p>
                </div>
            @endcan
        </div>
        <div class="col-6 col-md-3">
            @can('sales.view')
                <a href="{{ route('admin.sales.index') }}" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="stat-card__icon"><i class="bi bi-cash-stack"></i></div>
                        <p class="stat-card__label">Ingresos del día</p>
                        <p class="stat-card__value stat-card__value--money">{{ '$'.number_format($todayIncome, 2) }}</p>
                    </div>
                </a>
            @else
                <div class="stat-card">
                    <div class="stat-card__icon"><i class="bi bi-cash-stack"></i></div>
                    <p class="stat-card__label">Ingresos del día</p>
                    <p class="stat-card__value stat-card__value--money">{{ '$'.number_format($todayIncome, 2) }}</p>
                </div>
            @endcan
        </div>
        <div class="col-6 col-md-3">
            @can('instructors.manage')
                <a href="{{ route('admin.instructors.index') }}" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="stat-card__icon"><i class="bi bi-person-badge"></i></div>
                        <p class="stat-card__label">Instructores</p>
                        <p class="stat-card__value">{{ $instructorsCount }}</p>
                    </div>
                </a>
            @else
                <div class="stat-card">
                    <div class="stat-card__icon"><i class="bi bi-person-badge"></i></div>
                    <p class="stat-card__label">Instructores</p>
                    <p class="stat-card__value">{{ $instructorsCount }}</p>
                </div>
            @endcan
        </div>
        <div class="col-6 col-md-3">
            @can('vehicles.manage')
                <a href="{{ route('admin.vehicles.index') }}" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="stat-card__icon"><i class="bi bi-car-front"></i></div>
                        <p class="stat-card__label">Vehículos</p>
                        <p class="stat-card__value">{{ $vehiclesCount }}</p>
                    </div>
                </a>
            @else
                <div class="stat-card">
                    <div class="stat-card__icon"><i class="bi bi-car-front"></i></div>
                    <p class="stat-card__label">Vehículos</p>
                    <p class="stat-card__value">{{ $vehiclesCount }}</p>
                </div>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-4 dashboard-split align-items-stretch">
        <div class="{{ ($hasQuickActions || $canSeePayments) ? 'col-xl-8' : 'col-12' }}">
            @include('partials.day-agenda', [
                'dayAgenda' => $dayAgenda,
                'agendaUrl' => route('admin.agenda'),
            ])
        </div>

        @if ($hasQuickActions || $canSeePayments)
            <div class="col-xl-4">
                <div class="dashboard-side">
            @if ($hasQuickActions)
                    <section class="dash-panel is-collapsed" data-dash-panel="quick-actions">
                        <header class="dash-panel__head">
                            <h2 class="dash-panel__title">
                                <i class="bi bi-lightning-charge-fill"></i>
                                Acciones rápidas
                            </h2>
                            <button
                                type="button"
                                class="dash-panel__toggle"
                                aria-expanded="false"
                                title="Ver más"
                                aria-label="Ver más"
                            >
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </header>
                        <div class="dash-panel__body">
                        <div class="quick-actions">
                            @can('students.manage')
                                <button
                                    type="button"
                                    class="quick-action {{ $featuredAction === 'student' ? 'is-featured' : '' }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addStudentModal"
                                >
                                    <span class="quick-action__icon"><i class="bi {{ $featuredAction === 'student' ? 'bi-plus-lg' : 'bi-person-plus' }}"></i></span>
                                    <span class="quick-action__copy">
                                        <strong>Agregar alumno</strong>
                                        <small>Da de alta un nuevo alumno.</small>
                                    </span>
                                </button>
                            @endcan
                            @can('reservas.manage')
                                <button
                                    type="button"
                                    class="quick-action {{ $featuredAction === 'schedule' ? 'is-featured' : '' }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#scheduleClassModal"
                                    id="openScheduleManualBtn"
                                >
                                    <span class="quick-action__icon"><i class="bi {{ $featuredAction === 'schedule' ? 'bi-plus-lg' : 'bi-calendar-plus' }}"></i></span>
                                    <span class="quick-action__copy">
                                        <strong>Agendar clase</strong>
                                        <small>Agenda una clase o evaluación.</small>
                                    </span>
                                </button>
                            @endcan
                            @can('instructors.manage')
                                <button
                                    type="button"
                                    class="quick-action"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addInstructorModal"
                                >
                                    <span class="quick-action__icon"><i class="bi bi-person-badge"></i></span>
                                    <span class="quick-action__copy">
                                        <strong>Agregar instructor</strong>
                                        <small>Registra un instructor disponible.</small>
                                    </span>
                                </button>
                            @endcan
                            @can('vehicles.manage')
                                <button
                                    type="button"
                                    class="quick-action"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addVehicleModal"
                                >
                                    <span class="quick-action__icon"><i class="bi bi-car-front"></i></span>
                                    <span class="quick-action__copy">
                                        <strong>Agregar vehículo</strong>
                                        <small>Añade un vehículo a la flota.</small>
                                    </span>
                                </button>
                            @endcan
                            @can('courses.manage')
                                <button
                                    type="button"
                                    class="quick-action"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addCourseModal"
                                >
                                    <span class="quick-action__icon"><i class="bi bi-journal-plus"></i></span>
                                    <span class="quick-action__copy">
                                        <strong>Agregar curso</strong>
                                        <small>Crea un programa o paquete.</small>
                                    </span>
                                </button>
                            @endcan
                            @can('expenses.manage')
                                <button
                                    type="button"
                                    class="quick-action"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addExpenseModal"
                                >
                                    <span class="quick-action__icon"><i class="bi bi-receipt"></i></span>
                                    <span class="quick-action__copy">
                                        <strong>Agregar gasto</strong>
                                        <small>Registra una salida de dinero.</small>
                                    </span>
                                </button>
                            @endcan
                        </div>
                        </div>
                    </section>
            @endif

            @if ($canSeePayments)
                    <section class="dash-panel is-collapsed" data-dash-panel="upcoming-payments">
                        <header class="dash-panel__head">
                            <h2 class="dash-panel__title">
                                <i class="bi bi-credit-card"></i>
                                Próximos pagos
                            </h2>
                            <div class="dash-panel__tools">
                            @if (auth()->user()?->can('students.view') || auth()->user()?->can('students.manage') || auth()->user()?->can('students.edit'))
                                <a href="{{ route('admin.students.index') }}" class="dash-panel__link">
                                    Ver todos
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            @elseif (auth()->user()?->can('sales.view'))
                                <a href="{{ route('admin.sales.index') }}" class="dash-panel__link">
                                    Ver todos
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            @endif
                            <button
                                type="button"
                                class="dash-panel__toggle"
                                aria-expanded="false"
                                title="Ver más"
                                aria-label="Ver más"
                            >
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            </div>
                        </header>
                        <div class="dash-panel__body">
                        @if ($upcomingPayments === [])
                            <p class="dash-panel__empty">No hay pagos próximos por ahora.</p>
                        @else
                            <ul class="upcoming-pay">
                                @foreach ($upcomingPayments as $item)
                                    @php $isPayable = $item['status'] === 'pending' && $canRegisterPayment; @endphp
                                    <li
                                        class="upcoming-pay__row{{ $isPayable ? ' is-payable js-student-pay' : '' }}"
                                        @if ($isPayable)
                                            role="button"
                                            tabindex="0"
                                            data-student-id="{{ $item['student_id'] }}"
                                            data-student-name="{{ $item['name'] }}"
                                            data-balance="{{ number_format($item['balance'], 2, '.', '') }}"
                                            data-balance-label="${{ number_format($item['balance'], 2) }}"
                                            data-payment-method="{{ $item['payment_method'] }}"
                                            data-per-class-amount="{{ $item['suggested'] > 0 ? number_format($item['suggested'], 2, '.', '') : '' }}"
                                            data-pay-url="{{ $item['pay_url'] }}"
                                        @endif
                                    >
                                        <span class="upcoming-pay__avatar" aria-hidden="true">{{ $item['initials'] }}</span>
                                        <div class="upcoming-pay__person">
                                            <strong>{{ $item['name'] }}</strong>
                                            <small>{{ $item['date_label'] }}</small>
                                        </div>
                                        <span class="upcoming-pay__amount">{{ $item['amount_label'] }}</span>
                                        <span class="upcoming-pay__status upcoming-pay__status--{{ $item['status'] }}">
                                            {{ $item['status_label'] }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        </div>
                    </section>
            @endif
                </div>
            </div>
        @endif
    </div>

    <div class="panel-card dashboard-calendar">
        <div class="panel-card__header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="stat-card__icon mb-0 mt-1"><i class="bi bi-calendar3"></i></div>
                    <div>
                        <h2 class="h5 fw-semibold mb-1">Calendario de clases</h2>
                        <p class="text-muted small mb-0">Bloques verdes = horarios libres. Arrastra una clase para cambiar fecha y hora.</p>
                    </div>
                </div>
                @include('admin.partials.calendar-legend')
            </div>
        </div>
        <div class="panel-card__body">
            <div
                id="admin-calendar"
                data-events-url="{{ route('admin.calendar.events') }}"
                data-confirm-url="{{ url('admin/reservas') }}"
                data-min-date="{{ $minBookableDate }}"
                data-same-day-message="{{ $sameDayScheduleMessage }}"
            ></div>
            <div class="px-3 pb-3 pt-2 border-top">
                <div id="adminCalendarHint" class="alert alert-info mb-0">
                    <i class="bi bi-hand-index-thumb me-1"></i>
                    Haz clic en un bloque <strong>verde</strong> para agendar. Arrastra una clase para cambiar su fecha u horario.
                </div>
            </div>
        </div>
    </div>

    @include('partials.event-detail-modal', [
        'showInstructor' => true,
        'showActions' => true,
    ])

    <div
        class="modal fade"
        id="rescheduleClassModal"
        tabindex="-1"
        aria-labelledby="rescheduleClassModalLabel"
        aria-hidden="true"
        data-bs-backdrop="static"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content--stack">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold d-flex align-items-center" id="rescheduleClassModalLabel">
                        <span class="modal-title-icon"><i class="bi bi-arrows-move"></i></span>
                        Confirmar cambio de horario
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        ¿Mover la clase de
                        <strong id="rescheduleModalStudent">este alumno</strong>?
                    </p>
                    <div class="reschedule-compare">
                        <div class="reschedule-compare__col">
                            <span class="reschedule-compare__label">Actual</span>
                            <span class="reschedule-compare__date" id="rescheduleFromDate">—</span>
                            <span class="reschedule-compare__time" id="rescheduleFromTime">—</span>
                        </div>
                        <div class="reschedule-compare__arrow" aria-hidden="true">
                            <i class="bi bi-arrow-right"></i>
                        </div>
                        <div class="reschedule-compare__col reschedule-compare__col--next">
                            <span class="reschedule-compare__label">Nuevo</span>
                            <span class="reschedule-compare__date" id="rescheduleToDate">—</span>
                            <span class="reschedule-compare__time" id="rescheduleToTime">—</span>
                        </div>
                    </div>
                    <div id="rescheduleModalError" class="alert alert-danger mt-3 mb-0 d-none" role="alert"></div>
                </div>
                <div class="modal-footer flex-wrap gap-2">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="confirmRescheduleBtn" class="btn btn-brand d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle"></i>
                        Confirmar cambio
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/admin-reservas.js', 'resources/js/admin-calendar.js'])
@endpush
