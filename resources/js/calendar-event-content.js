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

    if (view.includes('list') || view.includes('dayGrid')) {
        return;
    }

    const props = arg.event.extendedProps ?? {};
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

    return { domNodes: [wrap] };
};
