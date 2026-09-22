import L from 'leaflet';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

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
    const homeFeeInput = form.querySelector('[name="home_fee"]');
    const notesInput = form.querySelector('[name="meeting_point"]');
    const latInput = form.querySelector('[name="meeting_lat"]');
    const lngInput = form.querySelector('[name="meeting_lng"]');
    const homeWrap = document.getElementById('studentHomeClassWrap');
    const mapEl = document.getElementById('studentMeetingMap');
    const searchInput = document.getElementById('student_meeting_search');
    const searchBtn = document.getElementById('studentMeetingSearchBtn');
    const locateBtn = document.getElementById('studentMeetingLocateBtn');
    const hintEl = document.getElementById('studentMeetingHint');
    const homeFeeHint = document.getElementById('studentHomeFeeHint');
    const googleLink = document.getElementById('studentMeetingGoogleLink');
    const discountInput = form.querySelector('[name="discount"]');
    const planSelect = form.querySelector('[name="payment_plan"]');
    const initialInput = form.querySelector('[name="payment_initial"]');
    const initialWrap = document.getElementById('studentPaymentInitialWrap');
    const courseSelect = form.querySelector('[name="course_id"]');
    const subtotalLabel = document.getElementById('studentPaymentSubtotalLabel');
    const totalLabel = document.getElementById('studentPaymentTotalLabel');
    const planHint = document.getElementById('studentPaymentPlanHint');
    const defaultLat = Number(modalEl.dataset.mapLat || 19.4326);
    const defaultLng = Number(modalEl.dataset.mapLng || -99.1332);
    const geocodeUrl = modalEl.dataset.geocodeUrl;
    const reverseUrl = modalEl.dataset.reverseUrl;

    let map = null;
    let marker = null;
    let schoolCentered = false;
    let didAutoLocate = false;
    let locating = false;

    const isHomeClass = () => String(homeSelect?.value || '0') === '1';

    const parsedCoords = () => {
        const lat = Number(latInput?.value);
        const lng = Number(lngInput?.value);

        if (!Number.isFinite(lat) || !Number.isFinite(lng) || latInput?.value === '' || lngInput?.value === '') {
            return null;
        }

        return { lat, lng };
    };

    const updateGoogleLink = (lat, lng) => {
        if (!googleLink) {
            return;
        }

        googleLink.href = `https://www.google.com/maps?q=${lat},${lng}`;
        googleLink.classList.remove('d-none');
    };

    const setHint = (text) => {
        if (hintEl) {
            hintEl.textContent = text;
        }
    };

    const setCoords = (lat, lng, { pan = true, hint } = {}) => {
        if (latInput) {
            latInput.value = Number(lat).toFixed(7);
        }

        if (lngInput) {
            lngInput.value = Number(lng).toFixed(7);
        }

        updateGoogleLink(lat, lng);

        if (hint) {
            setHint(hint);
        }

        if (!map) {
            return;
        }

        const point = [lat, lng];

        if (!marker) {
            marker = L.marker(point, { draggable: true }).addTo(map);
            marker.on('dragend', () => {
                const position = marker.getLatLng();
                setCoords(position.lat, position.lng, { pan: false });
                reverseGeocode(position.lat, position.lng);
            });
        } else {
            marker.setLatLng(point);
        }

        if (pan) {
            map.setView(point, Math.max(map.getZoom() || 12, 16));
        }
    };

    const clearCoords = () => {
        if (latInput) {
            latInput.value = '';
        }

        if (lngInput) {
            lngInput.value = '';
        }

        if (searchInput) {
            searchInput.value = '';
        }

        googleLink?.classList.add('d-none');
        setHint('Usamos tu ubicación actual. También puedes buscar, hacer clic o arrastrar el pin.');

        if (marker && map) {
            map.removeLayer(marker);
            marker = null;
        }
    };

    const fetchJson = async (url) => {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            return null;
        }

        return response.json();
    };

    const searchGeocode = async (query) => {
        if (!geocodeUrl || !query || query.trim().length < 3) {
            return [];
        }

        const data = await fetchJson(`${geocodeUrl}?q=${encodeURIComponent(query.trim())}`);

        return Array.isArray(data?.results) ? data.results : [];
    };

    const reverseGeocode = async (lat, lng) => {
        if (!reverseUrl) {
            setHint('Punto seleccionado en el mapa.');
            return;
        }

        const data = await fetchJson(`${reverseUrl}?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`);
        setHint(data?.label || 'Punto seleccionado en el mapa.');
    };

    const locateErrorMessage = (error) => {
        if (!navigator.geolocation) {
            return 'Este navegador no permite leer la ubicación.';
        }

        if (error?.code === 1) {
            return 'Activa el permiso de ubicación del navegador para usar tu posición.';
        }

        if (error?.code === 3) {
            return 'No se pudo obtener la ubicación a tiempo. Busca o haz clic en el mapa.';
        }

        return 'No se pudo obtener tu ubicación. Busca o haz clic en el mapa.';
    };

    const centerOnSchool = () => {
        if (schoolCentered || !modalEl.dataset.mapQuery || !map) {
            return;
        }

        schoolCentered = true;
        searchGeocode(modalEl.dataset.mapQuery).then((results) => {
            const first = results[0];

            if (first && !parsedCoords()) {
                map.setView([first.lat, first.lng], 13);
            }
        });
    };

    const locateCurrentPosition = ({ fallback = true } = {}) => {
        if (locating) {
            return;
        }

        if (!navigator.geolocation) {
            setHint(locateErrorMessage());

            if (fallback) {
                centerOnSchool();
            }

            return;
        }

        locating = true;
        setHint('Obteniendo tu ubicación actual...');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                locating = false;
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                setCoords(lat, lng);
                reverseGeocode(lat, lng);
            },
            (error) => {
                locating = false;
                setHint(locateErrorMessage(error));

                if (fallback && !parsedCoords()) {
                    centerOnSchool();
                }
            },
            {
                enableHighAccuracy: true,
                timeout: 8000,
                maximumAge: 15000,
            },
        );
    };

    const ensureMap = () => {
        if (!mapEl) {
            return;
        }

        if (!map) {
            map = L.map(mapEl, { scrollWheelZoom: true }).setView([defaultLat, defaultLng], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);
            map.on('click', (event) => {
                setCoords(event.latlng.lat, event.latlng.lng);
                reverseGeocode(event.latlng.lat, event.latlng.lng);
            });
        }

        const current = parsedCoords();

        if (current) {
            setCoords(current.lat, current.lng, { pan: true, hint: hintEl?.textContent });
        } else if (!didAutoLocate) {
            didAutoLocate = true;
            locateCurrentPosition({ fallback: true });
        }

        window.setTimeout(() => map.invalidateSize(), 220);
    };

    const runSearch = async () => {
        const query = searchInput?.value || '';

        if (query.trim().length < 3) {
            setHint('Escribe al menos 3 letras para buscar.');
            return;
        }

        setHint('Buscando dirección...');
        const results = await searchGeocode(query);
        const first = results[0];

        if (!first) {
            setHint('No se encontró esa dirección. Prueba con otra búsqueda o haz clic en el mapa.');
            return;
        }

        setCoords(first.lat, first.lng, { pan: true, hint: first.label });
    };

    const syncHomeNotes = () => {
        const show = isHomeClass();

        homeWrap?.classList.toggle('d-none', !show);

        if (homeFeeInput) {
            homeFeeInput.disabled = !show;

            if (!show) {
                homeFeeInput.value = '';
            }
        }

        if (notesInput) {
            notesInput.disabled = !show;

            if (!show) {
                notesInput.value = '';
            }
        }

        if (latInput) {
            latInput.disabled = !show;
        }

        if (lngInput) {
            lngInput.disabled = !show;
        }

        if (show) {
            ensureMap();
            return;
        }

        clearCoords();
    };

    const courseCost = () => {
        const selected = courseSelect?.selectedOptions?.[0];

        return Number(selected?.dataset.cost || 0);
    };

    const parseSurcharge = (raw, base) => {
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

        const safeBase = Math.max(0, Number(base) || 0);

        if (isPercent) {
            const percent = Math.min(100, number);
            const amount = Number(((safeBase * percent) / 100).toFixed(2));

            return { percent, amount };
        }

        const amount = Number(number.toFixed(2));
        const percent = safeBase > 0 ? Number(((amount / safeBase) * 100).toFixed(2)) : 0;

        return { percent, amount };
    };

    const homeFee = () => (isHomeClass() ? parseSurcharge(homeFeeInput?.value, courseCost()).amount : 0);

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

        if (homeFeeHint) {
            const fee = homeFee();

            if (!isHomeClass()) {
                homeFeeHint.textContent = 'Escribe un monto para sumarlo al total, o un porcentaje (ej. %10) para aplicarlo sobre el costo del curso.';
            } else if (fee > 0) {
                homeFeeHint.textContent = `Se suman ${money(fee)} al costo del curso.`;
            } else {
                homeFeeHint.textContent = 'Escribe un monto para sumarlo al total, o un porcentaje (ej. %10) para aplicarlo sobre el costo del curso.';
            }
        }

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
        setFieldValue('home_fee', '');
        setFieldValue('meeting_point', '');
        setFieldValue('meeting_lat', '');
        setFieldValue('meeting_lng', '');
        setFieldValue('general_notes', '');
        setFieldValue('discount', '');
        setFieldValue('payment_method', '');
        setFieldValue('payment_plan', '1');
        setFieldValue('payment_initial', '');
        schoolCentered = false;
        didAutoLocate = false;
        syncHomeNotes();
        refreshPayment();
    };

    const fillEnrollment = (button) => {
        setFieldValue('is_home_class', button.dataset.isHomeClass || '0');
        setFieldValue('meeting_point', button.dataset.meetingPoint || '');
        setFieldValue('meeting_lat', button.dataset.meetingLat || '');
        setFieldValue('meeting_lng', button.dataset.meetingLng || '');
        setFieldValue('general_notes', button.dataset.generalNotes || '');
        setFieldValue('discount', button.dataset.discount || '');
        setFieldValue('payment_method', button.dataset.paymentMethod || '');
        setFieldValue('payment_plan', button.dataset.paymentPlan || '1');
        setFieldValue('payment_initial', button.dataset.paymentInitial || '');
        schoolCentered = Boolean(button.dataset.meetingLat && button.dataset.meetingLng);
        didAutoLocate = schoolCentered;
        syncHomeNotes();
        setFieldValue('home_fee', button.dataset.homeFee || '');
        refreshPayment();
    };

    homeSelect?.addEventListener('change', () => {
        syncHomeNotes();
        refreshPayment();
    });
    courseSelect?.addEventListener('change', () => refreshPayment());
    homeFeeInput?.addEventListener('input', () => refreshPayment());
    discountInput?.addEventListener('input', () => refreshPayment());
    planSelect?.addEventListener('change', () => refreshPayment());
    initialInput?.addEventListener('input', () => refreshPayment());
    searchBtn?.addEventListener('click', () => {
        runSearch();
    });
    locateBtn?.addEventListener('click', () => {
        locateCurrentPosition({ fallback: false });
    });
    searchInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            runSearch();
        }
    });

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
