@php
    $showInstructor = $showInstructor ?? false;
    $showActions = $showActions ?? false;
@endphp

<div class="modal fade" id="eventDetailModal" tabindex="-1" aria-labelledby="eventModalTitle" aria-hidden="true">
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
                            title="Enviar horario por WhatsApp"
                            aria-label="Enviar horario por WhatsApp"
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
                        <div class="event-detail-row">
                            <span class="event-detail-row__icon"><i class="bi bi-person-badge"></i></span>
                            <div class="event-detail-row__body">
                                <span class="event-detail-row__label">Instructor</span>
                                <span class="event-detail-row__value" id="eventModalInstructor">—</span>
                            </div>
                        </div>
                    @endif

                    <div class="event-detail-row">
                        <span class="event-detail-row__icon"><i class="bi bi-car-front"></i></span>
                        <div class="event-detail-row__body">
                            <span class="event-detail-row__label">Vehículo</span>
                            <span class="event-detail-row__value" id="eventModalVehicle">—</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer event-detail-footer">
                <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
