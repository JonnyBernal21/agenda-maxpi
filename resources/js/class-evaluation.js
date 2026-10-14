const bindScoreGroup = (group) => {
    const card = group.closest('.eval-skill');
    const output = card?.querySelector('[data-eval-score]');

    const render = () => {
        const checked = group.querySelector('input:checked');
        const value = checked?.value ?? '0';

        if (output) {
            output.textContent = `${value} / 5`;
        }

        group.querySelectorAll('.eval-score-pill').forEach((pill) => {
            pill.classList.toggle('is-active', Boolean(pill.querySelector('input')?.checked));
        });
    };

    group.addEventListener('change', render);
    render();
};

const lockEvaluation = () => {
    const shell = document.querySelector('[data-eval-lock]');

    if (!shell) {
        return;
    }

    let allowLeave = false;

    history.pushState({ evalLock: true }, '', location.href);

    window.addEventListener('popstate', () => {
        history.pushState({ evalLock: true }, '', location.href);
    });

    window.addEventListener('beforeunload', (event) => {
        if (allowLeave) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    });

    document.querySelectorAll('[data-eval-leave]').forEach((element) => {
        if (element.tagName === 'A') {
            element.addEventListener('click', () => {
                allowLeave = true;
            });

            return;
        }

        element.addEventListener('submit', () => {
            allowLeave = true;
        });
    });

    document.querySelectorAll('[data-eval-cancel]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm('¿Seguro que quieres salir de la evaluación? La clase no se marcará como completada.')) {
                event.preventDefault();
                return;
            }

            allowLeave = true;
        });
    });
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.eval-score-pills').forEach(bindScoreGroup);
    lockEvaluation();
});
