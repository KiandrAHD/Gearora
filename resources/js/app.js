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
    steps: ['Jadwal', 'Peserta', 'Kebutuhan', 'Ringkasan', 'Pilih gear'],
    picked: {},
    summary: { location: '', departureTime: '', startDate: '', description: '', duration: '', participants: '', experience: '', mode: '', needs: '' },

    init() {
        const startDate = this.$root.querySelector('[name="start_date"]');
        const duration = this.$root.querySelector('[name="duration_days"]');
        if (startDate && duration) {
            startDate.addEventListener('change', () => duration.setAttribute('min', '1'));
        }
        this.capture();
    },

    next() {
        const panel = this.$root.querySelector('.trip-panel:not([style*="display: none"])');
        if (panel) {
            const invalid = Array.from(panel.querySelectorAll('input, select, textarea'))
                .find((field) => !field.checkValidity());
            if (invalid) {
                invalid.reportValidity();
                invalid.focus();
                return;
            }
        }
        this.capture();
        this.step = Math.min(4, this.step + 1);
        if (this.step === 4) {
            this.capture();
        }
    },

    submitPlan(event) {
        const form = event.currentTarget;
        const fields = Array.from(form.querySelectorAll('input, select, textarea'));
        const invalid = fields.find((field) => !field.checkValidity());
        if (invalid) {
            const panel = invalid.closest('.trip-panel');
            const index = Array.from(form.querySelectorAll('.trip-panel')).indexOf(panel);
            this.step = Math.max(1, index + 1);
            this.$nextTick(() => {
                invalid.reportValidity();
                invalid.focus();
            });
            return;
        }
        form.submit();
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

        const checkedLabels = (name) => {
            const selected = Array.from(form.querySelectorAll(`input[name="${name}"]:checked`));
            return selected.map((input) => {
                const label = input.closest('label');
                return (label && label.textContent) ? label.textContent.trim().replace(/\s+/g, ' ') : input.value;
            }).filter(Boolean).join(', ');
        };

        const capitalise = (word) => (word ? word.charAt(0).toUpperCase() + word.slice(1) : '');

        const modeValue = checkedValue('mode') || value('mode');
        const modeLabels = { sewa: 'Sewa', beli: 'Beli', fleksibel: 'Fleksibel (per item)' };

        const basecamp = value('basecamp');
        const basecampOther = value('basecamp_other');
        const locationLabel = basecamp === 'lainnya' ? basecampOther : basecamp;

        this.summary = {
            location: locationLabel,
            departureTime: value('departure_time'),
            startDate: value('start_date'),
            description: value('description'),
            duration: value('duration_days'),
            participants: value('participants'),
            experience: capitalise(checkedValue('experience') || 'pemula'),
            mode: modeLabels[modeValue] || capitalise(modeValue),
            needs: checkedLabels('special_needs[]') || checkedLabels('special_needs'),
        };
    },
}));

// Guide Studio preview uses server-rendered safe Markdown; client inserts returned
// HTML from the trusted same-origin endpoint only.
Alpine.data('guideEditor', (config = {}) => ({
    previewUrl: config.previewUrl,
    uploadUrl: config.uploadUrl,
    guideId: config.guideId || null,
    content: config.initial || '',
    html: '<p class="field-hint">Ketik konten untuk melihat pratinjau.</p>',
    minutes: 1,
    loading: false,
    uploading: false,
    uploadError: '',
    requestId: 0,

    init() {
        this.loadPreview();
    },

    wrap(before, after, placeholder) {
        const input = this.$refs.content;
        const start = input.selectionStart;
        const end = input.selectionEnd;
        const selected = this.content.slice(start, end) || placeholder;

        this.content = this.content.slice(0, start) + before + selected + after + this.content.slice(end);
        this.$nextTick(() => {
            input.focus();
            input.setSelectionRange(start + before.length, start + before.length + selected.length);
            this.loadPreview();
        });
    },

    prefix(marker, placeholder) {
        const input = this.$refs.content;
        const start = input.selectionStart;
        const lineStart = this.content.lastIndexOf('\\n', Math.max(0, start - 1)) + 1;
        const selected = this.content.slice(lineStart, input.selectionEnd) || placeholder;

        this.content = this.content.slice(0, lineStart) + marker + selected + this.content.slice(input.selectionEnd);
        this.$nextTick(() => {
            input.focus();
            this.loadPreview();
        });
    },

    insert(text) {
        const input = this.$refs.content;
        const start = input.selectionStart;
        this.content = this.content.slice(0, start) + text + this.content.slice(input.selectionEnd);
        this.$nextTick(() => {
            input.focus();
            input.setSelectionRange(start + text.length, start + text.length);
            this.loadPreview();
        });
    },

    async uploadImage(event) {
        const file = event.target.files?.[0];

        if (!file) {
            return;
        }

        this.uploading = true;
        this.uploadError = '';

        try {
            const body = new FormData();
            body.append('image', file);
            body.append('alt', file.name.replace(/\.[^.]+$/, ''));

            const response = await fetch(this.uploadUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body,
                credentials: 'same-origin',
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(data.message || 'Gambar gagal diunggah.');
            }

            this.insert(`![${data.alt || 'Ilustrasi panduan'}](${data.url})`);
        } catch (error) {
            this.uploadError = String(error.message || 'Gambar gagal diunggah.');
        } finally {
            this.uploading = false;
            event.target.value = '';
        }
    },

    insertYoutubeUrl() {
        const url = window.prompt('Tempel URL YouTube (watch, youtu.be, atau shorts).');

        if (!url) {
            return;
        }

        const match = url.trim().match(/^(?:https?:\/\/)?(?:www\.|m\.)?(?:youtube\.com\/(?:watch\?(?:[^\s]*&)?v=|shorts\/|embed\/)|youtu\.be\/)([A-Za-z0-9_-]{11})(?:[?&#][^\s]*)?$/i);

        if (!match) {
            this.uploadError = 'URL YouTube tidak valid.';
            return;
        }

        this.uploadError = '';
        this.insert(`@youtube[${match[1]}]`);
    },

    async loadPreview() {
        const current = ++this.requestId;
        this.loading = true;

        try {
            const response = await fetch(this.previewUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ content: this.content, guide_id: this.guideId }),
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Pratinjau gagal dimuat.');
            }

            const data = await response.json();

            if (current === this.requestId) {
                this.html = data.html;
                this.minutes = data.minutes;
            }
        } catch (error) {
            if (current === this.requestId) {
                this.html = `<p class="field-hint">${String(error.message || 'Pratinjau tidak tersedia.')}</p>`;
            }
        } finally {
            if (current === this.requestId) {
                this.loading = false;
            }
        }
    },
}));

window.Alpine = Alpine;

Alpine.start();