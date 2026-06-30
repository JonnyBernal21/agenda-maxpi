import Swal from 'sweetalert2';

const CONFIRM_COLOR = '#334155';
const CANCEL_COLOR = '#64748b';

const swalBookingDefaults = {
    showCancelButton: true,
    focusCancel: true,
    reverseButtons: true,
    confirmButtonColor: CONFIRM_COLOR,
    cancelButtonColor: CANCEL_COLOR,
    customClass: {
        popup: 'swal-booking-popup',
        title: 'swal-booking-title',
        htmlContainer: 'swal-booking-html',
        actions: 'swal-booking-actions',
        confirmButton: 'swal-booking-btn swal-booking-btn--confirm',
        cancelButton: 'swal-booking-btn swal-booking-btn--cancel',
    },
};

/**
 * @param {string} value
 */
function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * @param {{
 *   nombre: string,
 *   fecha: string,
 *   horario: string,
 *   confirmText?: string,
 * }} options
 */
export async function confirmAgendarClase({
    nombre,
    fecha,
    horario,
    confirmText = 'Sí, agendar',
}) {
    const safeNombre = escapeHtml(nombre);
    const safeFecha = escapeHtml(fecha);
    const safeHorario = escapeHtml(horario);

    const result = await Swal.fire({
        ...swalBookingDefaults,
        title: '¿Agendar esta clase?',
        html: `
            <p class="swal-booking-message">
                ¿Quieres agendar esta clase para
                <span class="swal-booking-highlight">${safeNombre}</span>
                en la fecha
                <span class="swal-booking-highlight">${safeFecha}</span>
                y horario
                <span class="swal-booking-highlight">${safeHorario}</span>?
            </p>
        `,
        icon: 'question',
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancelar',
    });

    return result.isConfirmed;
}

/**
 * @param {string} message
 */
export function showBookingSuccess(message) {
    return Swal.fire({
        title: '¡Reserva creada!',
        text: message,
        icon: 'success',
        confirmButtonText: 'Entendido',
        confirmButtonColor: CONFIRM_COLOR,
        customClass: {
            popup: 'swal-booking-popup',
            title: 'swal-booking-title',
        },
    });
}

export function initFlashAlerts() {
    const el = document.getElementById('app-flash');

    if (!el?.dataset.message) {
        return;
    }

    const type = el.dataset.type ?? 'success';
    const message = el.dataset.message;

    if (type === 'success') {
        showBookingSuccess(message);
    } else if (type === 'error') {
        Swal.fire({
            title: 'No se pudo completar',
            text: message,
            icon: 'error',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: CONFIRM_COLOR,
            customClass: {
                popup: 'swal-booking-popup',
                title: 'swal-booking-title',
            },
        });
    }
}

/**
 * @param {HTMLSelectElement|null} select
 */
export function optionText(select) {
    if (!select || select.selectedIndex < 0) {
        return '—';
    }

    return select.options[select.selectedIndex]?.text?.trim() ?? '—';
}
