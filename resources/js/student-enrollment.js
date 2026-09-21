const money = (value) =>
    new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(Number(value) || 0);

const splitTotal = (total, parts) => {
    const count = Math.max(1, Number(parts) || 1);
    const cents = Math.round((Number(total) || 0) * 100);
    const base = Math.floor(cents / count);
    const remainder = cents - base * count;

    return Array.from({ length: count }, (_, index) => (base + (index < remainder ? 1 : 0)) / 100);
};

export const bindStudentEnrollment = ({ form, modalEl, setFieldValue }) => {
    if (!form || !modalEl) {
        return {
            resetEnrollment: () => {},
            fillEnrollment: () => {},
            refreshPayment: () => {},
        };
    }

    const homeSelect = form.querySelector('[name="is_home_class"]');
    const notesInput = form.querySelector('[name="meeting_point"]');
    const notesWrap = document.getElementById('studentHomeNotesWrap');
    const discountInput = form.querySelector('[name="discount"]');
    const planSelect = form.querySelector('[name="payment_plan"]');
    const initialInput = form.querySelector('[name="payment_initial"]');
    const initialWrap = document.getElementById('studentPaymentInitialWrap');
    const courseSelect = form.querySelector('[name="course_id"]');
    const subtotalLabel = document.getElementById('studentPaymentSubtotalLabel');
    const totalLabel = document.getElementById('studentPaymentTotalLabel');
    const planHint = document.getElementById('studentPaymentPlanHint');
    const homeClassFee = Number(modalEl.dataset.homeClassFee || 100);

    const isHomeClass = () => String(homeSelect?.value || '0') === '1';

    const syncHomeNotes = () => {
        const show = isHomeClass();

        notesWrap?.classList.toggle('d-none', !show);

        if (notesInput) {
            notesInput.disabled = !show;

            if (!show) {
                notesInput.value = '';
            }
        }
    };

    const courseCost = () => {
        const selected = courseSelect?.selectedOptions?.[0];

        return Number(selected?.dataset.cost || 0);
    };

    const homeFee = () => (isHomeClass() ? homeClassFee : 0);

    const parseDiscount = (raw, subtotal) => {
        let text = String(raw || '')
            .trim()
            .toUpperCase()
            .replaceAll('$', '')
            .replaceAll(' ', '')
            .replaceAll(',', '.');

        if (!text || text === '0' || text === '0.00' || text === '%0' || text === '0%') {
            return { percent: 0, amount: 0 };
        }

        const isPercent = text.includes('%');
        const number = Number(text.replaceAll('%', ''));

        if (!Number.isFinite(number) || number < 0) {
            return { percent: 0, amount: 0 };
        }

        const safeSubtotal = Math.max(0, Number(subtotal) || 0);

        if (isPercent) {
            const percent = Math.min(100, number);
            const amount = Number(((safeSubtotal * percent) / 100).toFixed(2));

            return { percent, amount: Math.min(amount, safeSubtotal) };
        }

        const amount = Math.min(Number(number.toFixed(2)), safeSubtotal);
        const percent = safeSubtotal > 0 ? Number(((amount / safeSubtotal) * 100).toFixed(2)) : 0;

        return { percent, amount };
    };

    const updatePlanHint = (total) => {
        const parts = Number(planSelect?.value || 1);
        const showInitial = parts > 1;

        initialWrap?.classList.toggle('d-none', !showInitial);

        if (initialInput) {
            initialInput.disabled = !showInitial;
            initialInput.required = showInitial;
        }

        if (!planHint) {
            return;
        }

        if (!showInitial) {
            planHint.textContent = 'Se cobra el total en un solo pago.';
            return;
        }

        const initial = Number(initialInput?.value || 0);

        if (!Number.isFinite(initial) || initial <= 0) {
            planHint.textContent = `Escribe cuánto se abona ahora. El resto se divide en ${parts - 1} ${parts - 1 === 1 ? 'pago' : 'pagos'}.`;
            return;
        }

        if (initial > total) {
            planHint.textContent = 'El abono inicial no puede ser mayor al total.';
            return;
        }

        const remaining = Number((total - initial).toFixed(2));
        const restParts = parts - 1;

        if (remaining <= 0) {
            planHint.textContent = `Abonado ahora: ${money(initial)}. Cubre el total; no quedan pagos pendientes.`;
            return;
        }

        const installments = splitTotal(remaining, restParts)
            .map((value, index) => `Pago ${index + 2}: ${money(value)}`)
            .join(' · ');

        planHint.textContent = `Abonado ahora: ${money(initial)}. Restan ${restParts} ${restParts === 1 ? 'pago' : 'pagos'}. ${installments}`;
    };

    const refreshPayment = () => {
        const subtotal = Number((courseCost() + homeFee()).toFixed(2));
        const { amount } = parseDiscount(discountInput?.value, subtotal);
        const total = Math.max(0, Number((subtotal - amount).toFixed(2)));

        if (subtotalLabel) {
            subtotalLabel.textContent = money(subtotal);
        }

        if (totalLabel) {
            totalLabel.textContent = money(total);
        }

        updatePlanHint(total);
    };

    const resetEnrollment = () => {
        setFieldValue('is_home_class', '0');
        setFieldValue('meeting_point', '');
        setFieldValue('discount', '');
        setFieldValue('payment_method', '');
        setFieldValue('payment_plan', '1');
        setFieldValue('payment_initial', '');
        syncHomeNotes();
        refreshPayment();
    };

    const fillEnrollment = (button) => {
        setFieldValue('is_home_class', button.dataset.isHomeClass || '0');
        setFieldValue('meeting_point', button.dataset.meetingPoint || '');
        setFieldValue('discount', button.dataset.discount || '');
        setFieldValue('payment_method', button.dataset.paymentMethod || '');
        setFieldValue('payment_plan', button.dataset.paymentPlan || '1');
        setFieldValue('payment_initial', button.dataset.paymentInitial || '');
        syncHomeNotes();
        refreshPayment();
    };

    homeSelect?.addEventListener('change', () => {
        syncHomeNotes();
        refreshPayment();
    });
    courseSelect?.addEventListener('change', () => refreshPayment());
    discountInput?.addEventListener('input', () => refreshPayment());
    planSelect?.addEventListener('change', () => refreshPayment());
    initialInput?.addEventListener('input', () => refreshPayment());

    modalEl.addEventListener('shown.bs.modal', () => {
        syncHomeNotes();
        refreshPayment();
    });

    return {
        resetEnrollment,
        fillEnrollment,
        refreshPayment,
    };
};
