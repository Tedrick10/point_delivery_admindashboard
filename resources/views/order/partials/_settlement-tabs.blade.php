@php
    $activeTab = $activeTab ?? 'daily-check';
    $tabQuery = request()->only(['from_date', 'to_date', 'branch_id']);
    if (empty($tabQuery['from_date']) && request()->filled('date')) {
        $tabQuery['from_date'] = request('date');
        $tabQuery['to_date'] = request('date');
    }
    $remitQuery = array_filter([
        'date' => $tabQuery['from_date'] ?? null,
        'branch_id' => $tabQuery['branch_id'] ?? null,
    ], fn ($value) => $value !== null && $value !== '');
@endphp
<nav class="pds-settle-tabs" aria-label="{{ __('message.settlement') }}">
    <a href="{{ route('order.daily-checklist', $tabQuery + ['mode' => 'os']) }}"
       class="pds-settle-tabs__item {{ $activeTab === 'daily-check' ? 'is-active' : '' }}">
        <i class="fas fa-clipboard-check" aria-hidden="true"></i>
        <span>{{ __('message.daily_check_list') }}</span>
    </a>
    <a href="{{ route('order.money-transfer', $tabQuery) }}"
       class="pds-settle-tabs__item {{ $activeTab === 'money-transfer' ? 'is-active' : '' }}">
        <i class="fas fa-exchange-alt" aria-hidden="true"></i>
        <span>{{ __('message.money_transfer_list') }}</span>
    </a>
    <a href="{{ route('order.rider-remit', $remitQuery) }}"
       class="pds-settle-tabs__item {{ $activeTab === 'rider-remit' ? 'is-active' : '' }}">
        <i class="fas fa-wallet" aria-hidden="true"></i>
        <span>{{ __('message.rider_remit_title') }}</span>
    </a>
</nav>
