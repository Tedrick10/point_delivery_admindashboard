<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-expenses-page pds-expense-summary-page">
        <div class="pds-dispatch-to-assign-screen pds-rider-screen">
            <div class="pds-rider-hero">
                <div class="pds-rider-hero__copy">
                    <div class="pds-rider-hero__eyebrow">
                        <i class="fas fa-chart-pie" aria-hidden="true"></i>
                        <span>{{ __('message.order') }}</span>
                    </div>
                    <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                    <p class="pds-rider-hero__subtitle">{{ __('message.expense_summary_subtitle') }}</p>
                </div>
                <div class="pds-rider-hero__stat">
                    <span class="pds-rider-hero__stat-value">{{ number_format($totalAko) }}</span>
                    <span class="pds-rider-hero__stat-label">{{ __('message.expense_summary_ako_given') }}</span>
                </div>
            </div>

            <div class="pds-expenses-toolbar">
                <div class="pds-expenses-toolbar__month">
                    <div class="pds-expenses-month-nav">
                        <a href="{{ route('order.expense-summary', ['month' => $prevMonth]) }}" class="pds-expenses-month-nav__btn" title="Previous">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <span class="pds-expenses-month-nav__label">{{ $monthLabel }}</span>
                        <a href="{{ route('order.expense-summary', ['month' => $nextMonth]) }}" class="pds-expenses-month-nav__btn" title="Next">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
                <form method="GET" action="{{ route('order.expense-summary') }}" class="pds-expenses-filter" id="expenseSummaryFilterForm">
                    <input type="hidden" name="month" value="{{ $monthValue }}">
                    <div class="pds-expenses-filter__field">
                        <label for="summary_from">{{ __('message.from_date') }}</label>
                        <input type="text" name="from_date" id="summary_from" class="pds-dispatch-input dispatch-datepicker"
                               value="{{ $filterFrom }}" autocomplete="off" placeholder="dd-mm-yyyy">
                    </div>
                    <div class="pds-expenses-filter__field">
                        <label for="summary_to">{{ __('message.to_date') }}</label>
                        <input type="text" name="to_date" id="summary_to" class="pds-dispatch-input dispatch-datepicker"
                               value="{{ $filterTo }}" autocomplete="off" placeholder="dd-mm-yyyy">
                    </div>
                    <div class="pds-expenses-filter__actions">
                        <button type="submit" class="pds-daily-check-search-btn" title="{{ __('message.check') }}">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <span>{{ __('message.check') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="pds-expense-summary-table-wrap">
                <table class="pds-expense-summary-table">
                    <thead>
                        <tr>
                            <th>{{ __('message.expense_summary_no') }}</th>
                            <th>{{ __('message.date') }}</th>
                            <th>{{ __('message.expense_summary_income') }}</th>
                            <th>{{ __('message.expense_summary_expense') }}</th>
                            <th>{{ __('message.expense_summary_ako_given') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $row->summary_date->format('d-m-Y') }}</td>
                                <td class="is-income">{{ number_format($row->income) }}</td>
                                <td class="is-expense">{{ number_format($row->expense) }}</td>
                                <td class="is-ako {{ $row->ako_given < 0 ? 'is-neg' : '' }}">{{ number_format($row->ako_given) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="is-empty">{{ __('message.expense_summary_empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="2">{{ __('message.total') }}</td>
                                <td class="is-income">{{ number_format($totalIncome) }}</td>
                                <td class="is-expense">{{ number_format($totalExpense) }}</td>
                                <td class="is-ako {{ $totalAko < 0 ? 'is-neg' : '' }}">{{ number_format($totalAko) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <script>
        (function bootSummaryFilters() {
            if (typeof flatpickr !== 'undefined') {
                flatpickr('#summary_from, #summary_to', { dateFormat: 'd-m-Y', allowInput: true });
            } else {
                setTimeout(bootSummaryFilters, 40);
            }
        })();
    </script>
</x-master-layout>
