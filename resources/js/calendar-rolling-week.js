/**
 * Vista semanal que inicia en la fecha actual (7 días hacia adelante),
 * sin mostrar días anteriores en la semana en curso.
 */
export const rollingWeekViews = {
    rollingWeek: {
        type: 'timeGrid',
        duration: { days: 7 },
        dateIncrement: { days: 7 },
        dateAlignment: 'day',
        buttonText: 'Semana',
    },
    rollingListWeek: {
        type: 'list',
        duration: { days: 7 },
        dateIncrement: { days: 7 },
        dateAlignment: 'day',
        buttonText: 'Lista',
    },
};

export const rollingWeekToolbar = {
    left: 'prev,next today',
    center: 'title',
    right: 'rollingWeek,dayGridMonth,timeGridDay,rollingListWeek',
};
