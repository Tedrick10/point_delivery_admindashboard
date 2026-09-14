<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-kyo-shin-page">
        <div class="pds-dispatch-to-assign-screen pds-rider-screen">
            <div class="pds-rider-hero">
                <div class="pds-rider-hero__copy">
                    <div class="pds-rider-hero__eyebrow">
                        <i class="fas fa-hand-holding-usd" aria-hidden="true"></i>
                        <span>{{ __('message.order') }}</span>
                    </div>
                    <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                    <p class="pds-rider-hero__subtitle">{{ __('message.kyo_shin_subtitle') }}</p>
                </div>
            </div>

            @if(($scopes ?? collect())->count() > 1)
                <div class="pds-kyo-shin-scopes" role="tablist">
                    @foreach($scopes as $scope)
                        <a href="{{ route('order.kyo-shin', [
                                'scope' => $scope['key'],
                                'tab' => $tab,
                                'from_date' => $fromRaw,
                                'to_date' => $toRaw,
                            ]) }}"
                           class="pds-kyo-shin-scopes__item {{ $scopeKey === $scope['key'] ? 'is-active' : '' }}">
                            {{ $scope['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif

            <form method="GET" action="{{ route('order.kyo-shin') }}" class="pds-rider-toolbar" id="kyoShinFilterForm">
                <input type="hidden" name="scope" value="{{ $scopeKey }}">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="pds-rider-toolbar__fields">
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="kyo_shin_from_date">{{ __('message.from') }}</label>
                        <input type="text" name="from_date" id="kyo_shin_from_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $fromRaw }}" autocomplete="off">
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="kyo_shin_to_date">{{ __('message.to') }}</label>
                        <input type="text" name="to_date" id="kyo_shin_to_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $toRaw }}" autocomplete="off">
                    </div>
                </div>
                <div class="pds-rider-toolbar__aside">
                    <button type="submit" class="pds-rider-check-btn">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <span>{{ __('message.check') }}</span>
                    </button>
                </div>
            </form>

            <div class="pds-kyo-shin-summary">
                <article class="pds-kyo-shin-summary__card is-total">
                    <span>{{ __('message.kyo_shin_total') }}</span>
                    <strong>{{ number_format($summary['total']) }} Ks</strong>
                    <em>{{ number_format($summary['thein_total'], 2) }} {{ __('message.kyo_shin_thein') }}</em>
                </article>
                <article class="pds-kyo-shin-summary__card is-paid">
                    <span>{{ __('message.kyo_shin_advanced_paid') }}</span>
                    <strong>{{ number_format($summary['advanced_paid']) }} Ks</strong>
                    <em>{{ number_format($summary['thein_advanced'], 2) }} {{ __('message.kyo_shin_thein') }}</em>
                </article>
                <article class="pds-kyo-shin-summary__card is-remain {{ $summary['remain'] < 0 ? 'is-over' : '' }}">
                    <span>{{ __('message.kyo_shin_remain') }}</span>
                    <strong>{{ number_format($summary['remain']) }} Ks</strong>
                    <em>{{ number_format($summary['thein_remain'], 2) }} {{ __('message.kyo_shin_thein') }}</em>
                </article>
            </div>

            <div class="pds-rider-body">
                <div class="pds-os-settlement-tabs" role="tablist">
                    @php
                        $tabs = [
                            \App\Services\KyoShinService::TAB_OS_LIST => ['icon' => 'fa-store', 'label' => __('message.kyo_shin_tab_os_list')],
                            \App\Services\KyoShinService::TAB_ADVANCED_PAID => ['icon' => 'fa-coins', 'label' => __('message.kyo_shin_tab_advanced')],
                            \App\Services\KyoShinService::TAB_FINISHED => ['icon' => 'fa-check-circle', 'label' => __('message.kyo_shin_tab_finished')],
                        ];
                    @endphp
                    @foreach($tabs as $tabKey => $meta)
                        <a href="{{ route('order.kyo-shin', [
                                'scope' => $scopeKey,
                                'tab' => $tabKey,
                                'from_date' => $fromRaw,
                                'to_date' => $toRaw,
                            ]) }}"
                           class="pds-os-settlement-tab {{ $tab === $tabKey ? 'is-active' : '' }}">
                            <i class="fas {{ $meta['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $meta['label'] }}</span>
                            <em>{{ $counts[$tabKey] ?? 0 }}</em>
                        </a>
                    @endforeach
                </div>

                <div class="pds-rider-table-shell pds-rider-table-shell--scroll pds-no-freeze">
                    @if($rows->isEmpty())
                        <div class="pds-os-settlement-section__empty">
                            <p>{{ __('message.kyo_shin_empty') }}</p>
                        </div>
                    @else
                        <table class="table pds-rider-list-table pds-os-settlement-table">
                            <thead>
                                <tr>
                                    <th class="pds-rider-col-no">{{ __('message.no') }}</th>
                                    <th>{{ __('message.os_name') }}</th>
                                    <th class="text-right">{{ __('message.amount') }}</th>
                                    <th class="text-right">{{ __('message.items') }}</th>
                                    <th>{{ __('message.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $index => $row)
                                    @php $initial = mb_strtoupper(mb_substr(trim($row->name) ?: 'O', 0, 1)); @endphp
                                    <tr>
                                        <td class="pds-rider-col-no">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="pds-rider-person">
                                                <span class="pds-rider-avatar pds-os-settlement-avatar" aria-hidden="true">{{ $initial }}</span>
                                                <div class="pds-rider-person__meta">
                                                    <div class="pds-dispatch-rider-list-name">{{ $row->name }}</div>
                                                    @if($row->phone && $row->phone !== '-')
                                                        <div class="pds-dispatch-rider-list-phone">{{ $row->phone }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <span class="pds-os-amount-pill is-negative">{{ number_format($row->amount) }}</span>
                                        </td>
                                        <td class="text-right">{{ $row->item_count }}</td>
                                        <td>
                                            <a class="pds-kyo-shin-details-btn"
                                               href="{{ route('order.kyo-shin.items', [
                                                    'osId' => $row->id,
                                                    'scope' => $scopeKey,
                                                    'tab' => $tab,
                                                    'from_date' => $fromRaw,
                                                    'to_date' => $toRaw,
                                               ]) }}">
                                                {{ __('message.details') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        .pds-kyo-shin-scopes {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 0 0 12px;
        }
        .pds-kyo-shin-scopes__item {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 0.9rem;
            border-radius: 999px;
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-decoration: none;
        }
        .pds-kyo-shin-scopes__item.is-active {
            background: #ea580c;
            color: #fff;
        }
        .pds-kyo-shin-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin: 4px 0 16px;
        }
        .pds-kyo-shin-summary__card {
            background: #fff;
            border: 1px solid #e8e0d4;
            border-radius: 16px;
            padding: 14px 16px;
            box-shadow: 0 8px 20px rgba(26, 23, 20, 0.04);
        }
        .pds-kyo-shin-summary__card span {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .pds-kyo-shin-summary__card strong {
            display: block;
            margin-top: 6px;
            font-size: 1.15rem;
            color: #1a1714;
        }
        .pds-kyo-shin-summary__card em {
            display: block;
            margin-top: 2px;
            font-style: normal;
            font-size: 12px;
            color: #94a3b8;
        }
        .pds-kyo-shin-summary__card.is-total { border-color: #fdba74; background: #fff7ed; }
        .pds-kyo-shin-summary__card.is-paid { border-color: #86efac; background: #f0fdf4; }
        .pds-kyo-shin-summary__card.is-remain { border-color: #93c5fd; background: #eff6ff; }
        .pds-kyo-shin-summary__card.is-over { border-color: #fca5a5; background: #fef2f2; }
        .pds-kyo-shin-details-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 0 12px;
            border-radius: 10px;
            border: 1px solid #fdba74;
            background: #fff7ed;
            color: #c2410c;
            font-weight: 700;
            text-decoration: none;
        }
        @media (max-width: 720px) {
            .pds-kyo-shin-summary { grid-template-columns: 1fr; }
        }
    </style>
</x-master-layout>
