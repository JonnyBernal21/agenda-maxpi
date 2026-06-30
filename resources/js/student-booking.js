import * as bootstrap from 'bootstrap';
import { confirmAgendarClase } from './booking-confirm';

const pad = (value) => String(value).padStart(2, '0');

const normalizeTime = (time) => (time ? String(time).slice(0, 5) : null);

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

const setupStudentBooking = () => {
    const form = document.querySelector('#bookClassModal form');
    const bookModalEl = document.getElementById('bookClassModal');
    const checkUrl = bookModalEl?.dataset.checkUrl;
    const optionsUrl = bookModalEl?.dataset.optionsUrl;
    const modalFeedback = document.getElementById('bookingModalFeedback');
    const calendarAlert = document.getElementById('calendarAvailabilityAlert');
    const submitBtn = document.getElementById('bookSubmitBtn');
    const instructorSelect = document.getElementById('book_instructor_id');
    const vehicleSelect = document.getElementById('book_vehicle_id');
    const dateInput = document.getElementById('book_date');
    const timeSelect = document.getElementById('book_time');
    const slotSummary = document.getElementById('bookSlotSummary');
    const slotSummaryText = document.getElementById('bookSlotSummaryText');
    const modalHint = document.getElementById('bookModalHint');
    const modalTitleText = document.getElementById('bookModalTitleText');

    if (!form || !checkUrl || !bookModalEl) {
        return null;
    }

    let slotLocked = false;

    const setFeedback = (type, messages) => {
        const html = Array.isArray(messages)
            ? `<ul class="mb-0 ps-3">${messages.map((m) => `<li>${m}</li>`).join('')}</ul>`
            : messages;

        if (modalFeedback) {
            modalFeedback.className = `alert alert-${type} mb-0`;
            modalFeedback.innerHTML = html;
            modalFeedback.classList.remove('d-none');
        }

        if (calendarAlert) {
            calendarAlert.className = `alert alert-${type} mb-0`;
            calendarAlert.innerHTML = html;
            calendarAlert.classList.remove('d-none');
        }
    };

    const hideFeedback = () => {
        modalFeedback?.classList.add('d-none');

        if (submitBtn) {
            submitBtn.disabled = false;
        }
    };

    const filterSelectOptions = (select, allowedIds) => {
        if (!select) {
            return;
        }

        const allowed = new Set(allowedIds.map(String));

        Array.from(select.options).forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            const isAllowed = allowed.has(option.value);
            option.hidden = !isAllowed;
            option.disabled = !isAllowed;

            if (!isAllowed && option.selected) {
                option.selected = false;
            }
        });

        if (select.value && !allowed.has(select.value)) {
            select.value = '';
        }
    };

    const resetSelectFilters = () => {
        [instructorSelect, vehicleSelect].forEach((select) => {
            if (!select) {
                return;
            }

            Array.from(select.options).forEach((option) => {
                option.hidden = false;
                option.disabled = false;
            });
        });
    };

    const setSlotLocked = (locked, summaryText = '') => {
        slotLocked = locked;

        if (dateInput) {
            dateInput.readOnly = locked;
            dateInput.classList.toggle('bg-light', locked);
        }

        if (timeSelect) {
            timeSelect.classList.toggle('bg-light', locked);
            timeSelect.classList.toggle('slot-field-locked', locked);
            timeSelect.setAttribute('aria-readonly', locked ? 'true' : 'false');
        }

        if (dateInput) {
            dateInput.classList.toggle('slot-field-locked', locked);
        }

        if (slotSummary) {
            slotSummary.classList.toggle('d-none', !locked);
        }

        if (slotSummaryText && locked) {
            slotSummaryText.textContent = summaryText;
        }

        if (modalHint) {
            modalHint.classList.toggle('d-none', locked);
        }

        if (modalTitleText) {
            modalTitleText.textContent = locked ? 'Reservar este horario' : 'Reservar clase';
        }
    };

    const unlockSlot = () => {
        setSlotLocked(false);
        resetSelectFilters();
        hideFeedback();

        if (timeSelect) {
            timeSelect.classList.remove('slot-field-locked');
            timeSelect.setAttribute('aria-readonly', 'false');
        }

        if (dateInput) {
            dateInput.classList.remove('slot-field-locked');
        }
    };

    const applyFirstAvailablePair = (pairs) => {
        if (!pairs?.length) {
            return;
        }

        const first = pairs[0];

        if (instructorSelect) {
            instructorSelect.value = String(first.instructor_id);
        }

        if (vehicleSelect) {
            vehicleSelect.value = String(first.vehicle_id);
        }
    };

    const checkAvailability = async () => {
        const instructorId = instructorSelect?.value;
        const vehicleId = vehicleSelect?.value;
        const date = dateInput?.value;
        const time = timeSelect?.value;

        if (!instructorId || !vehicleId || !date || !time) {
            hideFeedback();
            if (submitBtn) {
                submitBtn.disabled = true;
            }
            return;
        }

        try {
            const params = new URLSearchParams({
                instructor_id: instructorId,
                vehicle_id: vehicleId,
                date,
                time,
            });

            const response = await fetch(`${checkUrl}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            const result = await response.json();

            if (result.available) {
                setFeedback('success', 'Horario listo. Pulsa <strong>Confirmar reserva</strong> para agendar tu clase.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
                return;
            }

            setFeedback('danger', result.messages?.length ? result.messages : ['Horario no disponible.']);
            if (submitBtn) {
                submitBtn.disabled = true;
            }
        } catch {
            setFeedback('warning', 'No se pudo verificar la disponibilidad. Intenta de nuevo.');
            if (submitBtn) {
                submitBtn.disabled = true;
            }
        }
    };

    const loadSlotOptions = async () => {
        const date = dateInput?.value;
        const time = timeSelect?.value;

        if (!optionsUrl || !date || !time) {
            resetSelectFilters();
            hideFeedback();
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
        }

        try {
            const params = new URLSearchParams({ date, time });
            const response = await fetch(`${optionsUrl}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            const result = await response.json();

            if (!result.available) {
                filterSelectOptions(instructorSelect, []);
                filterSelectOptions(vehicleSelect, []);
                setFeedback('danger', ['Este horario ya no está disponible. Elige otro bloque verde en el calendario.']);
                return;
            }

            filterSelectOptions(instructorSelect, result.instructor_ids ?? []);
            filterSelectOptions(vehicleSelect, result.vehicle_ids ?? []);
            applyFirstAvailablePair(result.pairs);
            await checkAvailability();
        } catch {
            resetSelectFilters();
            setFeedback('warning', 'No se pudieron cargar las opciones. Intenta de nuevo.');
        }
    };

    const openSlot = async ({ date, time, endTime }) => {
        if (!date || !time) {
            return;
        }

        const summaryText = `${formatDateLabel(date)} · ${time}${endTime ? ` - ${endTime}` : ''} (2 h)`;

        if (dateInput) {
            dateInput.value = date;
        }

        if (timeSelect) {
            timeSelect.value = normalizeTime(time) ?? '';
        }

        setSlotLocked(true, summaryText);
        hideFeedback();

        const modal = bootstrap.Modal.getOrCreateInstance(bookModalEl);

        if (!bookModalEl.classList.contains('show')) {
            modal.show();
        }

        await loadSlotOptions();
    };

    const fields = ['book_instructor_id', 'book_vehicle_id', 'book_date', 'book_time'];

    fields.forEach((id) => {
        document.getElementById(id)?.addEventListener('change', async () => {
            if (slotLocked && (id === 'book_date' || id === 'book_time')) {
                return;
            }

            if (id === 'book_date' || id === 'book_time') {
                await loadSlotOptions();
                return;
            }

            await checkAvailability();
        });
    });

    document.querySelectorAll('[data-bs-target="#bookClassModal"]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            if (!bookModalEl.dataset.pendingSlot) {
                unlockSlot();
            }
        });
    });

    bookModalEl.addEventListener('hidden.bs.modal', () => {
        delete bookModalEl.dataset.pendingSlot;
        unlockSlot();
    });

    let formSubmitting = false;

    form.addEventListener('submit', async (event) => {
        if (formSubmitting) {
            return;
        }

        event.preventDefault();

        if (submitBtn?.disabled) {
            setFeedback('danger', 'Selecciona instructor y vehículo disponibles antes de confirmar.');
            return;
        }

        if (!dateInput?.value || !timeSelect?.value) {
            setFeedback('danger', 'Selecciona fecha y hora para tu reserva.');
            return;
        }

        const dateLabel = formatDateLabel(dateInput.value);
        const studentName = bookModalEl?.dataset.studentName?.trim() || 'ti';

        const confirmed = await confirmAgendarClase({
            nombre: studentName,
            fecha: dateLabel,
            horario: `${timeSelect.value} (2 h)`,
            confirmText: 'Sí, reservar',
        });

        if (confirmed) {
            formSubmitting = true;
            form.submit();
        }
    });

    if (dateInput?.value && timeSelect?.value) {
        loadSlotOptions();
    }

    return { openSlot, unlockSlot, loadSlotOptions };
};

document.addEventListener('DOMContentLoaded', () => {
    const booking = setupStudentBooking();

    if (booking) {
        window.StudentBooking = booking;
    }

    document.addEventListener('student:slot-selected', async (event) => {
        if (!window.StudentBooking) {
            return;
        }

        await window.StudentBooking.openSlot({
            date: event.detail?.date,
            time: event.detail?.time,
            endTime: event.detail?.endTime,
        });
    });
});
