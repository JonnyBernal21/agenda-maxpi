import './bootstrap';
import * as bootstrap from 'bootstrap';
import './flash-alerts';

window.bootstrap = bootstrap;

const compactNav = window.matchMedia('(max-width: 1199.98px)');
const syncCompactNav = () => {
    document.documentElement.classList.toggle('nav-compact', compactNav.matches);
};

syncCompactNav();
compactNav.addEventListener('change', syncCompactNav);
