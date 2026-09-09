@extends('super-admin.layout')

@section('title', __('message.dashboard'))
@section('page_title', __('message.sa_network_dashboard'))
@section('page_sub', __('message.sa_all_branches') . ' · ' . $monthLabel)

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n);
    $money = fn ($n) => number_format((float) $n, 0) . ' Ks';
    $s = $stats;
    $periodMode = $period['mode'] ?? 'month';
    $periodHint = $periodMode === 'month' ? __('message.sa_this_month_lc') : __('message.sa_in_period');
    $maxCreated = max(1, collect($last7)->max('created'));
    $maxDelivered = max(1, collect($last7)->max('delivered'));
    $chartTitle = match ($periodMode) {
        'day' => __('message.sa_selected_day'),
        'range' => __('message.sa_daily_trend'),
        default => __('message.sa_last_7_days'),
    };

    $tabs = [
        'overview' => ['label' => __('message.sa_tab_overview'), 'icon' => 'fa-tachometer-alt'],
        'dispatch' => ['label' => __('message.sa_tab_dispatch'), 'icon' => 'fa-truck-moving'],
        'finance' => ['label' => __('message.sa_tab_finance'), 'icon' => 'fa-coins'],
        'settlements' => ['label' => __('message.sa_tab_settlements'), 'icon' => 'fa-file-invoice-dollar'],
    ];

    $statusLabel = function (string $status): string {
        $key = 'message.sa_status_'.$status;
        $translated = __($key);
        return $translated === $key ? str_replace('_', ' ', $status) : $translated;
    };
@endphp

