import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Chart = Chart;
window.Alpine = Alpine;

const iconAliases = {
    activity: 'pulse',
    'alert-circle': 'circle-alert',
    'arrow-down': 'arrow-down-right',
    'arrow-up': 'arrow-up-right',
    'arrow-up-down': 'sort',
    'badge-alert': 'circle-alert',
    ban: 'x-circle',
    'check-circle-2': 'circle-check',
    'clock-3': 'clock',
    chrome: 'circle',
    'file-down': 'download',
    'file-spreadsheet': 'file-text',
    'folder-open': 'folder',
    'key-round': 'key',
    'layout-dashboard': 'dashboard',
    'lock-keyhole': 'lock',
    'mail-check': 'mail',
    'octagon-alert': 'circle-alert',
    'receipt-text': 'file-text',
    'scan-search': 'search',
    shield: 'shield-check',
    'shield-alert': 'shield-check',
    'sliders-horizontal': 'sliders',
    'triangle-alert': 'circle-alert',
    'upload-cloud': 'upload',
};

const iconPaths = {
    'arrow-left': ['<path d="M19 12H5"/>', '<path d="m12 19-7-7 7-7"/>'],
    'arrow-down-right': ['<path d="M7 7l10 10"/>', '<path d="M17 7v10H7"/>'],
    'arrow-up-right': ['<path d="M7 17L17 7"/>', '<path d="M7 7h10v10"/>'],
    bell: ['<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>', '<path d="M10 21h4"/>'],
    'chevron-down': ['<path d="m6 9 6 6 6-6"/>'],
    'chevron-right': ['<path d="m9 18 6-6-6-6"/>'],
    circle: ['<circle cx="12" cy="12" r="9"/>'],
    'circle-alert': ['<circle cx="12" cy="12" r="9"/>', '<path d="M12 7v6"/>', '<path d="M12 17h.01"/>'],
    'circle-check': ['<circle cx="12" cy="12" r="9"/>', '<path d="m8 12 3 3 5-6"/>'],
    clock: ['<circle cx="12" cy="12" r="9"/>', '<path d="M12 7v5l3 2"/>'],
    coins: ['<ellipse cx="12" cy="6" rx="7" ry="3"/>', '<path d="M5 6v6c0 1.7 3.1 3 7 3s7-1.3 7-3V6"/>', '<path d="M5 12v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/>'],
    'credit-card': ['<rect x="3" y="5" width="18" height="14" rx="2"/>', '<path d="M3 10h18"/>', '<path d="M7 15h3"/>'],
    dashboard: ['<rect x="3" y="3" width="7" height="8" rx="1"/>', '<rect x="14" y="3" width="7" height="5" rx="1"/>', '<rect x="14" y="12" width="7" height="9" rx="1"/>', '<rect x="3" y="15" width="7" height="6" rx="1"/>'],
    download: ['<path d="M12 3v12"/>', '<path d="m7 10 5 5 5-5"/>', '<path d="M5 21h14"/>'],
    eye: ['<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/>', '<circle cx="12" cy="12" r="3"/>'],
    'eye-off': ['<path d="M3 3l18 18"/>', '<path d="M10.6 10.6a3 3 0 0 0 3.8 3.8"/>', '<path d="M9.9 4.2A10.8 10.8 0 0 1 12 4c6 0 10 8 10 8a16.5 16.5 0 0 1-3.1 4.2"/>', '<path d="M6.1 6.1C3.5 8 2 12 2 12s4 8 10 8c1.5 0 2.9-.4 4.1-1.1"/>'],
    'file-text': ['<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>', '<path d="M14 3v6h6"/>', '<path d="M8 13h8"/>', '<path d="M8 17h5"/>'],
    folder: ['<path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>'],
    home: ['<path d="m3 11 9-8 9 8"/>', '<path d="M5 10v10h14V10"/>', '<path d="M10 20v-6h4v6"/>'],
    info: ['<circle cx="12" cy="12" r="9"/>', '<path d="M12 11v6"/>', '<path d="M12 7h.01"/>'],
    key: ['<circle cx="8" cy="15" r="4"/>', '<path d="m11 12 9-9"/>', '<path d="M16 4h4v4"/>'],
    landmark: ['<path d="M3 10h18"/>', '<path d="M5 10v8"/>', '<path d="M9 10v8"/>', '<path d="M15 10v8"/>', '<path d="M19 10v8"/>', '<path d="M4 18h16"/>', '<path d="m12 3 8 5H4z"/>'],
    'loader-circle': ['<path d="M21 12a9 9 0 1 1-6.2-8.6"/>'],
    lock: ['<rect x="5" y="10" width="14" height="11" rx="2"/>', '<path d="M8 10V7a4 4 0 0 1 8 0v3"/>'],
    'log-out': ['<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>', '<path d="M16 17l5-5-5-5"/>', '<path d="M21 12H9"/>'],
    mail: ['<rect x="3" y="5" width="18" height="14" rx="2"/>', '<path d="m3 7 9 6 9-6"/>', '<path d="m9 16 2 2 4-5"/>'],
    menu: ['<path d="M4 6h16"/>', '<path d="M4 12h16"/>', '<path d="M4 18h16"/>'],
    'message-circle': ['<path d="M21 11.5a8.5 8.5 0 0 1-12.6 7.4L3 20l1.2-5.1A8.5 8.5 0 1 1 21 11.5Z"/>'],
    moon: ['<path d="M21 13a8 8 0 1 1-10-10 6.5 6.5 0 0 0 10 10Z"/>'],
    pencil: ['<path d="M12 20h9"/>', '<path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>'],
    plus: ['<path d="M12 5v14"/>', '<path d="M5 12h14"/>'],
    'plus-circle': ['<circle cx="12" cy="12" r="9"/>', '<path d="M12 8v8"/>', '<path d="M8 12h8"/>'],
    printer: ['<path d="M6 9V3h12v6"/>', '<rect x="6" y="14" width="12" height="7"/>', '<rect x="3" y="9" width="18" height="8" rx="2"/>'],
    pulse: ['<path d="M3 12h4l3-8 4 16 3-8h4"/>'],
    'refresh-cw': ['<path d="M21 12a9 9 0 0 1-15 6.7"/>', '<path d="M3 12a9 9 0 0 1 15-6.7"/>', '<path d="M18 3v5h-5"/>', '<path d="M6 21v-5h5"/>'],
    'rotate-ccw': ['<path d="M3 12a9 9 0 1 0 3-6.7"/>', '<path d="M3 3v6h6"/>'],
    send: ['<path d="m22 2-7 20-4-9-9-4Z"/>', '<path d="M22 2 11 13"/>'],
    search: ['<circle cx="11" cy="11" r="7"/>', '<path d="m21 21-4.3-4.3"/>'],
    'shield-check': ['<path d="M12 3 20 6v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>', '<path d="m8.5 12 2.5 2.5 4.5-5"/>'],
    ship: ['<path d="M3 17h18l-2 4H5z"/>', '<path d="M7 17V7h10v10"/>', '<path d="M9 7V3h6v4"/>', '<path d="M8 11h2"/>', '<path d="M14 11h2"/>'],
    sliders: ['<path d="M4 7h10"/>', '<path d="M18 7h2"/>', '<circle cx="16" cy="7" r="2"/>', '<path d="M4 17h2"/>', '<path d="M10 17h10"/>', '<circle cx="8" cy="17" r="2"/>'],
    sort: ['<path d="m7 8 5-5 5 5"/>', '<path d="m7 16 5 5 5-5"/>'],
    sun: ['<circle cx="12" cy="12" r="4"/>', '<path d="M12 2v2"/>', '<path d="M12 20v2"/>', '<path d="m4.9 4.9 1.4 1.4"/>', '<path d="m17.7 17.7 1.4 1.4"/>', '<path d="M2 12h2"/>', '<path d="M20 12h2"/>', '<path d="m4.9 19.1 1.4-1.4"/>', '<path d="m17.7 6.3 1.4-1.4"/>'],
    'trash-2': ['<path d="M3 6h18"/>', '<path d="M8 6V4h8v2"/>', '<path d="M6 6l1 15h10l1-15"/>', '<path d="M10 11v6"/>', '<path d="M14 11v6"/>'],
    upload: ['<path d="M12 16V4"/>', '<path d="m7 9 5-5 5 5"/>', '<path d="M20 16.5A4.5 4.5 0 0 0 15.5 12H15a6 6 0 1 0-11 3.3"/>', '<path d="M16 20H8"/>'],
    user: ['<circle cx="12" cy="8" r="4"/>', '<path d="M4 21a8 8 0 0 1 16 0"/>'],
    'user-cog': ['<circle cx="10" cy="8" r="4"/>', '<path d="M3 21a7 7 0 0 1 11-5.8"/>', '<circle cx="18" cy="17" r="3"/>', '<path d="M18 13v1"/>', '<path d="M18 20v1"/>', '<path d="M14 17h1"/>', '<path d="M21 17h1"/>'],
    users: ['<path d="M16 21a6 6 0 0 0-12 0"/>', '<circle cx="10" cy="8" r="4"/>', '<path d="M22 21a5 5 0 0 0-5-5"/>', '<path d="M17 4a4 4 0 0 1 0 8"/>'],
    x: ['<path d="M18 6 6 18"/>', '<path d="m6 6 12 12"/>'],
    'x-circle': ['<circle cx="12" cy="12" r="9"/>', '<path d="m15 9-6 6"/>', '<path d="m9 9 6 6"/>'],
};

