{{-- Rider salary sheet scripts; needs jQuery (+ optional iziToast) --}}
            (function () {
                var csrf = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';
                var rowUrlBase = '/hr/rider-salary/row';
                var timers = {};
                var canEdit = {{ $canEdit ? 'true' : 'false' }};
                function money(n) { return new Intl.NumberFormat().format(Math.round(Number(n) || 0)); }
                function parseNum(text) {
                    return parseFloat(String(text == null ? '' : text).replace(/,/g, '')) || 0;
                }

                function paintNetPay($el, value) {
                    var n = Number(value) || 0;
                    $el.text(money(n))
                        .toggleClass('pds-hr-cell--danger', n < 0)
                        .toggleClass('pds-hr-cell--success', n >= 0);
                }

                function refreshFooter() {
                    var $rows = $('#rider-salary-table tbody tr[data-row-id]');
                    if (!$rows.length) return;
                    var totals = {
                        way_count: 0,
                        total_salary: 0,
                        late_minute_amount: 0,
                        fine_amount: 0,
                        bag_deduction: 0,
                        deposit: 0,
                        total_deduction: 0,
                        net_pay: 0
                    };
                    $rows.each(function () {
                        var $tr = $(this);
                        totals.way_count += parseNum($tr.find('.js-way-count').text());
                        totals.total_salary += parseNum($tr.find('.js-total-salary').text());
                        totals.late_minute_amount += parseNum($tr.find('.js-late-minute-amount').text());
                        totals.fine_amount += parseNum($tr.find('.js-fine-amount').text());
                        totals.bag_deduction += parseNum($tr.find('.js-bag-deduction').text());
                        var $dep = $tr.find('[data-field="deposit"]');
                        totals.deposit += $dep.length
                            ? parseNum($dep.val())
                            : parseNum($tr.find('.js-deposit').text());
                        totals.total_deduction += parseNum($tr.find('.js-total-deduction').text());
                        totals.net_pay += parseNum($tr.find('.js-net-pay').text());
                    });
                    Object.keys(totals).forEach(function (key) {
                        if (key === 'net_pay') {
                            paintNetPay($('#rider-salary-table .js-foot-net_pay'), totals.net_pay);
                            return;
                        }
                        $('#rider-salary-table .js-foot-' + key).text(money(totals[key]));
                    });
                }

                function toastError(msg) {
                    if (window.iziToast) {
                        iziToast.error({title: 'Error', message: msg || 'Save failed', position: 'topRight'});
                    }
                }

                function fieldValue($input) {
                    var val = $input.val();
                    if (val !== '') {
                        return val;
                    }
                    return $input.is('[type="number"]') || $input.attr('data-pds-number') === '1' ? '0' : '';
                }

                function saveRow($tr, immediate) {
                    var id = $tr.data('row-id');
                    if (!id) return;

                    clearTimeout(timers[id]);
                    var run = function () {
                        var payload = {_token: csrf, _method: 'PUT'};
                        $tr.find('.sal-input').each(function () {
                            payload[$(this).data('field')] = fieldValue($(this));
                        });
                        $.ajax({
                            url: rowUrlBase + '/' + id,
                            method: 'POST',
                            data: payload,
                            headers: {'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest'},
                            success: function (res) {
                                if (!res.success) {
                                    toastError(res.message);
                                    return;
                                }
                                var d = res.data;
                                $tr.find('.js-total-salary').text(money(d.total_salary));
                                $tr.find('.js-total-deduction').text(money(d.total_deduction));
                                paintNetPay($tr.find('.js-net-pay'), d.net_pay);
                                refreshFooter();
                            },
                            error: function (xhr) {
                                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                    ? xhr.responseJSON.message
                                    : 'Save failed (' + xhr.status + ')';
                                toastError(msg);
                            }
                        });
                    };

                    if (immediate) {
                        run();
                    } else {
                        timers[id] = setTimeout(run, 400);
                    }
                }

                var canSaveDeposit = {{ !empty($canEditDeposit) ? 'true' : 'false' }};
                if (canEdit || canSaveDeposit) {
                    $('#rider-salary-table').on('input', '.sal-input', function () {
                        saveRow($(this).closest('tr'), false);
                    }).on('change blur', '.sal-input', function () {
                        saveRow($(this).closest('tr'), true);
                    });
                }
            })();
        </script>
