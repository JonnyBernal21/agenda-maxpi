@php
    $canDiscount = auth()->user()?->can('students.discount');
    $wizardErrorStep = '';

    if ($errors->any() && in_array(old('_form'), ['student', 'student-edit'], true)) {
        $wizardErrorMap = [
            'person' => ['name', 'last_name', 'email', 'phone', 'general_notes'],
            'address' => ['address', 'city', 'state', 'zip', 'country'],
            'course' => ['course_id', 'is_home_class', 'meeting_point', 'meeting_lat', 'meeting_lng'],
            'pay' => ['discount', 'payment_method', 'payment_plan', 'payment_initial', 'home_fee', 'home_fee_mode'],
            'extras' => ['extra_classes'],
        ];

        foreach ($wizardErrorMap as $step => $fields) {
            $hasError = collect($fields)->contains(function ($field) use ($errors) {
                return $errors->has($field) || collect($errors->keys())->contains(
                    fn ($key) => str_starts_with((string) $key, $field.'.')
                );
            });

            if ($hasError) {
                $wizardErrorStep = $step;
                break;
            }
        }
    }
@endphp

<div
    class="modal fade"
    id="addStudentModal"
    tabindex="-1"
    aria-labelledby="addStudentModalLabel"
    aria-hidden="true"
    data-store-url="{{ route('admin.students.store') }}"
    data-update-base="{{ url('admin/students') }}"
    data-editing-id="{{ old('_form') === 'student-edit' ? old('editing_id') : '' }}"
    data-auto-open="{{ ($errors->any() && in_array(old('_form'), ['student', 'student-edit'], true)) ? 'true' : 'false' }}"
    data-old-extras='@json(old('extra_classes', []))'
    data-wizard-error-step="{{ $wizardErrorStep }}"
    data-geocode-url="{{ route('admin.geocode.search') }}"
    data-reverse-url="{{ route('admin.geocode.reverse') }}"
    data-map-lat="19.4326"
    data-map-lng="-99.1332"
    data-map-query="{{ collect([$appSetting?->city, $appSetting?->state, $appSetting?->countryName()])->filter()->implode(', ') }}"
