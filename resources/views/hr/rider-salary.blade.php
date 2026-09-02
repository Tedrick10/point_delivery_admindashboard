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

        <div class="pds-hr-panel">
            <div class="table-responsive">
                <table class="table pds-hr-table mb-0" id="rider-salary-table">
                    <thead>
                    <tr>
                        <th class="pds-hr-col-no">#</th>
                        <th>{{ __('message.name') }}</th>
                        <th>{{ __('message.hr_way_count') }}</th>
                        <th>{{ __('message.hr_way_rate') }}</th>
                        <th>{{ __('message.hr_way_pay') }}</th>
                        <th>{{ __('message.hr_total_salary') }}</th>
                        <th>{{ __('message.hr_late_minute_amount') }}</th>
                        <th>{{ __('message.hr_fine_amount') }}</th>
                        <th>{{ __('message.hr_bag_deduction') }}</th>
                        <th>{{ __('message.hr_personal_expense') }}</th>
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
                            <td>
                                <div class="pds-hr-person">
                                    <span class="pds-hr-avatar is-rider">{{ $initial }}</span>
                                    <div>
                                        <div class="pds-hr-person__name">{{ $row->staff?->name }}</div>
                                        <span class="pds-hr-badge is-rider">Delivery Man</span>
                                    </div>
                                </div>
                            </td>
                            <td><input type="number" min="0" class="pds-hr-input sal-input" data-field="way_count" value="{{ $row->way_count }}" @disabled(! $canEdit)></td>
                            <td class="pds-hr-num pds-hr-readonly" title="{{ __('message.hr_readonly_super_admin') }}">{{ (int) $row->way_rate }}</td>
                            <td class="js-way-pay pds-hr-num pds-hr-num--strong">{{ number_format($row->way_pay) }}</td>
                            <td class="js-total-salary pds-hr-num pds-hr-num--strong">{{ number_format($row->total_salary) }}</td>
                            <td><input type="number" class="pds-hr-input sal-input" data-field="late_minute_amount" value="{{ (int) $row->late_minute_amount }}" @disabled(! $canEdit)></td>
                            <td><input type="number" min="0" class="pds-hr-input sal-input" data-field="fine_amount" value="{{ (int) $row->fine_amount }}" @disabled(! $canEdit)></td>
                            <td><input type="number" min="0" class="pds-hr-input sal-input" data-field="bag_deduction" value="{{ (int) $row->bag_deduction }}" @disabled(! $canEdit)></td>
                            <td><input type="number" min="0" class="pds-hr-input sal-input" data-field="personal_expense" value="{{ (int) $row->personal_expense }}" @disabled(! $canEdit)></td>
                            <td><input type="number" min="0" class="pds-hr-input sal-input" data-field="deposit" value="{{ (int) $row->deposit }}" @disabled(! $canEdit)></td>
                            <td class="js-total-deduction pds-hr-num text-danger">{{ number_format($row->total_deduction) }}</td>
                            <td class="js-net-pay pds-hr-num pds-hr-num--success">{{ number_format($row->net_pay) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="pds-hr-empty">{{ __('message.hr_no_rider_accounts_hint') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('hr.partials.styles')

    @push('bottom_script')
        @if($canEdit)
            <script>
                (function () {
                    var csrf = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';
                    var rowUrlBase = '/hr/rider-salary/row';
                    var timers = {};
                    function money(n) { return new Intl.NumberFormat().format(Math.round(Number(n) || 0)); }

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
                                    $tr.find('.js-way-pay').text(money(d.way_pay));
                                    $tr.find('.js-total-salary').text(money(d.total_salary));
                                    $tr.find('.js-total-deduction').text(money(d.total_deduction));
                                    $tr.find('.js-net-pay').text(money(d.net_pay));
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

                    $('#rider-salary-table').on('input', '.sal-input', function () {
                        saveRow($(this).closest('tr'), false);
                    }).on('change blur', '.sal-input', function () {
                        saveRow($(this).closest('tr'), true);
                    });
                })();
            </script>
        @endif
    @endpush
</x-master-layout>
