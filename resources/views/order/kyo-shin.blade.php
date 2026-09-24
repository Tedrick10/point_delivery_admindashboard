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

            @if(($scopes ?? collect())->isNotEmpty())
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
                        <input type="text" name="from_date" id="kyo_shin_from_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $fromRaw }}" placeholder="{{ __('message.all') }}" autocomplete="off">
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="kyo_shin_to_date">{{ __('message.to') }}</label>
                        <input type="text" name="to_date" id="kyo_shin_to_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $toRaw }}" placeholder="{{ __('message.all') }}" autocomplete="off">
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
                <article class="pds-kyo-shin-hero">
                    <div class="pds-kyo-shin-hero__icon" aria-hidden="true">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="pds-kyo-shin-hero__copy">
                        <span>{{ __('message.kyo_shin_sa_amount') }}</span>
                        <strong>{{ number_format($summary['total']) }} <small>Ks</small></strong>
                    </div>
                    <em class="pds-kyo-shin-hero__thein">{{ number_format($summary['thein_total'], 2) }} {{ __('message.kyo_shin_thein') }}</em>
                </article>
                <div class="pds-kyo-shin-metrics">
                    <article class="pds-kyo-shin-metric is-balance is-{{ $summary['balance_sign'] }}">
                        <div class="pds-kyo-shin-metric__top">
                            <span class="pds-kyo-shin-metric__icon" aria-hidden="true"><i class="fas fa-balance-scale"></i></span>
                            <span class="pds-kyo-shin-metric__label">{{ __('message.kyo_shin_books_balance') }}</span>
                            <button type="button" class="pds-kyo-shin-history-btn" data-toggle="modal" data-target="#kyoShinLedgerModal" title="{{ __('message.kyo_shin_ledger') }}" aria-label="{{ __('message.kyo_shin_ledger') }}">
                                <i class="fas fa-history" aria-hidden="true"></i>
                            </button>
                        </div>
                        <strong>{{ $summary['balance_display'] }} <small>Ks</small></strong>
                        <em>{{ $summary['thein_balance_display'] }} {{ __('message.kyo_shin_thein') }}</em>
                    </article>
                    <article class="pds-kyo-shin-metric is-paid">
                        <div class="pds-kyo-shin-metric__top">
                            <span class="pds-kyo-shin-metric__icon" aria-hidden="true"><i class="fas fa-coins"></i></span>
                            <span class="pds-kyo-shin-metric__label">{{ __('message.kyo_shin_cash_held') }}</span>
                        </div>
                        <strong>{{ number_format($summary['cash_on_hand']) }} <small>Ks</small></strong>
                        <em>{{ number_format($summary['thein_cash_on_hand'], 2) }} {{ __('message.kyo_shin_thein') }}</em>
                    </article>
                    <article class="pds-kyo-shin-metric is-returned">
                        <div class="pds-kyo-shin-metric__top">
                            <span class="pds-kyo-shin-metric__icon" aria-hidden="true"><i class="fas fa-undo"></i></span>
                            <span class="pds-kyo-shin-metric__label">{{ __('message.kyo_shin_returned_today') }}</span>
                        </div>
                        <strong>{{ number_format($summary['returned_today']) }} <small>Ks</small></strong>
                        <em>{{ number_format($summary['thein_returned_today'], 2) }} {{ __('message.kyo_shin_thein') }}</em>
                    </article>
                    <article class="pds-kyo-shin-metric is-remain {{ $summary['os_receivable'] < 0 ? 'is-over' : '' }}">
                        <div class="pds-kyo-shin-metric__top">
                            <span class="pds-kyo-shin-metric__icon" aria-hidden="true"><i class="fas fa-file-invoice-dollar"></i></span>
                            <span class="pds-kyo-shin-metric__label">{{ __('message.kyo_shin_os_receivable') }}</span>
                        </div>
                        <strong>{{ number_format($summary['os_receivable']) }} <small>Ks</small></strong>
                        <em>{{ number_format($summary['thein_os_receivable'], 2) }} {{ __('message.kyo_shin_thein') }}</em>
                    </article>
                </div>
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

                @if(! empty($canCheck) && $rows->isNotEmpty())
                    <form method="POST" action="{{ route('order.kyo-shin.check') }}" id="kyoShinCheckForm" class="pds-kyo-shin-check-bar">
                        @csrf
                        <input type="hidden" name="scope" value="{{ $scopeKey }}">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        <input type="hidden" name="from_date" value="{{ $fromRaw }}">
                        <input type="hidden" name="to_date" value="{{ $toRaw }}">
                        <p class="pds-kyo-shin-check-bar__hint">{{ __('message.kyo_shin_check_hint') }}</p>
                        <button type="submit" class="pds-rider-check-btn" id="kyoShinCheckBtn" disabled>
                            <i class="fas fa-check" aria-hidden="true"></i>
                            <span>{{ __('message.kyo_shin_check') }}</span>
                        </button>
                    </form>
                @endif

                <div class="pds-rider-table-shell pds-rider-table-shell--scroll pds-no-freeze">
                    @if(! empty($showItemList))
                        @if(($listItems ?? collect())->isEmpty())
                            <div class="pds-os-settlement-section__empty">
                                <p>{{ __('message.kyo_shin_empty') }}</p>
                            </div>
                        @else
                            <table class="table pds-rider-list-table pds-os-settlement-table">
                                <thead>
                                    <tr>
                                        <th class="pds-rider-col-no">{{ __('message.no') }}</th>
                                        <th>{{ __('message.code') }}</th>
                                        <th>{{ __('message.os_name') }}</th>
                                        <th>{{ __('message.customer_name') }}</th>
                                        <th>{{ __('message.phone') }}</th>
                                        <th>{{ __('message.kyo_shin_paid_date') }}</th>
                                        <th>{{ __('message.kyo_shin_due_date') }}</th>
                                        @if($tab === \App\Services\KyoShinService::TAB_FINISHED)
                                            <th>{{ __('message.kyo_shin_finished_date') }}</th>
                                        @endif
                                        <th class="text-right">{{ __('message.amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listItems as $index => $item)
                                        @php
                                            $osName = resolveDispatchOsName($item->order);
                                            $due = $item->kyoShinItem?->due_finished_at
                                                ? \Carbon\Carbon::parse($item->kyoShinItem->due_finished_at)->toDateString()
                                                : null;
                                            $overdueDays = 0;
                                            if ($due && $tab !== \App\Services\KyoShinService::TAB_FINISHED) {
                                                $today = now('Asia/Yangon')->toDateString();
                                                if ($due < $today) {
                                                    $overdueDays = (int) \Carbon\Carbon::parse($due, 'Asia/Yangon')->diffInDays(\Carbon\Carbon::parse($today, 'Asia/Yangon'));
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <td class="pds-rider-col-no">{{ $index + 1 }}</td>
                                            <td>{{ $item->code ?: '-' }}</td>
                                            <td>{{ $osName }}</td>
                                            <td>{{ $item->customer_name ?: '-' }}</td>
                                            <td>{{ $item->customer_phone ?: '-' }}</td>
                                            <td>
                                                {{ $item->kyoShinItem?->advanced_paid_at
                                                    ? $item->kyoShinItem->advanced_paid_at->timezone('Asia/Yangon')->format('d-m-Y')
                                                    : '-' }}
                                            </td>
                                            <td>
                                                @include('order.partials._kyo-shin-due-editor', [
                                                    'dueValue' => $due ? \Carbon\Carbon::parse($due)->format('d-m-Y') : '',
                                                    'overdueDays' => $overdueDays,
                                                    'canEditDue' => $canEditDue ?? false,
                                                    'saveUrl' => route('order.kyo-shin.item-due-date', $item->id),
                                                    'itemId' => $item->id,
                                                ])
                                            </td>
                                            @if($tab === \App\Services\KyoShinService::TAB_FINISHED)
                                                <td>
                                                    {{ $item->kyoShinItem?->finished_at
                                                        ? $item->kyoShinItem->finished_at->timezone('Asia/Yangon')->format('d-m-Y')
                                                        : ($item->admin_finished_at ? \Carbon\Carbon::parse($item->admin_finished_at)->timezone('Asia/Yangon')->format('d-m-Y') : '-') }}
                                                </td>
                                            @endif
                                            <td class="text-right">
                                                <span class="pds-os-amount-pill is-negative">{{ number_format((float) ($item->kyo_shin_amount ?? 0)) }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                @php
                                    $listAmountTotal = (float) $listItems->sum(fn ($item) => (float) ($item->kyo_shin_amount ?? 0));
                                    $listAmountColspan = 7 + ($tab === \App\Services\KyoShinService::TAB_FINISHED ? 1 : 0);
                                @endphp
                                <tfoot>
                                    <tr class="pds-daily-check-total-row">
                                        <td colspan="{{ $listAmountColspan }}" class="pds-daily-check-total-label">{{ __('message.total') }}</td>
                                        <td class="text-right">
                                            <span class="pds-os-amount-pill is-negative">{{ number_format($listAmountTotal) }}</span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        @endif
                    @elseif($rows->isEmpty())
                        <div class="pds-os-settlement-section__empty">
                            <p>{{ __('message.kyo_shin_empty') }}</p>
                        </div>
                    @else
                        <table class="table pds-rider-list-table pds-os-settlement-table">
                            <thead>
                                <tr>
                                    <th class="pds-rider-col-no">{{ __('message.no') }}</th>
                                    @if(! empty($showCheckStatus))
                                        <th class="pds-rider-sticky-col pds-rider-sticky-col--check">
                                            @if(! empty($canCheck))
                                                <input type="checkbox" id="kyoShinSelectAll" title="{{ __('message.select_all') }}">
                                            @endif
                                        </th>
                                    @endif
                                    <th>{{ __('message.os_name') }}</th>
                                    <th class="text-right">{{ __('message.kyo_shin_total_advanced_paid') }}</th>
                                    @if($tab === \App\Services\KyoShinService::TAB_OS_LIST)
                                        <th class="text-right">{{ __('message.kyo_shin_remain') }}</th>
                                        <th class="text-right">{{ __('message.kyo_shin_advanced_paid') }}</th>
                                    @endif
                                    <th class="text-center">{{ __('message.kyo_shin_total_parcels') }}</th>
                                    @if($tab === \App\Services\KyoShinService::TAB_OS_LIST)
                                        <th>{{ __('message.kyo_shin_due_date') }}</th>
                                        <th>{{ __('message.kyo_shin_created_date') }}</th>
                                    @elseif($tab === \App\Services\KyoShinService::TAB_ADVANCED_PAID)
                                        <th>{{ __('message.kyo_shin_finished_date') }}</th>
                                        <th>{{ __('message.kyo_shin_due_date') }}</th>
                                    @else
                                        <th>{{ __('message.kyo_shin_return_date') }}</th>
                                        <th>{{ __('message.kyo_shin_due_date') }}</th>
                                        <th>{{ __('message.action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $groupField = $tab === \App\Services\KyoShinService::TAB_OS_LIST
                                        ? 'created_at'
                                        : ($tab === \App\Services\KyoShinService::TAB_ADVANCED_PAID ? 'finished_at' : 'return_at');
                                    $groupedRows = $rows
                                        ->groupBy(function ($row) use ($groupField) {
                                            $value = $row->{$groupField} ?? null;
                                            if (empty($value)) {
                                                return 'unknown';
                                            }

                                            return \Carbon\Carbon::parse($value, 'Asia/Yangon')->toDateString();
                                        })
                                        ->sortKeysDesc();
                                    $isOsListTab = $tab === \App\Services\KyoShinService::TAB_OS_LIST;
                                    $colspan = ($isOsListTab ? 8 : 6)
                                        + (! empty($showCheckStatus) ? 1 : 0)
                                        + ($tab === \App\Services\KyoShinService::TAB_FINISHED ? 1 : 0);
                                    $rowNo = 0;
                                @endphp
                                @foreach($groupedRows as $finishedDay => $dayRows)
                                    <tr class="pds-os-receive-date-group">
                                        <td colspan="{{ $colspan }}">
                                            <div class="pds-os-receive-date-group__label">
                                                <i class="far fa-calendar-check" aria-hidden="true"></i>
                                                <span>
                                                    @if($finishedDay === 'unknown')
                                                        —
                                                    @else
                                                        {{ \Carbon\Carbon::parse($finishedDay)->timezone('Asia/Yangon')->format('jS M, Y') }}
                                                    @endif
                                                </span>
                                                <em>{{ $dayRows->count() }}</em>
                                            </div>
                                        </td>
                                    </tr>
                                    @foreach($dayRows as $row)
                                    @php
                                        $rowNo++;
                                        $initial = mb_strtoupper(mb_substr(trim($row->name) ?: 'O', 0, 1));
                                        $dueValue = ! empty($row->due_finished_at)
                                            ? \Carbon\Carbon::parse($row->due_finished_at)->format('d-m-Y')
                                            : '-';
                                        $createdValue = ! empty($row->created_at)
                                            ? \Carbon\Carbon::parse($row->created_at, 'Asia/Yangon')->format('d-m-Y')
                                            : '-';
                                        $finishedValue = ! empty($row->finished_at)
                                            ? \Carbon\Carbon::parse($row->finished_at)->format('d-m-Y')
                                            : '-';
                                        $returnValue = ! empty($row->return_at)
                                            ? \Carbon\Carbon::parse($row->return_at)->format('d-m-Y')
                                            : '-';
                                    @endphp
                                    <tr>
                                        <td class="pds-rider-col-no">{{ $rowNo }}</td>
                                        @if(! empty($showCheckStatus))
                                            <td class="pds-rider-sticky-col pds-rider-sticky-col--check">
                                                @if($tab === \App\Services\KyoShinService::TAB_OS_LIST)
                                                    @if(! empty($row->all_checked))
                                                        <span class="pds-kyo-shin-checked-mark" title="{{ __('message.kyo_shin_already_checked') }}" aria-label="{{ __('message.kyo_shin_already_checked') }}">
                                                            <i class="fas fa-check" aria-hidden="true"></i>
                                                        </span>
                                                    @endif
                                                @elseif((int) ($row->pending_returned_count ?? 0) > 0)
                                                    <input
                                                        type="checkbox"
                                                        class="pds-kyo-shin-os-check"
                                                        form="kyoShinCheckForm"
                                                        name="os_ids[]"
                                                        value="{{ $row->id }}"
                                                    >
                                                @elseif((int) ($row->unchecked_count ?? 0) <= 0)
                                                    <span class="pds-kyo-shin-checked-mark" title="{{ __('message.kyo_shin_already_checked') }}" aria-label="{{ __('message.kyo_shin_already_checked') }}">
                                                        <i class="fas fa-check" aria-hidden="true"></i>
                                                    </span>
                                                @endif
                                            </td>
                                        @endif
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
                                            <span class="pds-os-amount-pill is-negative">{{ number_format((float) ($row->total_advanced_paid ?? $row->amount ?? 0)) }}</span>
                                        </td>
                                        @if($tab === \App\Services\KyoShinService::TAB_OS_LIST)
                                            <td class="text-right">{{ number_format((float) ($row->remain ?? 0)) }}</td>
                                            <td class="text-right">{{ number_format((float) ($row->advanced_paid ?? 0)) }}</td>
                                        @endif
                                        <td class="text-center">
                                            @php $parcelCount = (int) ($row->tab_item_count ?? $row->item_count ?? 0); @endphp
                                            @if($parcelCount > 0)
                                                <a
                                                    href="{{ route('order.kyo-shin.items', [
                                                        'osId' => $row->id,
                                                        'scope' => $scopeKey,
                                                        'tab' => $tab,
                                                        'from_date' => $fromRaw,
                                                        'to_date' => $toRaw,
                                                    ]) }}"
                                                    class="pds-rider-count pds-rider-count--kyo-shin is-link"
                                                    title="{{ __('message.kyo_shin_details') }}"
                                                >{{ $parcelCount }}</a>
                                            @else
                                                <span class="pds-rider-count pds-rider-count--muted">0</span>
                                            @endif
                                        </td>
                                        @if($tab === \App\Services\KyoShinService::TAB_OS_LIST)
                                            <td>
                                                @include('order.partials._kyo-shin-due-editor', [
                                                    'dueValue' => $dueValue !== '-' ? $dueValue : '',
                                                    'overdueDays' => (int) ($row->overdue_days ?? 0),
                                                    'canEditDue' => $canEditDue ?? false,
                                                    'saveUrl' => route('order.kyo-shin.due-date', $row->id),
                                                    'osId' => $row->id,
                                                ])
                                            </td>
                                            <td>{{ $createdValue }}</td>
                                        @elseif($tab === \App\Services\KyoShinService::TAB_ADVANCED_PAID)
                                            <td>{{ $finishedValue }}</td>
                                            <td>
                                                @include('order.partials._kyo-shin-due-editor', [
                                                    'dueValue' => $dueValue !== '-' ? $dueValue : '',
                                                    'overdueDays' => (int) ($row->overdue_days ?? 0),
                                                    'canEditDue' => $canEditDue ?? false,
                                                    'saveUrl' => route('order.kyo-shin.due-date', $row->id),
                                                    'osId' => $row->id,
                                                ])
                                            </td>
                                        @else
                                            <td>{{ $returnValue }}</td>
                                            <td>
                                                @include('order.partials._kyo-shin-due-editor', [
                                                    'dueValue' => $dueValue !== '-' ? $dueValue : '',
                                                    'overdueDays' => (int) ($row->overdue_days ?? 0),
                                                    'canEditDue' => $canEditDue ?? false,
                                                    'saveUrl' => route('order.kyo-shin.due-date', $row->id),
                                                    'osId' => $row->id,
                                                ])
                                            </td>
                                            <td>
                                                <form method="POST" action="{{ route('order.kyo-shin.send-to-os', $row->id) }}" class="pds-kyo-shin-send-os-form">
                                                    @csrf
                                                    <input type="hidden" name="scope" value="{{ $scopeKey }}">
                                                    <input type="hidden" name="tab" value="{{ $tab }}">
                                                    <input type="hidden" name="from_date" value="{{ $fromRaw }}">
                                                    <input type="hidden" name="to_date" value="{{ $toRaw }}">
                                                    <button type="submit" class="pds-kyo-shin-send-os-btn" title="{{ __('message.kyo_shin_send_to_os') }}">
                                                        <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                                        <span>{{ __('message.kyo_shin_send_to_os') }}</span>
                                                    </button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                            @php
                                $totalAdvancedPaid = (float) $rows->sum(fn ($row) => (float) ($row->total_advanced_paid ?? $row->amount ?? 0));
                                $totalRemain = (float) $rows->sum(fn ($row) => (float) ($row->remain ?? 0));
                                $totalAdvanced = (float) $rows->sum(fn ($row) => (float) ($row->advanced_paid ?? 0));
                                $totalParcels = (int) $rows->sum(fn ($row) => (int) ($row->tab_item_count ?? $row->item_count ?? 0));
                                $labelColspan = 2 + (! empty($showCheckStatus) ? 1 : 0);
                            @endphp
                            <tfoot>
                                <tr class="pds-daily-check-total-row">
                                    <td colspan="{{ $labelColspan }}" class="pds-daily-check-total-label">{{ __('message.total') }}</td>
                                    <td class="text-right">
                                        <span class="pds-os-amount-pill is-negative">{{ number_format($totalAdvancedPaid) }}</span>
                                    </td>
                                    @if($tab === \App\Services\KyoShinService::TAB_OS_LIST)
                                        <td class="text-right">{{ number_format($totalRemain) }}</td>
                                        <td class="text-right">{{ number_format($totalAdvanced) }}</td>
                                    @endif
                                    <td class="text-center">{{ number_format($totalParcels) }}</td>
                                    <td></td>
                                    <td></td>
                                    @if($tab === \App\Services\KyoShinService::TAB_FINISHED)
                                        <td></td>
                                    @endif
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade pds-dispatch-modal" id="kyoShinLedgerModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg pds-kyo-shin-ledger-dialog">
            <div class="modal-content pds-kyo-shin-modal pds-kyo-shin-ledger">
                <div class="pds-kyo-shin-ledger__header">
                    <div class="pds-kyo-shin-ledger__heading">
                        <span class="pds-kyo-shin-ledger__icon" aria-hidden="true">
                            <i class="fas fa-book-open"></i>
                        </span>
                        <div>
                            <h5 class="pds-kyo-shin-ledger__title">{{ __('message.kyo_shin_ledger_title') }}</h5>
                            <p class="pds-kyo-shin-ledger__sub">{{ __('message.kyo_shin_ledger_sub') }}</p>
                        </div>
                    </div>
                    <button type="button" class="pds-kyo-shin-ledger__close" data-dismiss="modal" aria-label="{{ __('message.close') }}">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="pds-kyo-shin-ledger__body">
                    @php $ledgers = $ledgers ?? collect(); @endphp
                    @if($ledgers->isEmpty())
                        <div class="pds-kyo-shin-ledger__empty">
                            <i class="far fa-folder-open" aria-hidden="true"></i>
                            <p>{{ __('message.kyo_shin_ledger_empty') }}</p>
                        </div>
                    @else
                        <div class="pds-kyo-shin-ledger__list">
                            @foreach($ledgers as $ledger)
                                @php
                                    $balance = (float) $ledger->balance;
                                    $sign = $balance > 0.004 ? 'plus' : ($balance < -0.004 ? 'minus' : 'zero');
                                    $balanceDisplay = (round($balance) > 0 ? '+' : '').number_format($balance);
                                    $dayLabel = optional($ledger->ledger_date)
                                        ? $ledger->ledger_date->timezone('Asia/Yangon')->format('jS M, Y')
                                        : '—';
                                @endphp
                                <article class="pds-kyo-shin-ledger__day">
                                    <header class="pds-kyo-shin-ledger__day-head">
                                        <div class="pds-kyo-shin-ledger__day-label">
                                            <i class="far fa-calendar-check" aria-hidden="true"></i>
                                            <span>{{ $dayLabel }}</span>
                                        </div>
                                    </header>
                                    <div class="pds-kyo-shin-ledger__grid">
                                        <div class="pds-kyo-shin-ledger__metric is-total">
                                            <span>{{ __('message.kyo_shin_sa_amount') }}</span>
                                            <strong>{{ number_format($ledger->sa_amount) }} <small>Ks</small></strong>
                                        </div>
                                        <div class="pds-kyo-shin-ledger__metric is-balance is-{{ $sign }}">
                                            <span>{{ __('message.kyo_shin_books_balance') }}</span>
                                            <strong>{{ $balanceDisplay }} <small>Ks</small></strong>
                                        </div>
                                        <div class="pds-kyo-shin-ledger__metric is-held">
                                            <span>{{ __('message.kyo_shin_cash_held') }}</span>
                                            <strong>{{ number_format($ledger->cash_on_hand ?? (float) $ledger->cash_held) }} <small>Ks</small></strong>
                                        </div>
                                        <div class="pds-kyo-shin-ledger__metric is-returned">
                                            <span>{{ __('message.kyo_shin_returned_today') }}</span>
                                            <strong>{{ number_format($ledger->returned_today ?? $ledger->returned_amount ?? 0) }} <small>Ks</small></strong>
                                        </div>
                                        <div class="pds-kyo-shin-ledger__metric is-remain">
                                            <span>{{ __('message.kyo_shin_os_receivable') }}</span>
                                            <strong>{{ number_format($ledger->os_receivable) }} <small>Ks</small></strong>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
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
            gap: 12px;
            margin: 4px 0 16px;
        }
        .pds-kyo-shin-hero {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 20px;
            border-radius: 20px;
            border: 1px solid #ffd7b0;
            background: linear-gradient(135deg, #fff7ed 0%, #fff 58%, #fffaf5 100%);
            box-shadow: 0 12px 28px rgba(154, 52, 18, 0.06);
        }
        .pds-kyo-shin-hero__icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            color: #fff;
            background: linear-gradient(135deg, #FE6F07, #ff8f3d);
            box-shadow: 0 10px 20px rgba(254, 111, 7, 0.28);
            font-size: 20px;
        }
        .pds-kyo-shin-hero__copy {
            min-width: 0;
            flex: 1;
        }
        .pds-kyo-shin-hero__copy span {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #9a3412;
            letter-spacing: .01em;
        }
        .pds-kyo-shin-hero__copy strong {
            display: block;
            margin-top: 2px;
            font-size: 1.7rem;
            line-height: 1.15;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #1c1917;
            font-variant-numeric: tabular-nums;
        }
        .pds-kyo-shin-hero__copy small,
        .pds-kyo-shin-metric strong small {
            font-size: 0.72em;
            font-weight: 700;
            color: #78716c;
        }
        .pds-kyo-shin-hero__thein {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 0 12px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid #fed7aa;
            color: #c2410c;
            font-size: 13px;
            font-weight: 800;
            font-style: normal;
            white-space: nowrap;
        }
        .pds-kyo-shin-metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }
        .pds-kyo-shin-metric {
            min-width: 0;
            padding: 16px;
            border-radius: 18px;
            border: 1px solid #ece7df;
            background: #fff;
            box-shadow: 0 8px 20px rgba(26, 23, 20, 0.04);
        }
        .pds-kyo-shin-metric__top {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        .pds-kyo-shin-metric__icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            font-size: 13px;
            background: #f8fafc;
            color: #64748b;
        }
        .pds-kyo-shin-metric__label {
            flex: 1;
            min-width: 0;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.35;
            color: #57534e;
        }
        .pds-kyo-shin-metric strong {
            display: block;
            font-size: 1.28rem;
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #1c1917;
            font-variant-numeric: tabular-nums;
        }
        .pds-kyo-shin-metric em {
            display: block;
            margin-top: 4px;
            font-style: normal;
            font-size: 12px;
            font-weight: 700;
            color: #94a3b8;
        }
        .pds-kyo-shin-metric.is-paid { border-color: #bbf7d0; background: linear-gradient(180deg, #f0fdf4 0%, #fff 70%); }
        .pds-kyo-shin-metric.is-paid .pds-kyo-shin-metric__icon { background: #dcfce7; color: #15803d; }
        .pds-kyo-shin-metric.is-returned { border-color: #fde68a; background: linear-gradient(180deg, #fffbeb 0%, #fff 70%); }
        .pds-kyo-shin-metric.is-returned .pds-kyo-shin-metric__icon { background: #fef3c7; color: #b45309; }
        .pds-kyo-shin-metric.is-remain { border-color: #bfdbfe; background: linear-gradient(180deg, #eff6ff 0%, #fff 70%); }
        .pds-kyo-shin-metric.is-remain .pds-kyo-shin-metric__icon { background: #dbeafe; color: #1d4ed8; }
        .pds-kyo-shin-metric.is-over { border-color: #fecaca; background: linear-gradient(180deg, #fef2f2 0%, #fff 70%); }
        .pds-kyo-shin-metric.is-over .pds-kyo-shin-metric__icon { background: #fee2e2; color: #b91c1c; }
        .pds-kyo-shin-metric.is-balance.is-zero { border-color: #e2e8f0; background: linear-gradient(180deg, #f8fafc 0%, #fff 70%); }
        .pds-kyo-shin-metric.is-balance.is-plus { border-color: #bbf7d0; background: linear-gradient(180deg, #f0fdf4 0%, #fff 70%); }
        .pds-kyo-shin-metric.is-balance.is-minus { border-color: #fecaca; background: linear-gradient(180deg, #fef2f2 0%, #fff 70%); }
        .pds-kyo-shin-metric.is-balance.is-plus strong { color: #047857; }
        .pds-kyo-shin-metric.is-balance.is-minus strong { color: #b91c1c; }
        .pds-kyo-shin-history-btn {
            flex: 0 0 auto;
            width: 30px;
            height: 30px;
            margin: 0;
            border: 1px solid #fdba74;
            border-radius: 999px;
            background: #fff;
            color: #c2410c;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            line-height: 1;
            padding: 0;
            cursor: pointer;
        }
        .pds-kyo-shin-history-btn:hover,
        .pds-kyo-shin-history-btn:focus {
            background: #ea580c;
            border-color: #ea580c;
            color: #fff;
            outline: none;
        }
        .pds-kyo-shin-ledger-dialog { max-width: 760px; }
        #kyoShinLedgerModal .pds-kyo-shin-ledger {
            border: 0;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 28px 70px rgba(28, 25, 23, .2);
            background: #fffdf9;
        }
        .pds-kyo-shin-ledger__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 22px 22px 18px;
            background: linear-gradient(180deg, #fff7ed 0%, #fffdf9 100%);
            border-bottom: 1px solid #ffedd5;
        }
        .pds-kyo-shin-ledger__heading { display: flex; align-items: flex-start; gap: 14px; min-width: 0; }
        .pds-kyo-shin-ledger__icon {
            width: 46px; height: 46px; border-radius: 14px; display: grid; place-items: center;
            background: linear-gradient(135deg, #FE6F07, #ff8f3d); color: #fff;
            box-shadow: 0 10px 20px rgba(254, 111, 7, .28); flex-shrink: 0;
        }
        .pds-kyo-shin-ledger__title { margin: 0; font-size: 1.2rem; font-weight: 800; color: #1c1917; line-height: 1.3; }
        .pds-kyo-shin-ledger__sub { margin: 4px 0 0; font-size: 13px; font-weight: 600; color: #9a3412; }
        .pds-kyo-shin-ledger__close {
            width: 36px; height: 36px; border: 0; border-radius: 10px; background: #fff; color: #78716c;
            box-shadow: inset 0 0 0 1px #e7e5e4; cursor: pointer;
        }
        .pds-kyo-shin-ledger__close:hover { color: #1c1917; background: #f5f5f4; }
        .pds-kyo-shin-ledger__body { padding: 16px 18px 20px; max-height: 68vh; overflow: auto; background: #fffdf9; }
        .pds-kyo-shin-ledger__list { display: grid; gap: 14px; }
        .pds-kyo-shin-ledger__day {
            background: #fff;
            border: 1px solid #ffe4c7;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 8px 22px rgba(154, 52, 18, .05);
        }
        .pds-kyo-shin-ledger__day-head {
            padding: 12px 16px 10px;
            background: linear-gradient(90deg, #fff7ed 0%, #ffffff 72%);
            border-bottom: 1px solid #ffedd5;
        }
        .pds-kyo-shin-ledger__day-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 800;
            font-size: 0.95rem;
            color: #9a3412;
            white-space: nowrap;
        }
        .pds-kyo-shin-ledger__day-label i { color: #ea580c; }
        .pds-kyo-shin-ledger__grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            padding: 12px;
        }
        .pds-kyo-shin-ledger__metric {
            min-width: 0;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px solid #e8e0d4;
            background: #fff;
        }
        .pds-kyo-shin-ledger__metric span {
            display: block;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.4;
            color: #64748b;
        }
        .pds-kyo-shin-ledger__metric strong {
            display: block;
            margin-top: 6px;
            font-size: 1.08rem;
            font-weight: 800;
            color: #1a1714;
            letter-spacing: -0.01em;
            word-break: break-word;
        }
        .pds-kyo-shin-ledger__metric small {
            font-size: 0.72rem;
            font-weight: 700;
            color: #94a3b8;
        }
        .pds-kyo-shin-ledger__metric.is-total { border-color: #fdba74; background: #fff7ed; }
        .pds-kyo-shin-ledger__metric.is-held { border-color: #86efac; background: #f0fdf4; }
        .pds-kyo-shin-ledger__metric.is-returned { border-color: #fcd34d; background: #fffbeb; }
        .pds-kyo-shin-ledger__metric.is-remain { border-color: #93c5fd; background: #eff6ff; }
        .pds-kyo-shin-ledger__metric.is-balance.is-zero { border-color: #cbd5e1; background: #f8fafc; }
        .pds-kyo-shin-ledger__metric.is-balance.is-plus { border-color: #86efac; background: #f0fdf4; }
        .pds-kyo-shin-ledger__metric.is-balance.is-minus { border-color: #fca5a5; background: #fef2f2; }
        .pds-kyo-shin-ledger__metric.is-balance.is-plus strong { color: #047857; }
        .pds-kyo-shin-ledger__metric.is-balance.is-minus strong { color: #b91c1c; }
        .pds-kyo-shin-ledger__empty {
            display: grid;
            place-items: center;
            gap: 10px;
            padding: 42px 16px;
            color: #94a3b8;
            text-align: center;
        }
        .pds-kyo-shin-ledger__empty i { font-size: 28px; color: #fdba74; }
        .pds-kyo-shin-ledger__empty p { margin: 0; font-weight: 700; color: #78716c; }
        @media (max-width: 640px) {
            .pds-kyo-shin-ledger__grid { grid-template-columns: 1fr; }
        }
        .pds-kyo-shin-overdue { color: #b91c1c; font-weight: 700; display: inline-flex; flex-direction: column; }
        .pds-kyo-shin-overdue small { font-weight: 600; color: #dc2626; }
        .pds-kyo-shin-due-cell { display: inline-flex; flex-direction: column; gap: 4px; min-width: 148px; }
        .pds-kyo-shin-due-cell.is-overdue small { font-weight: 600; color: #dc2626; }
        .pds-kyo-shin-due-editor {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 38px;
            padding: 0 10px 0 12px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            margin: 0;
            cursor: pointer;
        }
        .pds-kyo-shin-due-cell.is-overdue .pds-kyo-shin-due-editor {
            border-color: #fecaca;
            background: #fef2f2;
        }
        .pds-kyo-shin-due-editor > i { color: #f97316; }
        .pds-kyo-shin-due-input {
            width: 92px;
            border: 0;
            background: transparent;
            font-weight: 700;
            color: #1a1714;
            outline: none;
            cursor: pointer;
        }
        .pds-kyo-shin-due-cell.is-overdue .pds-kyo-shin-due-input { color: #b91c1c; }
        .pds-kyo-shin-due-edit-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            border-radius: 8px;
            background: #fff7ed;
            color: #c2410c;
        }
        .pds-kyo-shin-due-editor:hover { border-color: #fdba74; }
        .pds-kyo-shin-due-editor:hover .pds-kyo-shin-due-edit-icon { background: #ffedd5; }
        .pds-rider-count--kyo-shin.is-link,
        body.pds-admin .pds-rider-count--kyo-shin.is-link {
            background: #fff;
            border: 2px solid #fb923c;
            color: #ea580c;
            box-shadow: none;
        }
        .pds-rider-count--kyo-shin.is-link:hover,
        body.pds-admin .pds-rider-count--kyo-shin.is-link:hover {
            background: #fff7ed;
            border-color: #f97316;
            color: #c2410c;
            text-decoration: none;
            transform: translateY(-1px);
        }
        .pds-kyo-shin-check-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 0 0 12px;
            padding: 10px 14px;
            border: 1px solid #fed7aa;
            border-radius: 14px;
            background: #fff7ed;
        }
        .pds-kyo-shin-check-bar__hint {
            margin: 0;
            color: #9a3412;
            font-size: 13px;
            font-weight: 600;
        }
        .pds-kyo-shin-check-bar .pds-rider-check-btn:disabled { opacity: .45; cursor: not-allowed; }
        .pds-kyo-shin-checked-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 999px;
            background: #16a34a;
            color: #fff;
            box-shadow: 0 0 0 3px #dcfce7;
        }
        .pds-kyo-shin-checked-mark i { font-size: 11px; line-height: 1; }
        .pds-kyo-shin-send-os-form { margin: 0; }
        .pds-kyo-shin-send-os-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.42rem 0.75rem;
            border: 0;
            border-radius: 999px;
            background: #7c3aed;
            color: #fff;
            font-size: 0.76rem;
            font-weight: 700;
            line-height: 1.2;
            white-space: nowrap;
            box-shadow: 0 6px 14px rgba(124, 58, 237, 0.22);
        }
        .pds-kyo-shin-send-os-btn:hover { background: #6d28d9; color: #fff; }
        .pds-kyo-shin-send-os-btn i { font-size: 0.72rem; }
        body.pds-admin .pds-kyo-shin-checked-mark,
        body.pds-admin .pds-kyo-shin-checked-mark i { color: #fff !important; }
        @media (max-width: 1100px) {
            .pds-kyo-shin-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 720px) {
            .pds-kyo-shin-hero { flex-wrap: wrap; }
            .pds-kyo-shin-hero__thein { margin-left: 68px; }
            .pds-kyo-shin-metrics { grid-template-columns: 1fr; }
        }
    </style>
    @push('bottom_script')
        @include('order.partials._kyo-shin-due-edit-scripts')
        <script>
            (function () {
                var form = document.getElementById('kyoShinCheckForm');
                if (!form) return;
                var all = document.getElementById('kyoShinSelectAll');
                var btn = document.getElementById('kyoShinCheckBtn');
                function boxes() {
                    return Array.prototype.slice.call(document.querySelectorAll('.pds-kyo-shin-os-check:not(:disabled)'));
                }
                function sync() {
                    var selected = boxes().filter(function (box) { return box.checked; });
                    if (btn) btn.disabled = selected.length === 0;
                    if (all) {
                        var enabled = boxes();
                        all.checked = enabled.length > 0 && selected.length === enabled.length;
                        all.indeterminate = selected.length > 0 && selected.length < enabled.length;
                    }
                }
                if (all) {
                    all.addEventListener('change', function () {
                        boxes().forEach(function (box) { box.checked = all.checked; });
                        sync();
                    });
                }
                document.addEventListener('change', function (event) {
                    if (event.target && event.target.classList.contains('pds-kyo-shin-os-check')) {
                        sync();
                    }
                });
                form.addEventListener('submit', function (event) {
                    if (boxes().filter(function (box) { return box.checked; }).length === 0) {
                        event.preventDefault();
                    }
                });
                sync();
            })();
        </script>
    @endpush
</x-master-layout>
