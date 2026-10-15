import './bootstrap';
import * as bootstrap from 'bootstrap';
import './flash-alerts';

window.bootstrap = bootstrap;

const SIDEBAR_KEY = 'app-sidebar';
const sidebarMq = window.matchMedia('(max-width: 991.98px)');

const sidebarToggles = () => document.querySelectorAll('[data-sidebar-toggle]');

const syncToggleState = () => {
    const expanded = sidebarMq.matches
        ? document.documentElement.classList.contains('sidebar-open')
        : !document.documentElement.classList.contains('sidebar-collapsed');

    sidebarToggles().forEach((button) => {
        button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    });
};

const closeMobileSidebar = () => {
    document.documentElement.classList.remove('sidebar-open');
    syncToggleState();
};

const applySidebar = () => {
    const collapsed = window.localStorage.getItem(SIDEBAR_KEY) === 'collapsed';

    if (sidebarMq.matches) {
        document.documentElement.classList.remove('sidebar-collapsed');
        syncToggleState();
        return;
    }

    closeMobileSidebar();
    document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
    syncToggleState();
};

const toggleSidebar = () => {
    if (sidebarMq.matches) {
        document.documentElement.classList.toggle('sidebar-open');
        syncToggleState();
        return;
    }

    const collapsed = !document.documentElement.classList.contains('sidebar-collapsed');
    document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
    window.localStorage.setItem(SIDEBAR_KEY, collapsed ? 'collapsed' : 'open');
    syncToggleState();
};

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-sidebar-toggle]')) {
        toggleSidebar();
        return;
    }

    if (event.target.closest('[data-sidebar-close]')) {
        closeMobileSidebar();
        return;
    }

    if (sidebarMq.matches && event.target.closest('.app-sidebar__link')) {
        closeMobileSidebar();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeMobileSidebar();
    }
});

sidebarMq.addEventListener('change', applySidebar);
applySidebar();

if (window.isSecureContext && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}
