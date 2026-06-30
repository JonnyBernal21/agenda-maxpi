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
        return { date: props.date, time: String(props.time).slice(0, 5), endTime: props.endTime };
    }

    if (!start) {
        return { date: null, time: null, endTime: null };
    }

    return {
        date: `${start.getFullYear()}-${pad(start.getMonth() + 1)}-${pad(start.getDate())}`,
        time: `${pad(start.getHours())}:${pad(start.getMinutes())}`,
        endTime: props.endTime ?? null,
    };
};

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

document.addEventListener('DOMContentLoaded', () => {
    const calendarEl = document.getElementById('admin-calendar');

    if (!calendarEl) {
        return;
    }

    const modalEl = document.getElementById('eventDetailModal');
    const detailModal = modalEl ? new bootstrap.Modal(modalEl) : null;
    const confirmBtn = document.getElementById('confirmReservaBtn');
    const completeBtn = document.getElementById('completeReservaBtn');
    const confirmBaseUrl = calendarEl.dataset.confirmUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const calendarHint = document.getElementById('adminCalendarHint');

    let currentReservaId = null;
    let currentEvent = null;

    const showHint = (type, html) => {
        if (!calendarHint) {
            return;
        }

        calendarHint.className = `alert alert-${type} mb-0`;
        calendarHint.innerHTML = html;
    };

    const resetHint = () => {
        showHint(
            'info',
            '<i class="bi bi-hand-index-thumb me-1"></i> Haz clic en un bloque <strong>verde</strong> para agendar, o en una clase para ver detalle y cambiar su estado.'
        );
    };

    const updateStatusButtons = (status) => {
        confirmBtn?.classList.add('d-none');
        confirmBtn?.classList.remove('d-inline-flex');
        completeBtn?.classList.add('d-none');
        completeBtn?.classList.remove('d-inline-flex');

        if (status === 'pendiente') {
            confirmBtn?.classList.remove('d-none');
            confirmBtn?.classList.add('d-inline-flex');
        }

        if (status === 'pendiente' || status === 'confirmada') {
            completeBtn?.classList.remove('d-none');
            completeBtn?.classList.add('d-inline-flex');
        }
    };

    const patchStatus = async (url, successStatus, successLabel) => {
        const response = await fetch(url, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
        });

        const data = await response.json();

        if (!response.ok) {
            alert(data.message ?? 'No se pudo actualizar la reserva.');
            return false;
        }

        document.getElementById('eventModalStatus').textContent = successLabel;

        if (currentEvent) {
            const colors = {
                confirmada: { bg: '#2563eb', border: '#1d4ed8', class: 'fc-event-confirmada' },
                completada: { bg: '#16a34a', border: '#15803d', class: 'fc-event-completada' },
            }[successStatus];

            if (colors) {
                currentEvent.setProp('backgroundColor', colors.bg);
                currentEvent.setProp('borderColor', colors.border);
                currentEvent.setProp('classNames', [colors.class]);
                currentEvent.setExtendedProp('status', successStatus);
            }
        }

        updateStatusButtons(successStatus);
        calendar.refetchEvents();
        return true;
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
                info.jsEvent.stopPropagation();

                const slot = slotFromEvent(info.event);

                if (window.AdminScheduling?.openSlot) {
                    window.AdminScheduling.openSlot({
                        date: slot.date,
                        time: slot.time,
                        endTime: slot.endTime ?? props.endTime,
                    });
                }

                showHint(
                    'success',
                    `<i class="bi bi-calendar-plus me-1"></i> Agendando: <strong>${formatDateLabel(slot.date)}</strong> · ${slot.time}`
                );
                return;
            }

            if (!detailModal) {
                return;
            }

            const slot = slotFromEvent(info.event);
            currentReservaId = info.event.id;
            currentEvent = info.event;

            document.getElementById('eventModalTitle').textContent = info.event.title;
            document.getElementById('eventModalStudent').textContent = props.student ?? '—';
            document.getElementById('eventModalInstructor').textContent = props.instructor ?? '—';
            document.getElementById('eventModalVehicle').textContent = props.vehicle ?? '—';
            document.getElementById('eventModalDate').textContent = formatDateLabel(slot.date ?? props.date);
            const startTime = slot.time ?? props.time ?? '—';
            const endTime = props.endTime ?? '—';
            document.getElementById('eventModalTime').textContent =
                startTime !== '—' && endTime !== '—' ? `${startTime} - ${endTime}` : startTime;
            document.getElementById('eventModalStatus').textContent = statusLabels[props.status] ?? props.status ?? '—';

            updateStatusButtons(props.status);
            resetHint();
            detailModal.show();
        },
        eventsSet() {
            const hasAvailable = calendar.getEvents().some((event) => event.extendedProps.isAvailable);

            if (!hasAvailable) {
                showHint(
                    'warning',
                    '<i class="bi bi-calendar-x me-1"></i> No hay cupos libres en este periodo. Cambia de semana o revisa las clases ya agendadas.'
                );
            } else {
                resetHint();
            }
        },
    });

    confirmBtn?.addEventListener('click', async () => {
        if (!currentReservaId || !confirmBaseUrl || !csrfToken) {
            return;
        }

        confirmBtn.disabled = true;
        const originalHtml = confirmBtn.innerHTML;
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Confirmando...';

        await patchStatus(`${confirmBaseUrl}/${currentReservaId}/confirm`, 'confirmada', statusLabels.confirmada);

        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalHtml;
    });

    completeBtn?.addEventListener('click', async () => {
        if (!currentReservaId || !confirmBaseUrl || !csrfToken) {
            return;
        }

        completeBtn.disabled = true;
        const originalHtml = completeBtn.innerHTML;
        completeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

        await patchStatus(`${confirmBaseUrl}/${currentReservaId}/complete`, 'completada', statusLabels.completada);

        completeBtn.disabled = false;
        completeBtn.innerHTML = originalHtml;
    });

    calendar.render();
});
