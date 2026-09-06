<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-hr-page">
        <div class="pds-hr-hero">
            <div class="pds-hr-hero__copy">
                <div class="pds-hr-hero__eyebrow">
                    <i class="fas fa-wallet" aria-hidden="true"></i>
                    <span>{{ __('message.hr_payroll') }}</span>
                </div>
                <h4 class="pds-hr-hero__title">{{ $pageTitle }}</h4>
                <p class="pds-hr-hero__subtitle">{{ __('message.hr_my_salary_hint') }}</p>
            </div>
            <div class="pds-hr-hero__stats">
                <div class="pds-hr-stat{{ ((float) ($salary['net_pay'] ?? 0)) < 0 ? ' pds-hr-stat--danger' : '' }}">
                    <span class="pds-hr-stat__value">{{ number_format((float) ($salary['net_pay'] ?? 0)) }}</span>
                    <span class="pds-hr-stat__label">{{ __('message.hr_net_pay') }}</span>
                </div>
                <div class="pds-hr-stat pds-hr-stat--soft">
                    <span class="pds-hr-stat__value">{{ $monthLabel }}</span>
                    <span class="pds-hr-stat__label">Month</span>
                </div>
            </div>
        </div>

        <div class="pds-hr-toolbar">
            <div class="pds-hr-month pds-hr-month--end">
                <a href="{{ route('hr.my-salary.index', ['month' => $prevMonth]) }}" class="pds-hr-month__btn"><i class="fas fa-chevron-left"></i></a>
                <span class="pds-hr-month__label">{{ $monthLabel }}</span>
                <a href="{{ route('hr.my-salary.index', ['month' => $nextMonth]) }}" class="pds-hr-month__btn"><i class="fas fa-chevron-right"></i></a>
            </div>
        </div>

        @if(! $salary)
            <div class="pds-hr-panel">
                <div class="pds-hr-empty">{{ __('message.hr_my_salary_not_found') }}</div>
            </div>
        @else
            <div class="pds-hr-summary">
                <div class="pds-hr-summary__card">
                    <span class="pds-hr-summary__label">{{ __('message.hr_total_salary') }}</span>
                    <span class="pds-hr-summary__value">{{ number_format($salary['total_salary']) }}</span>
                </div>
                <div class="pds-hr-summary__card pds-hr-summary__card--danger">
                    <span class="pds-hr-summary__label">{{ __('message.hr_total_deduction') }}</span>
                    <span class="pds-hr-summary__value">{{ number_format($salary['total_deduction']) }}</span>
                </div>
                <div class="pds-hr-summary__card {{ ((float) $salary['net_pay']) < 0 ? 'pds-hr-summary__card--danger' : 'pds-hr-summary__card--success' }}">
                    <span class="pds-hr-summary__label">{{ __('message.hr_net_pay') }}</span>
                    <span class="pds-hr-summary__value">{{ number_format($salary['net_pay']) }}</span>
                </div>
            </div>

            <div class="pds-hr-panel pds-hr-my-salary">
                <div class="pds-hr-my-salary__grid">
                    @if(($salary['staff_group'] ?? '') === 'rider')
                        <div class="pds-hr-my-salary__item">
                            <span>{{ __('message.hr_way_count') }}</span>
                            <strong>{{ number_format($salary['way_count']) }}</strong>
                        </div>
                        <div class="pds-hr-my-salary__item">
                            <span>{{ __('message.hr_way_rate') }}</span>
                            <strong>{{ number_format($salary['way_rate']) }}</strong>
                        </div>
                    @else
                        <div class="pds-hr-my-salary__item">
                            <span>{{ __('message.hr_monthly_salary') }}</span>
                            <strong>{{ number_format($salary['basic_salary']) }}</strong>
                        </div>
                        <div class="pds-hr-my-salary__item">
                            <span>{{ __('message.hr_day_rate') }}</span>
                            <strong>{{ number_format($salary['day_rate']) }}</strong>
                        </div>
                        <div class="pds-hr-my-salary__item">
                            <span>{{ __('message.hr_days_in_month') }}</span>
                            <strong>{{ $salary['days_in_month'] }}</strong>
                        </div>
                        <div class="pds-hr-my-salary__item">
                            <span>{{ __('message.hr_rest_days') }}</span>
                            <strong>{{ $salary['rest_days'] }}</strong>
                        </div>
                        <div class="pds-hr-my-salary__item">
                            <span>{{ __('message.hr_worked_days') }}</span>
                            <strong>{{ $salary['worked_days'] }}</strong>
                        </div>
                    @endif

                    <div class="pds-hr-my-salary__item">
                        <span>{{ __('message.hr_late_minute_amount') }}</span>
                        <strong>{{ number_format($salary['late_minute']) }}</strong>
                    </div>
                    <div class="pds-hr-my-salary__item">
                        <span>{{ __('message.hr_fine_amount') }}</span>
                        <strong class="text-danger">{{ number_format($salary['fine_amount']) }}</strong>
                    </div>
                    <div class="pds-hr-my-salary__item">
                        <span>{{ __('message.hr_bag_deduction') }}</span>
                        <strong>{{ number_format($salary['bag_deduction']) }}</strong>
                    </div>
                    <div class="pds-hr-my-salary__item">
                        <span>Deposit</span>
                        <strong>{{ number_format($salary['deposit']) }}</strong>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @include('hr.partials.styles')
</x-master-layout>
