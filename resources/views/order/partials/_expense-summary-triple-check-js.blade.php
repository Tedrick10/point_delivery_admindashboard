<script>
    (function () {
        const cal = document.querySelector('.pds-summary-cal');
        const btn = document.querySelector('.js-summary-triple-confirm');
        const pop = document.getElementById('summaryTriplePop');
        if (!cal || !btn || !pop) return;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const i18n = {
            confirmTitle: @json(__('message.expense_summary_confirm_title')),
            confirmRange: @json(__('message.expense_summary_confirm_range')),
            confirm: @json(__('message.expense_summary_confirm')),
            confirmed: @json(__('message.expense_summary_confirmed')),
            already: @json(__('message.expense_summary_already_confirmed')),
            denied: @json(__('message.expense_summary_confirm_denied')),
            cancel: @json(__('message.cancel')),
            ok: 'OK',
        };
        const titleEl = document.getElementById('summaryTriplePopTitle');
        const textEl = document.getElementById('summaryTriplePopText');
        const iconEl = document.getElementById('summaryTriplePopIcon');
        const okBtn = document.getElementById('summaryTriplePopOk');
        const cancelBtn = document.getElementById('summaryTriplePopCancel');
        let popResolve = null;

        function selectedRange() {
            return {
                from: cal.getAttribute('data-from') || '',
                to: cal.getAttribute('data-to') || '',
            };
        }

        function dayCount(from, to) {
            if (!from || !to) return 1;
            const a = new Date(from + 'T00:00:00');
            const b = new Date(to + 'T00:00:00');
            if (Number.isNaN(a.getTime()) || Number.isNaN(b.getTime())) return 1;
            return Math.abs(Math.round((b - a) / 86400000)) + 1;
        }

        function closePop(result) {
            pop.hidden = true;
            document.body.classList.remove('pds-summary-pop-open');
            const resolve = popResolve;
            popResolve = null;
            if (typeof resolve === 'function') resolve(result);
        }

        function showPop(opts) {
            const mode = opts.mode || 'confirm';
            titleEl.textContent = opts.title || i18n.confirmTitle;
            textEl.textContent = opts.text || '';
            okBtn.textContent = opts.okLabel || (mode === 'confirm' ? i18n.confirm : i18n.ok);
            cancelBtn.hidden = mode !== 'confirm';
            pop.classList.toggle('is-error', mode === 'error');
            pop.classList.toggle('is-success', mode === 'success');
            iconEl.innerHTML = mode === 'error'
                ? '<i class="fas fa-exclamation"></i>'
                : (mode === 'success' ? '<i class="fas fa-check"></i>' : '<i class="fas fa-calendar-check"></i>');
            pop.hidden = false;
            document.body.classList.add('pds-summary-pop-open');
            okBtn.focus();

            return new Promise((resolve) => {
                popResolve = resolve;
            });
        }

        pop.querySelectorAll('.js-summary-pop-cancel').forEach((el) => {
            el.addEventListener('click', function () {
                closePop(false);
            });
        });
        okBtn.addEventListener('click', function () {
            closePop(true);
        });
        document.addEventListener('keydown', function (e) {
            if (pop.hidden) return;
            if (e.key === 'Escape') closePop(false);
            if (e.key === 'Enter') closePop(true);
        });

        function applyChecks(checks) {
            if (!checks || typeof checks !== 'object') return;
            Object.keys(checks).forEach((ymd) => {
                const cell = cal.querySelector('.pds-summary-cal__day[data-ymd="' + ymd + '"]');
                if (!cell) return;
                const dots = checks[ymd] || {};
                cell.querySelectorAll('.pds-summary-cal__dot').forEach((dot) => {
                    const key = Array.from(dot.classList)
                        .find((c) => c.indexOf('is-') === 0 && c !== 'is-on')
                        ?.replace('is-', '');
                    if (!key) return;
                    dot.classList.toggle('is-on', !!dots[key]);
                });
                const allOn = ['super_admin', 'ma_noe_noe', 'ma_phyu_sin']
                    .every((key) => !!dots[key]);
                cell.classList.toggle('is-checked', allOn);
            });
        }

        function markButtonConfirmed() {
            btn.disabled = true;
            const icon = btn.querySelector('i');
            const label = btn.querySelector('span');
            if (icon) icon.className = 'fas fa-check-circle';
            if (label) label.textContent = i18n.already;
        }

        btn.addEventListener('click', async function () {
            if (btn.disabled) return;
            const range = selectedRange();
            const count = dayCount(range.from, range.to);
            const accepted = await showPop({
                mode: 'confirm',
                title: i18n.confirmTitle,
                text: i18n.confirmRange.replace(':count', String(count)),
                okLabel: i18n.confirm,
            });
            if (!accepted) return;

            btn.disabled = true;
            try {
                const res = await fetch(cal.getAttribute('data-confirm-url'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        from_date: range.from,
                        to_date: range.to,
                    }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || i18n.denied);
                }
                applyChecks(data.checks || {});
                markButtonConfirmed();
                await showPop({
                    mode: 'success',
                    title: i18n.confirmTitle,
                    text: data.message || i18n.confirmed,
                    okLabel: i18n.ok,
                });
            } catch (err) {
                btn.disabled = false;
                await showPop({
                    mode: 'error',
                    title: i18n.confirmTitle,
                    text: err.message || i18n.denied,
                    okLabel: i18n.ok,
                });
            }
        });
    })();
</script>
