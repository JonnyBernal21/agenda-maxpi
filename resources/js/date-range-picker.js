import Litepicker from 'litepicker';
import 'litepicker/dist/css/litepicker.css';
import 'litepicker/dist/css/plugins/ranges.js.css';

const isoDate = (value) => {
    if (!value) {
        return '';
    }

    if (typeof value.format === 'function') {
        return value.format('YYYY-MM-DD');
    }

    const date = value.toJSDate ? value.toJSDate() : value;
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const parseLocalDate = (value) => {
    if (!value) {
        return null;
    }

    if (value instanceof Date) {
        return startOfDay(value);
    }

    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);

    if (!match) {
        return startOfDay(new Date(value));
    }

    return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
};

const startOfDay = (date) => {
    const next = new Date(date.getFullYear(), date.getMonth(), date.getDate());

    return next;
};

const startOfWeek = (date) => {
    const next = startOfDay(date);
    const weekday = next.getDay();
    const offset = weekday === 0 ? 6 : weekday - 1;
    next.setDate(next.getDate() - offset);

    return next;
};

const addCalendarDays = (date, days) => {
    const next = startOfDay(date);
    next.setDate(next.getDate() + days);

    return next;
};

const nextIsoWeekday = (from, isoDay) => {
    const date = startOfDay(from);
    const current = date.getDay() === 0 ? 7 : date.getDay();
    let delta = isoDay - current;

    if (delta <= 0) {
        delta += 7;
    }

    return addCalendarDays(date, delta);
};

const formatDisplayDate = (iso) => {
    const date = parseLocalDate(iso);

    if (!date) {
        return '';
    }

    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');

    return `${day}/${month}/${date.getFullYear()}`;
};

const presetRanges = () => {
    const today = startOfDay(new Date());
    const yesterday = startOfDay(today);
    yesterday.setDate(yesterday.getDate() - 1);

    const weekStart = startOfWeek(today);
    const weekEnd = startOfDay(weekStart);
    weekEnd.setDate(weekEnd.getDate() + 6);

    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
    const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
    const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
    const yearStart = new Date(today.getFullYear(), 0, 1);
    const yearEnd = new Date(today.getFullYear(), 11, 31);

    return {
        Hoy: [today, today],
        Ayer: [yesterday, yesterday],
        'Esta semana': [weekStart, weekEnd],
        'Este mes': [monthStart, monthEnd],
        'Mes anterior': [lastMonthStart, lastMonthEnd],
        'Este año': [yearStart, yearEnd],
    };
};

const presetSingleDays = (minIso = '') => {
    const min = parseLocalDate(minIso);
    const today = startOfDay(new Date());
    const allowed = (date) => !min || date.getTime() >= min.getTime();
    const days = {
        Hoy: today,
        Ayer: addCalendarDays(today, -1),
        Mañana: addCalendarDays(today, 1),
        'Próximo lunes': nextIsoWeekday(today, 1),
        'Próximo viernes': nextIsoWeekday(today, 5),
    };

    return Object.fromEntries(Object.entries(days).filter(([, date]) => allowed(date)));
};

const markActiveRange = (ui, fromValue, toValue) => {
    ui.querySelectorAll('.container__predefined-ranges button').forEach((button) => {
        const start = isoDate(new Date(Number(button.dataset.start)));
        const end = isoDate(new Date(Number(button.dataset.end)));
        button.classList.toggle('is-active', start === fromValue && end === toValue);
    });
};

