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

const CLASS_DURATION_MINUTES = 120;

const pad = (value) => String(value).padStart(2, '0');

const halfHourTimes = (() => {
    const times = [];

    for (let hour = 8; hour <= 19; hour += 1) {
        times.push(`${pad(hour)}:00`);

        if (hour < 19) {
            times.push(`${pad(hour)}:30`);
        }
    }

    return new Set(times);
})();

const addMinutes = (time, minutes) => {
    const [hour, minute] = String(time).slice(0, 5).split(':').map(Number);
    const total = hour * 60 + minute + minutes;

    return `${pad(Math.floor(total / 60))}:${pad(total % 60)}`;
};

const formatTimeRange = (time) => {
    if (!time) {
        return '—';
    }

    return `${time} - ${addMinutes(time, CLASS_DURATION_MINUTES)}`;
};

const slotFromDate = (date) => {
    if (!date) {
        return { date: null, time: null };
    }

    return {
        date: `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`,
        time: `${pad(date.getHours())}:${pad(date.getMinutes())}`,
    };
};

const slotFromEvent = (event) => {
    const props = event.extendedProps ?? {};
    const start = event.start;

    if (props.date && props.time) {
        return { date: props.date, time: String(props.time).slice(0, 5), endTime: props.endTime };
    }

    if (!start) {
        return { date: null, time: null, endTime: null };
    }

    const slot = slotFromDate(start);

    return {
        date: slot.date,
        time: slot.time,
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

const isMovableStatus = (status) => status === 'pendiente' || status === 'confirmada';

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
    const minDate = calendarEl.dataset.minDate || '';
    const sameDayMessage = calendarEl.dataset.sameDayMessage || 'Elige una fecha a partir de mañana.';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const calendarHint = document.getElementById('adminCalendarHint');
    const moveModalEl = document.getElementById('rescheduleClassModal');
    const moveModal = moveModalEl ? bootstrap.Modal.getOrCreateInstance(moveModalEl) : null;
    const confirmRescheduleBtn = document.getElementById('confirmRescheduleBtn');
    const moveErrorEl = document.getElementById('rescheduleModalError');

    let currentReservaId = null;
    let currentEvent = null;
    let pendingMove = null;
    let skipNextHintReset = false;

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
            '<i class="bi bi-hand-index-thumb me-1"></i> Haz clic en un bloque <strong>verde</strong> para agendar. Arrastra una clase para cambiar su fecha u horario.'
        );
    };

    const clearMoveError = () => {
        if (!moveErrorEl) {
            return;
        }

        moveErrorEl.classList.add('d-none');
        moveErrorEl.textContent = '';
    };

    const showMoveError = (message) => {
        if (!moveErrorEl) {
            return;
        }

        moveErrorEl.textContent = message;
        moveErrorEl.classList.remove('d-none');
    };

    const discardPendingMove = () => {
        if (pendingMove?.revert && !pendingMove.committed) {
            pendingMove.revert();
        }

        pendingMove = null;
    };

    const fillMoveModal = (from, to, student) => {
        const studentEl = document.getElementById('rescheduleModalStudent');
        const fromDateEl = document.getElementById('rescheduleFromDate');
        const fromTimeEl = document.getElementById('rescheduleFromTime');
        const toDateEl = document.getElementById('rescheduleToDate');
        const toTimeEl = document.getElementById('rescheduleToTime');

        if (studentEl) {
            studentEl.textContent = student || 'este alumno';
        }

        if (fromDateEl) {
            fromDateEl.textContent = formatDateLabel(from.date);
        }

        if (fromTimeEl) {
            fromTimeEl.textContent = formatTimeRange(from.time);
        }

        if (toDateEl) {
            toDateEl.textContent = formatDateLabel(to.date);
        }

        if (toTimeEl) {
            toTimeEl.textContent = formatTimeRange(to.time);
        }
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
        snapDuration: '00:30:00',
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
        editable: true,
        eventStartEditable: true,
        eventDurationEditable: false,
        eventOverlap: true,
        eventAllow(dropInfo, draggedEvent) {
            if (draggedEvent.extendedProps?.isAvailable || !isMovableStatus(draggedEvent.extendedProps?.status)) {
                return false;
            }

            if (dropInfo.allDay || !dropInfo.start) {
                return false;
            }

            const slot = slotFromDate(dropInfo.start);

            if (minDate && slot.date < minDate) {
                return false;
            }

            return halfHourTimes.has(slot.time);
        },
        eventDrop(info) {
            const props = info.event.extendedProps ?? {};

            if (props.isAvailable || !isMovableStatus(props.status) || info.event.allDay) {
                info.revert();

                if (info.event.allDay) {
                    showHint('warning', 'Usa la vista semanal para cambiar la hora de la clase.');
                }

                return;
            }

            const from = slotFromEvent(info.oldEvent);
            const to = slotFromDate(info.event.start);

            if (!to.date || !to.time || !halfHourTimes.has(to.time)) {
                info.revert();
                showHint('warning', 'Elige un horario en intervalos de 30 minutos entre 08:00 y 19:00.');
                return;
            }

            if (minDate && to.date < minDate) {
                info.revert();
                showHint('warning', sameDayMessage);
                return;
            }

            if (from.date === to.date && from.time === to.time) {
                return;
            }

            if (!moveModal) {
                info.revert();
                return;
            }

            pendingMove = {
                reservaId: info.event.id,
                date: to.date,
                time: to.time,
                revert: info.revert,
                committed: false,
            };

            clearMoveError();
            fillMoveModal(from, to, props.student);
            moveModal.show();
        },
        eventClick(info) {
            const props = info.event.extendedProps;

            if (pendingMove) {
                return;
            }

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
            if (skipNextHintReset) {
                skipNextHintReset = false;
                return;
            }

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

    moveModalEl?.addEventListener('hidden.bs.modal', () => {
        if (pendingMove && !pendingMove.committed) {
            discardPendingMove();
            resetHint();
            return;
        }

        pendingMove = null;
        clearMoveError();

        if (confirmRescheduleBtn) {
            confirmRescheduleBtn.disabled = false;
        }
    });

    confirmRescheduleBtn?.addEventListener('click', async () => {
        if (!pendingMove || !confirmBaseUrl || !csrfToken) {
            return;
        }

        const originalHtml = confirmRescheduleBtn.innerHTML;
        confirmRescheduleBtn.disabled = true;
        confirmRescheduleBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';
        clearMoveError();

        try {
            const response = await fetch(`${confirmBaseUrl}/${pendingMove.reservaId}/reschedule`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    date: pendingMove.date,
                    time: pendingMove.time,
                }),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = data.message ?? 'No se pudo mover la clase a ese horario.';
                showMoveError(message);
                discardPendingMove();
                moveModal?.hide();
                showHint('danger', `<i class="bi bi-exclamation-triangle me-1"></i> ${message}`);
                return;
            }

            pendingMove.committed = true;
            pendingMove = null;
            skipNextHintReset = true;
            moveModal?.hide();
            calendar.refetchEvents();
            showHint(
                'success',
                `<i class="bi bi-check-circle me-1"></i> ${data.message ?? 'La clase se reprogramó correctamente.'}`
            );
        } catch {
            const message = 'No se pudo guardar el cambio. Inténtalo de nuevo.';
            showMoveError(message);
            discardPendingMove();
            moveModal?.hide();
            showHint('danger', `<i class="bi bi-exclamation-triangle me-1"></i> ${message}`);
        } finally {
            confirmRescheduleBtn.disabled = false;
            confirmRescheduleBtn.innerHTML = originalHtml;
        }
    });

    calendar.render();
});
