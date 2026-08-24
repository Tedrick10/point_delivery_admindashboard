@php
    $period = $saPeriod ?? app(\App\Services\SuperAdminDashboardService::class)->periodFromRequest();
    $mode = $period['mode'] ?? 'month';
    $today = $period['today'] ?? now('Asia/Yangon')->toDateString();
    $monthStart = \Carbon\Carbon::parse($today, 'Asia/Yangon')->startOfMonth()->toDateString();
    $periodLabel = $period['label'] ?? now('Asia/Yangon')->format('F Y');
@endphp
<form class="sa-date-filter" method="GET" action="{{ url()->current() }}" id="sa-date-filter" data-sa-mode="{{ $mode }}">
    <div class="sa-date-filter__row sa-date-filter__row--main">
        <div class="sa-date-filter__lead">
            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
            <span>{{ __('message.sa_period') }}</span>
        </div>

        <div class="sa-date-filter__modes" role="tablist" aria-label="{{ __('message.sa_stats_period') }}">
            <label class="sa-date-filter__mode {{ $mode === 'month' ? 'is-active' : '' }}">
                <input type="radio" name="period" value="month" {{ $mode === 'month' ? 'checked' : '' }}>
                <span>{{ __('message.sa_this_month') }}</span>
            </label>
            <label class="sa-date-filter__mode {{ $mode === 'day' ? 'is-active' : '' }}">
                <input type="radio" name="period" value="day" {{ $mode === 'day' ? 'checked' : '' }}>
                <span>{{ __('message.sa_single_day') }}</span>
            </label>
            <label class="sa-date-filter__mode {{ $mode === 'range' ? 'is-active' : '' }}">
                <input type="radio" name="period" value="range" {{ $mode === 'range' ? 'checked' : '' }}>
                <span>{{ __('message.sa_date_range') }}</span>
            </label>
        </div>

        <div class="sa-date-filter__context">
            <div class="sa-date-filter__summary {{ $mode === 'month' ? 'is-visible' : '' }}" data-sa-mode-panel="month">
                <i class="fas fa-chart-line" aria-hidden="true"></i>
                <span>{{ $periodLabel }}</span>
            </div>

            <div class="sa-date-filter__inputs {{ $mode === 'day' ? 'is-visible' : '' }}" data-sa-mode-panel="day">
                <label class="sa-date-filter__field" for="sa-date-single">
                    <span>{{ __('message.sa_date') }}</span>
                    <input type="date" id="sa-date-single" name="date" value="{{ $mode === 'day' ? $period['start'] : $today }}" max="{{ $today }}">
                </label>
            </div>

            <div class="sa-date-filter__inputs sa-date-filter__inputs--range {{ $mode === 'range' ? 'is-visible' : '' }}" data-sa-mode-panel="range">
                <label class="sa-date-filter__field" for="sa-date-from">
                    <span>{{ __('message.from') }}</span>
                    <input type="date" id="sa-date-from" name="date_from" value="{{ $mode === 'range' ? $period['start'] : $monthStart }}" max="{{ $today }}">
                </label>
                <label class="sa-date-filter__field" for="sa-date-to">
                    <span>{{ __('message.to') }}</span>
                    <input type="date" id="sa-date-to" name="date_to" value="{{ $mode === 'range' ? $period['end'] : $today }}" max="{{ $today }}">
                </label>
            </div>
        </div>

        <div class="sa-date-filter__actions">
            <button type="submit" class="sa-date-filter__apply">
                <i class="fas fa-check" aria-hidden="true"></i>
                <span>{{ __('message.sa_apply') }}</span>
            </button>
            <a href="{{ url()->current() }}" class="sa-date-filter__reset">{{ __('message.sa_reset') }}</a>
        </div>
    </div>

    <div class="sa-date-filter__row sa-date-filter__row--quick">
        <span class="sa-date-filter__quick-label">{{ __('message.sa_quick') }}</span>
        <div class="sa-date-filter__presets">
            <button type="button" class="sa-date-filter__preset {{ $mode === 'day' && ($period['start'] ?? '') === $today ? 'is-active' : '' }}" data-sa-preset="today">{{ __('message.sa_today') }}</button>
            <button type="button" class="sa-date-filter__preset" data-sa-preset="yesterday">{{ __('message.sa_yesterday') }}</button>
            <button type="button" class="sa-date-filter__preset {{ $mode === 'range' ? 'is-active' : '' }}" data-sa-preset="last7">{{ __('message.sa_last_7_days') }}</button>
        </div>
    </div>
</form>
@include('super-admin.partials.date-filter-script')
