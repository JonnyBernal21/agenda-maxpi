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

document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('scheduleClassModal');

    if (!modalEl) {
        return;
    }

    const searchUrl = modalEl.dataset.searchUrl;
    const optionsUrl = modalEl.dataset.optionsUrl;
    const checkUrl = modalEl.dataset.checkUrl;
    const searchInput = document.getElementById('studentSearchInput');
    const resultsEl = document.getElementById('studentSearchResults');
    const selectedAlert = document.getElementById('studentSelectedAlert');
    const selectedNameEl = document.getElementById('studentSelectedName');
    const selectedCourseEl = document.getElementById('studentSelectedCourse');
    const notFoundAlert = document.getElementById('studentNotFoundAlert');
    const studentIdInput = document.getElementById('reserva_student_id');
    const clearBtn = document.getElementById('studentClearBtn');
    const formFields = document.getElementById('scheduleFormFields');
    const submitBtn = document.getElementById('scheduleSubmitBtn');
    const dateInput = document.getElementById('reserva_date');
    const timeSelect = document.getElementById('reserva_time');
    const instructorSelect = document.getElementById('reserva_instructor_id');
    const vehicleSelect = document.getElementById('reserva_vehicle_id');
    const slotSummary = document.getElementById('scheduleSlotSummary');
    const slotSummaryText = document.getElementById('scheduleSlotSummaryText');
    const scheduleFeedback = document.createElement('div');
    scheduleFeedback.id = 'scheduleModalFeedback';
    scheduleFeedback.className = 'alert d-none mb-3';
    formFields?.parentElement?.insertBefore(scheduleFeedback, formFields);

    let slotLocked = false;
    let searchTimer = 0;
    let searchRequest = 0;
    const MIN_SEARCH_CHARS = 4;

    const setFeedback = (type, message) => {
        scheduleFeedback.className = `alert alert-${type} mb-3`;
        scheduleFeedback.innerHTML = message;
        scheduleFeedback.classList.remove('d-none');
    };

    const hideFeedback = () => {
        scheduleFeedback.classList.add('d-none');
    };

    const hideAlerts = () => {
        selectedAlert?.classList.add('d-none');
        notFoundAlert?.classList.add('d-none');
        resultsEl?.classList.add('d-none');
    };

    const enableForm = (enabled) => {
        if (!formFields || !submitBtn) {
            return;
        }

        if (enabled) {
            formFields.classList.remove('opacity-50');
            formFields.style.pointerEvents = '';
        } else {
            formFields.classList.add('opacity-50');
            formFields.style.pointerEvents = 'none';
            submitBtn.disabled = true;
        }
    };

    const showNotFound = (message) => {
        if (!notFoundAlert) {
            return;
        }

        notFoundAlert.innerHTML = message;
        notFoundAlert.classList.remove('d-none');
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
            dateInput.classList.toggle('slot-field-locked', locked);
        }

        if (timeSelect) {
            // No usar disabled: los campos deshabilitados no se envían en el POST
            timeSelect.classList.toggle('bg-light', locked);
            timeSelect.classList.toggle('slot-field-locked', locked);
            timeSelect.setAttribute('aria-readonly', locked ? 'true' : 'false');
        }

        slotSummary?.classList.toggle('d-none', !locked || !summaryText);
        if (slotSummaryText && summaryText) {
            slotSummaryText.textContent = summaryText;
        }
    };

    const unlockSlot = () => {
        setSlotLocked(false);
        if (timeSelect) {
            timeSelect.classList.remove('bg-light', 'slot-field-locked');
            timeSelect.setAttribute('aria-readonly', 'false');
        }
        if (dateInput) {
            dateInput.classList.remove('slot-field-locked');
        }
    };

    const applyFirstPair = (pairs) => {
        if (!pairs?.length) {
            return;
        }

        if (instructorSelect) {
            instructorSelect.value = String(pairs[0].instructor_id);
        }

        if (vehicleSelect) {
            vehicleSelect.value = String(pairs[0].vehicle_id);
        }
    };

    const checkAvailability = async () => {
        const studentId = studentIdInput?.value;
        const instructorId = instructorSelect?.value;
        const vehicleId = vehicleSelect?.value;
        const date = dateInput?.value;
        const time = timeSelect?.value;

        if (!studentId || !instructorId || !vehicleId || !date || !time || !checkUrl) {
            if (submitBtn && studentId) {
                submitBtn.disabled = true;
            }
            return;
        }

        try {
            const params = new URLSearchParams({
                student_id: studentId,
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
                hideFeedback();
                submitBtn.disabled = false;
                return;
            }

            setFeedback('danger', (result.messages ?? ['Horario no disponible.']).join(' '));
            submitBtn.disabled = true;
        } catch {
            setFeedback('warning', 'No se pudo verificar la disponibilidad.');
            submitBtn.disabled = true;
        }
    };

    const loadSlotOptions = async () => {
        const date = dateInput?.value;
        const time = timeSelect?.value;
        const studentId = studentIdInput?.value;

        if (!optionsUrl || !date || !time) {
            resetSelectFilters();
            return;
        }

        submitBtn.disabled = true;

        try {
            const params = new URLSearchParams({ date, time });
            if (studentId) {
                params.set('student_id', studentId);
            }

            const response = await fetch(`${optionsUrl}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            const result = await response.json();

            if (!result.available) {
                filterSelectOptions(instructorSelect, []);
                filterSelectOptions(vehicleSelect, []);
                setFeedback(
                    'danger',
                    (result.messages ?? ['Sin cupos para este horario.']).join(' ')
                );
                return;
            }

            filterSelectOptions(instructorSelect, result.instructor_ids ?? []);
            filterSelectOptions(vehicleSelect, result.vehicle_ids ?? []);
            applyFirstPair(result.pairs);
            hideFeedback();
            await checkAvailability();
        } catch {
            resetSelectFilters();
            setFeedback('warning', 'No se pudieron cargar instructores y vehículos disponibles.');
        }
    };

    const openSlot = async ({ date, time, endTime }) => {
        if (date && dateInput) {
            dateInput.value = date;
        }

        if (time && timeSelect) {
            timeSelect.value = normalizeTime(time) ?? '';
        }

        const summaryParts = [];
        if (date) {
            summaryParts.push(formatDateLabel(date));
        }
        if (time) {
            summaryParts.push(`${normalizeTime(time)}${endTime ? ` - ${normalizeTime(endTime)}` : ''} (2 h)`);
        }

        setSlotLocked(Boolean(date && time), summaryParts.join(' · '));

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        if (!modalEl.classList.contains('show')) {
            modal.show();
        }

        if (date && time && studentIdInput?.value) {
            await loadSlotOptions();
        }
    };

    const selectStudent = async (student) => {
        if (!studentIdInput) {
            return;
        }

        if (!student.can_reserve) {
            hideAlerts();
            showNotFound(
                `El alumno <strong>${student.full_name}</strong> no tiene clases disponibles en su curso (${student.course ?? 'sin curso'}).`
            );
            clearStudent();
            return;
        }

        studentIdInput.value = student.id;
        selectedNameEl.textContent = student.full_name;

        if (selectedCourseEl) {
            selectedCourseEl.textContent = `${student.course} · ${student.completed_classes ?? student.used_classes}/${student.allowed_classes} completadas · ${student.remaining_classes} por agendar · máx. 2 clases/día`;
        }

        if (searchInput) {
            searchInput.value = student.full_name;
        }

        selectedAlert?.classList.remove('d-none');
        notFoundAlert?.classList.add('d-none');
        resultsEl?.classList.add('d-none');
        enableForm(true);

        if (dateInput?.value && timeSelect?.value) {
            await loadSlotOptions();
        }
    };

    const clearStudent = (resetInput = false) => {
        if (studentIdInput) {
            studentIdInput.value = '';
        }

        if (resetInput && searchInput) {
            searchInput.value = '';
        }

        hideAlerts();
        enableForm(false);
        resetSelectFilters();
        hideFeedback();
    };

    const escapeHtml = (value) =>
        String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');

    const renderResults = (students) => {
        if (!resultsEl) {
            return;
        }

        resultsEl.innerHTML = '';

        if (students.length === 0) {
            resultsEl.classList.add('d-none');
            showNotFound(`
                No se encontró ningún alumno con ese nombre.
                <button
                    type="button"
                    class="btn btn-sm btn-brand-outline ms-1"
                    data-bs-dismiss="modal"
                    data-bs-toggle="modal"
                    data-bs-target="#addStudentModal"
                >
                    <i class="bi bi-person-plus"></i> Agregar alumno
                </button>
            `);
            return;
        }

        notFoundAlert?.classList.add('d-none');

        students.forEach((student) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'list-group-item list-group-item-action';
            button.setAttribute('role', 'option');

            const statusLabel = student.can_reserve
                ? `${student.remaining_classes} clases restantes`
                : 'Sin clases disponibles';

            button.innerHTML = `
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="fw-semibold">${escapeHtml(student.full_name)}</div>
                        <small class="text-muted">${escapeHtml(student.email)}</small>
                    </div>
                    <span class="table-badge">${escapeHtml(student.course ?? 'Sin curso')}</span>
                </div>
                <small class="${student.can_reserve ? 'text-muted' : 'text-danger'}">${escapeHtml(statusLabel)}</small>
            `;

            if (student.can_reserve) {
                button.addEventListener('click', () => selectStudent(student));
            } else {
                button.classList.add('opacity-50');
                button.addEventListener('click', () => {
                    hideAlerts();
                    showNotFound(
                        `El alumno <strong>${escapeHtml(student.full_name)}</strong> no tiene clases disponibles en su curso (${escapeHtml(student.course ?? 'sin curso')}).`
                    );
                });
            }

            resultsEl.appendChild(button);
        });

        resultsEl.classList.remove('d-none');
    };

    const searchStudents = async () => {
        const query = searchInput?.value.trim() ?? '';

        if (query.length < MIN_SEARCH_CHARS) {
            resultsEl?.classList.add('d-none');
            if (resultsEl) {
                resultsEl.innerHTML = '';
            }
            notFoundAlert?.classList.add('d-none');
            return;
        }

        const requestId = ++searchRequest;

        if (resultsEl) {
            resultsEl.innerHTML = '<div class="list-group-item text-muted small">Buscando alumnos...</div>';
            resultsEl.classList.remove('d-none');
        }

        notFoundAlert?.classList.add('d-none');

        try {
            const response = await fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json' },
            });

            if (requestId !== searchRequest) {
                return;
            }

            const students = await response.json();
            renderResults(Array.isArray(students) ? students : []);
        } catch {
            if (requestId !== searchRequest) {
                return;
            }

            resultsEl?.classList.add('d-none');
            showNotFound('Error al buscar. Intenta de nuevo.');
        }
    };

    searchInput?.addEventListener('input', () => {
        if (studentIdInput?.value) {
            studentIdInput.value = '';
            selectedAlert?.classList.add('d-none');
            enableForm(false);
            resetSelectFilters();
            hideFeedback();
        }

        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(searchStudents, 250);
    });

    searchInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            window.clearTimeout(searchTimer);
            searchStudents();
        }

        if (event.key === 'Escape') {
            resultsEl?.classList.add('d-none');
        }
    });

    clearBtn?.addEventListener('click', () => clearStudent(true));

    ['reserva_date', 'reserva_time', 'reserva_instructor_id', 'reserva_vehicle_id'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', async () => {
            if (slotLocked && (id === 'reserva_date' || id === 'reserva_time')) {
                return;
            }

            if (id === 'reserva_date' || id === 'reserva_time') {
                await loadSlotOptions();
                return;
            }

            await checkAvailability();
        });
    });

    document.getElementById('openScheduleManualBtn')?.addEventListener('click', () => {
        unlockSlot();
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        if (modalEl.dataset.autoOpen !== 'true') {
            clearStudent(true);
            unlockSlot();
            if (searchInput) {
                searchInput.value = '';
            }
        }
    });

  const scheduleForm = document.getElementById('scheduleClassForm');
    let scheduleFormSubmitting = false;

    scheduleForm?.addEventListener('submit', async (event) => {
        if (scheduleFormSubmitting) {
            return;
        }

        event.preventDefault();

        if (submitBtn?.disabled) {
            setFeedback('danger', 'Selecciona instructor y vehículo disponibles antes de confirmar.');
            return;
        }

        if (slotLocked && (!dateInput?.value || !timeSelect?.value)) {
            setFeedback('danger', 'Falta la fecha u hora. Vuelve a elegir un bloque verde en el calendario.');
            return;
        }

        const studentName = selectedNameEl?.textContent?.trim() || 'el alumno';
        const dateLabel = dateInput?.value
            ? new Date(`${dateInput.value}T12:00:00`).toLocaleDateString('es-MX', {
                  weekday: 'long',
                  day: 'numeric',
                  month: 'long',
                  year: 'numeric',
              })
            : '—';
        const horarioLabel = timeSelect?.value ? `${timeSelect.value} (2 h)` : '—';

        const confirmed = await confirmAgendarClase({
            nombre: studentName,
            fecha: dateLabel,
            horario: horarioLabel,
            confirmText: 'Sí, agendar',
        });

        if (confirmed) {
            scheduleFormSubmitting = true;
            scheduleForm.submit();
        }
    });

    window.AdminScheduling = { openSlot, loadSlotOptions, checkAvailability };

    if (modalEl.dataset.oldStudent) {
        try {
            const student = JSON.parse(modalEl.dataset.oldStudent);
            selectStudent({
                ...student,
                course: student.course ?? 'Curso asignado',
                allowed_classes: student.allowed_classes ?? 0,
                completed_classes: student.completed_classes ?? student.used_classes ?? 0,
                used_classes: student.completed_classes ?? student.used_classes ?? 0,
                remaining_classes: student.remaining_classes ?? 1,
                can_reserve: true,
            });
        } catch {
            // ignore invalid JSON
        }
    } else if (studentIdInput?.value) {
        enableForm(true);
    }
});
