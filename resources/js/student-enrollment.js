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
    const discountInput = form.querySelector('[name="discount"]');
    const planSelect = form.querySelector('[name="payment_plan"]');
    const courseSelect = form.querySelector('[name="course_id"]');
    const subtotalLabel = document.getElementById('studentPaymentSubtotalLabel');
    const totalLabel = document.getElementById('studentPaymentTotalLabel');
    const planHint = document.getElementById('studentPaymentPlanHint');
    const homeClassFee = Number(modalEl.dataset.homeClassFee || 100);

    const isHomeClass = () => String(homeSelect?.value || '0') === '1';

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
        if (!planHint) {
            return;
        }

        const parts = Number(planSelect?.value || 1);

        if (parts <= 1) {
            planHint.textContent = 'Se cobra el total en un solo pago.';
            return;
        }

        const installments = splitTotal(total, parts)
            .map((value, index) => `Pago ${index + 1}: ${money(value)}`)
            .join(' · ');

        planHint.textContent = `${parts} pagos. ${installments}`;
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
        setFieldValue('discount', '');
        setFieldValue('payment_method', '');
        setFieldValue('payment_plan', '1');
        refreshPayment();
    };

    const fillEnrollment = (button) => {
        setFieldValue('is_home_class', button.dataset.isHomeClass || '0');
        setFieldValue('discount', button.dataset.discount || '');
        setFieldValue('payment_method', button.dataset.paymentMethod || '');
        setFieldValue('payment_plan', button.dataset.paymentPlan || '1');
        refreshPayment();
    };

    homeSelect?.addEventListener('change', () => refreshPayment());
    courseSelect?.addEventListener('change', () => refreshPayment());
    discountInput?.addEventListener('input', () => refreshPayment());
    planSelect?.addEventListener('change', () => refreshPayment());

    modalEl.addEventListener('shown.bs.modal', () => {
        refreshPayment();
    });

    return {
        resetEnrollment,
        fillEnrollment,
        refreshPayment,
    };
};