function renderLocalIcon(element) {
    const requestedName = element.getAttribute('data-lucide');

    if (!requestedName) {
        return;
    }

    const name = iconAliases[requestedName] || requestedName;

    if (element.dataset.localIconRendered === requestedName) {
        return;
    }

    const paths = iconPaths[name] || iconPaths.circle;
    element.textContent = '';
    element.insertAdjacentHTML(
        'afterbegin',
        `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-full w-full" aria-hidden="true">${paths.join('')}</svg>`,
    );
    element.dataset.localIconRendered = requestedName;
    element.style.display = element.style.display || 'inline-flex';
    element.style.alignItems = element.style.alignItems || 'center';
    element.style.justifyContent = element.style.justifyContent || 'center';
}

window.createLocalIcons = function createLocalIcons(root = document) {
    root.querySelectorAll('[data-lucide]').forEach(renderLocalIcon);
};

window.lucide = {
    createIcons: () => window.createLocalIcons(),
};

let lucideRefreshQueued = false;

window.refreshLucideIcons = function refreshLucideIcons() {
    if (lucideRefreshQueued) {
        return;
    }

    lucideRefreshQueued = true;

    queueMicrotask(() => {
        lucideRefreshQueued = false;

        if (window.lucide) {
            window.lucide.createIcons();
        }
    });
};

