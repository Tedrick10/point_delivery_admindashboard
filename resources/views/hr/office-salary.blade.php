<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-hr-page">
        <div class="pds-hr-hero">
            <div class="pds-hr-hero__copy">
                <div class="pds-hr-hero__eyebrow">
                    <i class="fas fa-wallet" aria-hidden="true"></i>
                    <span>{{ __('message.hr_payroll') }}</span>
                </div>
                <h4 class="pds-hr-hero__title">{{ $pageTitle }}</h4>
                <p class="pds-hr-hero__subtitle">{{ __('message.hr_office_sheet_hint') }}</p>
            </div>
            <div class="pds-hr-hero__stats">
                <div class="pds-hr-stat{{ $sumNetPay < 0 ? ' pds-hr-stat--danger' : '' }}">
                    <span class="pds-hr-stat__value">{{ number_format($sumNetPay) }}</span>
                    <span class="pds-hr-stat__label">{{ __('message.hr_net_pay') }}</span>
                </div>
                <div class="pds-hr-stat pds-hr-stat--soft">
                    <span class="pds-hr-stat__value">{{ $rows->count() }}</span>
                    <span class="pds-hr-stat__label">{{ __('message.hr_people') }}</span>
                </div>
            </div>
        </div>

        <div class="pds-hr-toolbar">
            <div class="pds-hr-month pds-hr-month--end">
                <a href="{{ route('hr.office-salary.index', ['month' => $prevMonth]) }}" class="pds-hr-month__btn"><i class="fas fa-chevron-left"></i></a>
                <span class="pds-hr-month__label">{{ $monthLabel }}</span>
                <a href="{{ route('hr.office-salary.index', ['month' => $nextMonth]) }}" class="pds-hr-month__btn"><i class="fas fa-chevron-right"></i></a>
            </div>
        </div>

        <div class="pds-hr-summary">
            <div class="pds-hr-summary__card">
                <span class="pds-hr-summary__label">{{ __('message.hr_total_salary') }}</span>
                <span class="pds-hr-summary__value">{{ number_format($sumTotalSalary) }}</span>
            </div>
            <div class="pds-hr-summary__card pds-hr-summary__card--danger">
                <span class="pds-hr-summary__label">{{ __('message.hr_total_deduction') }}</span>
                <span class="pds-hr-summary__value">{{ number_format($sumDeduction) }}</span>
            </div>
            <div class="pds-hr-summary__card {{ $sumNetPay < 0 ? 'pds-hr-summary__card--danger' : 'pds-hr-summary__card--success' }}">
                <span class="pds-hr-summary__label">{{ __('message.hr_net_pay') }}</span>
                <span class="pds-hr-summary__value">{{ number_format($sumNetPay) }}</span>
            </div>
        </div>

        <div class="pds-hr-panel pds-hr-panel--salary">
            <div class="pds-hr-freeze-shell">
                <table class="table pds-hr-table pds-hr-table--salary pds-hr-table--freeze mb-0" id="office-salary-table">
                    <thead>
                    <tr>
                        <th class="pds-hr-col-no">#</th>
                        <th class="pds-hr-col-name">{{ __('message.name') }}</th>
                        <th>{{ __('message.hr_monthly_salary') }}</th>
                        <th>{{ __('message.hr_day_rate') }}</th>
                        <th>{{ __('message.hr_days_in_month') }}</th>
                        <th class="pds-hr-col-rest">{{ __('message.hr_rest_days') }}</th>
                        <th>{{ __('message.hr_worked_days') }}</th>
                        <th>{{ __('message.hr_total_salary') }}</th>
                        <th>{{ __('message.hr_late_minute_amount') }}</th>
                        <th>{{ __('message.hr_fine_amount') }}</th>
                        <th>{{ __('message.hr_bag_deduction') }}</th>
                        <th>Deposit</th>
                        <th>{{ __('message.hr_total_deduction') }}</th>
                        <th>{{ __('message.hr_net_pay') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $i => $row)
                        @php
                            $role = $row->staff?->user?->user_type
                                ? ucwords(str_replace('_', ' ', $row->staff->user->user_type))
                                : 'Office';
                            $initial = strtoupper(substr(preg_replace('/\s+/', '', (string) ($row->staff?->name ?? '?')), 0, 1));
                        @endphp
                        <tr data-row-id="{{ $row->id }}">
                            <td class="pds-hr-col-no">{{ $i + 1 }}</td>
                            <td class="pds-hr-col-name">
                                <div class="pds-hr-person">
                                    <span class="pds-hr-avatar">{{ $initial }}</span>
                                    <div>
                                        <div class="pds-hr-person__name">{{ $row->staff?->name }}</div>
                                        <span class="pds-hr-badge">{{ $role }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="js-monthly-salary pds-hr-cell" title="{{ __('message.hr_office_salary_readonly_hint') }}">{{ number_format($row->monthly_salary) }}</span>
                            </td>
                            <td>
                                <span class="js-day-rate pds-hr-cell pds-hr-cell--muted" title="{{ __('message.hr_day_rate_auto_hint') }}">{{ number_format(round($row->day_rate)) }}</span>
                            </td>
                            <td><span class="pds-hr-cell pds-hr-cell--muted">{{ $daysInMonth }}</span></td>
                            <td class="pds-hr-col-rest">
                                @php
                                    $offDates = array_values(array_filter(array_map('strval', (array) ($row->rest_off_dates ?? []))));
                                    $offDateItems = collect($offDates)->map(function ($offDate) {
                                        $c = \Carbon\Carbon::parse($offDate, 'Asia/Yangon');

                                        return [
                                            'iso' => $c->toDateString(),
                                            'label' => $c->format('d/m/Y'),
                                            'short' => $c->format('j/n'),
                                        ];
                                    })->values()->all();
                                @endphp
                                <div class="pds-hr-rest-cell">
                                    <span class="js-rest-days pds-hr-rest-cell__count" title="{{ __('message.hr_rest_days_readonly_hint') }}">{{ (int) $row->rest_days }}</span>
                                    <button type="button"
                                            class="pds-hr-rest-btn"
                                            data-name="{{ $row->staff?->name }}"
                                            data-dates='@json($offDateItems)'
                                            title="{{ __('message.hr_rest_dates_btn_hint') }}"
                                            aria-label="{{ __('message.hr_rest_dates_btn_hint') }}">
                                        <i class="fas fa-calendar-day" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                            <td><span class="js-worked-days pds-hr-cell">{{ $row->worked_days }}</span></td>
                            <td><span class="js-total-salary pds-hr-cell pds-hr-cell--strong">{{ number_format($row->total_salary) }}</span></td>
                            <td>
                                <span class="js-late-minute-amount pds-hr-cell" title="{{ __('message.hr_late_minute_readonly_hint') }}">{{ number_format($row->late_minute_amount) }}</span>
                            </td>
                            <td>
                                <span class="js-fine-amount pds-hr-cell" title="{{ __('message.hr_fine_amount_readonly_hint') }}">{{ number_format($row->fine_amount) }}</span>
                            </td>
                            <td>
                                <span class="js-bag-deduction pds-hr-cell" title="{{ __('message.hr_bag_readonly_hint') }}">{{ number_format($row->bag_deduction) }}</span>
                            </td>
                            <td>
                                <input type="number" min="0" class="pds-hr-input sal-input" data-field="deposit" value="{{ (int) $row->deposit }}" @disabled(! $canEdit)>
                            </td>
                            <td><span class="js-total-deduction pds-hr-cell pds-hr-cell--danger">{{ number_format($row->total_deduction) }}</span></td>
                            <td><span class="js-net-pay pds-hr-cell {{ ((float) $row->net_pay) < 0 ? 'pds-hr-cell--danger' : 'pds-hr-cell--success' }}">{{ number_format($row->net_pay) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="pds-hr-empty">{{ __('message.hr_no_office_accounts_hint') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                        <tfoot>
                            <tr class="pds-hr-tfoot">
                                <td class="pds-hr-col-no"></td>
                                <td class="pds-hr-col-name pds-hr-tfoot__label">{{ __('message.total') }}</td>
                                <td><span class="js-foot-monthly_salary pds-hr-cell">{{ number_format($colTotals['monthly_salary']) }}</span></td>
                                <td><span class="js-foot-day_rate pds-hr-cell">{{ number_format($colTotals['day_rate']) }}</span></td>
                                <td><span class="pds-hr-cell">—</span></td>
                                <td><span class="js-foot-rest_days pds-hr-cell">{{ number_format($colTotals['rest_days']) }}</span></td>
                                <td><span class="js-foot-worked_days pds-hr-cell">{{ number_format($colTotals['worked_days']) }}</span></td>
                                <td><span class="js-foot-total_salary pds-hr-cell pds-hr-cell--strong">{{ number_format($colTotals['total_salary']) }}</span></td>
                                <td><span class="js-foot-late_minute_amount pds-hr-cell">{{ number_format($colTotals['late_minute_amount']) }}</span></td>
                                <td><span class="js-foot-fine_amount pds-hr-cell">{{ number_format($colTotals['fine_amount']) }}</span></td>
                                <td><span class="js-foot-bag_deduction pds-hr-cell">{{ number_format($colTotals['bag_deduction']) }}</span></td>
                                <td><span class="js-foot-deposit pds-hr-cell">{{ number_format($colTotals['deposit']) }}</span></td>
                                <td><span class="js-foot-total_deduction pds-hr-cell pds-hr-cell--danger">{{ number_format($colTotals['total_deduction']) }}</span></td>
                                <td><span class="js-foot-net_pay pds-hr-cell {{ ((float) $colTotals['net_pay']) < 0 ? 'pds-hr-cell--danger' : 'pds-hr-cell--success' }}">{{ number_format($colTotals['net_pay']) }}</span></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <div id="pds-hr-rest-popover" class="pds-hr-rest-popover" hidden>
            <div class="pds-hr-rest-popover__head">
                <strong class="js-rest-pop-name"></strong>
                <button type="button" class="pds-hr-rest-popover__close" aria-label="Close">&times;</button>
            </div>
            <p class="pds-hr-rest-popover__sub">{{ __('message.hr_rest_dates_popup_title') }}</p>
            <ul class="pds-hr-rest-popover__list js-rest-pop-list"></ul>
            <p class="pds-hr-rest-popover__empty js-rest-pop-empty">{{ __('message.hr_rest_dates_empty') }}</p>
        </div>
    </div>

    @include('hr.partials.styles')

    @push('bottom_script')
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
                        totals.deposit += parseNum($tr.find('[data-field="deposit"]').val());
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

                if (canEdit) {
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
    @endpush
</x-master-layout>
