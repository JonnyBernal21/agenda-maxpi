import * as bootstrap from 'bootstrap';
import { confirmSoftDelete, showBookingError, showBookingSuccess } from './booking-confirm';

let openScheduleSummary = () => {};

const WEEKDAY_NAMES = {
    1: 'lunes',
    2: 'martes',
    3: 'miércoles',
    4: 'jueves',
    5: 'viernes',
    6: 'sábado',
    7: 'domingo',
};

const WEEKDAY_SHORT = {
    1: 'Lun',
    2: 'Mar',
    3: 'Mié',
    4: 'Jue',
    5: 'Vie',
    6: 'Sáb',
};

const isoWeekdayFromDate = (value) => {
    const [year, month, day] = String(value).split('-').map(Number);
    const date = new Date(year, month - 1, day);

    return date.getDay() === 0 ? 7 : date.getDay();
};

const formatFullDate = (isoDate) => {
    const [year, month, day] = isoDate.split('-').map(Number);
    const date = new Date(year, month - 1, day);

    return date.toLocaleDateString('es-MX', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const weekdayName = (isoDate) => {
    const iso = isoWeekdayFromDate(isoDate);
    const name = WEEKDAY_NAMES[iso] ?? '';

    return name ? name.charAt(0).toUpperCase() + name.slice(1) : '—';
};

const emptyPreviewRow = '<tr class="schedule-preview-empty"><td colspan="6" class="text-muted text-center py-3">Selecciona fecha, días, hora e instructor.</td></tr>';

const formatTimeLabel = (time) => {
    const [hour, minute] = time.split(':').map(Number);
    const suffix = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;

    return `${hour12}:${String(minute).padStart(2, '0')} ${suffix}`;
};

const addDays = (isoDate, amount) => {
    const [year, month, day] = isoDate.split('-').map(Number);
    const date = new Date(year, month - 1, day);
    date.setDate(date.getDate() + amount);

    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');

    return `${y}-${m}-${d}`;
};

const collectWeekdays = (modalEl) =>
    [...modalEl.querySelectorAll('.weekday-chip__input:checked')].map((input) => Number(input.value));

const previewDates = (startDate, weekdays, count) => {
    const dates = [];
    let cursor = startDate;
    let guard = 0;

    while (dates.length < count && guard < 400) {
        if (weekdays.includes(isoWeekdayFromDate(cursor))) {
            dates.push(cursor);
        }

        cursor = addDays(cursor, 1);
        guard += 1;
    }

    return dates;
};

const initAssignSchedule = () => {
    const modalEl = document.getElementById('assignScheduleModal');

    if (!modalEl) {
        return;
    }

    const dateInput = document.getElementById('schedule_start_date');
    const timeInput = document.getElementById('schedule_time');
    const instructorInput = document.getElementById('schedule_instructor_id');
    const vehicleInput = document.getElementById('schedule_vehicle_id');
    const hint = document.getElementById('scheduleStartHint');
    const summary = document.getElementById('schedulePreviewSummary');
    const tableBody = document.getElementById('schedulePreviewBody');
    const conflictAlert = document.getElementById('scheduleConflictAlert');
    const submitBtn = document.getElementById('assignScheduleSubmit');
    const numClasses = Number(modalEl.dataset.numClasses || 0);
    const conflictsUrl = modalEl.dataset.conflictsUrl || '';
    const minStartDate = modalEl.dataset.minStartDate || '';
    const sameDayBlocked = modalEl.dataset.sameDayBlocked === 'true';
    const sameDayMessage =
        modalEl.dataset.sameDayMessage ||
        'A partir de las 9:00 AM no se pueden agendar clases ni horarios para el día de hoy. Elige una fecha a partir de mañana.';
    const parseJsonData = (value, fallback) => {
        try {
            const parsed = JSON.parse(value || '');

            return parsed ?? fallback;
        } catch {
            return fallback;
        }
    };
    const hourSlots = parseJsonData(modalEl.dataset.hourSlots, []);
    const vehicles = parseJsonData(modalEl.dataset.vehicles, []);
    let rowTimes = {};
    let rowVehicles = {};
    let previewDatesList = [];
    let previewRequest = 0;
    let autoAdjustDepth = 0;

    const applyMinStartDate = () => {
        if (!dateInput || !minStartDate) {
            return;
        }

        dateInput.min = minStartDate;

        if (dateInput.value && dateInput.value < minStartDate) {
            dateInput.value = '';
        }
    };

    const setWeekdayChecked = (iso, checked) => {
        const input = modalEl.querySelector(`.weekday-chip__input[data-iso="${iso}"]`);

        if (input) {
            input.checked = checked;
        }
    };

    const updateStartHint = () => {
        const value = dateInput?.value;

        modalEl.querySelectorAll('.weekday-chip').forEach((chip) => chip.classList.remove('is-start-day'));

        if (!value) {
            if (hint) {
                hint.textContent = sameDayBlocked
                    ? 'A partir de las 9:00 AM no se agenda el día de hoy. Elige desde mañana.'
                    : 'Elige lunes o viernes para detectar el día de arranque.';
            }
            return null;
        }

        if (minStartDate && value < minStartDate) {
            if (hint) {
                hint.textContent = sameDayMessage;
            }
            return null;
        }

        const iso = isoWeekdayFromDate(value);
        const startChip = modalEl.querySelector(`.weekday-chip__input[data-iso="${iso}"]`)?.closest('.weekday-chip');
        startChip?.classList.add('is-start-day');

        if (iso === 1 || iso === 5) {
            if (hint) {
                hint.innerHTML = `Inicio detectado: <strong>${WEEKDAY_NAMES[iso]}</strong>. Marca los días que se repetirán cada semana.`;
            }
            return iso;
        }

        if (hint) {
            hint.innerHTML = `La fecha cae en <strong>${WEEKDAY_NAMES[iso] ?? 'otro día'}</strong>. Se recomienda iniciar en lunes o viernes.`;
        }

        return iso;
    };

    const setConflictAlert = (html, show, type = 'warning') => {
        if (!conflictAlert) {
            return;
        }

        conflictAlert.innerHTML = html;
        conflictAlert.classList.toggle('d-none', !show);
        conflictAlert.classList.toggle('alert-warning', type === 'warning');
        conflictAlert.classList.toggle('alert-info', type === 'info');
    };

    const setSubmitEnabled = (enabled) => {
        if (submitBtn) {
            submitBtn.disabled = !enabled;
        }
    };

    const timeOptionsHtml = (selected) =>
        hourSlots
            .map(
                (slot) =>
                    `<option value="${slot}" ${slot === selected ? 'selected' : ''}>${formatTimeLabel(slot)}</option>`
            )
            .join('');

    const vehicleOptionsHtml = (selected) =>
        vehicles
            .map((vehicle) => {
                const isSelected = String(vehicle.id) === String(selected || '');

                return `<option value="${vehicle.id}" ${isSelected ? 'selected' : ''}>${vehicle.modelo} (${vehicle.plate}) · ${vehicle.type_label}</option>`;
            })
            .join('');

    const pruneRowValues = (store, dates, defaultValue, resetAll) => {
        if (resetAll) {
            Object.keys(store).forEach((key) => {
                delete store[key];
            });
        }

        const keep = new Set(dates);

        Object.keys(store).forEach((date) => {
            if (!keep.has(date)) {
                delete store[date];
            }
        });

        dates.forEach((date) => {
            if (!store[date] && defaultValue) {
                store[date] = defaultValue;
            }
        });
    };

    const allRowsHaveVehicle = () =>
        previewDatesList.length > 0 && previewDatesList.every((date) => Boolean(rowVehicles[date]));

    const statusHtml = (conflict) => {
        if (!conflict?.busy) {
            return '<span class="text-success">Disponible</span>';
        }

        const reasons = [];

        if (conflict.instructor_busy) {
            reasons.push('Instructor ocupado');
        }

        if (conflict.vehicle_busy) {
            reasons.push('Vehículo ocupado');
        }

        const label = reasons.length > 0 ? reasons.join(' y ') : 'Ocupado';
        const next = conflict.next_time ? ` · siguiente ${formatTimeLabel(conflict.next_time)}` : '';

        return `<span class="text-danger">${label}${next}</span>`;
    };

    const collectPreviewSlots = () =>
        previewDatesList.map((date) => ({
            date,
            time: rowTimes[date] || timeInput?.value,
            vehicle_id: rowVehicles[date] || vehicleInput?.value,
        }));

    const renderRows = (dates, conflictMap = {}) =>
        dates
            .map((date, index) => {
                const time = rowTimes[date] || timeInput?.value;
                const vehicleId = rowVehicles[date] || vehicleInput?.value;
                const conflict = conflictMap[date];
                const busy = Boolean(conflict?.busy);

                return `
                    <tr class="${busy ? 'schedule-row-busy' : ''}" data-date="${date}">
                        <td>${index + 1}</td>
                        <td>${weekdayName(date)}</td>
                        <td>${formatFullDate(date)}</td>
                        <td>
                            <input type="hidden" name="class_dates[]" value="${date}">
                            <select name="class_times[]" class="form-select form-select-sm schedule-time-select" data-row-date="${date}" aria-label="Hora de la clase ${index + 1}">
                                ${timeOptionsHtml(time)}
                            </select>
                        </td>
                        <td class="schedule-vehicle-cell">
                            <select name="class_vehicle_ids[]" class="form-select form-select-sm schedule-vehicle-select" data-row-vehicle="${date}" aria-label="Vehículo de la clase ${index + 1}">
                                <option value="">Seleccionar vehículo</option>
                                ${vehicleOptionsHtml(vehicleId)}
                            </select>
                        </td>
                        <td data-cupo-cell>${statusHtml(conflict)}</td>
                    </tr>
                `;
            })
            .join('');

    const applyConflicts = (conflicts = []) => {
        const conflictMap = Object.fromEntries((conflicts || []).map((item) => [item.date, item]));
        const shifted = [];

        if (autoAdjustDepth < 5) {
            (conflicts || []).forEach((item) => {
                if (!item.busy || !item.next_time || !hourSlots.includes(item.next_time)) {
                    return;
                }

                const select = tableBody?.querySelector(`[data-row-date="${item.date}"]`);

                if (!select || select.value === item.next_time) {
                    return;
                }

                select.value = item.next_time;
                rowTimes[item.date] = item.next_time;
                shifted.push(`${weekdayName(item.date)} ${formatFullDate(item.date)} → ${formatTimeLabel(item.next_time)}`);
            });
        }

        if (shifted.length > 0) {
            autoAdjustDepth += 1;
            setConflictAlert(
                `<i class="bi bi-clock-history me-1"></i> Se movió el horario 2 horas después porque el instructor o el vehículo ya tenían clase: ${shifted.join('; ')}.`,
                true,
                'info'
            );
            fetchConflicts();
            return;
        }

        autoAdjustDepth = 0;

        tableBody?.querySelectorAll('tr[data-date]').forEach((row) => {
            const conflict = conflictMap[row.dataset.date];
            const busy = Boolean(conflict?.busy);

            row.classList.toggle('schedule-row-busy', busy);

            const cell = row.querySelector('[data-cupo-cell]');

            if (cell) {
                cell.innerHTML = statusHtml(conflict);
            }
        });

        const busyItems = (conflicts || []).filter((item) => item.busy);

        if (busyItems.length > 0) {
            const first = busyItems[0];
            const extra = busyItems.length > 1 ? ` Hay ${busyItems.length} fechas con cruce.` : '';
            setConflictAlert(
                `<i class="bi bi-exclamation-triangle me-1"></i>${first.message || 'Hay un cruce de instructor o vehículo.'}${extra}`,
                true,
                'warning'
            );
            setSubmitEnabled(false);
            return;
        }

        setConflictAlert('', false);
        setSubmitEnabled(Boolean(instructorInput?.value && allRowsHaveVehicle()));
    };

    const fetchConflicts = async () => {
        const instructorId = instructorInput?.value;
        const vehicleId = vehicleInput?.value;
        const slots = collectPreviewSlots();

        if (!instructorId || !conflictsUrl || slots.length === 0) {
            applyConflicts([]);
            setSubmitEnabled(false);
            return;
        }

        const requestId = ++previewRequest;
        const params = new URLSearchParams();
        params.set('instructor_id', instructorId);

        if (vehicleId) {
            params.set('vehicle_id', vehicleId);
        }

        slots.forEach((slot) => {
            params.append('dates[]', slot.date);
            params.append('times[]', slot.time);

            if (slot.vehicle_id) {
                params.append('vehicle_ids[]', slot.vehicle_id);
            } else {
                params.append('vehicle_ids[]', '');
            }
        });

        try {
            const response = await fetch(`${conflictsUrl}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok || requestId !== previewRequest) {
                return;
            }

            const payload = await response.json();
            applyConflicts(payload.conflicts || []);
        } catch {
            if (requestId === previewRequest) {
                setConflictAlert(
                    '<i class="bi bi-exclamation-triangle me-1"></i> No se pudo verificar el cupo del instructor o del vehículo. Intenta de nuevo.',
                    true
                );
                setSubmitEnabled(false);
            }
        }
    };

    const bindRowSelects = () => {
        tableBody?.querySelectorAll('[data-row-date]').forEach((select) => {
            select.addEventListener('change', () => {
                rowTimes[select.dataset.rowDate] = select.value;
                fetchConflicts();
            });
        });

        tableBody?.querySelectorAll('[data-row-vehicle]').forEach((select) => {
            select.addEventListener('change', () => {
                rowVehicles[select.dataset.rowVehicle] = select.value;
                fetchConflicts();
            });
        });
    };

    const refreshPreview = async ({ resetTimes = false, resetVehicles = false } = {}) => {
        if (!summary || !tableBody) {
            return;
        }

        const startDate = dateInput?.value;
        const weekdays = collectWeekdays(modalEl);
        const time = timeInput?.value;
        const instructorId = instructorInput?.value;
        const vehicleId = vehicleInput?.value;

        setConflictAlert('', false);

        if (minStartDate && startDate && startDate < minStartDate) {
            summary.textContent = sameDayMessage;
            tableBody.innerHTML = emptyPreviewRow;
            previewDatesList = [];
            setConflictAlert(`<i class="bi bi-exclamation-triangle me-1"></i>${sameDayMessage}`, true);
            setSubmitEnabled(false);
            return;
        }

        if (!startDate || weekdays.length === 0 || !time || numClasses < 1) {
            summary.textContent = 'Marca o quita días para actualizar la tabla.';
            tableBody.innerHTML = emptyPreviewRow;
            previewDatesList = [];
            setSubmitEnabled(false);
            return;
        }

        const dates = previewDates(startDate, weekdays, numClasses);
        pruneRowValues(rowTimes, dates, time, resetTimes);
        pruneRowValues(rowVehicles, dates, vehicleId, resetVehicles);
        previewDatesList = dates;
        autoAdjustDepth = 0;

        const dayNames = weekdays
            .sort((a, b) => a - b)
            .map((day) => WEEKDAY_SHORT[day])
            .join(', ');

        summary.textContent = `${dates.length} ${dates.length === 1 ? 'clase' : 'clases'} · ${dayNames}`;
        tableBody.innerHTML = renderRows(dates);
        bindRowSelects();
        setSubmitEnabled(Boolean(instructorId && allRowsHaveVehicle()));
        await fetchConflicts();
    };

    dateInput?.addEventListener('change', () => {
        const iso = updateStartHint();

        if (iso && iso >= 1 && iso <= 6 && collectWeekdays(modalEl).length === 0) {
            setWeekdayChecked(iso, true);
        } else if (iso && iso >= 1 && iso <= 6) {
            setWeekdayChecked(iso, true);
        }

        refreshPreview();
    });

    modalEl.querySelectorAll('.weekday-chip__input').forEach((input) => {
        input.addEventListener('change', () => refreshPreview());
        input.addEventListener('click', () => refreshPreview());
    });

    timeInput?.addEventListener('change', () => {
        refreshPreview({ resetTimes: true });
    });
    instructorInput?.addEventListener('change', () => refreshPreview());
    vehicleInput?.addEventListener('change', () => {
        refreshPreview({ resetVehicles: true });
    });

    modalEl.querySelector('form')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        applyMinStartDate();

        if (minStartDate && dateInput?.value && dateInput.value < minStartDate) {
            setConflictAlert(`<i class="bi bi-exclamation-triangle me-1"></i>${sameDayMessage}`, true);
            setSubmitEnabled(false);

            return;
        }

        const form = event.currentTarget;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const originalHtml = submitBtn?.innerHTML;
        let assigned = false;

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Asignando...';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: new FormData(form),
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                setConflictAlert(
                    `<i class="bi bi-exclamation-triangle me-1"></i>${escapeHtml(payload.message || 'No se pudieron asignar los horarios.')}`,
                    true
                );

                return;
            }

            assigned = true;
            const assignModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modalEl.addEventListener(
                'hidden.bs.modal',
                () => {
                    openScheduleSummary(payload);
                },
                { once: true }
            );
            assignModal.hide();
        } catch {
            setConflictAlert(
                '<i class="bi bi-exclamation-triangle me-1"></i> No se pudieron asignar los horarios. Intenta de nuevo.',
                true
            );
        } finally {
            if (!assigned && submitBtn) {
                submitBtn.innerHTML = originalHtml;
                setSubmitEnabled(true);
            }
        }
    });

    modalEl.addEventListener('shown.bs.modal', () => {
        applyMinStartDate();
        updateStartHint();
        refreshPreview();
    });

    applyMinStartDate();
    updateStartHint();
    refreshPreview();
};

const initUppercaseInputs = () => {
    const forceUppercase = (input) => {
        const start = input.selectionStart;
        const end = input.selectionEnd;
        const next = input.value.toLocaleUpperCase('es-MX');

        if (input.value === next) {
            return;
        }

        input.value = next;
        if (typeof start === 'number' && typeof end === 'number') {
            input.setSelectionRange(start, end);
        }
    };

    document.querySelectorAll('.input-uppercase').forEach((input) => {
        input.addEventListener('input', () => forceUppercase(input));
        input.addEventListener('blur', () => forceUppercase(input));
        input.form?.addEventListener('submit', () => forceUppercase(input));
    });
};

const initDocumentUploads = () => {
    document.querySelectorAll('[data-doc-upload]').forEach((wrapper) => {
        const fileInput = wrapper.querySelector('[data-doc-file-input]');
        const cameraInput = wrapper.querySelector('[data-doc-camera-input]');
        const preview = wrapper.querySelector('[data-doc-preview]');
        const previewImage = wrapper.querySelector('[data-doc-image]');
        const previewIcon = wrapper.querySelector('[data-doc-icon]');
        const filename = wrapper.querySelector('[data-doc-filename]');
        let previewUrl = '';

        const clearPreview = () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = '';
            }

            if (previewImage) {
                previewImage.src = '';
                previewImage.classList.add('d-none');
            }

            previewIcon?.classList.add('d-none');
            if (filename) {
                filename.textContent = '';
            }
            preview?.classList.add('d-none');
        };

        const assignFile = (file) => {
            if (!file || !fileInput) {
                return;
            }

            const transfer = new DataTransfer();
            transfer.items.add(file);
            fileInput.files = transfer.files;
            fileInput.dispatchEvent(new Event('change', { bubbles: true }));
        };

        const showPreview = (file) => {
            if (!file || !preview) {
                clearPreview();
                return;
            }

            if (filename) {
                filename.textContent = file.name;
            }

            if (file.type.startsWith('image/') && previewImage) {
                if (previewUrl) {
                    URL.revokeObjectURL(previewUrl);
                }
                previewUrl = URL.createObjectURL(file);
                previewImage.src = previewUrl;
                previewImage.classList.remove('d-none');
                previewIcon?.classList.add('d-none');
            } else {
                previewImage?.classList.add('d-none');
                previewIcon?.classList.remove('d-none');
            }

            preview.classList.remove('d-none');
        };

        wrapper.querySelector('[data-doc-camera]')?.addEventListener('click', () => {
            cameraInput?.click();
        });

        wrapper.querySelector('[data-doc-file]')?.addEventListener('click', () => {
            fileInput?.click();
        });

        cameraInput?.addEventListener('change', () => {
            const file = cameraInput.files?.[0];

            if (file) {
                assignFile(file);
            }

            cameraInput.value = '';
        });

        fileInput?.addEventListener('change', () => {
            const file = fileInput.files?.[0];

            if (file) {
                showPreview(file);
                wrapper.classList.remove('is-invalid');
            } else {
                clearPreview();
            }
        });

        wrapper.querySelector('[data-doc-clear]')?.addEventListener('click', () => {
            if (fileInput) {
                fileInput.value = '';
            }

            if (wrapper.dataset.existingUrl) {
                wrapper.showExistingDoc?.(wrapper.dataset.existingUrl, wrapper.dataset.existingLabel);
                return;
            }

            clearPreview();
        });

        wrapper.resetDocUpload = (required = true) => {
            delete wrapper.dataset.existingUrl;
            delete wrapper.dataset.existingLabel;

            if (fileInput) {
                fileInput.value = '';
                fileInput.required = required;
            }

            if (cameraInput) {
                cameraInput.value = '';
            }

            wrapper.classList.remove('is-invalid');
            clearPreview();
        };

        wrapper.showExistingDoc = (url, label) => {
            if (fileInput) {
                fileInput.value = '';
                fileInput.required = false;
            }

            if (!url) {
                clearPreview();
                return;
            }

            wrapper.dataset.existingUrl = url;
            wrapper.dataset.existingLabel = label || wrapper.dataset.docLabel || 'Archivo actual';

            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = '';
            }

            if (filename) {
                filename.textContent = wrapper.dataset.existingLabel;
            }

            const isPdf = /\.pdf(\?|$)/i.test(url);

            if (!isPdf && previewImage) {
                previewImage.src = url;
                previewImage.classList.remove('d-none');
                previewIcon?.classList.add('d-none');
            } else {
                previewImage?.classList.add('d-none');
                previewIcon?.classList.remove('d-none');
            }

            preview?.classList.remove('d-none');
        };
    });
};

const escapeHtml = (value) =>
    String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');

const toIsoDate = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const initScheduleSummary = () => {
    const modalEl = document.getElementById('scheduleSummaryModal');

    if (!modalEl) {
        return;
    }

    const nameEl = document.getElementById('scheduleSummaryStudentName');
    const metaEl = document.getElementById('scheduleSummaryStudentMeta');
    const monthLabel = document.getElementById('scheduleSummaryMonthLabel');
    const gridEl = document.getElementById('scheduleSummaryGrid');
    const listBody = document.getElementById('scheduleSummaryListBody');
    const prevBtn = document.getElementById('scheduleSummaryPrev');
    const nextBtn = document.getElementById('scheduleSummaryNext');
    const printBtn = document.getElementById('scheduleSummaryPrint');
    const emailBtn = document.getElementById('scheduleSummaryEmail');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const state = {
        classes: [],
        student: null,
        sendUrl: '',
        year: new Date().getFullYear(),
        month: new Date().getMonth(),
        sending: false,
    };

    const parseIso = (value) => {
        const [year, month, day] = String(value).split('-').map(Number);

        return new Date(year, month - 1, day);
    };

    const classesByDate = () => {
        const map = new Map();

        state.classes.forEach((item) => {
            const list = map.get(item.date) || [];
            list.push(item);
            map.set(item.date, list);
        });

        return map;
    };

    const monthBounds = () => {
        if (state.classes.length === 0) {
            const now = new Date();

            return { min: now.getFullYear() * 12 + now.getMonth(), max: now.getFullYear() * 12 + now.getMonth() };
        }

        const months = state.classes.map((item) => {
            const date = parseIso(item.date);

            return date.getFullYear() * 12 + date.getMonth();
        });

        return { min: Math.min(...months), max: Math.max(...months) };
    };

    const renderList = () => {
        if (!listBody) {
            return;
        }

        if (state.classes.length === 0) {
            listBody.innerHTML =
                '<tr><td colspan="6" class="text-muted text-center py-3">No hay clases asignadas.</td></tr>';

            return;
        }

        listBody.innerHTML = state.classes
            .map((item, index) => {
                const timeRange = `${formatTimeLabel(item.time)} – ${formatTimeLabel(item.end_time)}`;

                return `
                    <tr>
                        <td>${index + 1}</td>
                        <td class="text-capitalize">${escapeHtml(item.weekday || weekdayName(item.date))}</td>
                        <td>${escapeHtml(item.date_label || formatFullDate(item.date))}</td>
                        <td class="fw-semibold">${escapeHtml(timeRange)}</td>
                        <td>${escapeHtml(item.instructor)}</td>
                        <td>${escapeHtml(item.vehicle)}</td>
                    </tr>
                `;
            })
            .join('');
    };

    const renderMonth = () => {
        if (!gridEl || !monthLabel) {
            return;
        }

        const cursor = state.year * 12 + state.month;
        const bounds = monthBounds();

        if (prevBtn) {
            prevBtn.disabled = cursor <= bounds.min;
        }

        if (nextBtn) {
            nextBtn.disabled = cursor >= bounds.max;
        }

        monthLabel.textContent = new Date(state.year, state.month, 1).toLocaleDateString('es-MX', {
            month: 'long',
            year: 'numeric',
        });

        const first = new Date(state.year, state.month, 1);
        const lastDate = new Date(state.year, state.month + 1, 0).getDate();
        const startPad = (first.getDay() + 6) % 7;
        const grouped = classesByDate();
        const cells = [];

        for (let index = 0; index < startPad; index += 1) {
            cells.push('<div class="schedule-month__day is-outside"></div>');
        }

        for (let day = 1; day <= lastDate; day += 1) {
            const iso = toIsoDate(new Date(state.year, state.month, day));
            const items = grouped.get(iso) || [];
            const chips = items
                .map(
                    (item) => `
                        <span class="schedule-month__chip" title="${escapeHtml(item.instructor)} · ${escapeHtml(item.vehicle)}">
                            ${escapeHtml(formatTimeLabel(item.time))}
                        </span>
                    `
                )
                .join('');

            cells.push(`
                <div class="schedule-month__day${items.length ? ' has-class' : ''}">
                    <span class="schedule-month__num">${day}</span>
                    <div class="schedule-month__events">${chips}</div>
                </div>
            `);
        }

        gridEl.innerHTML = cells.join('');
    };

    const renderHeader = () => {
        const student = state.student || {};
        const count = state.classes.length;
        const email = student.email ? escapeHtml(student.email) : 'sin correo';

        if (nameEl) {
            nameEl.textContent = student.name || 'Alumno';
        }

        if (metaEl) {
            metaEl.innerHTML = `${escapeHtml(student.course || 'Sin curso')} · ${count} ${
                count === 1 ? 'clase' : 'clases'
            } · ${email}`;
        }
    };

    prevBtn?.addEventListener('click', () => {
        const previous = new Date(state.year, state.month - 1, 1);
        state.year = previous.getFullYear();
        state.month = previous.getMonth();
        renderMonth();
    });

    nextBtn?.addEventListener('click', () => {
        const next = new Date(state.year, state.month + 1, 1);
        state.year = next.getFullYear();
        state.month = next.getMonth();
        renderMonth();
    });

    printBtn?.addEventListener('click', () => {
        window.print();
    });

    emailBtn?.addEventListener('click', async () => {
        if (state.sending || !state.sendUrl) {
            return;
        }

        const originalHtml = emailBtn.innerHTML;
        state.sending = true;
        emailBtn.disabled = true;
        emailBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';

        try {
            const response = await fetch(state.sendUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                },
                body: '{}',
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                await showBookingError(payload.message || 'No se pudieron enviar los horarios por correo.');

                return;
            }

            const email = payload.email || state.student?.email || 'el alumno';
            modalEl.addEventListener(
                'hidden.bs.modal',
                async () => {
                    await showBookingSuccess(`Horarios enviados por correo a ${email}.`, 'Registro exitoso');
                    window.location.reload();
                },
                { once: true }
            );
            modal.hide();
        } catch {
            await showBookingError('No se pudieron enviar los horarios por correo. Intenta de nuevo.');
        } finally {
            state.sending = false;
            emailBtn.disabled = false;
            emailBtn.innerHTML = originalHtml;
        }
    });

    openScheduleSummary = (payload = {}) => {
        state.classes = Array.isArray(payload.classes) ? payload.classes : [];
        state.student = payload.student || {};
        state.sendUrl = payload.send_url || '';

        const first = state.classes[0]?.date ? parseIso(state.classes[0].date) : new Date();
        state.year = first.getFullYear();
        state.month = first.getMonth();

        renderHeader();
        renderList();
        renderMonth();
        modal.show();
    };
};

const initStudentAdmin = () => {
    const fieldNames = ['course_id', 'name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'zip', 'country'];
    const modalEl = document.getElementById('addStudentModal');
    const form = document.getElementById('studentAdminForm');
    const scheduleModalEl = document.getElementById('studentScheduleModal');
    const scheduleModal = scheduleModalEl ? bootstrap.Modal.getOrCreateInstance(scheduleModalEl) : null;
    const studentModal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
    let openingStudentEdit = false;

    const setFieldValue = (name, value) => {
        const field = form?.querySelector(`[name="${name}"]`);

        if (field) {
            field.value = value ?? '';
        }
    };

    const clearStudentInvalid = () => {
        form?.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        const errorAlert = document.getElementById('studentFormErrorAlert');
        errorAlert?.classList.add('d-none');
    };

    const setStudentCreateMode = ({ clearFields = true } = {}) => {
        if (!form || !modalEl) {
            return;
        }

        form.action = modalEl.dataset.storeUrl;
        const methodInput = document.getElementById('studentFormSpoofMethod');
        if (methodInput) {
            methodInput.disabled = true;
        }

        setFieldValue('_form', 'student');
        setFieldValue('editing_id', '');

        const title = document.getElementById('studentFormTitle');
        const icon = document.getElementById('studentFormIcon');
        const hint = document.getElementById('studentFormHint');
        const submitLabel = document.getElementById('studentFormSubmitLabel');

        if (title) {
            title.textContent = 'Agregar alumno';
        }

        if (icon) {
            icon.className = 'bi bi-person-plus';
        }

        hint?.classList.remove('d-none');

        if (submitLabel) {
            submitLabel.textContent = 'Guardar alumno';
        }

        if (clearFields) {
            fieldNames.forEach((name) => setFieldValue(name, name === 'country' ? 'México' : ''));
            clearStudentInvalid();
        }
    };

    const setStudentEditMode = (studentId) => {
        if (!form || !modalEl || !studentId) {
            return;
        }

        form.action = `${modalEl.dataset.updateBase}/${studentId}`;
        const methodInput = document.getElementById('studentFormSpoofMethod');
        if (methodInput) {
            methodInput.disabled = false;
            methodInput.value = 'PUT';
        }

        setFieldValue('_form', 'student-edit');
        setFieldValue('editing_id', studentId);

        const title = document.getElementById('studentFormTitle');
        const icon = document.getElementById('studentFormIcon');
        const hint = document.getElementById('studentFormHint');
        const submitLabel = document.getElementById('studentFormSubmitLabel');

        if (title) {
            title.textContent = 'Editar alumno';
        }

        if (icon) {
            icon.className = 'bi bi-pencil';
        }

        hint?.classList.add('d-none');

        if (submitLabel) {
            submitLabel.textContent = 'Guardar cambios';
        }
    };

    const fillStudentForm = (button) => {
        setFieldValue('course_id', button.dataset.courseId || '');
        setFieldValue('name', button.dataset.name || '');
        setFieldValue('last_name', button.dataset.lastName || '');
        setFieldValue('email', button.dataset.email || '');
        setFieldValue('phone', button.dataset.phone || '');
        setFieldValue('address', button.dataset.address || '');
        setFieldValue('city', button.dataset.city || '');
        setFieldValue('state', button.dataset.state || '');
        setFieldValue('zip', button.dataset.zip || '');
        setFieldValue('country', button.dataset.country || 'México');
        clearStudentInvalid();
    };

    const renderSchedule = (payload) => {
        const nameEl = document.getElementById('studentScheduleName');
        const summaryEl = document.getElementById('studentScheduleSummary');
        const contentEl = document.getElementById('studentScheduleContent');

        if (nameEl) {
            nameEl.textContent = payload.name || 'alumno';
        }

        if (summaryEl) {
            const course = payload.course ? escapeHtml(payload.course) : 'Sin curso';
            summaryEl.innerHTML = `${course} · ${payload.booked ?? 0} / ${payload.allowed ?? 0} clases · ${payload.remaining ?? 0} restantes`;
        }

        if (!contentEl) {
            return;
        }

        const classes = payload.classes ?? [];

        if (!classes.length) {
            contentEl.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="bi bi-calendar-x display-6 d-block mb-2 opacity-50"></i>
                    <p class="mb-0">Este alumno no tiene clases agendadas.</p>
                </div>
            `;
            return;
        }

        const rows = classes
            .map((item) => {
                const date = String(item.date ?? '').slice(0, 10);
                const timeRange = item.time && item.end_time ? `${item.time} - ${item.end_time}` : item.time || '—';

                return `
                    <tr class="${item.is_past ? 'is-past' : ''}">
                        <td>
                            <span class="d-block text-capitalize">${escapeHtml(weekdayName(date))}</span>
                            <span class="text-muted small">${escapeHtml(formatFullDate(date))}</span>
                        </td>
                        <td class="fw-semibold">${escapeHtml(timeRange)}</td>
                        <td>${escapeHtml(item.instructor)}</td>
                        <td>${escapeHtml(item.vehicle)}</td>
                        <td>
                            <span class="badge ${escapeHtml(item.status_class)}">${escapeHtml(item.status_label)}</span>
                        </td>
                    </tr>
                `;
            })
            .join('');

        contentEl.innerHTML = `
            <div class="table-responsive">
                <table class="table table-hover align-middle student-schedule-table w-100 mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Instructor</th>
                            <th>Vehículo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    };

    const openSchedule = async (button) => {
        if (!scheduleModal) {
            return;
        }

        const nameEl = document.getElementById('studentScheduleName');
        const summaryEl = document.getElementById('studentScheduleSummary');
        const contentEl = document.getElementById('studentScheduleContent');

        if (nameEl) {
            nameEl.textContent = button.dataset.studentName || 'alumno';
        }

        if (summaryEl) {
            summaryEl.textContent = '';
        }

        if (contentEl) {
            contentEl.innerHTML = `
                <div class="text-center text-muted py-4">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Cargando horarios...
                </div>
            `;
        }

        scheduleModal.show();

        try {
            const response = await fetch(button.dataset.scheduleUrl, {
                headers: { Accept: 'application/json' },
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(data.message || 'No se pudieron cargar los horarios.');
            }

            renderSchedule(data);
        } catch (error) {
            if (contentEl) {
                contentEl.innerHTML = `
                    <div class="alert alert-danger mb-0" role="alert">
                        ${escapeHtml(error.message || 'No se pudieron cargar los horarios.')}
                    </div>
                `;
            }
        }
    };

    if (modalEl?.dataset.editingId) {
        setStudentEditMode(modalEl.dataset.editingId);
    }

    modalEl?.addEventListener('hidden.bs.modal', () => {
        if (openingStudentEdit) {
            return;
        }

        setStudentCreateMode();
    });

    document.addEventListener('click', (event) => {
        const scheduleBtn = event.target.closest('.js-view-student-schedule');

        if (scheduleBtn) {
            event.preventDefault();
            openSchedule(scheduleBtn);
            return;
        }

        const editBtn = event.target.closest('.js-edit-student');

        if (!editBtn || !studentModal) {
            return;
        }

        event.preventDefault();
        openingStudentEdit = true;
        fillStudentForm(editBtn);
        setStudentEditMode(editBtn.dataset.id);
        studentModal.show();
        openingStudentEdit = false;
    });
};

const initInstructorAdmin = () => {
    const fieldNames = ['name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'zip', 'country'];
    const docFields = [
        { name: 'photo', urlKey: 'photoUrl', label: 'Fotografía actual' },
        { name: 'dni_front', urlKey: 'dniFrontUrl', label: 'DNI frente actual' },
        { name: 'dni_back', urlKey: 'dniBackUrl', label: 'DNI reverso actual' },
        { name: 'address_proof', urlKey: 'addressProofUrl', label: 'Comprobante actual' },
    ];
    const modalEl = document.getElementById('addInstructorModal');
    const form = document.getElementById('instructorAdminForm');
    const instructorModal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
    let openingInstructorEdit = false;

    const setFieldValue = (name, value) => {
        const field = form?.querySelector(`[name="${name}"]`);

        if (field) {
            field.value = value ?? '';
        }
    };

    const docWrapper = (name) => form?.querySelector(`[name="${name}"]`)?.closest('[data-doc-upload]');

    const clearInstructorInvalid = () => {
        form?.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        document.getElementById('instructorFormErrorAlert')?.classList.add('d-none');
    };

    const resetInstructorDocs = (required) => {
        docFields.forEach(({ name }) => {
            docWrapper(name)?.resetDocUpload?.(required);
        });
    };

    const showInstructorDocs = (button) => {
        docFields.forEach(({ name, urlKey, label }) => {
            const wrapper = docWrapper(name);
            const url = button?.dataset?.[urlKey] || '';

            if (!wrapper) {
                return;
            }

            wrapper.resetDocUpload?.(false);

            if (url) {
                wrapper.showExistingDoc?.(url, label);
            }
        });
    };

    const setInstructorCreateMode = ({ clearFields = true } = {}) => {
        if (!form || !modalEl) {
            return;
        }

        form.action = modalEl.dataset.storeUrl;
        const methodInput = document.getElementById('instructorFormSpoofMethod');
        if (methodInput) {
            methodInput.disabled = true;
        }

        setFieldValue('_form', 'instructor');
        setFieldValue('editing_id', '');

        const title = document.getElementById('instructorFormTitle');
        const icon = document.getElementById('instructorFormIcon');
        const hint = document.getElementById('instructorFormHint');
        const submitLabel = document.getElementById('instructorFormSubmitLabel');

        if (title) {
            title.textContent = 'Agregar instructor';
        }

        if (icon) {
            icon.className = 'bi bi-person-badge';
        }

        hint?.classList.add('d-none');

        if (submitLabel) {
            submitLabel.textContent = 'Guardar instructor';
        }

        resetInstructorDocs(true);

        if (clearFields) {
            fieldNames.forEach((name) => setFieldValue(name, name === 'country' ? 'México' : ''));
            clearInstructorInvalid();
        }
    };

    const setInstructorEditMode = (instructorId) => {
        if (!form || !modalEl || !instructorId) {
            return;
        }

        form.action = `${modalEl.dataset.updateBase}/${instructorId}`;
        const methodInput = document.getElementById('instructorFormSpoofMethod');
        if (methodInput) {
            methodInput.disabled = false;
            methodInput.value = 'PUT';
        }

        setFieldValue('_form', 'instructor-edit');
        setFieldValue('editing_id', instructorId);

        const title = document.getElementById('instructorFormTitle');
        const icon = document.getElementById('instructorFormIcon');
        const hint = document.getElementById('instructorFormHint');
        const submitLabel = document.getElementById('instructorFormSubmitLabel');

        if (title) {
            title.textContent = 'Editar instructor';
        }

        if (icon) {
            icon.className = 'bi bi-pencil';
        }

        hint?.classList.remove('d-none');

        if (submitLabel) {
            submitLabel.textContent = 'Guardar cambios';
        }
    };

    const fillInstructorForm = (button) => {
        setFieldValue('name', button.dataset.name || '');
        setFieldValue('last_name', button.dataset.lastName || '');
        setFieldValue('email', button.dataset.email || '');
        setFieldValue('phone', button.dataset.phone || '');
        setFieldValue('address', button.dataset.address || '');
        setFieldValue('city', button.dataset.city || '');
        setFieldValue('state', button.dataset.state || '');
        setFieldValue('zip', button.dataset.zip || '');
        setFieldValue('country', button.dataset.country || 'México');
        clearInstructorInvalid();
        showInstructorDocs(button);
    };

    if (modalEl?.dataset.editingId) {
        setInstructorEditMode(modalEl.dataset.editingId);
        const button = document.querySelector(`.js-edit-instructor[data-id="${modalEl.dataset.editingId}"]`);
        if (button) {
            showInstructorDocs(button);
        } else {
            resetInstructorDocs(false);
        }
    }

    modalEl?.addEventListener('hidden.bs.modal', () => {
        if (openingInstructorEdit) {
            return;
        }

        setInstructorCreateMode();
    });

    document.addEventListener('click', (event) => {
        const editBtn = event.target.closest('.js-edit-instructor');

        if (!editBtn || !instructorModal) {
            return;
        }

        event.preventDefault();
        openingInstructorEdit = true;
        fillInstructorForm(editBtn);
        setInstructorEditMode(editBtn.dataset.id);
        instructorModal.show();
        openingInstructorEdit = false;
    });
};

const initVehicleAdmin = () => {
    const fieldNames = ['modelo', 'año', 'color', 'plate', 'type', 'status', 'owner', 'owner_id'];
    const modalEl = document.getElementById('addVehicleModal');
    const form = document.getElementById('vehicleAdminForm');
    const vehicleModal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
    let openingVehicleEdit = false;

    const setFieldValue = (name, value) => {
        const field = form?.querySelector(`[name="${name}"]`);

        if (field) {
            field.value = value ?? '';
        }
    };

    const clearVehicleInvalid = () => {
        form?.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        document.getElementById('vehicleFormErrorAlert')?.classList.add('d-none');
    };

    const setVehicleCreateMode = ({ clearFields = true } = {}) => {
        if (!form || !modalEl) {
            return;
        }

        form.action = modalEl.dataset.storeUrl;
        const methodInput = document.getElementById('vehicleFormSpoofMethod');
        if (methodInput) {
            methodInput.disabled = true;
        }

        setFieldValue('_form', 'vehicle');
        setFieldValue('editing_id', '');

        const title = document.getElementById('vehicleFormTitle');
        const icon = document.getElementById('vehicleFormIcon');
        const submitLabel = document.getElementById('vehicleFormSubmitLabel');

        if (title) {
            title.textContent = 'Agregar vehículo';
        }

        if (icon) {
            icon.className = 'bi bi-car-front';
        }

        if (submitLabel) {
            submitLabel.textContent = 'Guardar vehículo';
        }

        if (clearFields) {
            fieldNames.forEach((name) => {
                if (name === 'owner') {
                    setFieldValue(name, 'Autoescuela MaxPi');
                    return;
                }

                if (name === 'owner_id') {
                    setFieldValue(name, 'MAXPI-001');
                    return;
                }

                if (name === 'status') {
                    setFieldValue(name, 'disponible');
                    return;
                }

                setFieldValue(name, '');
            });
            clearVehicleInvalid();
        }
    };

    const setVehicleEditMode = (vehicleId) => {
        if (!form || !modalEl || !vehicleId) {
            return;
        }

        form.action = `${modalEl.dataset.updateBase}/${vehicleId}`;
        const methodInput = document.getElementById('vehicleFormSpoofMethod');
        if (methodInput) {
            methodInput.disabled = false;
            methodInput.value = 'PUT';
        }

        setFieldValue('_form', 'vehicle-edit');
        setFieldValue('editing_id', vehicleId);

        const title = document.getElementById('vehicleFormTitle');
        const icon = document.getElementById('vehicleFormIcon');
        const submitLabel = document.getElementById('vehicleFormSubmitLabel');

        if (title) {
            title.textContent = 'Editar vehículo';
        }

        if (icon) {
            icon.className = 'bi bi-pencil';
        }

        if (submitLabel) {
            submitLabel.textContent = 'Guardar cambios';
        }
    };

    const fillVehicleForm = (button) => {
        setFieldValue('modelo', button.dataset.modelo || '');
        setFieldValue('año', button.dataset.anio || '');
        setFieldValue('color', button.dataset.color || '');
        setFieldValue('plate', button.dataset.plate || '');
        setFieldValue('type', button.dataset.type || '');
        setFieldValue('status', button.dataset.status || 'disponible');
        setFieldValue('owner', button.dataset.owner || 'Autoescuela MaxPi');
        setFieldValue('owner_id', button.dataset.ownerId || 'MAXPI-001');
        clearVehicleInvalid();
    };

    if (modalEl?.dataset.editingId) {
        setVehicleEditMode(modalEl.dataset.editingId);
    }

    modalEl?.addEventListener('hidden.bs.modal', () => {
        if (openingVehicleEdit) {
            return;
        }

        setVehicleCreateMode();
    });

    document.addEventListener('click', (event) => {
        const editBtn = event.target.closest('.js-edit-vehicle');

        if (!editBtn || !vehicleModal) {
            return;
        }

        event.preventDefault();
        openingVehicleEdit = true;
        fillVehicleForm(editBtn);
        setVehicleEditMode(editBtn.dataset.id);
        vehicleModal.show();
        openingVehicleEdit = false;
    });
};

const initSoftDelete = () => {
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form.js-soft-delete');

        if (!form) {
            return;
        }

        event.preventDefault();

        const confirmed = await confirmSoftDelete({
            name: form.dataset.name || 'este registro',
            entity: form.dataset.entity || 'registro',
        });

        if (confirmed) {
            form.submit();
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    initDocumentUploads();
    initStudentAdmin();
    initInstructorAdmin();
    initVehicleAdmin();
    initSoftDelete();

    const modals = [
        { id: 'addStudentModal', form: 'student' },
        { id: 'addInstructorModal', form: 'instructor' },
        { id: 'addVehicleModal', form: 'vehicle' },
        { id: 'scheduleClassModal', form: 'reserva' },
        { id: 'assignScheduleModal', form: 'schedule' },
        { id: 'scheduleSummaryModal' },
        { id: 'studentScheduleModal' },
    ];

    const cleanupModalOverlay = () => {
        if (document.querySelector('.modal.show')) {
            return;
        }

        document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    };

    modals.forEach(({ id }) => {
        const modalEl = document.getElementById(id);

        if (!modalEl) {
            return;
        }

        modalEl.addEventListener('hidden.bs.modal', cleanupModalOverlay);

        if (modalEl.dataset.autoOpen !== 'true') {
            return;
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    initScheduleSummary();
    initAssignSchedule();
    initUppercaseInputs();
});
