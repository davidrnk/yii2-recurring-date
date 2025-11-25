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

        // helper: days in month for given year/month (month: 1-12)
        daysInMonth(year, month) {
            return new Date(year, month, 0).getDate();
        },

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

        human(cfg, locale, translations) {
            if (!cfg || !cfg.type) return '—';

            translations = translations || {};

            switch (cfg.type) {
                case 'no_expiration':
                    return translations.no_expiration || 'No expiration';

                case 'interval': {
                    let v = cfg.value || 1;
                    let u = cfg.unit || 'days';

                    const label = (u === 'days')
                        ? (v === 1 ? (translations.day || 'day') : (translations.days || 'days'))
                        : (u === 'months')
                            ? (v === 1 ? (translations.month || 'month') : (translations.months || 'months'))
                            : (u === 'years')
                                ? (v === 1 ? (translations.year || 'year') : (translations.years || 'years'))
                                : u;

                    const every = translations.every || 'Every';
                    return `${every} ${v} ${label}.`;
                }

                case 'monthly': {
                    const template = translations.every_month_day || 'Every month, day {day}.';
                    return template.replace('{day}', cfg.day || 1);
                }

                case 'yearly': {
                    const monthsList = translations.months_list || {};
                    const m = monthsList[cfg.month || 1] || RDWEngine.monthName(cfg.month || 1, locale);
                    const template = translations.every_year_day || 'Every year, {day} of {month}.';
                    return template.replace('{day}', cfg.day || 1).replace('{month}', m);
                }

                case 'specific_date': {
                    const template = translations.on_date || 'Expires on {date}.';
                    return template.replace('{date}', cfg.date || '—');
                }

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

                    // static invalid combos that should be rejected in the UI
                    if (cfg.month === 2 && cfg.day > 29) return 'Invalid date: February has at most 29 days';
                    if ([4,6,9,11].includes(cfg.month) && cfg.day === 31) return 'Invalid date: the selected month has only 30 days';

                    return null;

                case 'specific_date':
                    if (!cfg.date) return 'Select a date';
                    return null;

                default:
                    return null;
            }
        },

        // non-fatal warnings for UI (informational). Return string or null.
        checkWarnings(cfg, locale, translations) {
            if (!cfg || !cfg.type) return null;

            // monthly: if day >= 29 warn that some months will be adjusted
            if (cfg.type === 'monthly') {
                const d = cfg.day || 1;
                if (d >= 29) {
                    return translations?.adjust_months_note || 'If the selected day does not exist in a month, the widget will follow the Adjustment setting: "Previous" clamps to the month\'s last valid day; "Next" advances to the following day.';
                }
                return null;
            }

            if (cfg.type === 'yearly') {
                const d = cfg.day || 1;
                const m = cfg.month || 1;

                if (m === 2 && d === 29) {
                    return translations?.feb29_note || 'Note: if the year is not leap the date will be adjusted to 28; in leap years 29 will be used.';
                }

                // other non-fatal cases: none
                return null;
            }

            return null;
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
            inst.sel.adjust = '.rdw-adjust';

            inst.$preview.text(RDWEngine.human(cfg, inst.locale, inst.translations));

            // set initial validation / warnings and save button state
            const err = RDWEngine.validate(cfg);
            if (err) {
                inst.$validation.text(err);
                inst.$container.find(inst.sel.saveBtn).prop('disabled', true);
            } else {
                const warn = RDWEngine.checkWarnings(cfg, inst.locale, inst.translations);
                inst.$validation.text(warn || '');
                inst.$container.find(inst.sel.saveBtn).prop('disabled', false);
            }

            // set any adjust controls (monthly/yearly) if present
            try {
                c.find(inst.sel.adjust).each(function () {
                    $(this).val(cfg.adjust || 'previous');
                });
            } catch (e) { /* ignore */ }

            // show/hide adjust wrappers based on loaded config (so initial load reflects visibility)
            try {
                if (cfg.type === 'monthly') {
                    const d = cfg.day || 1;
                    if (d >= 29 && d <= 31) {
                        c.find('.rdw-adjust-wrapper').removeClass('d-none');
                    } else {
                        c.find('.rdw-adjust-wrapper').addClass('d-none');
                    }
                } else {
                    c.find('.rdw-adjust-wrapper').addClass('d-none');
                }

                if (cfg.type === 'yearly') {
                    if ((cfg.month || 1) === 2 && (cfg.day || 1) === 29) {
                        c.find('.rdw-adjust-wrapper-yearly').removeClass('d-none');
                    } else {
                        c.find('.rdw-adjust-wrapper-yearly').addClass('d-none');
                    }
                } else {
                    c.find('.rdw-adjust-wrapper-yearly').addClass('d-none');
                }
            } catch (e) { /* ignore */ }
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
        this.translations = options.translations || {};

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
            adjust: '.rdw-adjust',
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
            // hide adjust wrappers when changing type
            inst.$container.find('.rdw-adjust-wrapper, .rdw-adjust-wrapper-yearly').addClass('d-none');

            // trigger an input event on a visible relevant control so the input-change handler
            // re-evaluates preview/validation and restores adjust visibility if applicable.
            try {
                const selList = [inst.sel.monthlyDay, inst.sel.yearlyDay, inst.sel.yearlyMonth, inst.sel.intervalValue, inst.sel.intervalUnit, inst.sel.specificDate, inst.sel.adjust].join(', ');
                const $visibleFirst = inst.$container.find(selList).filter(':visible').first();
                if ($visibleFirst && $visibleFirst.length) {
                    $visibleFirst.trigger('input');
                }
            } catch (e) { /* ignore */ }
        });

        // Changes in inputs
        inst.$container.on('input change', [
            inst.sel.intervalValue,
            inst.sel.intervalUnit,
            inst.sel.monthlyDay,
            inst.sel.yearlyDay,
            inst.sel.yearlyMonth,
            inst.sel.specificDate,
            inst.sel.adjust
        ].join(', '), function () {
            const cfg = inst.buildConfig();
            inst.$preview.text(RDWEngine.human(cfg, inst.locale, inst.translations));

            // validation: fatal errors block save; warnings inform user
            const err = RDWEngine.validate(cfg);
            if (err) {
                inst.$validation.text(err);
                inst.$container.find(inst.sel.saveBtn).prop('disabled', true);
            } else {
                const warn = RDWEngine.checkWarnings(cfg, inst.locale, inst.translations);
                inst.$validation.text(warn || '');
                inst.$container.find(inst.sel.saveBtn).prop('disabled', false);
            }

            // show/hide adjust control depending on selected type and day
            try {
                const c = inst.$container;
                // monthly: show adjust if day >= 29
                if (cfg.type === 'monthly') {
                    const d = cfg.day || 1;
                    if (d >= 29 && d <= 31) {
                        c.find('.rdw-adjust-wrapper').removeClass('d-none');
                    } else {
                        c.find('.rdw-adjust-wrapper').addClass('d-none');
                    }
                } else {
                    c.find('.rdw-adjust-wrapper').addClass('d-none');
                }

                // yearly: show adjust when month==2 and day==29
                if (cfg.type === 'yearly') {
                    if ((cfg.month || 1) === 2 && (cfg.day || 1) === 29) {
                        c.find('.rdw-adjust-wrapper-yearly').removeClass('d-none');
                    } else {
                        c.find('.rdw-adjust-wrapper-yearly').addClass('d-none');
                    }
                } else {
                    c.find('.rdw-adjust-wrapper-yearly').addClass('d-none');
                }
            } catch (e) { /* ignore */ }
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

        // include adjustment flag from visible control if present
        try {
            const visibleAdjust = c.find(this.sel.adjust + ':visible');
            cfg.adjust = visibleAdjust.length ? visibleAdjust.val() : (c.find(this.sel.adjust).val() || 'previous');
        } catch (e) {
            cfg.adjust = 'previous';
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

        const human = this.$hidden.data('human') || RDWEngine.human(cfg, this.locale, this.translations);
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