export const bindDateRangePickers = async () => {
    const forms = [...document.querySelectorAll('form.js-date-range')];

    if (forms.length === 0) {
        return;
    }

    window.Litepicker = Litepicker;
    await import('litepicker/dist/plugins/ranges.js');

    const twoMonths = window.matchMedia('(min-width: 768px)').matches;

    forms.forEach((form) => {
        const display = form.querySelector('.js-date-range-display');
        const fromInput = form.querySelector('input[name="from"]');
        const toInput = form.querySelector('input[name="to"]');

        if (!display || !fromInput || !toInput) {
            return;
        }

        new Litepicker({
            element: display,
            startDate: parseLocalDate(fromInput.value),
            endDate: parseLocalDate(toInput.value),
            singleMode: false,
            autoApply: true,
            numberOfMonths: twoMonths ? 2 : 1,
            numberOfColumns: twoMonths ? 2 : 1,
            firstDay: 1,
            format: 'DD/MM/YYYY',
            delimiter: ' – ',
            lang: 'es-MX',
            maxDays: 370,
            showTooltip: true,
            plugins: ['ranges'],
            dropdowns: {
                minYear: 2020,
                maxYear: null,
                months: true,
                years: true,
            },
            tooltipText: {
                one: 'día',
                other: 'días',
            },
            ranges: {
                position: twoMonths ? 'left' : 'top',
                autoApply: true,
                force: true,
                customRanges: presetRanges(),
            },
            setup(instance) {
                instance.on('render', (ui) => {
                    markActiveRange(ui, fromInput.value, toInput.value);
                });

                instance.on('selected', (start, end) => {
                    if (!start || !end) {
                        return;
                    }

                    const nextFrom = isoDate(start);
                    const nextTo = isoDate(end);

                    if (nextFrom === fromInput.value && nextTo === toInput.value) {
                        return;
                    }

                    fromInput.value = nextFrom;
                    toInput.value = nextTo;
                    form.submit();
                });
            },
        });
    });
};

export const bindSingleDatePickers = async (root = document) => {
    const fields = [...root.querySelectorAll('.js-single-date')].filter((field) => field.dataset.pickerBound !== 'true');

    if (fields.length === 0) {
        return [];
    }

    const wide = window.matchMedia('(min-width: 768px)').matches;

    return fields.map((field) => {
        const display = field.querySelector('.js-single-date-display');
        const valueInput = field.querySelector('.js-single-date-value');

        if (!display || !valueInput) {
            return null;
        }

        field.dataset.pickerBound = 'true';

        const minDate = parseLocalDate(field.dataset.minDate || valueInput.min || '');
        const selected = parseLocalDate(valueInput.value);
        const presets = presetSingleDays(field.dataset.minDate || valueInput.min || '');
        const rangesPosition = wide ? 'left' : 'top';

        const instance = new Litepicker({
            element: display,
            startDate: selected || undefined,
            singleMode: true,
            autoApply: true,
            numberOfMonths: 1,
            numberOfColumns: 1,
            firstDay: 1,
            format: 'DD/MM/YYYY',
            lang: 'es-MX',
            minDate: minDate || undefined,
            showTooltip: false,
            zIndex: 2000,
            dropdowns: {
                minYear: 2020,
                maxYear: null,
                months: true,
                years: true,
            },
            setup(picker) {
                picker.on('render', (ui) => {
                    ui.dataset.plugins = 'ranges';
                    ui.dataset.rangesPosition = rangesPosition;
                    ui.dataset.singleDay = 'true';

                    const main = ui.querySelector('.container__main');

                    if (!main || main.querySelector('.container__predefined-ranges')) {
                        return;
                    }

                    const sidebar = document.createElement('div');
                    sidebar.className = 'container__predefined-ranges';

                    Object.entries(presets).forEach(([label, date]) => {
                        const button = document.createElement('button');
                        const iso = isoDate(date);
                        button.type = 'button';
                        button.textContent = label;
                        button.dataset.date = iso;
                        button.classList.toggle('is-active', iso === valueInput.value);
                        button.addEventListener('click', (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            picker.setDate(date);
                            picker.hide();
                        });
                        sidebar.appendChild(button);
                    });

                    main.prepend(sidebar);
                });

                picker.on('selected', (start) => {
                    if (!start) {
                        return;
                    }

                    const next = isoDate(start);
                    display.value = formatDisplayDate(next);

                    if (valueInput.value === next) {
                        return;
                    }

                    valueInput.value = next;
                    valueInput.dispatchEvent(new Event('change', { bubbles: true }));
                });
            },
        });

        if (valueInput.value) {
            display.value = formatDisplayDate(valueInput.value);
        }

        valueInput._litepicker = instance;

        return instance;
    }).filter(Boolean);
};

