import {
    addCalendarDays,
    isoDate,
    parseLocalDate,
} from './date-range-picker';

const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');

const emptyMarkup = `
    <div class="day-agenda__empty">
        <i class="bi bi-calendar-x"></i>
        <p>No hay clases para este día.</p>
    </div>
`;

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const startActionMarkup = (item) => {
    if (item.can_start && item.start_url) {
        return `
            <form method="POST" action="${escapeHtml(item.start_url)}" class="day-agenda__start-form">
                <input type="hidden" name="_token" value="${escapeHtml(csrfToken())}">
                <button type="submit" class="day-agenda__start">Iniciar</button>
            </form>
        `;
    }

    if (item.can_continue && item.form_url) {
        return `<a class="day-agenda__start" href="${escapeHtml(item.form_url)}">Continuar</a>`;
    }

    if (item.can_view_evaluation && item.summary_url) {
        return `<a class="day-agenda__start day-agenda__start--ghost" href="${escapeHtml(item.summary_url)}">Ver evaluación</a>`;
    }

    return '';
};

const itemMarkup = (item) => {
    const course = item.course ? ` · ${escapeHtml(item.course)}` : '';

    return `
        <div class="day-agenda__row">
        <button type="button" class="day-agenda__item" data-agenda-item>
            <span class="day-agenda__time">${escapeHtml(item.time_label)}</span>
            <span class="day-agenda__copy">
                <strong>${escapeHtml(item.title)}</strong>
                <small>${escapeHtml(item.student)}${course}</small>
            </span>
            <span class="day-agenda__status day-agenda__status--${escapeHtml(item.live_status)}">
                ${escapeHtml(item.status_label)}
            </span>
            <i class="bi bi-chevron-right day-agenda__chevron" aria-hidden="true"></i>
        </button>
        ${startActionMarkup(item)}
        </div>
    `;
};

const dayMarkup = (day) => {
    const classes = ['day-agenda__day'];

    if (!day.in_month) {
        classes.push('is-outside');
    }

    if (day.is_today) {
        classes.push('is-today');
    }

    if (day.is_selected) {
        classes.push('is-selected');
    }

    if (day.has_class) {
        classes.push('has-class');
    }

    return `<button type="button" class="${classes.join(' ')}" data-agenda-day="${escapeHtml(day.date)}">${escapeHtml(day.day)}</button>`;
};

const bindItems = (root, classes) => {
    root.querySelectorAll('[data-agenda-item]').forEach((button, index) => {
        button.addEventListener('click', () => {
            const item = classes[index];

            if (!item) {
                return;
            }

            document.dispatchEvent(new CustomEvent('agenda:open-class', { detail: item }));
        });
    });
};

const shiftMonth = (month, amount) => {
    const [year, monthNumber] = String(month).split('-').map(Number);
    const date = new Date(year, (monthNumber || 1) - 1 + amount, 1);
    const nextYear = date.getFullYear();
    const nextMonth = String(date.getMonth() + 1).padStart(2, '0');

    return `${nextYear}-${nextMonth}`;
};

export const initDayAgenda = () => {
    const root = document.getElementById('dayAgenda');

    if (!root) {
        return;
    }

    const url = root.dataset.url;
    const list = root.querySelector('[data-agenda-list]');
    const title = root.querySelector('[data-agenda-title]');
    const subtitle = root.querySelector('[data-agenda-subtitle]');
    const dateLabel = root.querySelector('[data-agenda-date-text]');
    const monthLabel = root.querySelector('[data-agenda-month-label]');
    const daysHost = root.querySelector('[data-agenda-days]');
    let currentDate = root.dataset.date;
    let viewedMonth = root.dataset.month || currentDate.slice(0, 7);
    let loading = false;

    const renderCalendar = (calendar) => {
        if (!calendar || !daysHost) {
            return;
        }

        viewedMonth = calendar.month || viewedMonth;
        root.dataset.month = viewedMonth;

        if (monthLabel) {
            monthLabel.textContent = calendar.month_label || '';
        }

        daysHost.innerHTML = (calendar.weeks || []).flat().map(dayMarkup).join('');
    };

    const render = (payload) => {
        title.textContent = payload.title;
        subtitle.textContent = payload.subtitle;

        if (dateLabel) {
            dateLabel.textContent = payload.date_label;
        }

        root.dataset.date = payload.date;
        currentDate = payload.date;

        if (!payload.classes.length) {
            list.innerHTML = emptyMarkup;
        } else {
            list.innerHTML = payload.classes.map(itemMarkup).join('');
            bindItems(list, payload.classes);
        }

        renderCalendar(payload.calendar);
    };

    const load = async (iso, month = null) => {
        if (!url || loading) {
            return;
        }

        loading = true;
        const params = new URLSearchParams({ date: iso });

        if (month) {
            params.set('month', month);
        }

        try {
            const response = await fetch(`${url}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            render(await response.json());
        } finally {
            loading = false;
        }
    };

    bindItems(list, [...list.querySelectorAll('[data-agenda-item]')].map((button) => {
        try {
            return JSON.parse(button.getAttribute('data-agenda-item') || '{}');
        } catch {
            return null;
        }
    }).filter(Boolean));

    root.querySelectorAll('[data-agenda-prev]').forEach((button) => {
        button.addEventListener('click', () => {
            load(isoDate(addCalendarDays(parseLocalDate(currentDate), -1)));
        });
    });

    root.querySelectorAll('[data-agenda-next]').forEach((button) => {
        button.addEventListener('click', () => {
            load(isoDate(addCalendarDays(parseLocalDate(currentDate), 1)));
        });
    });

    root.querySelector('[data-agenda-date]')?.addEventListener('click', () => {
        const today = isoDate(new Date());

        if (today !== currentDate) {
            load(today);
        }
    });

    root.querySelector('[data-agenda-month-prev]')?.addEventListener('click', () => {
        load(currentDate, shiftMonth(viewedMonth, -1));
    });

    root.querySelector('[data-agenda-month-next]')?.addEventListener('click', () => {
        load(currentDate, shiftMonth(viewedMonth, 1));
    });

    daysHost?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-agenda-day]');
        const iso = button?.dataset.agendaDay;

        if (!iso || iso === currentDate) {
            return;
        }

        load(iso);
    });

    document.addEventListener('agenda:refresh', () => {
        load(currentDate, viewedMonth);
    });
};
