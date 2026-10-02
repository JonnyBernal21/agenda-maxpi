<div
    class="modal fade payment-receipt-modal"
    id="paymentReceiptModal"
    tabindex="-1"
    aria-labelledby="paymentReceiptModalLabel"
    aria-hidden="true"
    data-bs-focus="false"
>
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-content--stack">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-semibold d-flex align-items-center" id="paymentReceiptModalLabel">
                        <span class="modal-title-icon"><i class="bi bi-receipt"></i></span>
                        Recibo de pago
                    </h5>
                    <p class="small text-muted mb-0 mt-1" id="paymentReceiptMeta"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-0">
                <div class="payment-receipt-frame-wrap">
                    <iframe
                        id="paymentReceiptFrame"
                        class="payment-receipt-frame"
                        title="Vista previa del recibo de pago"
                        src="about:blank"
                    ></iframe>
                </div>
            </div>
            <div class="modal-footer schedule-summary-footer">
                <button
                    type="button"
                    class="btn btn-brand-outline d-flex align-items-center gap-2"
                    id="paymentReceiptPrint"
                    title="Imprimir recibo"
                    aria-label="Imprimir recibo"
                >
                    <i class="bi bi-printer"></i>
                    Imprimir
                </button>
                <button
                    type="button"
                    class="btn btn-brand-outline d-flex align-items-center gap-2"
                    id="paymentReceiptHistory"
                    title="Ver historial de pagos"
                    aria-label="Ver historial de pagos"
                >
                    <i class="bi bi-clock-history"></i>
                    Historial
                </button>
                <button
                    type="button"
                    class="btn btn-brand d-flex align-items-center gap-2"
                    id="paymentReceiptEmail"
                    title="Enviar horarios y recibo por correo"
                    aria-label="Enviar horarios y recibo por correo"
                >
                    <i class="bi bi-envelope"></i>
                    Enviar
                </button>
            </div>
        </div>
    </div>
</div>
