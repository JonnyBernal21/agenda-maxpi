<div
    class="modal fade"
    id="studentScheduleModal"
    tabindex="-1"
    aria-labelledby="studentScheduleModalLabel"
    aria-hidden="true"
>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-content--stack">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold d-flex align-items-center" id="studentScheduleModalLabel">
                    <span class="modal-title-icon"><i class="bi bi-calendar-week"></i></span>
                    <span>Horarios de <span id="studentScheduleName">alumno</span></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3" id="studentScheduleSummary"></p>
                <div id="studentScheduleContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
