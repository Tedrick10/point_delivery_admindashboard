{{-- Shared Office Salary sheet (HR + Super Admin embed) --}}
@php
    $prevMonthUrl = $prevMonthUrl ?? route('hr.office-salary.index', ['month' => $prevMonth]);
    $nextMonthUrl = $nextMonthUrl ?? route('hr.office-salary.index', ['month' => $nextMonth]);
@endphp
        <div class="pds-hr-toolbar">
            <div class="pds-hr-month pds-hr-month--end">
                <a href="{{ $prevMonthUrl }}" class="pds-hr-month__btn"><i class="fas fa-chevron-left"></i></a>
                <span class="pds-hr-month__label">{{ $monthLabel }}</span>
                <a href="{{ $nextMonthUrl }}" class="pds-hr-month__btn"><i class="fas fa-chevron-right"></i></a>
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
                                @if(!empty($canEditDeposit))
                                    <input type="number" min="0" class="pds-hr-input sal-input" data-field="deposit" value="{{ (int) $row->deposit }}">
                                @else
                                    <span class="js-deposit pds-hr-cell" title="{{ __('message.hr_deposit_readonly_hint') }}">{{ number_format($row->deposit) }}</span>
                                @endif
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
