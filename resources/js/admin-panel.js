import * as bootstrap from 'bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const modals = [
        { id: 'addStudentModal', form: 'student' },
        { id: 'addInstructorModal', form: 'instructor' },
        { id: 'addVehicleModal', form: 'vehicle' },
        { id: 'scheduleClassModal', form: 'reserva' },
    ];

    modals.forEach(({ id, form }) => {
        const modalEl = document.getElementById(id);

        if (!modalEl || modalEl.dataset.autoOpen !== 'true') {
            return;
        }

        new bootstrap.Modal(modalEl).show();
    });
});