window.appShell = function appShell() {
    return {
        mobileMenuOpen: false,
        isDark: document.documentElement.classList.contains('dark'),
        iconObserver: null,

        init() {
            window.refreshLucideIcons();

            this.iconObserver = new MutationObserver(() => window.refreshLucideIcons());
            this.iconObserver.observe(document.body, {
                attributes: true,
                attributeFilter: ['data-lucide'],
                childList: true,
                subtree: true,
            });
        },

        toggleTheme() {
            document.documentElement.classList.toggle('dark');
            this.isDark = document.documentElement.classList.contains('dark');
            localStorage.theme = this.isDark ? 'dark' : 'light';
            document.documentElement.dataset.theme = this.isDark ? 'dark' : 'light';
            this.$dispatch('theme-changed', { dark: this.isDark });
            window.refreshLucideIcons();
        },

        trapMobileMenuFocus(event) {
            if (!this.mobileMenuOpen || event.key !== 'Tab' || !this.$refs.mobileSidebar) {
                return;
            }

            const focusables = [...this.$refs.mobileSidebar.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')]
                .filter((element) => element.offsetParent !== null);

            if (!focusables.length) {
                return;
            }

            const first = focusables[0];
            const last = focusables[focusables.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
                return;
            }

            if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },
    };
};

window.notificationCenter = function notificationCenter(config) {
    return {
        open: false,
        loading: false,
        markingAll: false,
        notifications: config.initialNotifications || [],
        unreadCount: Number(config.initialUnreadCount || 0),
        poller: null,
        pollInterval: Number(config.pollInterval || 6000),

        get displayCount() {
            return this.unreadCount > 99 ? '99+' : String(this.unreadCount);
        },

        get unreadCountLabel() {
            if (this.unreadCount === 0) {
                return 'Tout est lu';
            }

            return `${this.unreadCount} non lue${this.unreadCount > 1 ? 's' : ''}`;
        },

        init() {
            this.fetchFeed(false);
            this.poller = setInterval(() => {
                if (!document.hidden) {
                    this.fetchFeed(false);
                }
            }, this.pollInterval);

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    this.fetchFeed(false);
                }
            });

            window.addEventListener('focus', () => this.fetchFeed(false));
            this.$nextTick(() => window.refreshLucideIcons());
        },

        toggle() {
            this.open = !this.open;

            if (this.open) {
                this.fetchFeed(true);
            }
        },

        async fetchFeed(showLoading = false) {
            if (!config.feedUrl) {
                return;
            }

            this.loading = showLoading;

            try {
                const response = await window.axios.get(config.feedUrl, {
                    params: { limit: 12, _: Date.now() },
                    headers: { Accept: 'application/json' },
                });

                this.notifications = response.data.notifications || [];
                this.unreadCount = Number(response.data.unread_count || 0);
            } finally {
                this.loading = false;
                this.$nextTick(() => window.refreshLucideIcons());
            }
        },

        openNotification(notification) {
            if (!notification.is_read && this.unreadCount > 0) {
                notification.is_read = true;
                this.unreadCount -= 1;
            }

            window.location.href = notification.open_url || notification.url || config.historyUrl;
        },

        async markAllAsRead() {
            if (this.unreadCount === 0 || this.markingAll) {
                return;
            }

            this.markingAll = true;

            try {
                const response = await window.axios.patch(config.markAllReadUrl, {}, {
                    headers: { Accept: 'application/json' },
                });

                this.unreadCount = Number(response.data.unread_count || 0);
                this.notifications = this.notifications.map((notification) => ({
                    ...notification,
                    is_read: true,
                    read_at: notification.read_at || new Date().toISOString(),
                }));
            } finally {
                this.markingAll = false;
            }
        },

        iconClass(notification) {
            const color = notification.color || notification.severity || 'info';
            const classes = {
                success: 'bg-success-100 text-success-700 dark:bg-success-900/40 dark:text-success-200',
                warning: 'bg-warning-100 text-warning-700 dark:bg-warning-900/40 dark:text-warning-200',
                danger: 'bg-danger-100 text-danger-700 dark:bg-danger-900/40 dark:text-danger-200',
                critical: 'bg-danger-100 text-danger-700 dark:bg-danger-900/40 dark:text-danger-200',
                info: 'bg-info-100 text-info-700 dark:bg-info-900/40 dark:text-info-200',
            };

            return classes[color] || classes.info;
        },

        badgeClass(notification) {
            const color = notification.color || notification.severity || 'info';
            const classes = {
                success: 'bg-success-100 text-success-700 dark:bg-success-900/40 dark:text-success-200',
                warning: 'bg-warning-100 text-warning-700 dark:bg-warning-900/40 dark:text-warning-200',
                danger: 'bg-danger-100 text-danger-700 dark:bg-danger-900/40 dark:text-danger-200',
                critical: 'bg-danger-100 text-danger-700 dark:bg-danger-900/40 dark:text-danger-200',
                info: 'bg-info-100 text-info-700 dark:bg-info-900/40 dark:text-info-200',
            };

            return classes[color] || classes.info;
        },
    };
};

Alpine.start();

document.addEventListener('DOMContentLoaded', () => window.refreshLucideIcons());
