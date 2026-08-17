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

const markActiveRange = (ui, fromValue, toValue) => {
    ui.querySelectorAll('.container__predefined-ranges button').forEach((button) => {
        const start = isoDate(new Date(Number(button.dataset.start)));
        const end = isoDate(new Date(Number(button.dataset.end)));
        button.classList.toggle('is-active', start === fromValue && end === toValue);
    });
};

document.addEventListener('DOMContentLoaded', async () => {
    const form = document.getElementById('reportRangeForm');
    const display = document.getElementById('report_range');
    const fromInput = document.getElementById('report_from');
    const toInput = document.getElementById('report_to');

    if (!form || !display || !fromInput || !toInput) {
        return;
    }

    window.Litepicker = Litepicker;
    await import('litepicker/dist/plugins/ranges.js');

    const twoMonths = window.matchMedia('(min-width: 768px)').matches;

    new Litepicker({
        element: display,
        startDate: fromInput.value,
        endDate: toInput.value,
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
