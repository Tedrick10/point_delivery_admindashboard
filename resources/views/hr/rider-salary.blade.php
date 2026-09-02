<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-hr-page">
        <div class="pds-hr-hero">
            <div class="pds-hr-hero__copy">
                <div class="pds-hr-hero__eyebrow">
                    <i class="fas fa-motorcycle" aria-hidden="true"></i>
                    <span>{{ __('message.hr_payroll') }}</span>
                </div>
                <h4 class="pds-hr-hero__title">{{ $pageTitle }}</h4>
                <p class="pds-hr-hero__subtitle">{{ __('message.hr_rider_sheet_hint') }}</p>
            </div>
            <div class="pds-hr-hero__stats">
                <div class="pds-hr-stat">
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
                <a href="{{ route('hr.rider-salary.index', ['month' => $prevMonth]) }}" class="pds-hr-month__btn"><i class="fas fa-chevron-left"></i></a>
                <span class="pds-hr-month__label">{{ $monthLabel }}</span>
                <a href="{{ route('hr.rider-salary.index', ['month' => $nextMonth]) }}" class="pds-hr-month__btn"><i class="fas fa-chevron-right"></i></a>
            </div>
        </div>

        <div class="pds-hr-summary">
            <div class="pds-hr-summary__card">
                <span class="pds-hr-summary__label">{{ __('message.hr_total_salary') }}</span>
                <span class="pds-hr-summary__value">{{ number_format($sumWayPay) }}</span>
            </div>
            <div class="pds-hr-summary__card pds-hr-summary__card--danger">
                <span class="pds-hr-summary__label">{{ __('message.hr_total_deduction') }}</span>
                <span class="pds-hr-summary__value">{{ number_format($sumDeduction) }}</span>
            </div>
            <div class="pds-hr-summary__card pds-hr-summary__card--success">
                <span class="pds-hr-summary__label">{{ __('message.hr_net_pay') }}</span>
                <span class="pds-hr-summary__value">{{ number_format($sumNetPay) }}</span>
            </div>
        </div>

        <div class="pds-hr-panel pds-hr-panel--salary">
            <div class="table-responsive">
                <table class="table pds-hr-table pds-hr-table--salary mb-0" id="rider-salary-table">
                    <thead>
                    <tr>
                        <th class="pds-hr-col-no">#</th>
                        <th class="pds-hr-col-name">{{ __('message.name') }}</th>
                        <th>{{ __('message.hr_way_count') }}</th>
                        <th>{{ __('message.hr_way_rate') }}</th>
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
                            $initial = strtoupper(substr(preg_replace('/\s+/', '', (string) ($row->staff?->name ?? '?')), 0, 1));
                        @endphp
                        <tr data-row-id="{{ $row->id }}">
                            <td class="pds-hr-col-no">{{ $i + 1 }}</td>
                            <td class="pds-hr-col-name">
                                <div class="pds-hr-person">
                                    <span class="pds-hr-avatar is-rider">{{ $initial }}</span>
                                    <div>
                                        <div class="pds-hr-person__name">{{ $row->staff?->name }}</div>
                                        <span class="pds-hr-badge is-rider">Delivery Man</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="js-way-count pds-hr-cell" title="{{ __('message.hr_way_count_auto_hint') }}">{{ (int) $row->way_count }}</span>
                            </td>
                            <td>
                                <span class="pds-hr-cell pds-hr-cell--muted" title="{{ __('message.hr_readonly_super_admin') }}">{{ (int) $row->way_rate }}</span>
                            </td>
                            <td><span class="js-total-salary pds-hr-cell pds-hr-cell--strong">{{ number_format($row->total_salary) }}</span></td>
                            <td>
                                <input type="number" class="pds-hr-input sal-input" data-field="late_minute_amount" value="{{ (int) $row->late_minute_amount }}" @disabled(! $canEdit)>
                            </td>
                            <td>
                                <input type="number" min="0" class="pds-hr-input sal-input" data-field="fine_amount" value="{{ (int) $row->fine_amount }}" @disabled(! $canEdit)>
                            </td>
                            <td>
                                <span class="js-bag-deduction pds-hr-cell" title="{{ __('message.hr_bag_readonly_hint') }}">{{ number_format($row->bag_deduction) }}</span>
                            </td>
                            <td>
                                <input type="number" min="0" class="pds-hr-input sal-input" data-field="deposit" value="{{ (int) $row->deposit }}" @disabled(! $canEdit)>
                            </td>
                            <td><span class="js-total-deduction pds-hr-cell pds-hr-cell--danger">{{ number_format($row->total_deduction) }}</span></td>
                            <td><span class="js-net-pay pds-hr-cell pds-hr-cell--success">{{ number_format($row->net_pay) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="pds-hr-empty">{{ __('message.hr_no_rider_accounts_hint') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                        <tfoot>
                            <tr class="pds-hr-tfoot">
                                <td class="pds-hr-col-no"></td>
                                <td class="pds-hr-col-name pds-hr-tfoot__label">{{ __('message.total') }}</td>
                                <td><span class="js-foot-way_count pds-hr-cell">{{ number_format($colTotals['way_count']) }}</span></td>
                                <td><span class="pds-hr-cell">—</span></td>
                                <td><span class="js-foot-total_salary pds-hr-cell pds-hr-cell--strong">{{ number_format($colTotals['total_salary']) }}</span></td>
                                <td><span class="js-foot-late_minute_amount pds-hr-cell">{{ number_format($colTotals['late_minute_amount']) }}</span></td>
                                <td><span class="js-foot-fine_amount pds-hr-cell">{{ number_format($colTotals['fine_amount']) }}</span></td>
                                <td><span class="js-foot-bag_deduction pds-hr-cell">{{ number_format($colTotals['bag_deduction']) }}</span></td>
                                <td><span class="js-foot-deposit pds-hr-cell">{{ number_format($colTotals['deposit']) }}</span></td>
                                <td><span class="js-foot-total_deduction pds-hr-cell pds-hr-cell--danger">{{ number_format($colTotals['total_deduction']) }}</span></td>
                                <td><span class="js-foot-net_pay pds-hr-cell pds-hr-cell--success">{{ number_format($colTotals['net_pay']) }}</span></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    @include('hr.partials.styles')

    @push('bottom_script')
        <script>
            (function () {
                var csrf = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';
                var rowUrlBase = '/hr/rider-salary/row';
                var timers = {};
                var canEdit = {{ $canEdit ? 'true' : 'false' }};
                function money(n) { return new Intl.NumberFormat().format(Math.round(Number(n) || 0)); }
                function parseNum(text) {
                    return parseFloat(String(text == null ? '' : text).replace(/,/g, '')) || 0;
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
                        totals.late_minute_amount += parseNum($tr.find('[data-field="late_minute_amount"]').val());
                        totals.fine_amount += parseNum($tr.find('[data-field="fine_amount"]').val());
                        totals.bag_deduction += parseNum($tr.find('.js-bag-deduction').text());
                        totals.deposit += parseNum($tr.find('[data-field="deposit"]').val());
                        totals.total_deduction += parseNum($tr.find('.js-total-deduction').text());
                        totals.net_pay += parseNum($tr.find('.js-net-pay').text());
                    });
                    Object.keys(totals).forEach(function (key) {
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
                                $tr.find('.js-net-pay').text(money(d.net_pay));
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
                    $('#rider-salary-table').on('input', '.sal-input', function () {
                        saveRow($(this).closest('tr'), false);
                    }).on('change blur', '.sal-input', function () {
                        saveRow($(this).closest('tr'), true);
                    });
                }
            })();
        </script>
    @endpush
</x-master-layout>
