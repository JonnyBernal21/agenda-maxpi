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
import { confirmCancelClass, confirmEditClassSchedule, optionText, showBookingError } from './booking-confirm';
import { bindSingleDatePickers, formatLongDate } from './date-range-picker';
import { bindEventHomeMap, fillEventHomeDetails } from './event-detail-home';

const statusLabels = {
    pendiente: 'Pendiente',
    confirmada: 'Confirmada',
    en_curso: 'En curso',
    completada: 'Completada',
    cancelada: 'Cancelada',
};

const CLASS_DURATION_MINUTES = 120;

const pad = (value) => String(value).padStart(2, '0');

const halfHourTimes = (() => {
    const times = [];

    for (let hour = 7; hour <= 19; hour += 1) {
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

const formatDateLabel = (isoDate) => formatLongDate(isoDate) || '—';

const isMovableStatus = (status) => status === 'pendiente' || status === 'confirmada';

document.addEventListener('DOMContentLoaded', () => {
    const calendarEl = document.getElementById('admin-calendar');

    if (!calendarEl) {
        return;
    }

    const modalEl = document.getElementById('eventDetailModal');
    const detailModal = modalEl ? new bootstrap.Modal(modalEl) : null;
    bindEventHomeMap(modalEl);
    const confirmBtn = document.getElementById('confirmReservaBtn');
    const completeBtn = document.getElementById('completeReservaBtn');
    const cancelBtn = document.getElementById('cancelReservaBtn');
    const whatsappBtn = document.getElementById('eventWhatsappBtn');
    const actionsWrap = document.getElementById('eventDetailActions');
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
    let currentWhatsapp = '';
    let currentSlot = { date: null, time: null, endTime: null };
    let currentAssignment = { instructorId: '', vehicleId: '' };
    let syncingSchedule = false;
    let scheduleBusy = false;
    let pendingMove = null;
    let skipNextHintReset = false;

    const dateField = document.getElementById('eventModalDateField');
    const dateValueInput = document.getElementById('eventModalDateValue');
    const dateDisplayInput = document.getElementById('eventModalDate');
    const timeSelect = document.getElementById('eventModalTimeField')
        ? document.getElementById('eventModalTime')
        : null;
    const instructorSelect = document.getElementById('eventModalInstructor');
    const vehicleSelect = document.getElementById('eventModalVehicle');
    const vehicleTypeEl = document.getElementById('eventModalVehicleType');
    const canEditSchedule = Boolean(dateValueInput && timeSelect);
    const canEditAssignment = instructorSelect?.tagName === 'SELECT' && vehicleSelect?.tagName === 'SELECT';

    if (minDate && dateField) {
        dateField.dataset.minDate = minDate;
    }

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

    const setEventStatus = (status, classNumber = null) => {
        const badge = document.getElementById('eventModalStatus');
        const kicker = document.getElementById('eventModalKicker');

        if (badge) {
            badge.textContent = statusLabels[status] ?? status ?? '—';
            badge.dataset.status = status || '';
        }

        if (kicker) {
            if (status === 'cancelada') {
                kicker.textContent = classNumber ? `Clase ${classNumber} cancelada` : 'Clase cancelada';
            } else {
                kicker.textContent = classNumber ? `Clase ${classNumber}` : 'Detalle de clase';
            }
        }
    };

    const setScheduleEditable = (enabled) => {
        dateField?.classList.toggle('is-editable', enabled);
        dateField?.classList.toggle('is-readonly', !enabled);
        document.getElementById('eventModalTimeField')?.classList.toggle('is-editable', enabled);
        document.getElementById('eventModalTimeField')?.classList.toggle('is-readonly', !enabled);
        document.getElementById('eventModalInstructorField')?.classList.toggle('is-editable', enabled);
        document.getElementById('eventModalInstructorField')?.classList.toggle('is-readonly', !enabled);
        document.getElementById('eventModalVehicleField')?.classList.toggle('is-editable', enabled);
        document.getElementById('eventModalVehicleField')?.classList.toggle('is-readonly', !enabled);

        if (dateDisplayInput) {
            dateDisplayInput.disabled = !enabled;
        }

        if (timeSelect) {
            timeSelect.disabled = !enabled;
        }

        if (canEditAssignment) {
            instructorSelect.disabled = !enabled;
            vehicleSelect.disabled = !enabled;
        }
    };

    const ensureSelectValue = (select, id, label) => {
        if (!select || select.tagName !== 'SELECT') {
            return;
        }

        const value = id ? String(id) : '';

        if (value && ![...select.options].some((option) => option.value === value)) {
            select.add(new Option(label || value, value));
        }

        select.value = value;
    };

    const selectedOptionType = (select) => select?.selectedOptions?.[0]?.dataset?.type || '';

    const fillVehicleType = (type) => {
        if (!vehicleTypeEl) {
            return;
        }

        const label = type || selectedOptionType(vehicleSelect) || '';
        vehicleTypeEl.textContent = label;
        vehicleTypeEl.classList.toggle('d-none', !label);
    };

    const syncAssignmentFields = ({ instructorId, instructor, vehicleId, vehicle, vehicleType } = {}) => {
        currentAssignment = {
            instructorId: instructorId ? String(instructorId) : '',
            vehicleId: vehicleId ? String(vehicleId) : '',
        };

        if (canEditAssignment) {
            syncingSchedule = true;
            ensureSelectValue(instructorSelect, currentAssignment.instructorId, instructor);
            ensureSelectValue(vehicleSelect, currentAssignment.vehicleId, vehicle);
            fillVehicleType(vehicleType || selectedOptionType(vehicleSelect));
            syncingSchedule = false;
            return;
        }

        if (instructorSelect) {
            instructorSelect.textContent = instructor || '—';
        }

        if (vehicleSelect) {
            vehicleSelect.textContent = vehicle || '—';
        }

        fillVehicleType(vehicleType);
    };

    const optionLabelByValue = (select, value) => (
        [...(select?.options ?? [])].find((option) => option.value === String(value || ''))?.text || '—'
    );

    const restoreAssignmentFields = () => {
        const vehicleOption = [...(vehicleSelect?.options ?? [])]
            .find((option) => option.value === currentAssignment.vehicleId);

        syncAssignmentFields({
            instructorId: currentAssignment.instructorId,
            instructor: optionLabelByValue(instructorSelect, currentAssignment.instructorId),
            vehicleId: currentAssignment.vehicleId,
            vehicle: vehicleOption?.text || '—',
            vehicleType: vehicleOption?.dataset?.type || '',
        });
    };

    const saveAssignment = async (instructorId, vehicleId) => {
        if (!currentReservaId || !confirmBaseUrl || !csrfToken) {
            return false;
        }

        scheduleBusy = true;

        try {
            const response = await fetch(`${confirmBaseUrl}/${currentReservaId}/asignacion`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    instructor_id: Number(instructorId),
                    vehicle_id: Number(vehicleId),
                }),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                await showBookingError(data.message ?? 'No se pudo guardar el cambio.');
                restoreAssignmentFields();
                return false;
            }

            skipNextHintReset = true;
            syncAssignmentFields({
                instructorId: data.instructor_id ?? instructorId,
                instructor: data.instructor,
                vehicleId: data.vehicle_id ?? vehicleId,
                vehicle: data.vehicle,
                vehicleType: data.vehicle_type,
            });

            if (currentEvent) {
                currentEvent.setExtendedProp('instructor', data.instructor);
                currentEvent.setExtendedProp('instructorId', data.instructor_id);
                currentEvent.setExtendedProp('vehicle', data.vehicle);
                currentEvent.setExtendedProp('vehicleId', data.vehicle_id);
                currentEvent.setExtendedProp('vehicleType', data.vehicle_type);
            }

            calendar.refetchEvents();
            document.dispatchEvent(new CustomEvent('agenda:refresh'));
            showHint(
                'success',
                `<i class="bi bi-check-circle me-1"></i> ${data.message ?? 'Se actualizó el instructor y el vehículo.'}`
            );
            return true;
        } catch {
            await showBookingError('No se pudo guardar el cambio. Inténtalo de nuevo.');
            restoreAssignmentFields();
            return false;
        } finally {
            scheduleBusy = false;
        }
    };

    const requestAssignmentChange = async (kind, instructorId, vehicleId) => {
        if (scheduleBusy || !currentReservaId) {
            restoreAssignmentFields();
            return;
        }

        scheduleBusy = true;

        const from = kind === 'vehiculo'
            ? optionLabelByValue(vehicleSelect, currentAssignment.vehicleId)
            : optionLabelByValue(instructorSelect, currentAssignment.instructorId);
        const to = optionText(kind === 'vehiculo' ? vehicleSelect : instructorSelect);

        try {
            const confirmed = await confirmEditClassSchedule({
                kind,
                from,
                to,
                student: document.getElementById('eventModalStudent')?.textContent,
            });

            if (!confirmed) {
                restoreAssignmentFields();
                return;
            }

            scheduleBusy = false;
            await saveAssignment(instructorId, vehicleId);
        } finally {
            scheduleBusy = false;
        }
    };

    const syncScheduleFields = (slot) => {
        syncingSchedule = true;
        currentSlot = {
            date: slot.date || null,
            time: slot.time ? String(slot.time).slice(0, 5) : null,
            endTime: slot.endTime || null,
        };

        if (dateValueInput) {
            dateValueInput.value = currentSlot.date || '';

            if (currentSlot.date && dateValueInput._litepicker) {
                dateValueInput._litepicker.setDate(currentSlot.date);
            }
        }

        if (dateDisplayInput) {
            if (dateDisplayInput.tagName === 'INPUT') {
                dateDisplayInput.value = formatDateLabel(currentSlot.date);
            } else {
                dateDisplayInput.textContent = formatDateLabel(currentSlot.date);
            }
        }

        if (timeSelect && timeSelect.tagName === 'SELECT') {
            timeSelect.value = currentSlot.time || '';
        } else if (timeSelect) {
            const startTime = currentSlot.time ?? '—';
            const endTime = currentSlot.endTime ?? '—';
            timeSelect.textContent = startTime !== '—' && endTime !== '—'
                ? `${startTime} - ${endTime}`
                : startTime;
        }

        syncingSchedule = false;
    };

    const restoreScheduleFields = () => {
        syncScheduleFields(currentSlot);
    };

    const saveSchedule = async (date, time) => {
        if (!currentReservaId || !confirmBaseUrl || !csrfToken) {
            return false;
        }

        scheduleBusy = true;

        try {
            const response = await fetch(`${confirmBaseUrl}/${currentReservaId}/reschedule`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ date, time }),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                await showBookingError(data.message ?? 'No se pudo guardar el cambio.');
                restoreScheduleFields();
                return false;
            }

            skipNextHintReset = true;
            syncScheduleFields({
                date: data.date ?? date,
                time: data.time ?? time,
                endTime: data.endTime ?? addMinutes(time, CLASS_DURATION_MINUTES),
            });
            calendar.refetchEvents();
            document.dispatchEvent(new CustomEvent('agenda:refresh'));
            showHint(
                'success',
                `<i class="bi bi-check-circle me-1"></i> ${data.message ?? 'La clase se reprogramó correctamente.'}`
            );
            return true;
        } catch {
            await showBookingError('No se pudo guardar el cambio. Inténtalo de nuevo.');
            restoreScheduleFields();
            return false;
        } finally {
            scheduleBusy = false;
        }
    };

    const requestScheduleChange = async (kind, nextDate, nextTime) => {
        if (scheduleBusy || !currentReservaId) {
            restoreScheduleFields();
            return;
        }

        scheduleBusy = true;

        const from = kind === 'horario'
            ? formatTimeRange(currentSlot.time)
            : formatDateLabel(currentSlot.date);
        const to = kind === 'horario'
            ? formatTimeRange(nextTime)
            : formatDateLabel(nextDate);

        try {
            const confirmed = await confirmEditClassSchedule({
                kind,
                from,
                to,
                student: document.getElementById('eventModalStudent')?.textContent,
            });

            if (!confirmed) {
                restoreScheduleFields();
                return;
            }

            scheduleBusy = false;
            await saveSchedule(nextDate, nextTime);
        } finally {
            scheduleBusy = false;
        }
    };

    const fillEventDetail = (props, slot) => {
        const student = props.student ?? '—';
        const startTime = slot.time ?? props.time ?? '—';
        const endTime = props.endTime ?? '—';

        document.getElementById('eventModalTitle').textContent = student;
        document.getElementById('eventModalStudent').textContent = student;
        currentWhatsapp = props.whatsapp || '';
        setEventStatus(props.status, props.classNumber);
        fillRsvp(props);
        fillEventHomeDetails(props);
        syncScheduleFields({
            date: slot.date ?? props.date,
            time: startTime === '—' ? null : startTime,
            endTime: endTime === '—' ? null : endTime,
        });
        syncAssignmentFields({
            instructorId: props.instructorId ?? props.instructor_id,
            instructor: props.instructor,
            vehicleId: props.vehicleId ?? props.vehicle_id,
            vehicle: props.vehicle,
            vehicleType: props.vehicleType ?? props.vehicle_type,
        });
        setScheduleEditable(canEditSchedule && isMovableStatus(props.status));
    };

    if (canEditSchedule) {
        bindSingleDatePickers(modalEl);

        dateValueInput.addEventListener('change', () => {
            if (syncingSchedule) {
                return;
            }

            const nextDate = dateValueInput.value;

            if (!nextDate || nextDate === currentSlot.date) {
                return;
            }

            requestScheduleChange('fecha', nextDate, currentSlot.time);
        });

        timeSelect.addEventListener('change', () => {
            if (syncingSchedule) {
                return;
            }

            const nextTime = timeSelect.value;

            if (!nextTime || nextTime === currentSlot.time) {
                return;
            }

            requestScheduleChange('horario', currentSlot.date, nextTime);
        });

        dateField?.addEventListener('click', (event) => {
            if (dateDisplayInput?.disabled || event.target === dateDisplayInput) {
                return;
            }

            dateValueInput._litepicker?.show();
        });

        modalEl?.addEventListener('hidden.bs.modal', () => {
            dateValueInput._litepicker?.hide();
        });
    }

    if (canEditAssignment) {
        instructorSelect.addEventListener('change', () => {
            if (syncingSchedule) {
                return;
            }

            const nextInstructor = instructorSelect.value;

            if (!nextInstructor || nextInstructor === currentAssignment.instructorId) {
                restoreAssignmentFields();
                return;
            }

            requestAssignmentChange('instructor', nextInstructor, currentAssignment.vehicleId);
        });

        vehicleSelect.addEventListener('change', () => {
            if (syncingSchedule) {
                return;
            }

            const nextVehicle = vehicleSelect.value;

            if (!nextVehicle || nextVehicle === currentAssignment.vehicleId) {
                restoreAssignmentFields();
                return;
            }

            fillVehicleType(selectedOptionType(vehicleSelect));
            requestAssignmentChange('vehiculo', currentAssignment.instructorId, nextVehicle);
        });
    }

    const fillRsvp = (props) => {
        const wrap = document.getElementById('eventModalRsvpWrap');
        const valueEl = document.getElementById('eventModalRsvp');
        const label = props.rsvpLabel || props.rsvp_label || '';
        const response = props.rsvp || props.rsvp_response || '';

        if (!wrap || !valueEl) {
            return;
        }

        wrap.classList.toggle('d-none', !label);
        valueEl.textContent = label || '—';
        valueEl.dataset.rsvp = response;
    };

    const toggleAction = (btn, visible) => {
        btn?.classList.toggle('d-none', !visible);
    };

    const updateStatusButtons = (status) => {
        toggleAction(confirmBtn, status === 'pendiente');
        toggleAction(completeBtn, status === 'pendiente' || status === 'confirmada');
        toggleAction(cancelBtn, status === 'pendiente' || status === 'confirmada');
        toggleAction(whatsappBtn, Boolean(currentWhatsapp) && (status === 'pendiente' || status === 'confirmada'));

        const hasVisible = [confirmBtn, completeBtn, cancelBtn, whatsappBtn].some(
            (btn) => btn && !btn.classList.contains('d-none')
        );

        actionsWrap?.classList.toggle('d-none', !hasVisible);
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

        setEventStatus(successStatus, currentEvent?.extendedProps?.classNumber);

        if (currentEvent) {
            const colors = {
                confirmada: (() => {
                    const theme = getComputedStyle(document.documentElement);

                    return {
                        bg: theme.getPropertyValue('--brand-accent').trim() || '#F7E018',
                        border: theme.getPropertyValue('--brand-accent-hover').trim() || '#E0C80A',
                        text: theme.getPropertyValue('--brand-accent-ink').trim() || '#201E1F',
                        class: 'fc-event-confirmada',
                    };
                })(),
                completada: { bg: '#16a34a', border: '#15803d', text: '#ffffff', class: 'fc-event-completada' },
                cancelada: { bg: '#dc2626', border: '#b91c1c', text: '#ffffff', class: 'fc-event-cancelada' },
            }[successStatus];

            if (colors) {
                currentEvent.setProp('backgroundColor', colors.bg);
                currentEvent.setProp('borderColor', colors.border);
                currentEvent.setProp('textColor', colors.text);
                currentEvent.setProp('classNames', [colors.class]);
                currentEvent.setExtendedProp('status', successStatus);

                if (successStatus === 'cancelada') {
                    const studentName = currentEvent.extendedProps?.student;
                    const homeMark = currentEvent.extendedProps?.isHomeClass ? ' A domicilio' : '';
                    currentEvent.setProp('title', studentName ? `Cancelada —${homeMark} ${studentName}` : 'Cancelada');
                    currentEvent.setProp('editable', false);
                    currentEvent.setProp('startEditable', false);
                }
            }
        }

        updateStatusButtons(successStatus);
        calendar.refetchEvents();
        document.dispatchEvent(new CustomEvent('agenda:refresh'));
        return true;
    };

    const eventsUrl = calendarEl.dataset.eventsUrl;
    let calendar;

    calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin, bootstrap5Plugin],
        themeSystem: 'bootstrap5',
        locale: esLocale,
        views: {
            ...rollingWeekViews,
            dayGridMonth: {
                dayMaxEvents: 5,
            },
        },
        initialView: 'rollingWeek',
        initialDate: new Date(),
        headerToolbar: rollingWeekToolbar,
        events(info, successCallback, failureCallback) {
            const url = new URL(eventsUrl, window.location.href);
            url.searchParams.set('start', info.startStr);
            url.searchParams.set('end', info.endStr);
            url.searchParams.set('view', calendar?.view?.type || '');

            fetch(url.toString(), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('No se pudieron cargar los eventos');
                    }

                    return response.json();
                })
                .then(successCallback)
                .catch(failureCallback);
        },
        height: 'auto',
        nowIndicator: true,
        allDaySlot: false,
        slotMinTime: '07:00:00',
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
        eventContent: calendarEventContent,
        eventMouseEnter: homeClassNoteHandlers.eventMouseEnter,
        eventMouseLeave: homeClassNoteHandlers.eventMouseLeave,
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
                showHint('warning', 'Elige un horario en intervalos de 30 minutos entre 07:00 y 19:00.');
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

            fillEventDetail(props, slot);
            updateStatusButtons(props.status);
            resetHint();
            detailModal.show();
        },
        eventsSet() {
            if (skipNextHintReset) {
                skipNextHintReset = false;
                return;
            }

            const view = String(calendar.view?.type || '');

            if (view.includes('dayGrid') || view.includes('list')) {
                resetHint();
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

    whatsappBtn?.addEventListener('click', async () => {
        if (!currentReservaId || !confirmBaseUrl || !csrfToken) {
            return;
        }

        if (!currentWhatsapp) {
            await showBookingError('Este alumno no tiene un teléfono válido para enviar por WhatsApp.');
            return;
        }

        whatsappBtn.disabled = true;
        const originalHtml = whatsappBtn.innerHTML;
        whatsappBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const response = await fetch(`${confirmBaseUrl}/${currentReservaId}/recordatorio`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
            });
            const data = await response.json();

            if (!response.ok) {
                await showBookingError(data.message ?? 'No se pudo armar el recordatorio.');
                return;
            }

            window.open(
                `https://wa.me/${data.whatsapp}?text=${encodeURIComponent(data.message)}`,
                '_blank',
                'noopener,noreferrer'
            );
        } catch {
            await showBookingError('No se pudo armar el recordatorio. Inténtalo de nuevo.');
        } finally {
            whatsappBtn.disabled = false;
            whatsappBtn.innerHTML = originalHtml;
        }
    });

    confirmBtn?.addEventListener('click', async () => {
        if (!currentReservaId || !confirmBaseUrl || !csrfToken) {
            return;
        }

        confirmBtn.disabled = true;
        const originalHtml = confirmBtn.innerHTML;
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

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
        completeBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        await patchStatus(`${confirmBaseUrl}/${currentReservaId}/complete`, 'completada', statusLabels.completada);

        completeBtn.disabled = false;
        completeBtn.innerHTML = originalHtml;
    });

    cancelBtn?.addEventListener('click', async () => {
        if (!currentReservaId || !confirmBaseUrl || !csrfToken) {
            return;
        }

        const confirmed = await confirmCancelClass({
            student: document.getElementById('eventModalStudent')?.textContent,
            date: formatDateLabel(currentSlot.date),
            time: formatTimeRange(currentSlot.time),
        });

        if (!confirmed) {
            return;
        }

        cancelBtn.disabled = true;
        const originalHtml = cancelBtn.innerHTML;
        cancelBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        skipNextHintReset = true;
        const ok = await patchStatus(`${confirmBaseUrl}/${currentReservaId}/cancel`, 'cancelada', statusLabels.cancelada);

        cancelBtn.disabled = false;
        cancelBtn.innerHTML = originalHtml;

        if (ok) {
            detailModal?.hide();
            showHint(
                'success',
                '<i class="bi bi-check-circle me-1"></i> Cita cancelada. Queda marcada en rojo en el calendario.'
            );
        } else {
            skipNextHintReset = false;
        }
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

    document.addEventListener('agenda:open-class', (event) => {
        const item = event.detail;

        if (!detailModal || !item?.id) {
            return;
        }

        currentReservaId = item.id;
        currentEvent = calendar.getEventById(String(item.id)) || null;
        fillEventDetail({
            student: item.student,
            instructor: item.instructor,
            instructorId: item.instructor_id,
            vehicle: item.vehicle,
            vehicleId: item.vehicle_id,
            vehicleType: item.vehicle_type,
            status: item.status,
            classNumber: item.class_number,
            date: item.date,
            time: item.time,
            endTime: item.end_time,
            whatsapp: item.whatsapp,
            isHomeClass: item.is_home,
            meetingPoint: item.meeting_point,
            meetingLat: item.meeting_lat,
            meetingLng: item.meeting_lng,
            notes: item.notes,
            rsvp: item.rsvp,
            rsvp_label: item.rsvp_label,
        }, {
            date: item.date,
            time: item.time,
            endTime: item.end_time,
        });
        updateStatusButtons(item.status);
        detailModal.show();
    });
});
