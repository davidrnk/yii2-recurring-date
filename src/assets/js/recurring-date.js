/**
 * recurring-date.js
 *
 * Modular architecture:
 *  - RDWStore  → State, parsing, JSON, defaults
 *  - RDWEngine → Business logic, validation, humanizer
 *  - RDWUI     → DOM, events, rendering, bootstrap modal
 *  - RecurringDateInstance → Orchestrator + jQuery plugin layer
 *
 * Requires jQuery + Bootstrap 5
 */
(function ($) {
    'use strict';

    /* --------------------------------------------------------------------------
     * RDWStore – State management
     * ------------------------------------------------------------------------ */
    const RDWStore = {
        safeParse(raw) {
            if (!raw) return null;

            try {
                return JSON.parse(raw);
            } catch {
                return null;
            }
        },

        defaultConfig() {
            return { type: 'interval', value: 1, unit: 'days' };
        }
    };

    /* --------------------------------------------------------------------------
     * RDWEngine – Business rules, validations, and humanization
     * ------------------------------------------------------------------------ */
    const RDWEngine = {

        monthName(index, locale) {
            index = parseInt(index, 10) || 1;

            if (Intl?.DateTimeFormat) {
                try {
                    return new Intl.DateTimeFormat(locale, { month: 'long' })
                                   .format(new Date(2000, index - 1, 1));
                } catch { /* fallback */ }
            }

            const MAP = [
                'January','February','March','April','May','June',
                'July','August','September','October','November','December'
            ];

            return MAP[index - 1] || index;
        },

        human(cfg, locale) {
            if (!cfg || !cfg.type) return '—';

            switch (cfg.type) {
                case 'no_expiration':
                    return 'No expiration';

                case 'interval': {
                    let v = cfg.value || 1;
                    let u = cfg.unit || 'days';

                    const label = (u === 'days')
                        ? (v === 1 ? 'day' : 'days')
                        : (u === 'months')
                            ? (v === 1 ? 'month' : 'months')
                            : (u === 'years')
                                ? (v === 1 ? 'year' : 'years')
                                : u;

                    return `Every ${v} ${label}.`;
                }

                case 'monthly':
                    return `Every month, day ${cfg.day || 1}.`;

                case 'yearly': {
                    const m = RDWEngine.monthName(cfg.month || 1, locale);
                    return `Every year, ${cfg.day || 1} of ${m}.`;
                }

                case 'specific_date':
                    return `On ${cfg.date || '—'}.`;

                default:
                    return '';
            }
        },

        validate(cfg) {
            switch (cfg.type) {
                case 'interval':
                    if (!cfg.value || cfg.value < 1) return 'Interval value must be >= 1';
                    return null;

                case 'monthly':
                    if (!cfg.day || cfg.day < 1 || cfg.day > 31) return 'Invalid day of month';
                    return null;

                case 'yearly':
                    if (!cfg.day || cfg.day < 1 || cfg.day > 31) return 'Invalid day';
                    if (!cfg.month || cfg.month < 1 || cfg.month > 12) return 'Invalid month';
                    return null;

                case 'specific_date':
                    if (!cfg.date) return 'Select a date';
                    return null;

                default:
                    return null;
            }
        }
    };

    /* --------------------------------------------------------------------------
     * RDWUI – DOM handling, events, bootstrap modal
     * ------------------------------------------------------------------------ */
    const RDWUI = {

        updateSections(inst) {
            const t = inst.$type.val();
            const c = inst.$container;

            c.find(inst.sel.sectionInterval).toggleClass('d-none', t !== 'interval');
            c.find(inst.sel.sectionMonthly).toggleClass('d-none', t !== 'monthly');
            c.find(inst.sel.sectionYearly).toggleClass('d-none', t !== 'yearly');
            c.find(inst.sel.sectionSpecific).toggleClass('d-none', t !== 'specific_date');

            inst.$validation.text('');
        },

        loadIntoUI(inst, cfg) {
            inst.$type.val(cfg.type || 'interval');

            const c = inst.$container;

            c.find(inst.sel.intervalValue).val(cfg.value || 1);
            c.find(inst.sel.intervalUnit).val(cfg.unit || 'days');

            c.find(inst.sel.monthlyDay).val(cfg.day || 1);

            c.find(inst.sel.yearlyDay).val(cfg.day || 1);
            c.find(inst.sel.yearlyMonth).val(cfg.month || 1);

            c.find(inst.sel.specificDate).val(cfg.date || '');

            inst.$preview.text(RDWEngine.human(cfg, inst.locale));
        },

        showModal(inst) {
            if (!inst.bsModal) {
                try {
                    inst.bsModal = new bootstrap.Modal(inst.$modalEl.get(0));
                } catch { /* ignore */ }
            }
            inst.bsModal?.show();
        },

        hideModal(inst) {
            try { inst.bsModal?.hide(); }
            catch { /* ignore */ }
        }
    };

    /* --------------------------------------------------------------------------
     * RecurringDateInstance – Widget orchestrator
     * ------------------------------------------------------------------------ */
    function RecurringDateInstance($container, options) {
        this.$container = $container;
        this.locale = options.locale || navigator.language || 'en-US';

        this.sel = {
            btnOpen: '.rdw-open-btn',
            text: '.rdw-text',
            hidden: 'input[type=hidden].rdw-hidden',
            modal: '.rdw-modal',
            type: '.rdw-type',
            sectionInterval: '.rdw-section-interval',
            sectionMonthly: '.rdw-section-monthly',
            sectionYearly: '.rdw-section-yearly',
            sectionSpecific: '.rdw-section-specific',
            intervalValue: '.rdw-interval-value',
            intervalUnit: '.rdw-interval-unit',
            monthlyDay: '.rdw-monthly-day',
            yearlyDay: '.rdw-yearly-day',
            yearlyMonth: '.rdw-yearly-month',
            specificDate: '.rdw-specific-date',
            preview: '.rdw-preview',
            validation: '.rdw-validation',
            saveBtn: '.rdw-save'
        };

        // Core DOM nodes
        this.$hidden = $container.find(this.sel.hidden).first();
        this.$modalEl = $container.find(this.sel.modal).first();
        this.$type = $container.find(this.sel.type).first();
        this.$preview = $container.find(this.sel.preview).first();
        this.$validation = $container.find(this.sel.validation).first();
        this.$text = $container.find(this.sel.text).first();

        this.bsModal = null;

        this.init();
    }

    RecurringDateInstance.prototype.init = function () {
        const inst = this;

        // Open modal
        inst.$container.on('click', inst.sel.btnOpen, function (e) {
            e.preventDefault();
            inst.loadExisting();
            RDWUI.updateSections(inst);
            RDWUI.showModal(inst);
        });

        // Changes in type selector
        inst.$type.on('change', function () {
            RDWUI.updateSections(inst);
            inst.$preview.text('');
            inst.$validation.text('');
        });

        // Changes in inputs
        inst.$container.on('input change', [
            inst.sel.intervalValue,
            inst.sel.intervalUnit,
            inst.sel.monthlyDay,
            inst.sel.yearlyDay,
            inst.sel.yearlyMonth,
            inst.sel.specificDate
        ].join(', '), function () {
            const cfg = inst.buildConfig();
            inst.$preview.text(RDWEngine.human(cfg, inst.locale));
        });

        // Save
        inst.$container.on('click', inst.sel.saveBtn, function (e) {
            e.preventDefault();
            inst.save();
        });

        inst.loadExisting();
        RDWUI.updateSections(inst);
    };

    RecurringDateInstance.prototype.buildConfig = function () {
        const t = this.$type.val();
        const c = this.$container;

        const cfg = { type: t };

        if (t === 'interval') {
            cfg.value = parseInt(c.find(this.sel.intervalValue).val() || 1, 10);
            cfg.unit = c.find(this.sel.intervalUnit).val() || 'days';
        }

        if (t === 'monthly') {
            cfg.day = parseInt(c.find(this.sel.monthlyDay).val() || 1, 10);
        }

        if (t === 'yearly') {
            cfg.day = parseInt(c.find(this.sel.yearlyDay).val() || 1, 10);
            cfg.month = parseInt(c.find(this.sel.yearlyMonth).val() || 1, 10);
        }

        if (t === 'specific_date') {
            cfg.date = c.find(this.sel.specificDate).val() || null;
        }

        return cfg;
    };

    RecurringDateInstance.prototype.loadExisting = function () {
        const raw = this.$hidden.val();
        const cfg = RDWStore.safeParse(raw) || RDWStore.defaultConfig();

        RDWUI.loadIntoUI(this, cfg);
    };

    RecurringDateInstance.prototype.save = function () {
        const cfg = this.buildConfig();
        const err = RDWEngine.validate(cfg);

        if (err) {
            this.$validation.text(err);
            return;
        }

        const json = JSON.stringify(cfg);
        this.$hidden.val(json).trigger('change');

        const human = this.$hidden.data('human') || RDWEngine.human(cfg, this.locale);
        this.$text.val(human);

        RDWUI.hideModal(this);
    };

    /* --------------------------------------------------------------------------
     * jQuery plugin
     * ------------------------------------------------------------------------ */
    $.fn.recurringDate = function (options) {
        return this.each(function () {
            const $el = $(this);
            if (!$el.data('recurringDate')) {
                const inst = new RecurringDateInstance($el, options || {});
                $el.data('recurringDate', inst);
            }
        });
    };

    window.RecurringDate = window.RecurringDate || {};
    window.RecurringDate.parse = RDWStore.safeParse;

})(jQuery);


