<div
    class="modal fade schedule-summary-modal"
    id="scheduleSummaryModal"
    tabindex="-1"
    aria-labelledby="scheduleSummaryModalLabel"
    aria-hidden="true"
    data-bs-backdrop="static"
    data-bs-keyboard="false"
>
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-content--stack">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold d-flex align-items-center" id="scheduleSummaryModalLabel">
                    <span class="modal-title-icon"><i class="bi bi-calendar3"></i></span>
                    Resumen de horarios
                </h5>
            </div>
            <div class="modal-body" id="scheduleSummaryPrintArea">
                <div class="alert alert-light border mb-2 py-2">
                    <p class="fw-semibold mb-1" id="scheduleSummaryStudentName"></p>
                    <p class="small text-muted mb-0" id="scheduleSummaryStudentMeta"></p>
                </div>

                <div class="schedule-month" id="scheduleSummaryLiveMonth">
                    <div class="schedule-month__nav">
                        <button type="button" class="btn btn-brand-outline schedule-month__arrow" id="scheduleSummaryPrev" aria-label="Mes anterior">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <h6 class="schedule-month__title text-capitalize mb-0" id="scheduleSummaryMonthLabel"></h6>
                        <button type="button" class="btn btn-brand-outline schedule-month__arrow" id="scheduleSummaryNext" aria-label="Mes siguiente">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                    <div class="schedule-month__weekdays">
                        <span>Lun</span>
                        <span>Mar</span>
                        <span>Mié</span>
                        <span>Jue</span>
                        <span>Vie</span>
                        <span>Sáb</span>
                        <span>Dom</span>
                    </div>
                    <div class="schedule-month__grid" id="scheduleSummaryGrid"></div>
                </div>
                <div class="schedule-summary-print-months" id="scheduleSummaryPrintMonths" hidden></div>

                <div class="schedule-summary-list">
                    <p class="small fw-semibold">Listado de clases</p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 schedule-preview-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Día</th>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Instructor</th>
                                    <th>Vehículo</th>
                                </tr>
                            </thead>
                            <tbody id="scheduleSummaryListBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer schedule-summary-footer">
                <button
                    type="button"
                    class="btn btn-brand-outline d-flex align-items-center gap-2"
                    id="scheduleSummaryPrint"
                    title="Imprimir horarios"
                    aria-label="Imprimir horarios"
                >
                    <i class="bi bi-printer"></i>
                    Imprimir
                </button>
                <button
                    type="button"
                    class="btn btn-whatsapp d-flex align-items-center gap-2"
                    id="scheduleSummaryWhatsapp"
                    title="Enviar horarios por WhatsApp"
                    aria-label="Enviar horarios por WhatsApp"
                >
                    <i class="bi bi-whatsapp"></i>
                    Enviar
                </button>
                <button
                    type="button"
                    class="btn btn-brand d-flex align-items-center gap-2"
                    id="scheduleSummaryEmail"
                    title="Enviar horarios por correo"
                    aria-label="Enviar horarios por correo"
                >
                    <i class="bi bi-envelope"></i>
                    Enviar
                </button>
            </div>
        </div>
    </div>
</div>
