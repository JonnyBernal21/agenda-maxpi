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
 * @param {string} [title]
 */
export function showBookingSuccess(message, title = '¡Reserva creada!') {
    return Swal.fire({
        title,
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

export function showBookingError(message, title = 'No se pudo completar') {
    return Swal.fire({
        title,
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

/**
 * @param {{
 *   name: string,
 *   entity?: string,
 * }} options
 */
export async function confirmSoftDelete({ name, entity = 'registro' }) {
    const safeName = escapeHtml(name);
    const article = entity === 'alumno' || entity === 'instructor' ? 'al' : 'el';

    const result = await Swal.fire({
        ...swalBookingDefaults,
        title: `¿Eliminar ${entity}?`,
        html: `
            <p class="swal-booking-message">
                Se desactivará ${article}
                <span class="swal-booking-highlight">${safeName}</span>
                y dejará de aparecer en las listas. El historial se conserva.
            </p>
        `,
        icon: 'warning',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            ...swalBookingDefaults.customClass,
            confirmButton: 'swal-booking-btn swal-booking-btn--danger',
        },
        confirmButtonColor: '#9f1239',
    });

    return result.isConfirmed;
}

/**
 * @param {{
 *   student?: string,
 *   date?: string,
 *   time?: string,
 * }} options
 */
export async function confirmCancelClass({ student, date, time } = {}) {
    const detail = [student, date, time].filter(Boolean).map(escapeHtml);

    const result = await Swal.fire({
        ...swalBookingDefaults,
        title: '¿Cancelar esta cita?',
        html: `
            <p class="swal-booking-message">
                El horario quedará libre de nuevo.
                ${detail.length ? `<br><span class="swal-booking-highlight">${detail.join(' · ')}</span>` : ''}
            </p>
        `,
        icon: 'warning',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No, conservar',
        customClass: {
            ...swalBookingDefaults.customClass,
            confirmButton: 'swal-booking-btn swal-booking-btn--danger',
        },
        confirmButtonColor: '#9f1239',
    });

    return result.isConfirmed;
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
        showBookingError(message);
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