>
    <div class="modal-dialog modal-lg student-modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content student-modal modal-content--stack">
            <form method="POST" action="{{ route('admin.students.store') }}" class="modal-form-layout" id="studentAdminForm">
                @csrf
                <input type="hidden" name="_method" id="studentFormSpoofMethod" value="PUT" disabled>
                <input type="hidden" name="_form" id="studentFormType" value="{{ old('_form', 'student') }}">
                <input type="hidden" name="editing_id" id="studentEditingId" value="{{ old('editing_id') }}">

                <div class="modal-header student-modal__header">
                    <div>
                        <p class="student-modal__kicker" id="studentFormKicker">Paso 1 de 4</p>
                        <h5 class="modal-title fw-semibold d-flex align-items-center" id="addStudentModalLabel">
                            <span class="modal-title-icon student-modal__title-icon"><i class="bi bi-person-plus" id="studentFormIcon"></i></span>
                            <span id="studentFormTitle">Agregar alumno</span>
                        </h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body student-modal__body">
                    @if ($errors->any() && in_array(old('_form'), ['student', 'student-edit'], true))
                        <div class="alert alert-danger" role="alert" id="studentFormErrorAlert">
                            Revisa los campos marcados e intenta de nuevo.
                        </div>
                    @endif

                    <ol class="student-wizard-nav" id="studentWizardNav">
                        <li data-wizard-nav="person" class="is-current">
                            <span class="student-wizard-nav__num">1</span>
                            <span class="student-wizard-nav__label">Datos</span>
                        </li>
                        <li data-wizard-nav="address">
                            <span class="student-wizard-nav__num">2</span>
                            <span class="student-wizard-nav__label">Domicilio</span>
                        </li>
                        <li data-wizard-nav="course">
                            <span class="student-wizard-nav__num">3</span>
                            <span class="student-wizard-nav__label">Curso</span>
                        </li>
                        <li data-wizard-nav="pay">
                            <span class="student-wizard-nav__num">4</span>
                            <span class="student-wizard-nav__label">Pago</span>
                        </li>
                        <li data-wizard-nav="extras" class="d-none">
                            <span class="student-wizard-nav__num">5</span>
                            <span class="student-wizard-nav__label">Extra</span>
                        </li>
                    </ol>

                    <section class="assign-step student-step--person student-wizard-pane is-active" data-wizard-step="person">
                        <header class="assign-step__head">
                            <span class="assign-step__num">1</span>
                            <div>
                                <h3 class="assign-step__title">Datos del alumno</h3>
                                <p class="assign-step__help">Nombre y forma de contacto.</p>
                            </div>
                        </header>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="student_name" class="form-label">Nombre</label>
                                <div class="assign-field">
                                    <i class="bi bi-person assign-field__icon"></i>
                                    <input
                                        type="text"
                                        id="student_name"
                                        name="name"
                                        value="{{ old('name') }}"
                                        class="form-control input-uppercase @error('name') is-invalid @enderror"
                                        placeholder="EJ. JUAN"
                                        autocapitalize="characters"
                                        required
                                    >
                                </div>
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="student_last_name" class="form-label">Apellido</label>
                                <div class="assign-field">
                                    <i class="bi bi-person assign-field__icon"></i>
                                    <input
                                        type="text"
                                        id="student_last_name"
                                        name="last_name"
                                        value="{{ old('last_name') }}"
                                        class="form-control input-uppercase @error('last_name') is-invalid @enderror"
                                        placeholder="EJ. PÉREZ GARCÍA"
                                        autocapitalize="characters"
                                        required
                                    >
                                </div>
                                @error('last_name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="student_email" class="form-label">Correo electrónico</label>
                                <div class="assign-field">
                                    <i class="bi bi-envelope assign-field__icon"></i>
                                    <input
                                        type="email"
                                        id="student_email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        class="form-control @error('email') is-invalid @enderror"
                                        placeholder="alumno@correo.com"
                                        required
                                    >
                                </div>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="student_phone" class="form-label">Teléfono</label>
                                <div class="assign-field">
                                    <i class="bi bi-telephone assign-field__icon"></i>
                                    <input
                                        type="text"
                                        id="student_phone"
                                        name="phone"
                                        value="{{ old('phone') }}"
                                        class="form-control @error('phone') is-invalid @enderror"
                                        placeholder="+52 55 1234 5678"
                                        required
                                    >
                                </div>
                                @error('phone')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="student_general_notes" class="form-label">Notas</label>
                                <div class="assign-field assign-field--textarea">
                                    <i class="bi bi-sticky assign-field__icon"></i>
                                    <textarea
                                        id="student_general_notes"
                                        name="general_notes"
                                        class="form-control @error('general_notes') is-invalid @enderror"
                                        placeholder="Observaciones del alumno (se muestran al pasar el cursor sobre su clase)"
                                        maxlength="1000"
                                        rows="3"
                                    >{{ old('general_notes') }}</textarea>
                                </div>
                                @error('general_notes')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <section class="assign-step student-step--address student-wizard-pane" data-wizard-step="address">
                        <header class="assign-step__head">
                            <span class="assign-step__num">2</span>
                            <div>
                                <h3 class="assign-step__title">Domicilio</h3>
                                <p class="assign-step__help">Se usa para contacto y para clases a domicilio.</p>
                            </div>
                        </header>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="student_address" class="form-label">Dirección</label>
                                <div class="assign-field">
                                    <i class="bi bi-geo assign-field__icon"></i>
                                    <input
                                        type="text"
                                        id="student_address"
                                        name="address"
                                        value="{{ old('address') }}"
                                        class="form-control input-uppercase @error('address') is-invalid @enderror"
                                        placeholder="CALLE, NÚMERO, COLONIA"
                                        autocapitalize="characters"
                                        required
                                    >
                                </div>
                                @error('address')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="student_city" class="form-label">Ciudad</label>
                                <input
                                    type="text"
                                    id="student_city"
                                    name="city"
                                    value="{{ old('city') }}"
                                    class="form-control input-uppercase @error('city') is-invalid @enderror"
                                    placeholder="CIUDAD DE MÉXICO"
                                    autocapitalize="characters"
                                    required
                                >
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="student_state" class="form-label">Estado</label>
                                <input
                                    type="text"
                                    id="student_state"
                                    name="state"
                                    value="{{ old('state') }}"
                                    class="form-control input-uppercase @error('state') is-invalid @enderror"
                                    placeholder="CDMX"
                                    autocapitalize="characters"
                                    required
                                >
                                @error('state')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="student_zip" class="form-label">Código postal</label>
                                <input
                                    type="text"
                                    id="student_zip"
                                    name="zip"
                                    value="{{ old('zip') }}"
                                    class="form-control @error('zip') is-invalid @enderror"
                                    placeholder="01000"
                                    required
                                >
                                @error('zip')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="student_country" class="form-label">País</label>
                                <input
                                    type="text"
                                    id="student_country"
                                    name="country"
                                    value="{{ old('country', 'México') }}"
                                    class="form-control @error('country') is-invalid @enderror"
                                    placeholder="México"
                                    required
                                >
                                @error('country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <section class="assign-step student-step--course student-wizard-pane" data-wizard-step="course">
                        <header class="assign-step__head">
                            <span class="assign-step__num">3</span>
                            <div>
                                <h3 class="assign-step__title">Curso y modalidad</h3>
                                <p class="assign-step__help">Define qué va a tomar y si las clases son en escuela o a domicilio.</p>
                            </div>
                        </header>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="student_course_id" class="form-label">Curso</label>
                                <div class="assign-field">
                                    <i class="bi bi-journal-text assign-field__icon"></i>
                                    <select
                                        id="student_course_id"
                                        name="course_id"
                                        class="form-select @error('course_id') is-invalid @enderror"
                                        required
                                    >
                                        <option value="">Seleccionar curso</option>
                                        @foreach ($courses as $course)
                                            <option
                                                value="{{ $course->id }}"
                                                data-num-classes="{{ $course->num_classes }}"
                                                data-cost="{{ $course->cost }}"
                                                @selected(old('course_id') == $course->id)
                                            >
                                                {{ $course->name }} — {{ $course->num_classes }} clases — ${{ number_format($course->cost, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('course_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="student_is_home_class" class="form-label">Modalidad</label>
                                <div class="assign-field">
                                    <i class="bi bi-geo-alt assign-field__icon"></i>
                                    <select
                                        id="student_is_home_class"
                                        name="is_home_class"
                                        class="form-select @error('is_home_class') is-invalid @enderror"
                                        required
                                    >
                                        <option value="0" @selected(! old('is_home_class'))>En escuela</option>
                                        <option value="1" @selected((string) old('is_home_class') === '1')>A domicilio</option>
                                    </select>
                                </div>
                                @error('is_home_class')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 {{ (string) old('is_home_class') === '1' ? '' : 'd-none' }}" id="studentHomeClassWrap">
                                <label class="form-label" for="student_meeting_search">Punto de encuentro</label>
                                <div class="student-meeting-search">
                                    <div class="assign-field">
                                        <i class="bi bi-search assign-field__icon"></i>
                                        <input
                                            type="text"
                                            id="student_meeting_search"
                                            class="form-control"
                                            placeholder="Buscar una dirección"
                                            autocomplete="off"
                                        >
                                    </div>
                                    <button type="button" class="btn btn-brand" id="studentMeetingSearchBtn">
                                        Buscar
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-brand-outline"
                                        id="studentMeetingLocateBtn"
                                        title="Usar mi ubicación actual"
                                    >
                                        <i class="bi bi-geo-alt-fill"></i>
                                        Mi ubicación
                                    </button>
                                </div>
                                <div id="studentMeetingMap" class="student-meeting-map" role="application" aria-label="Mapa para elegir el punto de encuentro"></div>
                                <p class="small text-muted mt-2 mb-1" id="studentMeetingHint">
                                    Usamos tu ubicación actual. También puedes buscar, hacer clic o arrastrar el pin.
                                </p>
                                <a
                                    id="studentMeetingGoogleLink"
                                    class="small {{ old('meeting_lat') && old('meeting_lng') ? '' : 'd-none' }}"
                                    href="{{ old('meeting_lat') && old('meeting_lng') ? 'https://www.google.com/maps?q='.old('meeting_lat').','.old('meeting_lng') : '#' }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Ver en Google Maps
                                </a>
                                <input
                                    type="hidden"
                                    id="student_meeting_lat"
                                    name="meeting_lat"
                                    value="{{ old('meeting_lat') }}"
                                    @disabled((string) old('is_home_class') !== '1')
                                >
                                <input
                                    type="hidden"
                                    id="student_meeting_lng"
                                    name="meeting_lng"
                                    value="{{ old('meeting_lng') }}"
                                    @disabled((string) old('is_home_class') !== '1')
                                >
                                @error('meeting_lat')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @error('meeting_lng')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror

                                <label for="student_meeting_point" class="form-label mt-3">Notas</label>
                                <div class="assign-field assign-field--textarea">
                                    <i class="bi bi-chat-left-text assign-field__icon"></i>
                                    <textarea
                                        id="student_meeting_point"
                                        name="meeting_point"
                                        class="form-control @error('meeting_point') is-invalid @enderror"
                                        placeholder="Indicaciones extra: portón, timbre, referencias..."
                                        maxlength="255"
                                        rows="3"
                                        @disabled((string) old('is_home_class') !== '1')
                                    >{{ old('meeting_point') }}</textarea>
                                </div>
                                @error('meeting_point')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <section class="assign-step student-step--pay student-wizard-pane" data-wizard-step="pay">
                        <header class="assign-step__head">
                            <span class="assign-step__num">4</span>
                            <div>
                                <h3 class="assign-step__title">Pago del curso</h3>
                                <p class="assign-step__help">
                                    @can('students.discount')
                                        El subtotal sale del curso y, si es a domicilio, de la tarifa de envío. En descuento puedes escribir <strong>%15</strong> o <strong>15.00</strong>.
                                    @else
                                        El subtotal sale del curso y, si es a domicilio, de la tarifa de envío.
                                    @endcan
                                </p>
                            </div>
                        </header>
                        <div class="row g-3">
                            <div class="col-12 {{ (string) old('is_home_class') === '1' ? '' : 'd-none' }}" id="studentHomeFeeWrap">
                                <label for="student_home_fee" class="form-label">Tarifa de envío</label>
                                <div class="assign-field">
                                    <i class="bi bi-cash-coin assign-field__icon"></i>
                                    <input
                                        type="text"
                                        id="student_home_fee"
                                        name="home_fee"
                                        value="{{ old('home_fee') }}"
                                        class="form-control @error('home_fee') is-invalid @enderror"
                                        placeholder="%10 o 100.00"
                                        inputmode="decimal"
                                        autocomplete="off"
                                        @disabled((string) old('is_home_class') !== '1')
                                    >
                                </div>
                                @error('home_fee')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror

                                <p class="form-label mt-3 mb-2">Cómo se aplica</p>
                                <div class="student-fee-mode" role="radiogroup" aria-label="Cómo se aplica la tarifa de envío">
                                    @php
                                        $homeFeeMode = old('home_fee_mode', \App\Models\Student::HOME_FEE_MODE_TOTAL);
                                    @endphp
                                    <label class="student-fee-mode__option">
                                        <input
                                            type="radio"
                                            name="home_fee_mode"
                                            value="{{ \App\Models\Student::HOME_FEE_MODE_TOTAL }}"
                                            @checked($homeFeeMode !== \App\Models\Student::HOME_FEE_MODE_PER_PAYMENT)
                                            @disabled((string) old('is_home_class') !== '1')
                                        >
                                        <span class="student-fee-mode__card">
                                            <strong>Al costo total</strong>
                                            <small>Se suma una sola vez</small>
                                        </span>
                                    </label>
                                    <label class="student-fee-mode__option">
                                        <input
                                            type="radio"
                                            name="home_fee_mode"
                                            value="{{ \App\Models\Student::HOME_FEE_MODE_PER_PAYMENT }}"
                                            @checked($homeFeeMode === \App\Models\Student::HOME_FEE_MODE_PER_PAYMENT)
                                            @disabled((string) old('is_home_class') !== '1')
                                        >
                                        <span class="student-fee-mode__card">
                                            <strong>En cada clase o abono</strong>
                                            <small>Se cobra en cada pago</small>
                                        </span>
                                    </label>
                                </div>
                                @error('home_fee_mode')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <p class="small text-muted mt-2 mb-0" id="studentHomeFeeHint">
                                    Escribe un monto o un porcentaje (ej. %10). Luego elige si se suma al total o se aplica en cada clase o abono.
                                </p>
                            </div>

                            <div class="{{ $canDiscount ? 'col-md-4' : 'col-md-6' }}">
                                <label class="form-label">Subtotal</label>
                                <div class="student-payment-figure" id="studentPaymentSubtotalLabel">$0.00</div>
                            </div>

                            @can('students.discount')
                                <div class="col-md-4">
                                    <label for="student_discount" class="form-label">Descuento</label>
                                    <div class="assign-field">
                                        <i class="bi bi-percent assign-field__icon"></i>
                                        <input
                                            type="text"
                                            id="student_discount"
                                            name="discount"
                                            value="{{ old('discount') }}"
                                            class="form-control @error('discount') is-invalid @enderror"
                                            placeholder="%15 o 15.00"
                                            inputmode="decimal"
                                            autocomplete="off"
                                        >
                                    </div>
                                    @error('discount')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            @else
                                <input type="hidden" id="student_discount" name="discount" value="{{ old('discount') }}">
                            @endcan

                            <div class="{{ $canDiscount ? 'col-md-4' : 'col-md-6' }}">
                                <label class="form-label">Total</label>
                                <div class="student-payment-figure student-payment-figure--total" id="studentPaymentTotalLabel">$0.00</div>
                            </div>

                            <div class="col-md-6">
                                <label for="student_payment_method" class="form-label">Método de pago</label>
                                <div class="assign-field">
                                    <i class="bi bi-wallet2 assign-field__icon"></i>
                                    <select
                                        id="student_payment_method"
                                        name="payment_method"
                                        class="form-select @error('payment_method') is-invalid @enderror"
                                        required
                                    >
                                        <option value="">Seleccionar</option>
                                        @foreach (\App\Models\Student::PAYMENT_METHODS as $value => $label)
                                            <option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('payment_method')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="student_payment_plan" class="form-label">Forma de pago</label>
                                <div class="assign-field">
                                    <i class="bi bi-cash-stack assign-field__icon"></i>
                                    <select
                                        id="student_payment_plan"
                                        name="payment_plan"
                                        class="form-select @error('payment_plan') is-invalid @enderror"
                                        required
                                    >
                                        @foreach (\App\Models\Student::PAYMENT_PLANS as $value => $label)
                                            <option value="{{ $value }}" @selected((string) old('payment_plan', '1') === (string) $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('payment_plan')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 {{ in_array((int) old('payment_plan', 1), [0, 2, 3, 4], true) ? '' : 'd-none' }}" id="studentPaymentInitialWrap">
                                <label for="student_payment_initial" class="form-label" id="studentPaymentInitialLabel">Cantidad inicial abonada</label>
                                <div class="assign-field">
                                    <i class="bi bi-cash-coin assign-field__icon"></i>
                                    <input
                                        type="number"
                                        id="student_payment_initial"
                                        name="payment_initial"
                                        value="{{ old('payment_initial') }}"
                                        class="form-control @error('payment_initial') is-invalid @enderror"
                                        placeholder="0.00"
                                        inputmode="decimal"
                                        step="0.01"
                                        min="{{ (int) old('payment_plan', 1) === 0 ? '0' : '0.01' }}"
                                        autocomplete="off"
                                        @disabled(! in_array((int) old('payment_plan', 1), [0, 2, 3, 4], true))
                                    >
                                </div>
                                @error('payment_initial')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <p class="small text-muted mt-2 mb-0" id="studentPaymentInitialHelp">Lo que paga hoy. El resto se reparte en los pagos siguientes.</p>
                            </div>

                            <div class="col-12">
                                <p class="student-pay-hint mb-0" id="studentPaymentPlanHint">Se cobra el total en un solo pago.</p>
                            </div>
                        </div>
                    </section>

                    <section
                        class="assign-step student-step--extras student-wizard-pane d-none"
                        id="studentExtraClassesSection"
                        data-wizard-step="extras"
                        data-extra-types='@json(\App\Models\StudentExtraClass::TYPES)'
                    >
                        <header class="assign-step__head">
                            <span class="assign-step__num">5</span>
                            <div class="flex-grow-1 d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div>
                                    <h3 class="assign-step__title">Clases adicionales</h3>
                                    <p class="assign-step__help mb-0">Reposiciones, clases extra o cortesías. Suman al cupo del curso.</p>
                                </div>
                                <button type="button" class="btn btn-brand-outline btn-sm d-inline-flex align-items-center gap-1" id="studentExtraClassAdd">
                                    <i class="bi bi-plus-lg"></i>
                                    Agregar
                                </button>
                            </div>
                        </header>
                        <p class="small mb-2" id="studentExtraClassesSummary"></p>
                        <div class="student-extra-head" aria-hidden="true">
                            <span>Tipo</span>
                            <span>Cant.</span>
                            <span>Motivo</span>
                            <span></span>
                        </div>
                        <div class="student-extra-list" id="studentExtraClassesList"></div>
                        @error('extra_classes')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </section>
                </div>

                <div class="modal-footer student-modal__footer">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-brand-outline d-none" id="studentWizardBack">
                        Atrás
                    </button>
                    <button type="button" class="btn btn-brand d-flex align-items-center gap-2" id="studentWizardNext">
                        Siguiente
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    <button type="submit" class="btn btn-brand d-none align-items-center gap-2" id="studentFormSubmit">
                        <i class="bi bi-check-lg"></i>
                        <span id="studentFormSubmitLabel">Guardar alumno</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="studentExtraClassRowTemplate">
    <div class="student-extra-row">
        <select class="form-select" data-extra-type aria-label="Tipo de clase adicional" required>
            <option value="">Tipo</option>
        </select>
        <input
            type="number"
            class="form-control"
            data-extra-quantity
            min="1"
            max="20"
            value="1"
            title="Cantidad"
            aria-label="Cantidad"
            required
        >
        <input
            type="text"
            class="form-control"
            data-extra-notes
            maxlength="255"
            placeholder="Motivo (opcional)"
            aria-label="Motivo"
        >
        <button type="button" class="btn btn-brand-outline btn-delete" data-extra-remove title="Quitar" aria-label="Quitar clase adicional">
            <i class="bi bi-trash"></i>
        </button>
    </div>
</template>
