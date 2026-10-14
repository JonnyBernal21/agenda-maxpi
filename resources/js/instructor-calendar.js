import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import bootstrap5Plugin from '@fullcalendar/bootstrap5';
import esLocale from '@fullcalendar/core/locales/es';
import * as bootstrap from 'bootstrap';
import { rollingWeekToolbar, rollingWeekViews } from './calendar-rolling-week';
import { calendarEventContent, homeClassNoteHandlers } from './calendar-event-content';
import { bindEventHomeMap, fillEventHomeDetails } from './event-detail-home';
import { initDayAgenda } from './day-agenda';

const statusLabels = {
    pendiente: 'Pendiente',
    confirmada: 'Confirmada',
    en_curso: 'En curso',
    completada: 'Completada',
    cancelada: 'Cancelada',
};

const fillStartActions = ({ canStart, canContinue, canViewEvaluation, startUrl, formUrl, summaryUrl }) => {
    const startForm = document.getElementById('startClassForm');
    const startBtn = document.getElementById('startClassBtn');
    const continueBtn = document.getElementById('continueClassBtn');
    const viewBtn = document.getElementById('viewEvaluationBtn');

    startForm?.classList.add('d-none');
    continueBtn?.classList.add('d-none');
    viewBtn?.classList.add('d-none');

    if (canStart && startUrl && startForm && startBtn) {
        startForm.action = startUrl;
        startBtn.textContent = 'Iniciar';
        startForm.classList.remove('d-none');
        return;
    }

    if (canContinue && formUrl && continueBtn) {
        continueBtn.href = formUrl;
        continueBtn.classList.remove('d-none');
        return;
    }

    if (canViewEvaluation && summaryUrl && viewBtn) {
        viewBtn.href = summaryUrl;
        viewBtn.classList.remove('d-none');
    }
};

const fillRsvp = (props) => {
    const wrap = document.getElementById('eventModalRsvpWrap');
    const valueEl = document.getElementById('eventModalRsvp');
    const label = props?.rsvpLabel || props?.rsvp_label || '';
    const response = props?.rsvp || props?.rsvp_response || '';

    if (!wrap || !valueEl) {
        return;
    }

    wrap.classList.toggle('d-none', !label);
    valueEl.textContent = label || '—';
    valueEl.dataset.rsvp = response;
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
    initDayAgenda();

    const calendarEl = document.getElementById('instructor-calendar');

    if (!calendarEl) {
        return;
    }

    const modalEl = document.getElementById('eventDetailModal');
    const detailModal = modalEl ? new bootstrap.Modal(modalEl) : null;
    bindEventHomeMap(modalEl);
    const calendarHint = document.getElementById('instructorCalendarHint');

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
            '<i class="bi bi-hand-index-thumb me-1"></i> Haz clic en una clase para ver el detalle del alumno y el vehículo.'
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
        slotMinTime: '07:00:00',
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
        eventContent: calendarEventContent,
        eventMouseEnter: homeClassNoteHandlers.eventMouseEnter,
        eventMouseLeave: homeClassNoteHandlers.eventMouseLeave,
        eventClick(info) {
            if (!detailModal) {
                return;
            }

            const props = info.event.extendedProps;
            const slot = slotFromEvent(info.event);
            const student = props.student ?? '—';
            const startTime = slot.time ?? props.time ?? '—';
            const endTime = props.endTime ?? '—';
            const status = props.status ?? '';
            const classNumber = props.classNumber;
            const kicker = document.getElementById('eventModalKicker');
            const badge = document.getElementById('eventModalStatus');

            document.getElementById('eventModalTitle').textContent = student;
            document.getElementById('eventModalStudent').textContent = student;
            document.getElementById('eventModalVehicle').textContent = props.vehicle ?? '—';
            const vehicleTypeEl = document.getElementById('eventModalVehicleType');
            const vehicleType = props.vehicleType || '';
            if (vehicleTypeEl) {
                vehicleTypeEl.textContent = vehicleType;
                vehicleTypeEl.classList.toggle('d-none', !vehicleType);
            }
            document.getElementById('eventModalDate').textContent = formatDateLabel(slot.date ?? props.date);
            document.getElementById('eventModalTime').textContent =
                startTime !== '—' && endTime !== '—' ? `${startTime} - ${endTime}` : startTime;

            if (kicker) {
                if (status === 'cancelada') {
                    kicker.textContent = classNumber ? `Clase ${classNumber} cancelada` : 'Clase cancelada';
                } else {
                    kicker.textContent = classNumber ? `Clase ${classNumber}` : 'Detalle de clase';
                }
            }

            if (badge) {
                badge.textContent = statusLabels[status] ?? status ?? '—';
                badge.dataset.status = status === 'en_curso' ? 'en_curso' : status;
            }

            fillRsvp(props);
            fillEventHomeDetails(props);

            fillStartActions({
                canStart: Boolean(props.canStart),
                canContinue: Boolean(props.canContinue),
                canViewEvaluation: Boolean(props.canViewEvaluation),
                startUrl: props.startUrl,
                formUrl: props.formUrl,
                summaryUrl: props.summaryUrl,
            });

            resetHint();
            detailModal.show();
        },
        eventsSet() {
            const hasClasses = calendar.getEvents().length > 0;

            if (!hasClasses) {
                showHint(
                    'warning',
                    '<i class="bi bi-calendar-x me-1"></i> No tienes clases en este periodo. Usa las flechas para ver otras fechas.'
                );
            } else {
                resetHint();
            }
        },
    });

    calendar.render();

    document.addEventListener('agenda:open-class', (event) => {
        const item = event.detail;

        if (!detailModal || !item) {
            return;
        }

        const student = item.student ?? '—';
        const status = item.status ?? '';
        const classNumber = item.class_number;
        const kicker = document.getElementById('eventModalKicker');
        const badge = document.getElementById('eventModalStatus');

        document.getElementById('eventModalTitle').textContent = student;
        document.getElementById('eventModalStudent').textContent = student;
        document.getElementById('eventModalVehicle').textContent = item.vehicle ?? '—';
        const vehicleTypeEl = document.getElementById('eventModalVehicleType');
        const vehicleType = item.vehicle_type || '';
        if (vehicleTypeEl) {
            vehicleTypeEl.textContent = vehicleType;
            vehicleTypeEl.classList.toggle('d-none', !vehicleType);
        }
        document.getElementById('eventModalDate').textContent = formatDateLabel(item.date);
        document.getElementById('eventModalTime').textContent = item.time_label
            || `${item.time ?? '—'} - ${item.end_time ?? '—'}`;

        if (kicker) {
            kicker.textContent = classNumber ? `Clase ${classNumber}` : 'Detalle de clase';
        }

        if (badge) {
            badge.textContent = statusLabels[status] ?? item.status_label ?? '—';
            badge.dataset.status = status === 'en_curso' || item.live_status === 'en_curso' ? 'en_curso' : status;
        }

        fillRsvp(item);
        fillEventHomeDetails(item);

        fillStartActions({
            canStart: Boolean(item.can_start),
            canContinue: Boolean(item.can_continue),
            canViewEvaluation: Boolean(item.can_view_evaluation),
            startUrl: item.start_url,
            formUrl: item.form_url,
            summaryUrl: item.summary_url,
        });

        resetHint();
        detailModal.show();
    });
});
