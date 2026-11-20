import './bootstrap';

// Alpine.js powers lightweight UI interaction only (menus, dropdowns, modals).
// No SPA, no persistent runtime on the server.
import Alpine from 'alpinejs';

Alpine.data('modalDialog', (id) => ({
    open: false,
    opener: null,
    id,

    init() {
        this.$watch('open', (value) => {
            if (value) {
                this.opener = document.activeElement;
                document.body.classList.add('modal-open');
                this.$nextTick(() => this.focusFirst());
                return;
            }

            document.body.classList.remove('modal-open');

            if (this.opener instanceof HTMLElement) {
                this.opener.focus();
                this.opener = null;
            }
        });
    },

    show() {
        this.open = true;
    },

    close() {
        this.open = false;
    },

    focusFirst() {
        const panel = this.$refs.panel;

        if (!panel) {
            return;
        }

        const target = panel.querySelector('[data-autofocus]')
            ?? panel.querySelector('button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])');

        (target ?? panel).focus();
    },

    trap(event) {
        const panel = this.$refs.panel;

        if (!this.open || !panel) {
            return;
        }

        const focusable = Array.from(panel.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
        )).filter((element) => element.offsetParent !== null || element === document.activeElement);

        if (focusable.length === 0) {
            event.preventDefault();
            panel.focus();

            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        const active = document.activeElement;

        if (!panel.contains(active)) {
            event.preventDefault();
            first.focus();

            return;
        }

        if (event.shiftKey && active === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && active === last) {
            event.preventDefault();
            first.focus();
        }
    },
}));

// Public header: accessible mobile navigation drawer (menu button, Escape,
// backdrop, focus management and focus trap). No external dependency.
Alpine.data('mobileNav', () => ({
    open: false,
    lastFocus: null,

    toggle() {
        if (this.open) {
            this.close();
        } else {
            this.show();
        }
    },

    show() {
        this.lastFocus = document.activeElement;
        this.open = true;
        this.$nextTick(() => this.$refs.drawerClose?.focus() ?? this.$refs.drawer?.focus());
    },

    close() {
        this.open = false;
        const restore = this.lastFocus;
        this.$nextTick(() => {
            if (restore instanceof HTMLElement) {
                restore.focus();
            }
        });
    },

    trap(event) {
        if (!this.open) {
            return;
        }

        const panel = this.$refs.drawer;

        if (!panel) {
            return;
        }

        const focusable = Array.from(panel.querySelectorAll(
            'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])',
        )).filter((element) => element.offsetParent !== null || element === document.activeElement);

        if (focusable.length === 0) {
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        const active = document.activeElement;

        if (!panel.contains(active)) {
            event.preventDefault();
            first.focus();

            return;
        }

        if (event.shiftKey && active === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && active === last) {
            event.preventDefault();
            first.focus();
        }
    },
}));

// Trip Planner: 5-step wizard. Progress is client-side only; the server
// recomputes every recommendation and package price. No pricing in JS.
Alpine.data('tripWizard', (config = {}) => ({
    step: Number(config.step) || 1,
    steps: ['Aktivitas', 'Durasi & peserta', 'Mode & tanggal', 'Ringkasan', 'Pilih gear'],
    picked: {},
    summary: { activity: '', destination: '', duration: '', participants: '', experience: '', mode: '', dates: '' },

    init() {
        this.capture();
    },

    next() {
        this.capture();

        if (this.step < 4) {
            this.step += 1;
        }
    },

    prev() {
        if (this.step > 1) {
            this.step -= 1;
        }
    },

    capture() {
        const form = this.$root.querySelector('form.trip-form');

        if (!form) {
            return;
        }

        const value = (name) => {
            const element = form.querySelector(`[name="${name}"]`);

            return element ? String(element.value || '').trim() : '';
        };

        const checkedValue = (name) => {
            const element = form.querySelector(`[name="${name}"]:checked`);

            return element ? element.value : '';
        };

        const capitalise = (word) => (word ? word.charAt(0).toUpperCase() + word.slice(1) : '');

        const start = value('start_date');
        const end = value('end_date');

        this.summary = {
            activity: capitalise(value('activity')),
            destination: value('destination'),
            duration: value('duration_days'),
            participants: value('participants'),
            experience: capitalise(checkedValue('experience') || 'pemula'),
            mode: capitalise(value('mode')),
            dates: start && end ? `${start} → ${end}` : '',
        };
    },
}));

window.Alpine = Alpine;

Alpine.start();