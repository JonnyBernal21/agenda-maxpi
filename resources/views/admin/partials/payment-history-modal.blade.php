<div
    class="modal fade payment-history-modal"
    id="paymentHistoryModal"
    tabindex="-1"
    aria-labelledby="paymentHistoryModalLabel"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-content--stack">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-semibold d-flex align-items-center" id="paymentHistoryModalLabel">
                        <span class="modal-title-icon"><i class="bi bi-clock-history"></i></span>
                        Historial de pagos
                    </h5>
                    <p class="small text-muted mb-0 mt-1" id="paymentHistoryMeta"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="pay-history-summary" id="paymentHistorySummary" hidden></div>
                <div class="pay-timeline" id="paymentHistoryTimeline"></div>
            </div>
        </div>
    </div>
</div>
