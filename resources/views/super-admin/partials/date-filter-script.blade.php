@once
@push('scripts')
<script>
(function () {
    function initSaDateFilter() {
        const form = document.getElementById('sa-date-filter');
        if (!form || form.dataset.saBound === '1') return;
        form.dataset.saBound = '1';

        const today = @json($today ?? now('Asia/Yangon')->toDateString());
        const monthStart = @json($monthStart ?? now('Asia/Yangon')->startOfMonth()->toDateString());
        const modeInputs = form.querySelectorAll('input[name="period"]');
        const panels = form.querySelectorAll('[data-sa-mode-panel]');
        const dateInput = form.querySelector('#sa-date-single');
        const fromInput = form.querySelector('#sa-date-from');
        const toInput = form.querySelector('#sa-date-to');

        function selectedMode() {
            const checked = form.querySelector('input[name="period"]:checked');
            return checked ? checked.value : 'month';
        }

        function syncFields() {
            const mode = selectedMode();
            form.dataset.saMode = mode;

            modeInputs.forEach(function (input) {
                input.closest('.sa-date-filter__mode').classList.toggle('is-active', input.checked);
            });

            panels.forEach(function (panel) {
                const show = panel.getAttribute('data-sa-mode-panel') === mode;
                panel.classList.toggle('is-visible', show);
                panel.querySelectorAll('input, select, textarea').forEach(function (el) {
                    el.disabled = !show;
                });
            });
        }

        function offsetDate(base, days) {
            const d = new Date(base + 'T00:00:00');
            d.setDate(d.getDate() + days);
            return d.toISOString().slice(0, 10);
        }

        modeInputs.forEach(function (input) {
            input.addEventListener('change', syncFields);
        });

        form.querySelectorAll('[data-sa-preset]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const preset = btn.getAttribute('data-sa-preset');
                if (preset === 'today') {
                    form.querySelector('input[name="period"][value="day"]').checked = true;
                    if (dateInput) dateInput.value = today;
                } else if (preset === 'yesterday') {
                    form.querySelector('input[name="period"][value="day"]').checked = true;
                    if (dateInput) dateInput.value = offsetDate(today, -1);
                } else if (preset === 'last7') {
                    form.querySelector('input[name="period"][value="range"]').checked = true;
                    if (fromInput) fromInput.value = offsetDate(today, -6);
                    if (toInput) toInput.value = today;
                }
                syncFields();
                form.requestSubmit();
            });
        });

        syncFields();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSaDateFilter);
    } else {
        initSaDateFilter();
    }
})();
</script>
@endpush
@endonce
