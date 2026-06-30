import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import bootstrap5Plugin from '@fullcalendar/bootstrap5';
import esLocale from '@fullcalendar/core/locales/es';
import * as bootstrap from 'bootstrap';
import { rollingWeekToolbar, rollingWeekViews } from './calendar-rolling-week';

const statusLabels = {
    pendiente: 'Pendiente',
    confirmada: 'Confirmada',
    completada: 'Completada',
    cancelada: 'Cancelada',
};

const pad = (value) => String(value).padStart(2, '0');

const slotFromEvent = (event) => {
    const props = event.extendedProps ?? {};
    const start = event.start;

    if (props.date && props.time) {
        return {
            date: props.date,
            time: String(props.time).slice(0, 5),
            endTime: props.endTime ? String(props.endTime).slice(0, 5) : null,
        };
    }

    if (!start) {
        return { date: null, time: null, endTime: null };
    }

    const date = `${start.getFullYear()}-${pad(start.getMonth() + 1)}-${pad(start.getDate())}`;
    const time = `${pad(start.getHours())}:${pad(start.getMinutes())}`;

    return {
        date,
        time,
        endTime: props.endTime ?? null,
    };
};

document.addEventListener('DOMContentLoaded', () => {
    const calendarEl = document.getElementById('student-calendar');

    if (!calendarEl) {
        return;
    }

    const modalEl = document.getElementById('eventDetailModal');
    const detailModal = modalEl ? new bootstrap.Modal(modalEl) : null;
    const bookModalEl = document.getElementById('bookClassModal');
    const calendarHint = document.getElementById('calendarAvailabilityAlert');
    const canReserve = calendarEl.dataset.canReserve === 'true';

    if (bookModalEl?.dataset.autoOpen === 'true') {
        bootstrap.Modal.getOrCreateInstance(bookModalEl).show();
    }

    const formatDateLabel = (isoDate) => {
        if (!isoDate) {
            return '—';
        }

        const [year, month, day] = isoDate.split('-').map(Number);
        const date = new Date(year, month - 1, day);

        return date.toLocaleDateString('es-MX', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    };

    const showHint = (type, html) => {
        if (!calendarHint) {
            return;
        }

        calendarHint.className = `alert alert-${type} mb-0`;
        calendarHint.innerHTML = html;
        calendarHint.classList.remove('d-none');
    };

    const resetHint = () => {
        if (!calendarHint) {
            return;
        }

        const message = canReserve
            ? '<i class="bi bi-hand-index-thumb me-1"></i> Haz clic en un bloque <strong>verde</strong> para reservar en esa fecha y hora.'
            : '<i class="bi bi-info-circle me-1"></i> Ya usaste todas las clases de tu curso. Aquí solo verás tus citas agendadas.';

        calendarHint.className = 'alert alert-info mb-0';
        calendarHint.innerHTML = message;
    };

    resetHint();

    const openBookingFromSlot = async (event) => {
        if (!canReserve) {
            showHint('warning', '<i class="bi bi-exclamation-triangle me-1"></i> No tienes clases disponibles en tu curso para reservar.');
            return;
        }

        const slot = slotFromEvent(event);
        const props = event.extendedProps ?? {};

        if (!slot.date || !slot.time) {
            showHint('danger', '<i class="bi bi-exclamation-triangle me-1"></i> No se pudo leer el horario. Intenta con otro bloque.');
            return;
        }

        if (window.StudentBooking?.openSlot) {
            await window.StudentBooking.openSlot({
                date: slot.date,
                time: slot.time,
                endTime: slot.endTime ?? props.endTime,
            });
        } else {
            document.dispatchEvent(
                new CustomEvent('student:slot-selected', {
                    detail: {
                        date: slot.date,
                        time: slot.time,
                        endTime: slot.endTime ?? props.endTime,
                    },
                })
            );
        }

        showHint(
            'success',
            `<i class="bi bi-calendar-plus me-1"></i>
            Reservando: <strong>${formatDateLabel(slot.date)}</strong> · ${slot.time}
            <span class="d-block small mt-1 mb-0">Elige instructor y vehículo, luego confirma.</span>`
        );
    };

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin, bootstrap5Plugin],
        themeSystem: 'bootstrap5',
        locale: esLocale,
        views: rollingWeekViews,
        initialView: 'rollingWeek',
        initialDate: new Date(),
        headerToolbar: rollingWeekToolbar,
        events: calendarEl.dataset.eventsUrl,
        height: 'auto',
        nowIndicator: true,
        allDaySlot: false,
        slotMinTime: '08:00:00',
        slotMaxTime: '19:00:00',
        slotDuration: '00:30:00',
        slotLabelInterval: '01:00:00',
        slotLabelFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        },
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        },
        eventClick(info) {
            const props = info.event.extendedProps;

            if (props.isAvailable) {
                info.jsEvent.preventDefault();
                openBookingFromSlot(info.event);
                return;
            }

            if (!detailModal) {
                return;
            }

            const slot = slotFromEvent(info.event);

            document.getElementById('eventModalTitle').textContent = info.event.title;
            document.getElementById('eventModalInstructor').textContent = props.instructor ?? '—';
            document.getElementById('eventModalVehicle').textContent = props.vehicle ?? '—';
            document.getElementById('eventModalDate').textContent = formatDateLabel(slot.date ?? props.date);
            const startTime = slot.time ?? props.time ?? '—';
            const endTime = props.endTime ?? '—';
            document.getElementById('eventModalTime').textContent =
                startTime !== '—' && endTime !== '—' ? `${startTime} - ${endTime}` : startTime;
            document.getElementById('eventModalStatus').textContent = statusLabels[props.status] ?? props.status ?? '—';

            resetHint();
            detailModal.show();
        },
        eventsSet() {
            if (!canReserve || !calendarHint) {
                return;
            }

            if (calendarHint.classList.contains('alert-danger')) {
                return;
            }

            const hasAvailable = calendar.getEvents().some((event) => event.extendedProps.isAvailable);

            if (hasAvailable) {
                if (calendarHint.classList.contains('alert-info')) {
                    resetHint();
                }
                return;
            }

            showHint(
                'warning',
                '<i class="bi bi-calendar-x me-1"></i> No hay horarios libres en este periodo. Usa las flechas del calendario para ver otras fechas.'
            );
        },
    });

    calendar.render();
});
