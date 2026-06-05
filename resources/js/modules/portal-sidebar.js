export function initPortalSidebar() {
    initCupDashboardSidebar();
    initPortalShellSidebar();
}

function initPortalShellSidebar() {
    const shell = document.querySelector('.portal-shell');

    if (!shell) {
        return;
    }

    const toggle = shell.querySelector('[data-portal-sidebar-toggle]');
    const close = shell.querySelector('[data-portal-sidebar-close]');
    const overlay = shell.querySelector('[data-portal-sidebar-overlay]');
    const mobile = window.matchMedia('(max-width: 900px)');

    const setSidebar = (collapsed, persist = true) => {
        shell.classList.toggle('is-sidebar-collapsed', collapsed);
        toggle?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

        if (persist && !mobile.matches) {
            window.localStorage.setItem('portalSidebarCollapsed', collapsed ? 'true' : 'false');
        }
    };

    const syncInitialState = () => {
        const stored = window.localStorage.getItem('portalSidebarCollapsed');
        setSidebar(mobile.matches ? true : stored === 'true', false);
    };

    toggle?.addEventListener('click', () => {
        setSidebar(!shell.classList.contains('is-sidebar-collapsed'));
    });

    close?.addEventListener('click', () => setSidebar(true));
    overlay?.addEventListener('click', () => setSidebar(true, false));

    shell.querySelectorAll('.portal-nav a, .portal-sidebar-footer a').forEach((link) => {
        link.addEventListener('click', () => {
            if (mobile.matches) {
                setSidebar(true, false);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setSidebar(true, false);
        }
    });

    mobile.addEventListener?.('change', syncInitialState);
    syncInitialState();
}

function initCupDashboardSidebar() {
    const dashboard = document.querySelector('.cup-dashboard');
    const toggle = document.querySelector('[data-toggle-sidebar]');
    const overlay = document.querySelector('[data-sidebar-overlay]');

    if (!dashboard || !toggle) {
        return;
    }

    const setOpen = (open) => {
        dashboard.classList.toggle('is-sidebar-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    toggle.addEventListener('click', () => {
        setOpen(!dashboard.classList.contains('is-sidebar-open'));
    });

    overlay?.addEventListener('click', () => setOpen(false));

    dashboard.querySelectorAll('.portal-nav a, .portal-sidebar-footer a').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
    });
}
