const el = (tag, className, text = '') => {
    const node = document.createElement(tag);
    node.className = className;

    if (text) {
        node.textContent = text;
    }

    return node;
};

const classKicker = (props) => {
    const number = props.classNumber;
    const cancelled = props.status === 'cancelada';

    if (cancelled) {
        return number ? `Clase ${number} · Cancelada` : 'Cancelada';
    }

    return number ? `Clase ${number}` : 'Clase';
};

const cuposLabel = (props) => {
    const instructors = Number(props.freeInstructors);
    const vehicles = Number(props.freeVehicles);
    let count = 0;

    if (instructors > 0 && vehicles > 0) {
        count = Math.min(instructors, vehicles);
    } else {
        count = Number(props.cupos) || 0;
    }

    return count === 1 ? '1 cupo' : `${count} cupos`;
};

export const calendarEventContent = (arg) => {
    const view = String(arg.view?.type || '');
    const props = arg.event.extendedProps ?? {};

    if (view.includes('list')) {
        return true;
    }

    if (view.includes('dayGrid')) {
        const wrap = el('div', 'cal-month-event');

        if (arg.timeText) {
            wrap.appendChild(el('span', 'cal-month-event__time', arg.timeText));
        }

        wrap.appendChild(el('span', 'cal-month-event__name', props.student || arg.event.title));

        if (props.isHomeClass) {
            wrap.appendChild(el('span', 'cal-month-event__home', 'Domicilio'));
        }

        return { domNodes: [wrap] };
    }

    const wrap = el('div', 'cal-slot');

    if (arg.timeText) {
        wrap.appendChild(el('div', 'cal-slot__time', arg.timeText));
    }

    if (props.isAvailable) {
        wrap.classList.add('cal-slot--free');
        wrap.appendChild(el('div', 'cal-slot__kicker', 'Disponible'));
        wrap.appendChild(el('div', 'cal-slot__name', cuposLabel(props)));

        return { domNodes: [wrap] };
    }

    const meta = el('div', 'cal-slot__meta');
    meta.appendChild(el('span', 'cal-slot__kicker', classKicker(props)));

    if (props.isHomeClass) {
        meta.appendChild(el('span', 'cal-slot__home', 'A domicilio'));
    }

    wrap.appendChild(meta);
    wrap.appendChild(el('div', 'cal-slot__name', props.student || arg.event.title));

    if (props.isHomeClass) {
        wrap.classList.add('cal-slot--home');
    } else if (String(props.notes || '').trim()) {
        wrap.classList.add('cal-slot--note');
    }

    return { domNodes: [wrap] };
};

const NOTE_ID = 'cal-home-note';

const hideHomeClassNote = () => {
    document.getElementById(NOTE_ID)?.remove();
};

const formatCoord = (value) => {
    const number = Number(value);

    return Number.isFinite(number) ? number.toFixed(5) : '';
};

const appendRow = (note, label, value) => {
    const row = el('div', 'cal-home-note__row');
    row.appendChild(el('span', 'cal-home-note__label', label));
    row.appendChild(el('span', 'cal-home-note__value', value));
    note.appendChild(row);
};

const positionNote = (note, anchor) => {
    const rect = anchor.getBoundingClientRect();
    const size = note.getBoundingClientRect();
    let left = rect.right + 10;
    let top = rect.top;

    if (left + size.width > window.innerWidth - 8) {
        left = Math.max(8, rect.left - size.width - 10);
        note.classList.add('cal-home-note--left');
    }

    if (top + size.height > window.innerHeight - 8) {
        top = Math.max(8, window.innerHeight - size.height - 8);
    }

    note.style.left = `${Math.max(8, left)}px`;
    note.style.top = `${top}px`;
};

const showHomeClassNote = (anchor, props) => {
    hideHomeClassNote();

    if (!anchor || !props?.isHomeClass) {
        return;
    }

    const note = el('div', 'cal-home-note');
    note.id = NOTE_ID;
    note.setAttribute('role', 'tooltip');
    note.appendChild(el('p', 'cal-home-note__title', 'Servicio a domicilio'));

    const meetingNotes = String(props.meetingPoint || '').trim();

    if (meetingNotes) {
        appendRow(note, 'Notas', meetingNotes);
    }

    const lat = formatCoord(props.meetingLat);
    const lng = formatCoord(props.meetingLng);

    if (lat && lng) {
        appendRow(note, 'Encuentro', `${lat}, ${lng}`);
    }

    if (!meetingNotes && !(lat && lng)) {
        note.appendChild(el('p', 'cal-home-note__empty', 'Sin notas ni punto de encuentro.'));
    }

    document.body.appendChild(note);
    positionNote(note, anchor);
};

const showGeneralNote = (anchor, props) => {
    hideHomeClassNote();

    const notes = String(props?.notes || '').trim();

    if (!anchor || !notes) {
        return;
    }

    const note = el('div', 'cal-home-note');
    note.id = NOTE_ID;
    note.setAttribute('role', 'tooltip');
    note.appendChild(el('p', 'cal-home-note__title', 'Notas'));
    appendRow(note, 'Observaciones', notes);
    document.body.appendChild(note);
    positionNote(note, anchor);
};

export const homeClassNoteHandlers = {
    eventMouseEnter(info) {
        const props = info.event.extendedProps ?? {};

        if (props.isAvailable) {
            return;
        }

        if (props.isHomeClass) {
            showHomeClassNote(info.el, props);
            return;
        }

        showGeneralNote(info.el, props);
    },
    eventMouseLeave() {
        hideHomeClassNote();
    },
};

if (typeof window !== 'undefined') {
    window.addEventListener('scroll', hideHomeClassNote, true);
    window.addEventListener('resize', hideHomeClassNote);
}
