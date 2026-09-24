<script>
    (function () {
        var kyoShinActionUrl = @json($kyoShinActionUrl ?? route('order.dispatch.give-kyo-shin'));
        var kyoShinOrderId = @json((int) ($kyoShinOrderId ?? 0));
        var countLabel = {!! json_encode(__('message.kyo_shin_selected_count', ['count' => '__COUNT__'])) !!};
        var photosCountLabel = {!! json_encode(__('message.kyo_shin_photos_count', ['count' => '__COUNT__'])) !!};
        var uploadHintDefault = @json(__('message.kyo_shin_upload_hint'));
        var kpayFiles = [];
        var cashFiles = [];

        function selectedItemIds() {
            return selectedGiveRows().map(function (row) { return row.id; }).filter(Boolean);
        }

        function selectedGiveRows() {
            var rows = [];
            $('.pds-dispatch-item-check:checked').each(function () {
                var $el = $(this);
                var id = parseInt($el.val(), 10);
                if (!id) {
                    return;
                }
                rows.push({
                    id: id,
                    amount: parseFloat($el.attr('data-item-value') || 0) || 0,
                    code: String($el.attr('data-item-code') || '').trim()
                });
            });
            return rows;
        }

        function formatKs(value) {
            return Math.round(Number(value) || 0).toLocaleString('en-US');
        }

        function updateGiveAmountUi() {
            var rows = selectedGiveRows();
            var total = rows.reduce(function (sum, row) { return sum + row.amount; }, 0);
            $('#kyoShinGiveAmountTotal').text(formatKs(total));
            var $list = $('#kyoShinGiveAmountList').empty();
            if (rows.length > 1) {
                rows.forEach(function (row) {
                    $list.append(
                        $('<li></li>')
                            .append($('<span></span>').text(row.code || ('#' + row.id)))
                            .append($('<strong></strong>').text(formatKs(row.amount)))
                    );
                });
            }
        }

        function selectedMethod() {
            return String($('#kyo_shin_payment_method').val() || 'kpay');
        }

        function updateKyoShinButtonState() {
            $('#giveKyoShinBtn').prop('disabled', selectedItemIds().length === 0);
        }

        function syncMethodUi() {
            var method = selectedMethod();
            $('.pds-kyo-shin-modal__method').each(function () {
                $(this).toggleClass('is-active', $(this).data('method') === method);
            });
            $('#kyoShinKpayFields').toggle(method === 'kpay');
            $('#kyoShinCashFields').toggle(method === 'cash');
        }

        function filesFor(method) {
            return method === 'cash' ? cashFiles : kpayFiles;
        }

        function revokePreviewUrls($grid) {
            $grid.find('img').each(function () {
                var src = this.getAttribute('src') || '';
                if (src.indexOf('blob:') === 0) {
                    URL.revokeObjectURL(src);
                }
            });
        }

        function resetGiveSlips() {
            kpayFiles = [];
            cashFiles = [];
            $('#kyo_shin_kpay_slip').val('');
            $('#kyo_shin_cash_slip').val('');
            renderSlipPreviews('kpay');
            renderSlipPreviews('cash');
        }

        function resetGiveForm() {
            resetGiveSlips();
            $('#kyo_shin_payment_method').val('kpay');
            $('#kyo_shin_kpay_name').val($('#kyo_shin_kpay_name').attr('data-default') || '');
            $('#kyo_shin_kpay_no').val($('#kyo_shin_kpay_no').attr('data-default') || '');
            syncMethodUi();
        }

        function addSlipFiles(method, fileList) {
            var target = filesFor(method);
            Array.prototype.forEach.call(fileList || [], function (file) {
                if (!file || !file.type || file.type.indexOf('image/') !== 0) {
                    return;
                }
                if (target.length >= 12) {
                    return;
                }
                target.push(file);
            });
            renderSlipPreviews(method);
        }

        function removeSlipFile(method, index) {
            var target = filesFor(method);
            if (index < 0 || index >= target.length) {
                return;
            }
            target.splice(index, 1);
            renderSlipPreviews(method);
        }

        function renderSlipPreviews(method) {
            var files = filesFor(method);
            var $grid = method === 'cash' ? $('#kyoShinCashPreviews') : $('#kyoShinKpayPreviews');
            var $hint = method === 'cash' ? $('#kyoShinCashHint') : $('#kyoShinKpayHint');
            revokePreviewUrls($grid);
            $grid.empty();
            files.forEach(function (file, index) {
                var url = URL.createObjectURL(file);
                var $item = $('<div class="pds-kyo-shin-modal__preview-item"></div>');
                $item.append($('<img alt="">').attr('src', url));
                $item.append(
                    $('<button type="button" class="pds-kyo-shin-modal__preview-remove" aria-label="Remove">&times;</button>')
                        .attr('data-method', method)
                        .attr('data-index', index)
                );
                $grid.append($item);
            });
            $hint.text(files.length ? photosCountLabel.replace('__COUNT__', String(files.length)) : uploadHintDefault);
        }

        function selectedSlipFiles() {
            return filesFor(selectedMethod()).slice();
        }

        $(document).on('change', '.pds-dispatch-item-check', updateKyoShinButtonState);
        $(document).on('draw.dt', '#dataTableBuilder', updateKyoShinButtonState);
        $(document).on('click', '.pds-kyo-shin-modal__method', function () {
            $('#kyo_shin_payment_method').val($(this).data('method') || 'kpay');
            syncMethodUi();
        });
        $(document).on('click', '.pds-kyo-shin-modal__upload', function (e) {
            if ($(e.target).is('input, img, button')) {
                return;
            }
            var input = $(this).data('input');
            if (input) {
                $(input).trigger('click');
            }
        });
        $(document).on('change', '#kyo_shin_kpay_slip', function () {
            addSlipFiles('kpay', this.files);
            this.value = '';
        });
        $(document).on('change', '#kyo_shin_cash_slip', function () {
            addSlipFiles('cash', this.files);
            this.value = '';
        });
        $(document).on('click', '.pds-kyo-shin-modal__preview-remove', function (e) {
            e.preventDefault();
            e.stopPropagation();
            removeSlipFile($(this).data('method'), parseInt($(this).data('index'), 10));
        });

        $('#giveKyoShinBtn').on('click', function () {
            var itemIds = selectedItemIds();
            if (!itemIds.length) {
                if (typeof errorMessage === 'function') {
                    errorMessage('{{ __('message.select_items_to_assign') }}');
                }
                return;
            }
            resetGiveForm();
            $('#kyoShinSelectedCountLabel').text(countLabel.replace('__COUNT__', String(itemIds.length)));
            updateGiveAmountUi();
            if (typeof flatpickr === 'function' && !$('#kyo_shin_due_finished_at').data('flatpickr')) {
                flatpickr('#kyo_shin_due_finished_at', { dateFormat: 'd-m-Y', allowInput: true });
            }
            syncMethodUi();
            $('#kyoShinGiveModal').modal('show');
        });

        $('#kyoShinGiveModal').on('hidden.bs.modal', resetGiveForm);

        $('#confirmKyoShinGiveBtn').on('click', function () {
            var itemIds = selectedItemIds();
            var dueDate = String($('#kyo_shin_due_finished_at').val() || '').trim();
            var method = selectedMethod();
            var slips = selectedSlipFiles();
            var kpayName = String($('#kyo_shin_kpay_name').val() || '').trim();
            var kpayNo = String($('#kyo_shin_kpay_no').val() || '').trim();

            if (!itemIds.length) {
                return;
            }
            if (!dueDate) {
                if (typeof errorMessage === 'function') {
                    errorMessage('{{ __('message.kyo_shin_due_required') }}');
                }
                return;
            }
            if (!method) {
                if (typeof errorMessage === 'function') {
                    errorMessage('{{ __('message.kyo_shin_method_required') }}');
                }
                return;
            }
            if (method === 'kpay' && (!kpayName || !kpayNo)) {
                if (typeof errorMessage === 'function') {
                    errorMessage('{{ __('message.kpay_name') }} / {{ __('message.kpay_no') }}');
                }
                return;
            }
            if (!slips.length) {
                if (typeof errorMessage === 'function') {
                    errorMessage('{{ __('message.kyo_shin_slip_required') }}');
                }
                return;
            }
            if (itemIds.length > 100) {
                if (typeof errorMessage === 'function') {
                    errorMessage('{{ __('message.max_assign_100_items') }}');
                }
                return;
            }

            var form = new FormData();
            form.append('_token', $('meta[name="csrf-token"]').attr('content'));
            form.append('due_finished_at', dueDate);
            form.append('payment_method', method);
            form.append('kpay_name', kpayName);
            form.append('kpay_no', kpayNo);
            slips.forEach(function (file) {
                form.append('slips[]', file);
            });
            itemIds.forEach(function (id) {
                form.append('item_ids[]', id);
            });
            if (kyoShinOrderId > 0) {
                form.append('order_id', String(kyoShinOrderId));
            }

            $('#confirmKyoShinGiveBtn').prop('disabled', true);
            $('#giveKyoShinBtn').prop('disabled', true);

            $.ajax({
                url: kyoShinActionUrl,
                type: 'POST',
                data: form,
                processData: false,
                contentType: false,
                success: function (res) {
                    $('#kyoShinGiveModal').modal('hide');
                    if (res && res.message && typeof showMessage === 'function') {
                        showMessage(res.message);
                    }
                    if (typeof window.reloadDispatchItemsTable === 'function') {
                        window.reloadDispatchItemsTable();
                    } else {
                        window.location.reload();
                    }
                    $('#confirmKyoShinGiveBtn').prop('disabled', false);
                    updateKyoShinButtonState();
                },
                error: function (xhr) {
                    $('#confirmKyoShinGiveBtn').prop('disabled', false);
                    updateKyoShinButtonState();
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : '{{ __('message.something_went_wrong') }}';
                    if (typeof errorMessage === 'function') {
                        errorMessage(msg);
                    }
                }
            });
        });

        updateKyoShinButtonState();
        syncMethodUi();
    })();
</script>
