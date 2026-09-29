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

        @include('hr.partials._office-salary-sheet')
    </div>

    @include('hr.partials.styles')

    @push('bottom_script')
        @include('hr.partials._office-salary-scripts')
    @endpush
</x-master-layout>
