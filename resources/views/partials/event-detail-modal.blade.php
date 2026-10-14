@php
    $showInstructor = $showInstructor ?? false;
    $showActions = $showActions ?? false;
    $showStartClass = $showStartClass ?? false;
    $canEditSchedule = $canEditSchedule ?? $showActions;
    $hourSlots = $hourSlots ?? \App\Models\Reservas::halfHourTimes();
@endphp

<div
    class="modal fade"
    id="eventDetailModal"
    tabindex="-1"
    aria-labelledby="eventModalTitle"
    aria-hidden="true"
    @if ($canEditSchedule) data-bs-focus="false" @endif
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content event-detail-modal modal-content--stack">
            <div class="modal-header event-detail-modal__header">
                <div class="event-detail-modal__top">
                    <div class="event-detail-modal__heading">
                        <p class="event-detail-modal__kicker" id="eventModalKicker">Detalle de clase</p>
                        <h5 class="modal-title fw-semibold" id="eventModalTitle">Detalle de reserva</h5>
                    </div>
                    <span class="event-status-badge" id="eventModalStatus" data-status="">—</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                @if ($showActions)
                    <div class="event-detail-actions d-none" id="eventDetailActions">
                        <button
                            type="button"
                            id="confirmReservaBtn"
                            class="event-detail-action event-detail-action--confirm d-none"
                            title="Confirmar cita"
                            aria-label="Confirmar cita"
                        >
                            <i class="bi bi-check-circle-fill"></i>
                        </button>
                        <button
                            type="button"
                            id="completeReservaBtn"
                            class="event-detail-action event-detail-action--complete d-none"
                            title="Marcar como completada"
                            aria-label="Marcar como completada"
                        >
                            <i class="bi bi-check2-all"></i>
                        </button>
                        <button
                            type="button"
                            id="eventWhatsappBtn"
                            class="event-detail-action event-detail-action--whatsapp d-none"
                            title="Enviar recordatorio"
                            aria-label="Enviar recordatorio"
                        >
                            <i class="bi bi-whatsapp"></i>
                        </button>
                        <button
                            type="button"
                            id="cancelReservaBtn"
                            class="event-detail-action event-detail-action--cancel d-none"
                            title="Cancelar cita"
                            aria-label="Cancelar cita"
                        >
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>
                @endif
            </div>
            <div class="modal-body">
                <div class="event-detail-hero">
                    @if ($canEditSchedule)
                        <div
                            class="event-detail-hero__item is-editable js-single-date"
                            data-no-presets="true"
                            data-label-style="long"
                            data-min-date="{{ $minBookableDate ?? '' }}"
                            id="eventModalDateField"
                        >
                            <span class="event-detail-hero__icon"><i class="bi bi-calendar-event"></i></span>
                            <div class="event-detail-hero__body">
                                <span class="event-detail-hero__label">Fecha</span>
                                <input type="hidden" class="js-single-date-value" id="eventModalDateValue">
                                <div class="event-detail-select event-detail-select--compact">
                                    <input
                                        type="text"
                                        class="event-detail-select__control js-single-date-display"
                                        id="eventModalDate"
                                        placeholder="Selecciona un día"
                                        autocomplete="off"
                                        readonly
                                    >
                                    <span class="event-detail-select__caret" aria-hidden="true"><i class="bi bi-calendar3"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="event-detail-hero__item is-editable" id="eventModalTimeField">
                            <span class="event-detail-hero__icon"><i class="bi bi-clock"></i></span>
                            <div class="event-detail-hero__body">
                                <span class="event-detail-hero__label">Horario</span>
                                <div class="event-detail-select event-detail-select--compact">
                                    <select class="event-detail-select__control" id="eventModalTime">
                                        @foreach ($hourSlots as $slot)
                                            <option value="{{ $slot }}">
                                                {{ $slot }} - {{ \App\Models\Reservas::slotEndTime($slot) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="event-detail-select__caret" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="event-detail-hero__item">
                            <span class="event-detail-hero__icon"><i class="bi bi-calendar-event"></i></span>
                            <div>
                                <span class="event-detail-hero__label">Fecha</span>
                                <strong class="event-detail-hero__value" id="eventModalDate">—</strong>
                            </div>
                        </div>
                        <div class="event-detail-hero__item">
                            <span class="event-detail-hero__icon"><i class="bi bi-clock"></i></span>
                            <div>
                                <span class="event-detail-hero__label">Horario</span>
                                <strong class="event-detail-hero__value" id="eventModalTime">—</strong>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="event-detail-rows">
                    <div class="event-detail-row">
                        <span class="event-detail-row__icon"><i class="bi bi-person"></i></span>
                        <div class="event-detail-row__body">
                            <span class="event-detail-row__label">Alumno</span>
                            <span class="event-detail-row__value" id="eventModalStudent">—</span>
                        </div>
                    </div>

                    @if ($showInstructor)
                        <div class="event-detail-row @if ($canEditSchedule) is-editable @endif" id="eventModalInstructorField">
                            <span class="event-detail-row__icon"><i class="bi bi-person-badge"></i></span>
                            <div class="event-detail-row__body">
                                <span class="event-detail-row__label">Instructor</span>
                                @if ($canEditSchedule)
                                    <div class="event-detail-select">
                                        <select class="event-detail-select__control" id="eventModalInstructor">
                                            <option value="">Seleccionar instructor</option>
                                            @foreach ($instructors ?? [] as $instructor)
                                                <option value="{{ $instructor->id }}">{{ $instructor->fullName() }}</option>
                                            @endforeach
                                        </select>
                                        <span class="event-detail-select__caret" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
                                    </div>
                                @else
                                    <span class="event-detail-row__value" id="eventModalInstructor">—</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="event-detail-row @if ($canEditSchedule) is-editable @endif" id="eventModalVehicleField">
                        <span class="event-detail-row__icon"><i class="bi bi-car-front"></i></span>
                        <div class="event-detail-row__body">
                                <span class="event-detail-row__label">Vehículo</span>
                                @if ($canEditSchedule)
                                    <div class="event-detail-select-wrap">
                                        <div class="event-detail-select">
                                            <select class="event-detail-select__control" id="eventModalVehicle">
                                                <option value="">Seleccionar vehículo</option>
                                                @foreach ($vehicles ?? [] as $vehicle)
                                                    <option value="{{ $vehicle->id }}" data-type="{{ $vehicle->typeLabel() }}">
                                                        {{ $vehicle->modelo }} ({{ $vehicle->plate }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span class="event-detail-select__caret" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
                                        </div>
                                        <span class="event-detail-row__meta d-none" id="eventModalVehicleType"></span>
                                    </div>
                                @else
                                    <span class="event-detail-row__value" id="eventModalVehicle">—</span>
                                    <span class="event-detail-row__meta d-none" id="eventModalVehicleType"></span>
                                @endif
                        </div>
                    </div>

                    <div class="event-detail-home d-none" id="eventModalHomeWrap">
                        <div class="event-detail-home__badge">
                            <i class="bi bi-house-door-fill"></i>
                            Servicio a domicilio
                        </div>
                        <div class="event-detail-home__map-wrap d-none" id="eventModalHomeMapWrap">
                            <div id="eventModalHomeMap" class="event-detail-home__map" role="application" aria-label="Punto de encuentro"></div>
                            <a
                                id="eventModalHomeMapsLink"
                                class="event-detail-home__maps"
                                href="#"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <i class="bi bi-box-arrow-up-right"></i>
                                Abrir en Google Maps
                            </a>
                        </div>
                        <p class="event-detail-home__empty d-none mb-0" id="eventModalHomeMapEmpty">
                            Esta clase es a domicilio, pero aún no hay un punto en el mapa.
                        </p>
                        <div class="event-detail-row event-detail-row--nested d-none" id="eventModalHomeNotesWrap">
                            <span class="event-detail-row__icon"><i class="bi bi-geo-alt"></i></span>
                            <div class="event-detail-row__body">
                                <span class="event-detail-row__label">Indicaciones</span>
                                <span class="event-detail-row__value" id="eventModalHomeNotes">—</span>
                            </div>
                        </div>
                        <div class="event-detail-row event-detail-row--nested d-none" id="eventModalNotesWrap">
                            <span class="event-detail-row__icon"><i class="bi bi-chat-left-text"></i></span>
                            <div class="event-detail-row__body">
                                <span class="event-detail-row__label">Observaciones</span>
                                <span class="event-detail-row__value" id="eventModalNotes">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="event-detail-row d-none" id="eventModalRsvpWrap">
                        <span class="event-detail-row__icon"><i class="bi bi-clipboard-check"></i></span>
                        <div class="event-detail-row__body">
                            <span class="event-detail-row__label">Confirmación del alumno</span>
                            <span class="event-detail-row__value" id="eventModalRsvp">—</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer event-detail-footer">
                <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Cerrar</button>
                @if ($showStartClass)
                    <form method="POST" action="#" id="startClassForm" class="d-none">
                        @csrf
                        <button type="submit" class="btn btn-brand" id="startClassBtn">Iniciar</button>
                    </form>
                    <a href="#" class="btn btn-brand d-none" id="continueClassBtn">Continuar</a>
                    <a href="#" class="btn btn-brand-outline d-none" id="viewEvaluationBtn">Ver evaluación</a>
                @endif
            </div>
        </div>
    </div>
</div>
