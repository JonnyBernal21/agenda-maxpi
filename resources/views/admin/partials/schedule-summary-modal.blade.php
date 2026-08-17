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

                <div class="schedule-month">
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
            <div class="modal-footer">
                <button type="button" class="btn btn-brand-outline d-flex align-items-center gap-2" id="scheduleSummaryPrint">
                    <i class="bi bi-printer"></i>
                    Imprimir horarios
                </button>
                <button type="button" class="btn btn-brand d-flex align-items-center gap-2" id="scheduleSummaryEmail">
                    <i class="bi bi-envelope"></i>
                    Enviar por correo
                </button>
            </div>
        </div>
    </div>
</div>
