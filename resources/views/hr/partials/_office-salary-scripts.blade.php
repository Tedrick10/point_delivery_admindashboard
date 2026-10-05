{{-- Office salary sheet scripts; needs jQuery (+ optional iziToast) --}}
<script>
            (function () {
                var csrf = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';
                var rowUrlBase = '/hr/office-salary/row';
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
                    var $rows = $('#office-salary-table tbody tr[data-row-id]');
                    if (!$rows.length) return;
                    var totals = {
                        monthly_salary: 0,
                        day_rate: 0,
                        rest_days: 0,
                        worked_days: 0,
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
                        totals.monthly_salary += parseNum($tr.find('.js-monthly-salary').text());
                        totals.day_rate += parseNum($tr.find('.js-day-rate').text());
                        totals.rest_days += parseNum($tr.find('.js-rest-days').text());
                        totals.worked_days += parseNum($tr.find('.js-worked-days').text());
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
                            paintNetPay($('#office-salary-table .js-foot-net_pay'), totals.net_pay);
                            return;
                        }
                        $('#office-salary-table .js-foot-' + key).text(money(totals[key]));
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
                                $tr.find('.js-day-rate').text(money(d.day_rate));
                                $tr.find('.js-worked-days').text(d.worked_days);
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
                    $('#office-salary-table').on('input', '.sal-input', function () {
                        saveRow($(this).closest('tr'), false);
                    }).on('change blur', '.sal-input', function () {
                        saveRow($(this).closest('tr'), true);
                    });
                }

                (function bindRestDatePopover() {
                    var $pop = $('#pds-hr-rest-popover');
                    var $list = $pop.find('.js-rest-pop-list');
                    var $empty = $pop.find('.js-rest-pop-empty');
                    var $name = $pop.find('.js-rest-pop-name');
                    var $activeBtn = null;

                    function closePop() {
                        $pop.attr('hidden', true);
                        if ($activeBtn) {
                            $activeBtn.removeClass('is-open');
                            $activeBtn = null;
                        }
                    }

                    function openPop($btn) {
                        var dates = [];
                        try {
                            dates = JSON.parse($btn.attr('data-dates') || '[]') || [];
                        } catch (e) {
                            dates = [];
                        }
                        $name.text($btn.attr('data-name') || '');
                        $list.empty();
                        if (!dates.length) {
                            $list.attr('hidden', true);
                            $empty.removeAttr('hidden');
                        } else {
                            $empty.attr('hidden', true);
                            $list.removeAttr('hidden');
                            dates.forEach(function (item, idx) {
                                $list.append(
                                    $('<li/>')
                                        .append($('<strong/>').text(item.label || item.short || item.iso || ''))
                                        .append($('<span/>').text('#' + (idx + 1)))
                                );
                            });
                        }

                        $('.pds-hr-rest-btn').removeClass('is-open');
                        $btn.addClass('is-open');
                        $activeBtn = $btn;
                        $pop.removeAttr('hidden');

                        var rect = $btn[0].getBoundingClientRect();
                        var popW = $pop.outerWidth() || 240;
                        var popH = $pop.outerHeight() || 160;
                        var left = Math.min(window.innerWidth - popW - 12, Math.max(12, rect.left + rect.width / 2 - popW / 2));
                        var top = rect.bottom + 8;
                        if (top + popH > window.innerHeight - 12) {
                            top = Math.max(12, rect.top - popH - 8);
                        }
                        $pop.css({ left: left + 'px', top: top + 'px' });
                    }

                    $('#office-salary-table').on('click', '.pds-hr-rest-btn', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var $btn = $(this);
                        if ($activeBtn && $activeBtn[0] === $btn[0] && !$pop.is('[hidden]')) {
                            closePop();
                            return;
                        }
                        openPop($btn);
                    });

                    $pop.on('click', '.pds-hr-rest-popover__close', function (e) {
                        e.preventDefault();
                        closePop();
                    });

                    $(document).on('click.pdsRestPop', function (e) {
                        if ($(e.target).closest('#pds-hr-rest-popover, .pds-hr-rest-btn').length) {
                            return;
                        }
                        closePop();
                    });

                    $(window).on('scroll.pdsRestPop resize.pdsRestPop', function () {
                        closePop();
                    });
                })();
            })();
        </script>
