<script>
    $(function () {
        if (typeof flatpickr !== 'undefined') {
            flatpickr('.dispatch-datepicker', { dateFormat: 'd-m-Y', allowInput: true });
        }

        var csrf = $('meta[name="csrf-token"]').attr('content');
        var scope = @json($scopeKey ?? '');
        var fromDate = @json($fromRaw ?? '');
        var toDate = @json($toRaw ?? '');

        function notify(ok, msg) {
            if (!msg) {
                return;
            }
            if (ok && typeof successMessage === 'function') {
                successMessage(msg);
                return;
            }
            if (!ok && typeof errorMessage === 'function') {
                errorMessage(msg);
                return;
            }
            if (typeof showMessage === 'function') {
                showMessage(msg);
            }
        }

        function saveDue($input, value) {
            var url = $input.data('dueUrl');
            var prev = String($input.data('original') || '');
            value = String(value || '').trim();
            if (!url || !value || value === prev || $input.data('saving')) {
                return;
            }

            $input.data('saving', 1);
            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    _token: csrf,
                    scope: scope,
                    from_date: fromDate,
                    to_date: toDate,
                    due_finished_at: value
                }
            }).done(function (res) {
                notify(true, res && res.message ? res.message : '');
                window.location.reload();
            }).fail(function (xhr) {
                $input.val(prev);
                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : @json(__('message.failed'));
                notify(false, msg);
            }).always(function () {
                $input.data('saving', 0);
            });
        }

        document.querySelectorAll('.pds-kyo-shin-due-input').forEach(function (el) {
            var $el = $(el);
            $el.data('original', String($el.val() || ''));
            if (typeof flatpickr === 'undefined') {
                $el.on('change', function () {
                    saveDue($el, $el.val());
                });
                return;
            }
            flatpickr(el, {
                dateFormat: 'd-m-Y',
                allowInput: true,
                clickOpens: true,
                onChange: function (selectedDates, dateStr) {
                    if (dateStr) {
                        saveDue($el, dateStr);
                    }
                }
            });
        });

        $(document).on('click', '.pds-kyo-shin-due-edit-icon', function (e) {
            e.preventDefault();
            var input = $(this).closest('.pds-kyo-shin-due-editor').find('.pds-kyo-shin-due-input')[0];
            if (input && input._flatpickr) {
                input._flatpickr.open();
            } else if (input) {
                input.focus();
            }
        });
    });
</script>