<div class="sa-dash">
    {{-- Summary banner --}}
    <section class="sa-dash-banner">
        <div class="sa-dash-banner__main">
            <div class="sa-dash-banner__item">
                <span class="sa-dash-banner__label">{{ __('message.sa_items') }} · {{ $monthLabel }}</span>
                <strong class="sa-dash-banner__value">{{ $fmt($s['items_month']) }}</strong>
                <span class="sa-dash-banner__hint">{{ $fmt($s['items_today']) }} {{ $periodMode === 'day' ? __('message.sa_on_day') : __('message.sa_today_lc') }} · {{ $fmt($s['delivered_period']) }} {{ __('message.sa_delivered_lc') }} {{ $periodHint }}</span>
            </div>
            <div class="sa-dash-banner__item">
                <span class="sa-dash-banner__label">{{ __('message.sa_cod_pending') }}</span>
                <strong class="sa-dash-banner__value">{{ $money($s['cod_pending']) }}</strong>
                <span class="sa-dash-banner__hint">{{ __('message.sa_open_across_network') }}</span>
            </div>
            <div class="sa-dash-banner__item">
                <span class="sa-dash-banner__label">{{ __('message.sa_income') }}</span>
                <strong class="sa-dash-banner__value">{{ $money($s['summary_income_month']) }}</strong>
                <span class="sa-dash-banner__hint">{{ $fmt($s['summary_days']) }} {{ __('message.sa_summary_days') }}</span>
            </div>
            <div class="sa-dash-banner__item">
                <span class="sa-dash-banner__label">{{ __('message.sa_akos_given') }}</span>
                <strong class="sa-dash-banner__value">{{ $money($s['summary_ako_month']) }}</strong>
                <span class="sa-dash-banner__hint">{{ $fmt($s['summary_days']) }} {{ __('message.sa_summary_days') }} · {{ $periodHint }}</span>
            </div>
        </div>
        <div class="sa-dash-banner__aside">
            <div class="sa-dash-banner__chip">
                <i class="fas fa-star"></i>
                <span>{{ $s['avg_rating'] ?? '—' }}</span>
                <small>{{ $fmt($s['rating_count']) }} {{ __('message.sa_reviews') }}</small>
            </div>
            <div class="sa-dash-banner__chip">
                <i class="fas fa-building"></i>
                <span>{{ $fmt($s['branches']) }}</span>
                <small>{{ __('message.sa_branches') }}</small>
            </div>
        </div>
    </section>

    <div class="sa-dash-layout">
        {{-- Left: tabbed metrics --}}
        <div class="sa-dash-main">
            <div class="sa-dash-tabs" role="tablist">
                @foreach($tabs as $id => $tab)
                    <button type="button"
                            class="sa-dash-tabs__btn {{ $loop->first ? 'is-active' : '' }}"
                            role="tab"
                            data-sa-tab="{{ $id }}"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                        <i class="fas {{ $tab['icon'] }}"></i>
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>

            <div class="sa-dash-panel is-active" data-sa-panel="overview" role="tabpanel">
                <div class="sa-metric-grid">
                    @foreach([
                        [__('message.sa_metric_branches'), $fmt($s['branches']), $fmt($s['active_branches']).' '.__('message.sa_active')],
                        [__('message.sa_metric_branch_admins'), $fmt($s['branch_admins']), $fmt($s['branches_without_admin']).' '.__('message.sa_missing')],
                        [__('message.sa_metric_riders'), $fmt($s['riders']), $fmt($s['riders_active']).' '.__('message.sa_active')],
                        [__('message.sa_metric_os_clients'), $fmt($s['clients']), $fmt($s['clients_vip']).' '.__('message.sa_vip')],
                        [__('message.sa_metric_legacy_orders'), $fmt($s['orders']), $fmt($s['orders_month']).' '.$periodHint],
                        [__('message.sa_metric_wallet_total'), $money($s['wallet_total']), __('message.sa_all_riders')],
                        [__('message.sa_metric_withdraw_pending'), $fmt($s['withdraw_pending']), $money($s['withdraw_pending_amount'])],
                        [__('message.sa_metric_delivered'), $fmt($s['delivered_ui']), $fmt($s['admin_completed']).' '.__('message.sa_admin_done')],
                    ] as [$label, $value, $hint])
                        <div class="sa-metric">
                            <span class="sa-metric__label">{{ $label }}</span>
                            <strong class="sa-metric__value">{{ $value }}</strong>
                            <span class="sa-metric__hint">{{ $hint }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="sa-dash-panel" data-sa-panel="dispatch" role="tabpanel" hidden>
                <div class="sa-metric-grid">
                    @foreach([
                        [__('message.sa_items_all'), $fmt($s['items_total']), __('message.sa_total_dispatch')],
                        [__('message.sa_in_progress'), $fmt($s['in_progress']), $fmt($s['unassigned']).' '.__('message.sa_unassigned_count')],
                        [__('message.sa_cancelled'), $fmt($s['cancelled_items']), __('message.sa_return_cancel')],
                        [__('message.sa_item_value'), $money($s['item_value_month']), $periodHint],
                        [__('message.sa_os_to_pay'), $money($s['os_to_pay_month']), $periodHint],
                        [__('message.sa_gate_amount'), $money($s['gate_amount_month']), __('message.sa_gate_fees')],
                        [__('message.sa_advance_paid'), $money($s['advance_paid_month']), __('message.sa_prepaid')],
                        [__('message.sa_deli_fees'), $money($s['deli_month']), $periodHint],
                    ] as [$label, $value, $hint])
                        <div class="sa-metric">
                            <span class="sa-metric__label">{{ $label }}</span>
                            <strong class="sa-metric__value">{{ $value }}</strong>
                            <span class="sa-metric__hint">{{ $hint }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="sa-dash-panel" data-sa-panel="finance" role="tabpanel" hidden>
                <div class="sa-money-strip">
                    <header class="sa-money-strip__head">
                        <h3>{{ __('message.sa_rider_remit') }}</h3>
                        <span>{{ $monthLabel }}</span>
                    </header>
                    <div class="sa-money-strip__cols">
                        <div class="sa-money-strip__col is-cash">
                            <span>{{ __('message.sa_money') }}</span>
                            <strong>{{ $money($s['remit_cash_month']) }}</strong>
                        </div>
                        <div class="sa-money-strip__col is-kpay">
                            <span>{{ __('message.sa_kpay') }}</span>
                            <strong>{{ $money($s['remit_kpay_month']) }}</strong>
                        </div>
                        <div class="sa-money-strip__col is-total">
                            <span>{{ __('message.sa_money_kpay') }}</span>
                            <strong>{{ $money($s['remit_combined_month']) }}</strong>
                        </div>
                    </div>
                    <footer class="sa-money-strip__foot">
                        {{ __('message.sa_due') }} {{ $money($s['remit_month']) }} · {{ __('message.sa_prepaid') }} {{ $money($s['remit_prepaid_month']) }} · {{ __('message.sa_fuel') }} {{ $money($s['remit_fuel_month']) }} · {{ __('message.sa_fees') }} {{ $money($s['remit_fee_month']) }}
                        · {{ $fmt($s['remit_balanced']) }}/{{ $fmt($s['remit_records']) }} {{ __('message.sa_balanced') }}
                    </footer>
                </div>

                <div class="sa-money-strip">
                    <header class="sa-money-strip__head">
                        <h3>{{ __('message.sa_money_transfer') }}</h3>
                        <span>{{ $fmt($s['mt_rows_month']) }} {{ __('message.sa_os_rows') }}</span>
                    </header>
                    <div class="sa-money-strip__cols">
                        <div class="sa-money-strip__col is-cash">
                            <span>{{ __('message.sa_money') }}</span>
                            <strong>{{ $money($s['mt_cash_month']) }}</strong>
                        </div>
                        <div class="sa-money-strip__col is-kpay">
                            <span>{{ __('message.sa_kpay') }}</span>
                            <strong>{{ $money($s['mt_kpay_month']) }}</strong>
                        </div>
                        <div class="sa-money-strip__col is-total">
                            <span>{{ __('message.sa_money_kpay') }}</span>
                            <strong>{{ $money($s['mt_combined_month']) }}</strong>
                        </div>
                    </div>
                    <footer class="sa-money-strip__foot">{{ __('message.sa_freight') }} {{ $money($s['mt_freight_month']) }}</footer>
                </div>

                <div class="sa-metric-grid sa-metric-grid--3">
                    @foreach([
                        [__('message.sa_expenses'), $money($s['expense_month']), $fmt($s['expense_cards']).' '.__('message.sa_cards')],
                        [__('message.sa_summary_expense'), $money($s['summary_expense_month']), __('message.sa_generated')],
                        [__('message.sa_payments_period'), $money($s['payment_month']), __('message.sa_comm').' '.$money($s['admin_commission_month'])],
                        [__('message.sa_payments_all'), $money($s['payment_total']), $fmt($s['payments_unsettled']).' '.__('message.sa_unsettled')],
                    ] as [$label, $value, $hint])
                        <div class="sa-metric">
                            <span class="sa-metric__label">{{ $label }}</span>
                            <strong class="sa-metric__value">{{ $value }}</strong>
                            <span class="sa-metric__hint">{{ $hint }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="sa-dash-panel" data-sa-panel="settlements" role="tabpanel" hidden>
                <div class="sa-metric-grid">
                    @foreach([
                        [__('message.sa_os_finished'), $money($s['settlement_month']), $fmt($s['settlement_count_month']).' '.__('message.sa_batches')],
                        [__('message.sa_receive_pending'), $fmt($s['receive_pending']), $money($s['receive_pending_amount'])],
                        [__('message.sa_received_period'), $money($s['receive_received_month']), __('message.sa_approved')],
                        [__('message.sa_cash_payout_open'), $fmt($s['cash_payout_open']), $money($s['cash_payout_open_amount'])],
                        [__('message.sa_cash_payout_done'), $money($s['cash_payout_done_month']), $periodHint],
                        [__('message.sa_daily_check_pending'), $fmt($s['daily_check_pending']), __('message.sa_not_remitted')],
                    ] as [$label, $value, $hint])
                        <div class="sa-metric">
                            <span class="sa-metric__label">{{ $label }}</span>
                            <strong class="sa-metric__value">{{ $value }}</strong>
                            <span class="sa-metric__hint">{{ $hint }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Right sidebar --}}
        <aside class="sa-dash-side">
            <div class="sa-side-card">
                <header class="sa-side-card__head">
                    <h3>{{ __('message.sa_activity') }}</h3>
                    <span>{{ $chartTitle }}</span>
                </header>
                <div class="sa-chart-legend">
                    <span><i class="sa-legend-dot is-created"></i> {{ __('message.sa_created') }}</span>
                    <span><i class="sa-legend-dot is-delivered"></i> {{ __('message.delivered') }}</span>
                </div>
                <div class="sa-bars sa-bars--dual">
                    @foreach($last7 as $day)
                        @php
                            $hCreated = max(4, (int) round(($day['created'] / $maxCreated) * 88));
                            $hDelivered = max(4, (int) round(($day['delivered'] / $maxDelivered) * 88));
                        @endphp
                        <div class="sa-bar-col" title="{{ $day['created'] }} {{ __('message.sa_created') }} · {{ $day['delivered'] }} {{ __('message.delivered') }}">
                            <div class="sa-bar-pair">
                                <div class="sa-bar is-created" style="height: {{ $hCreated }}px;"></div>
                                <div class="sa-bar is-delivered" style="height: {{ $hDelivered }}px;"></div>
                            </div>
                            <div class="sa-bar-label">{{ $day['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if(($s['status_counts'] ?? collect())->isNotEmpty())
            <div class="sa-side-card">
                <header class="sa-side-card__head">
                    <h3>{{ __('message.status') }}</h3>
                    <span>{{ __('message.sa_pipeline') }}</span>
                </header>
                <ul class="sa-status-list">
                    @foreach($s['status_counts'] as $status => $count)
                        @php
                            $pct = $s['items_total'] > 0 ? min(100, round(($count / $s['items_total']) * 100)) : 0;
                        @endphp
                        <li class="sa-status-list__item">
                            <div class="sa-status-list__row">
                                <span>{{ $statusLabel((string) $status) }}</span>
                                <strong>{{ $fmt($count) }}</strong>
                            </div>
                            <div class="sa-status-list__bar"><i style="width: {{ $pct }}%"></i></div>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </aside>
    </div>

    {{-- Branches --}}
    <section class="sa-branches">
        <header class="sa-branches__head">
            <div>
                <h2>{{ __('message.sa_branches_title') }}</h2>
                <p>{{ __('message.sa_regional_performance') }} · {{ $monthLabel }}</p>
            </div>
            <a href="{{ route('super-admin.branch-admins.index') }}" class="sa-btn sa-btn-primary">
                <i class="fas fa-user-shield"></i> {{ __('message.sa_manage_admins') }}
            </a>
        </header>
        <div class="sa-branch-grid">
            @forelse($byBranch as $row)
                <article class="sa-branch-card {{ !$row['status'] ? 'is-off' : '' }}">
                    <header class="sa-branch-card__head">
                        <h3>{{ $row['name'] }}</h3>
                        @if(!$row['status'])
                            <span class="sa-badge sa-badge-off">{{ __('message.sa_off') }}</span>
                        @endif
                    </header>
                    @if($row['admin'])
                        <p class="sa-branch-card__admin">{{ $row['admin']['name'] }}</p>
                    @else
                        <p class="sa-branch-card__admin is-missing">{{ __('message.sa_no_admin_assigned') }}</p>
                        <a href="{{ route('super-admin.branch-admins.create', ['branch_id' => $row['id']]) }}" class="sa-btn sa-btn-primary">
                            {{ __('message.sa_create_admin') }}
                        </a>
                    @endif
                    <dl class="sa-branch-card__stats">
                        <div><dt>{{ __('message.sa_items') }}</dt><dd>{{ $fmt($row['items_total']) }}</dd></div>
                        <div><dt>{{ __('message.sa_period_col') }}</dt><dd>{{ $fmt($row['items_month']) }}</dd></div>
                        <div><dt>{{ __('message.active') }}</dt><dd>{{ $fmt($row['in_progress']) }}</dd></div>
                        <div><dt>{{ __('message.sa_done') }}</dt><dd>{{ $fmt($row['delivered']) }}</dd></div>
                        <div><dt>{{ __('message.sa_riders') }}</dt><dd>{{ $fmt($row['riders']) }}</dd></div>
                        <div><dt>{{ __('message.sa_cod') }}</dt><dd>{{ $money($row['cod']) }}</dd></div>
                    </dl>
                </article>
            @empty
                <p class="sa-branch-empty">{{ __('message.sa_no_branches') }}</p>
            @endforelse
        </div>
    </section>

    <div class="sa-dash-quick">
        <a href="{{ route('super-admin.screens.hub') }}" class="sa-btn sa-btn-ghost">
            <i class="fas fa-th-large"></i> {{ __('message.sa_all_screens') }}
        </a>
    </div>
</div>

<script>
(function () {
    const tabs = document.querySelectorAll('[data-sa-tab]');
    const panels = document.querySelectorAll('[data-sa-panel]');
    if (!tabs.length) return;

    tabs.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = btn.getAttribute('data-sa-tab');
            tabs.forEach(function (t) {
                const active = t === btn;
                t.classList.toggle('is-active', active);
                t.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            panels.forEach(function (p) {
                const show = p.getAttribute('data-sa-panel') === id;
                p.classList.toggle('is-active', show);
                p.hidden = !show;
            });
        });
    });
})();
</script>
@endsection
