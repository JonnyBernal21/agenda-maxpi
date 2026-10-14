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

let map = null;
let marker = null;
let pending = null;

const toCoord = (value) => {
    const number = Number(value);

    return Number.isFinite(number) ? number : null;
};

const text = (value) => String(value || '').trim();

const normalize = (props = {}) => {
    const lat = toCoord(props.meetingLat ?? props.meeting_lat);
    const lng = toCoord(props.meetingLng ?? props.meeting_lng);

    return {
        isHome: Boolean(props.isHomeClass ?? props.is_home),
        meetingPoint: text(props.meetingPoint ?? props.meeting_point),
        notes: text(props.notes),
        lat,
        lng,
        hasCoords: lat !== null && lng !== null,
    };
};

const setRow = (wrapId, valueId, value) => {
    const wrap = document.getElementById(wrapId);
    const valueEl = document.getElementById(valueId);

    if (!wrap || !valueEl) {
        return;
    }

    const show = Boolean(value);
    wrap.classList.toggle('d-none', !show);
    valueEl.textContent = value || '—';
};

const ensureMap = (lat, lng) => {
    const mapEl = document.getElementById('eventModalHomeMap');

    if (!mapEl) {
        return;
    }

    if (!map) {
        map = L.map(mapEl, {
            scrollWheelZoom: false,
        }).setView([lat, lng], 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        }).addTo(map);

        marker = L.marker([lat, lng]).addTo(map);
    } else {
        marker.setLatLng([lat, lng]);
        map.setView([lat, lng], 16);
    }

    window.setTimeout(() => map?.invalidateSize(), 60);
};

const applyHomeDetails = (data, { layout = false } = {}) => {
    const wrap = document.getElementById('eventModalHomeWrap');
    const mapWrap = document.getElementById('eventModalHomeMapWrap');
    const mapEmpty = document.getElementById('eventModalHomeMapEmpty');
    const mapsLink = document.getElementById('eventModalHomeMapsLink');

    if (!wrap) {
        return;
    }

    if (!data?.isHome) {
        wrap.classList.add('d-none');
        return;
    }

    wrap.classList.remove('d-none');
    setRow('eventModalHomeNotesWrap', 'eventModalHomeNotes', data.meetingPoint);
    setRow('eventModalNotesWrap', 'eventModalNotes', data.notes);

    if (data.hasCoords) {
        mapWrap?.classList.remove('d-none');
        mapEmpty?.classList.add('d-none');

        if (mapsLink) {
            mapsLink.href = `https://www.google.com/maps?q=${data.lat},${data.lng}`;
            mapsLink.classList.remove('d-none');
        }

        if (layout) {
            ensureMap(data.lat, data.lng);
        }
    } else {
        mapWrap?.classList.add('d-none');
        mapEmpty?.classList.toggle('d-none', Boolean(data.meetingPoint || data.notes));

        if (mapsLink) {
            mapsLink.classList.add('d-none');
            mapsLink.href = '#';
        }
    }
};

export const fillEventHomeDetails = (props = {}) => {
    pending = normalize(props);
    applyHomeDetails(pending, { layout: false });
};

export const bindEventHomeMap = (modalEl) => {
    modalEl?.addEventListener('shown.bs.modal', () => {
        applyHomeDetails(pending, { layout: true });
    });
};
