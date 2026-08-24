@php
    $bar = $saShared ?? [];
    if (empty($bar)) return;
    $period = $bar['period'] ?? ($saPeriod ?? ['mode' => 'month', 'label' => $bar['monthLabel'] ?? '']);
    $mode = $period['mode'] ?? 'month';
    $money = fn ($n) => number_format((float) $n, 0) . ' Ks';

    $itemsLabel = match ($mode) {
        'day', 'range' => __('message.sa_items'),
        default => __('message.sa_today'),
    };
    $periodLabel = match ($mode) {
        'day', 'range' => __('message.delivered'),
        default => __('message.sa_month'),
    };
    $itemsValue = $mode === 'month'
        ? number_format($bar['items_today'] ?? 0)
        : number_format($bar['items_month'] ?? 0);
    $periodValue = $mode === 'month'
        ? number_format($bar['items_month'] ?? 0)
        : number_format($bar['delivered_period'] ?? 0);

    $items = [
        ['key' => 'items', 'label' => $itemsLabel, 'value' => $itemsValue, 'tone' => ''],
        ['key' => 'period', 'label' => $periodLabel, 'value' => $periodValue, 'tone' => ''],
        ['key' => 'cod', 'label' => __('message.sa_cod'), 'value' => $money($bar['cod_pending'] ?? 0), 'tone' => 'warn'],
        ['key' => 'active', 'label' => __('message.active'), 'value' => number_format($bar['in_progress'] ?? 0), 'tone' => ''],
        ['key' => 'income', 'label' => __('message.sa_income'), 'value' => $money($bar['summary_income_month'] ?? 0), 'tone' => ''],
        ['key' => 'ako', 'label' => __('message.sa_akos_given'), 'value' => $money($bar['summary_ako_month'] ?? 0), 'tone' => 'ok'],
    ];
@endphp
<aside class="sa-shared-bar" aria-label="{{ __('message.sa_network_summary') }}">
    <div class="sa-shared-bar__scroll">
        @foreach($items as $item)
            <div class="sa-shared-bar__chip {{ $item['tone'] ? 'is-'.$item['tone'] : '' }}">
                <span>{{ $item['label'] }}</span>
                <strong>{{ $item['value'] }}</strong>
            </div>
        @endforeach
    </div>
    <time class="sa-shared-bar__date">{{ $bar['monthLabel'] ?? '' }}</time>
</aside>
