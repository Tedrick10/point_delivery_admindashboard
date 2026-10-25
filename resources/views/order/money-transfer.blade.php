<x-master-layout :assets="$assets ?? []">
    
<div id="adminLiveRoot" data-live-page="auto">
<div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-to-assign-page pds-daily-check-page pds-money-transfer-page">
        <div class="pds-dispatch-to-assign-screen pds-rider-screen">
            <div class="pds-rider-hero">
                <div class="pds-rider-hero__copy">
                    <div class="pds-rider-hero__eyebrow">
                        <i class="fas fa-exchange-alt" aria-hidden="true"></i>
                        <span>{{ __('message.order') }}</span>
                    </div>
                    <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                </div>
                <div class="pds-rider-hero__stat">
                    <span class="pds-rider-hero__stat-value">{{ $summary->os_count }}</span>
                    <span class="pds-rider-hero__stat-label">OS</span>
                </div>
            </div>

            @include('order.partials._settlement-tabs', ['activeTab' => 'money-transfer'])

            @include('partials._branch-tabs', [
                'branchTabs' => $branchTabs ?? $branches,
                'selectedBranchId' => $selectedBranchId ?? ($branchFilter === 'all' ? null : (int) $branchFilter),
                'branchTabCounts' => $branchTabCounts ?? [],
                'allCount' => $allBranchCount ?? null,
                'includeAll' => false,
                'routeName' => 'order.money-transfer',
                'routeQuery' => [
                    'from_date' => $filterFromDate,
                    'to_date' => $filterToDate,
                    'os_id' => $osFilter,
                    'method' => $paymentMethod ?? 'all',
                ],
            ])

            @php
                $paymentMethod = $paymentMethod ?? 'all';
                $showCashCols = in_array($paymentMethod, ['cash', 'all'], true);
                $showMethodCol = $paymentMethod === 'all';
                $showKpayAmount = in_array($paymentMethod, ['all', 'kpay'], true);
                $showCashAmount = in_array($paymentMethod, ['all', 'cash'], true);
                $methodTabQuery = request()->except('method');
            @endphp
            <div class="pds-mt-method-tabs" role="tablist">
                <a href="{{ route('order.money-transfer', array_merge($methodTabQuery, ['method' => 'all'])) }}"
                   class="pds-mt-method-tabs__btn {{ $paymentMethod === 'all' ? 'is-active' : '' }}"
                   data-method="all">
                    {{ __('message.money_transfer_tab_all') }}
                </a>
                <a href="{{ route('order.money-transfer', array_merge($methodTabQuery, ['method' => 'kpay'])) }}"
                   class="pds-mt-method-tabs__btn {{ $paymentMethod === 'kpay' ? 'is-active' : '' }}"
                   data-method="kpay">
                    {{ __('message.money_transfer_tab_kpay') }}
                </a>
                <a href="{{ route('order.money-transfer', array_merge($methodTabQuery, ['method' => 'cash'])) }}"
                   class="pds-mt-method-tabs__btn {{ $paymentMethod === 'cash' ? 'is-active' : '' }}"
                   data-method="cash">
                    {{ __('message.money_transfer_tab_cash') }}
                </a>
            </div>

            <form method="GET" action="{{ route('order.money-transfer') }}" class="pds-daily-check-toolbar" id="moneyTransferFilterForm">
                <input type="hidden" name="method" value="{{ $paymentMethod }}">
                <input type="hidden" name="branch_id" id="mt_branch" value="{{ $branchFilter }}">
                <div class="pds-daily-check-toolbar__grid pds-money-transfer-toolbar__grid">
                    <div class="pds-daily-check-field pds-daily-check-field--date">
                        <label for="mt_from_date">{{ __('message.from') }}</label>
                        <input type="text" name="from_date" id="mt_from_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $filterFromDate }}" autocomplete="off">
                    </div>
                    <div class="pds-daily-check-field pds-daily-check-field--date">
                        <label for="mt_to_date">{{ __('message.to') }}</label>
                        <input type="text" name="to_date" id="mt_to_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $filterToDate }}" autocomplete="off">
                    </div>
                    <div class="pds-daily-check-field pds-daily-check-field--party">
                        <label for="mt_os">{{ __('message.online_shopping') }}</label>
                        <select name="os_id" id="mt_os" class="pds-dispatch-input pds-dispatch-select">
                            <option value="all" @selected($osFilter === 'all')>{{ __('message.all') }}</option>
                            <option value="0" @selected((string) $osFilter === '0')>{{ __('message.no_os') }}</option>
                            @foreach($osOptions as $option)
                                @php $osPhone = normalizeContactNumber($option->contact_number ?? ''); @endphp
                                <option value="{{ $option->id }}"
                                        data-phone="{{ $osPhone }}"
                                        @selected((string) $osFilter === (string) $option->id)>{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pds-daily-check-toolbar__actions">
                        <button type="submit" class="pds-daily-check-search-btn" title="{{ __('message.check') }}">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <span>{{ __('message.check') }}</span>
                        </button>
                    </div>
                </div>
            </form>

            <div class="pds-money-transfer-summary" id="mtSummary">
                <div class="pds-money-transfer-summary__card is-ostopay">
                    <span class="pds-money-transfer-summary__label">{{ __('message.os_to_pay') }}</span>
                    <strong class="pds-money-transfer-summary__value" data-summary="amount_due">{{ number_format($summary->amount_due) }}</strong>
                    <em class="pds-money-transfer-summary__hint">{{ __('message.money_transfer_total_hint') }}</em>
                </div>
                @if($showKpayAmount)
                    <div class="pds-money-transfer-summary__card is-kpay">
                        <span class="pds-money-transfer-summary__label">{{ __('message.money_transfer_kpay_amount') }}</span>
                        <strong class="pds-money-transfer-summary__value" data-summary="kpay_total">{{ number_format($summary->kpay_total ?? 0) }}</strong>
                    </div>
                @endif
                @if($showCashAmount)
                    <div class="pds-money-transfer-summary__card is-cash">
                        <span class="pds-money-transfer-summary__label">{{ __('message.money_transfer_cash_amount') }}</span>
                        <strong class="pds-money-transfer-summary__value" data-summary="cash_total">{{ number_format($summary->cash_total ?? 0) }}</strong>
                    </div>
                @endif
                <div class="pds-money-transfer-summary__card">
                    <span class="pds-money-transfer-summary__label">OS</span>
                    <strong class="pds-money-transfer-summary__value" data-summary="os_count">{{ $summary->os_count }}</strong>
                </div>
            </div>

            <div class="pds-rider-body">
                @if($rows->isEmpty())
                    <div class="pds-money-transfer-empty">
                        <div class="pds-money-transfer-empty__icon"><i class="fas fa-exchange-alt"></i></div>
                        <h5>{{ __('message.money_transfer_empty_title') }}</h5>
                        <p>{{ __('message.money_transfer_empty') }}</p>
                        <div class="pds-money-transfer-empty__actions">
                            <a href="{{ route('order.dispatch.os-list', ['from_date' => $filterFromDate, 'to_date' => $filterToDate, 'branch_id' => $branchFilter]) }}"
                               class="pds-daily-check-search-btn">
                                {{ __('message.money_transfer_go_os_list') }}
                            </a>
                            <a href="{{ route('order.daily-checklist', ['from_date' => $filterFromDate, 'to_date' => $filterToDate, 'branch_id' => $branchFilter, 'mode' => 'os']) }}"
                               class="pds-money-transfer-secondary-btn">
                                {{ __('message.daily_check_list') }}
                            </a>
                        </div>
                    </div>
                @else
                    @php
                        $showKpayDetailCols = in_array($paymentMethod, ['all', 'kpay'], true);
                        $mtHeadCols = [
                            ['label' => __('message.no'), 'class' => '', 'col' => 'mt-col-no'],
                            ['label' => __('message.os_name'), 'class' => '', 'col' => 'mt-col-os'],
                        ];
                        if ($showKpayDetailCols) {
                            $mtHeadCols[] = ['label' => __('message.money_transfer_col_os_kpay'), 'class' => '', 'col' => 'mt-col-os-kpay'];
                            $mtHeadCols[] = ['label' => __('message.money_transfer_col_admin_kpay'), 'class' => '', 'col' => 'mt-col-admin-kpay', 'title' => __('message.admin_kbz_pay_account')];
                        }
                        if ($showMethodCol) {
                            $mtHeadCols[] = ['label' => __('message.money_transfer_col_method'), 'class' => '', 'col' => 'mt-col-method', 'title' => __('message.payment_method')];
                        }
                        $mtHeadCols[] = ['label' => __('message.os_to_pay'), 'class' => 'text-right', 'title' => __('message.money_transfer_col_help_due'), 'col' => 'mt-col-due'];
                        if ($showCashCols) {
                            $mtHeadCols[] = ['label' => __('message.delivery_man'), 'class' => '', 'col' => 'mt-col-rider'];
                            $mtHeadCols[] = ['label' => __('message.status'), 'class' => '', 'col' => 'mt-col-status'];
                            $mtHeadCols[] = ['label' => __('message.remark_label'), 'class' => '', 'col' => 'mt-col-remark'];
                            $mtHeadCols[] = ['label' => __('message.money_transfer_col_finish'), 'class' => 'pds-mt-photo-col', 'col' => 'mt-col-photo', 'title' => __('message.finish_image')];
                            $mtHeadCols[] = ['label' => __('message.money_transfer_col_pending'), 'class' => 'pds-mt-photo-col', 'col' => 'mt-col-photo', 'title' => __('message.pending_image')];
                            $mtHeadCols[] = ['label' => __('message.action'), 'class' => 'text-center', 'col' => 'mt-col-action'];
                        } else {
                            $mtHeadCols[] = ['label' => __('message.kpay_slip'), 'class' => '', 'col' => 'mt-col-photo'];
                            $mtHeadCols[] = ['label' => __('message.action'), 'class' => 'text-center', 'col' => 'mt-col-action'];
                        }
                        $mtDueColIndex = collect($mtHeadCols)->search(static fn ($c) => ($c['col'] ?? '') === 'mt-col-due');
                        if ($mtDueColIndex === false) {
                            $mtDueColIndex = 2;
                        }
                        $mtTotalDue = (float) $rows->sum(function ($r) { return (float) ($r->amount_due ?? 0); });
                        $mtColCount = count($mtHeadCols);
                        $mtTableClass = 'table pds-rider-list-table pds-daily-check-table pds-money-transfer-table '.($showCashCols ? 'is-cash-cols' : 'is-kpay-compact').($showKpayDetailCols ? ' has-kpay-detail-cols' : '');
                    @endphp
                    <div class="pds-frozen-table pds-daily-check-shell pds-money-transfer-shell" id="moneyTransferFrozen" data-due-col="{{ $mtDueColIndex }}">
                        <div class="pds-frozen-table__head">
                            <table class="{{ $mtTableClass }}">
                                <colgroup>
                                    @foreach($mtHeadCols as $col)
                                        <col class="{{ $col['col'] }}">
                                    @endforeach
                                </colgroup>
                                <thead>
                                    <tr>
                                        @foreach($mtHeadCols as $col)
                                            <th class="{{ $col['class'] }}" @if(!empty($col['title'])) title="{{ $col['title'] }}" @endif>{{ $col['label'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        <div class="pds-frozen-table__body">
                        <table class="{{ $mtTableClass }}" id="moneyTransferTable">
                            <colgroup>
                                @foreach($mtHeadCols as $col)
                                    <col class="{{ $col['col'] }}">
                                @endforeach
                            </colgroup>
                            <tbody>
                                @foreach($rows as $index => $row)
                                    @php $rowMethod = (string) ($row->payment_method ?? $paymentMethod); @endphp
                                    <tr class="pds-money-transfer-row"
                                        data-os-user-id="{{ $row->os_user_id }}"
                                        data-amount-due="{{ $row->amount_due }}"
                                        data-transfer-id="{{ $row->transfer_id ?? '' }}"
                                        data-payment-method="{{ $row->payment_method ?? $paymentMethod }}">
                                        <td class="pds-money-transfer-no">{{ $index + 1 }}</td>
                                        <td class="pds-money-transfer-os-cell">
                                            <div class="pds-money-transfer-os">
                                                <div class="pds-money-transfer-os__identity">
                                                    <strong class="pds-money-transfer-os__name">{{ $row->name }}</strong>
                                                    @if(!empty($row->os_phone))
                                                        <span class="pds-money-transfer-os__phone">
                                                            <i class="fas fa-phone-alt" aria-hidden="true"></i>{{ $row->os_phone }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        @if($showKpayDetailCols)
                                            <td class="pds-mt-kpay-col">
                                                @if($rowMethod === 'kpay' && ($row->kpay_name || $row->kpay_no))
                                                    <div class="pds-mt-pay-chip pds-mt-pay-chip--shop"
                                                         title="{{ trim(($row->kpay_name ?? '').' '.($row->kpay_no ?? '')) }}">
                                                        <span class="pds-mt-pay-chip__icon" aria-hidden="true"><i class="fas fa-wallet"></i></span>
                                                        <div class="pds-mt-pay-chip__body">
                                                            @if($row->kpay_name)
                                                                <strong>{{ $row->kpay_name }}</strong>
                                                            @endif
                                                            @if($row->kpay_no)
                                                                <span>{{ $row->kpay_no }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="pds-mt-empty">—</span>
                                                @endif
                                            </td>
                                            <td class="pds-mt-kpay-col">
                                                @if($rowMethod === 'kpay' && (!empty($row->admin_kpay_name) || !empty($row->admin_kpay_phone)))
                                                    <div class="pds-mt-pay-chip pds-mt-pay-chip--admin"
                                                         title="{{ trim(($row->admin_kpay_name ?? '').' '.($row->admin_kpay_phone ?? '')) }}">
                                                        <span class="pds-mt-pay-chip__icon" aria-hidden="true"><i class="fas fa-user-shield"></i></span>
                                                        <div class="pds-mt-pay-chip__body">
                                                            @if(!empty($row->admin_kpay_name))
                                                                <strong>{{ $row->admin_kpay_name }}</strong>
                                                            @endif
                                                            @if(!empty($row->admin_kpay_phone))
                                                                <span>{{ $row->admin_kpay_phone }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="pds-mt-empty">—</span>
                                                @endif
                                            </td>
                                        @endif
                                        @if($showMethodCol)
                                            <td class="pds-mt-method-cell">
                                                <span class="pds-mt-method-badge {{ $rowMethod === 'cash' ? 'is-cash' : 'is-kpay' }}">
                                                    <i class="fas {{ $rowMethod === 'cash' ? 'fa-money-bill-wave' : 'fa-mobile-alt' }}" aria-hidden="true"></i>
                                                    {{ $rowMethod === 'cash' ? __('message.money_transfer_tab_cash') : __('message.money_transfer_tab_kpay') }}
                                                </span>
                                            </td>
                                        @endif
                                        <td class="text-right pds-money-transfer-due">
                                            {{ number_format((float) $row->amount_due) }}
                                        </td>
                                        @if($showCashCols)
                                            @if($rowMethod === 'cash')
                                            <td>
                                                @if(!empty($row->delivery_man_name))
                                                    {{ $row->delivery_man_name }}
                                                    @if(!empty($row->delivery_man_phone))
                                                        <div class="text-muted small">{{ $row->delivery_man_phone }}</div>
                                                    @endif
                                                @else
                                                    <span class="pds-mt-empty">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="pds-mt-status-badge">{{ $row->cash_payout_status ?? 'unassigned' }}</span>
                                            </td>
                                            <td class="pds-mt-remark-cell">
                                                @php
                                                    $hasPendingRemark = filled($row->pending_note ?? null);
                                                    $hasDoneRemark = filled($row->done_note ?? null);
                                                @endphp
                                                @if($hasPendingRemark)
                                                    <div class="pds-mt-remark">
                                                        <span class="pds-mt-remark__tag is-pending">Pending</span>
                                                        <div class="pds-mt-remark__text">{{ $row->pending_note }}</div>
                                                    </div>
                                                @endif
                                                @if($hasDoneRemark)
                                                    <div class="pds-mt-remark {{ $hasPendingRemark ? 'is-stacked' : '' }}">
                                                        <span class="pds-mt-remark__tag is-done">Done</span>
                                                        <div class="pds-mt-remark__text">{{ $row->done_note }}</div>
                                                    </div>
                                                @endif
                                                @if(! $hasPendingRemark && ! $hasDoneRemark)
                                                    <span class="pds-mt-empty">—</span>
                                                @endif
                                            </td>
                                            @php
                                                $finishUrls = collect($row->kpay_slip_urls ?? [])
                                                    ->filter(static fn ($u) => is_string($u) && trim($u) !== '')
                                                    ->values()
                                                    ->all();
                                                if ($finishUrls === [] && ! empty($row->kpay_slip_url)) {
                                                    $finishUrls = [(string) $row->kpay_slip_url];
                                                }
                                                if (! empty($row->done_photo_url) && $finishUrls === []) {
                                                    $finishUrls = [(string) $row->done_photo_url];
                                                }
                                                $pendingUrl = $row->pending_photo_url ?? null;
                                            @endphp
                                            <td class="pds-mt-photo-cell">
                                                @include('order.partials._money-transfer-photo', [
                                                    'urls' => $finishUrls,
                                                    'title' => __('message.finish_image'),
                                                ])
                                            </td>
                                            <td class="pds-mt-photo-cell">
                                                @include('order.partials._money-transfer-photo', [
                                                    'url' => $pendingUrl,
                                                    'title' => __('message.pending_image'),
                                                ])
                                            </td>
                                            <td class="text-center pds-money-transfer-actions">
                                                @if($canEdit && !empty($row->transfer_id) && !empty($row->can_switch_to_kpay))
                                                    <button type="button"
                                                            class="pds-mt-switch-btn pds-mt-switch-btn--kpay js-mt-switch"
                                                            data-transfer-id="{{ $row->transfer_id }}"
                                                            data-kpay-name="{{ $row->kpay_name ?? '' }}"
                                                            data-kpay-no="{{ $row->kpay_no ?? '' }}"
                                                            data-method="kpay"
                                                            title="{{ __('message.switch_to_kpay') }}">
                                                        <span class="pds-mt-switch-btn__icon" aria-hidden="true"><i class="fas fa-mobile-alt"></i></span>
                                                        <span class="pds-mt-switch-btn__label">{{ __('message.switch_to_kpay') }}</span>
                                                    </button>
                                                @else
                                                    <span class="pds-mt-switch-empty">—</span>
                                                @endif
                                            </td>
                                            @else
                                            <td><span class="pds-mt-empty">—</span></td>
                                            <td><span class="pds-mt-empty">—</span></td>
                                            <td><span class="pds-mt-empty">—</span></td>
                                            @php
                                                $cashFinishUrls = collect($row->kpay_slip_urls ?? [])
                                                    ->filter(static fn ($u) => is_string($u) && trim($u) !== '')
                                                    ->values()
                                                    ->all();
                                                if ($cashFinishUrls === [] && ! empty($row->kpay_slip_url)) {
                                                    $cashFinishUrls = [(string) $row->kpay_slip_url];
                                                }
                                            @endphp
                                            <td class="pds-mt-photo-cell">
                                                @include('order.partials._money-transfer-photo', [
                                                    'urls' => $cashFinishUrls,
                                                    'title' => __('message.finish_image'),
                                                ])
                                            </td>
                                            <td class="pds-mt-photo-cell">
                                                @include('order.partials._money-transfer-photo', [
                                                    'url' => null,
                                                    'title' => __('message.pending_image'),
                                                ])
                                            </td>
                                            <td class="text-center pds-money-transfer-actions">
                                                @if($canEdit && !empty($row->settlement_batch_id) && !empty($row->can_switch_to_cash))
                                                    <button type="button"
                                                            class="pds-mt-switch-btn pds-mt-switch-btn--cash js-mt-switch-cash"
                                                            data-settlement-batch-id="{{ $row->settlement_batch_id }}"
                                                            data-transfer-id="{{ $row->transfer_id ?? '' }}"
                                                            data-os-name="{{ $row->name }}"
                                                            data-amount="{{ number_format((float) $row->amount_due) }}"
                                                            title="{{ __('message.switch_to_cash') }}">
                                                        <span class="pds-mt-switch-btn__icon" aria-hidden="true"><i class="fas fa-money-bill-wave"></i></span>
                                                        <span class="pds-mt-switch-btn__label">{{ __('message.switch_to_cash') }}</span>
                                                    </button>
                                                @else
                                                    <span class="pds-mt-switch-empty">—</span>
                                                @endif
                                            </td>
                                            @endif
                                        @else
                                            @php
                                                $kpayUrls = collect($row->kpay_slip_urls ?? [])
                                                    ->filter(static fn ($u) => is_string($u) && trim($u) !== '')
                                                    ->values()
                                                    ->all();
                                                if ($kpayUrls === [] && ! empty($row->kpay_slip_url)) {
                                                    $kpayUrls = [(string) $row->kpay_slip_url];
                                                }
                                            @endphp
                                            <td class="pds-mt-photo-cell" style="min-width:120px;">
                                                @include('order.partials._money-transfer-photo', [
                                                    'urls' => $kpayUrls,
                                                    'title' => __('message.kpay_slip'),
                                                    'large' => true,
                                                ])
                                            </td>
                                            <td class="text-center pds-money-transfer-actions">
                                                @if($canEdit && !empty($row->settlement_batch_id) && ($row->can_switch_to_cash ?? true))
                                                    <button type="button"
                                                            class="pds-mt-switch-btn pds-mt-switch-btn--cash js-mt-switch-cash"
                                                            data-settlement-batch-id="{{ $row->settlement_batch_id }}"
                                                            data-transfer-id="{{ $row->transfer_id ?? '' }}"
                                                            data-os-name="{{ $row->name }}"
                                                            data-amount="{{ number_format((float) $row->amount_due) }}"
                                                            title="{{ __('message.switch_to_cash') }}">
                                                        <span class="pds-mt-switch-btn__icon" aria-hidden="true"><i class="fas fa-money-bill-wave"></i></span>
                                                        <span class="pds-mt-switch-btn__label">{{ __('message.switch_to_cash') }}</span>
                                                    </button>
                                                @else
                                                    <span class="pds-mt-switch-empty">—</span>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                        <div class="pds-frozen-table__foot">
                            <table class="{{ $mtTableClass }}">
                                <colgroup>
                                    @foreach($mtHeadCols as $col)
                                        <col class="{{ $col['col'] }}">
                                    @endforeach
                                </colgroup>
                                <tfoot>
                                    <tr class="pds-daily-check-total-row pds-money-transfer-total-row">
                                        @for($i = 0; $i < $mtColCount; $i++)
                                            @if($i === $mtDueColIndex - 1)
                                                <td class="pds-daily-check-total-label text-right">{{ __('message.total') }}</td>
                                            @elseif($i === $mtDueColIndex)
                                                <td class="text-right pds-money-transfer-due">{{ number_format($mtTotalDue) }}</td>
                                            @else
                                                <td></td>
                                            @endif
                                        @endfor
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="mtSwitchKpayModal" tabindex="-1" role="dialog" aria-labelledby="mtSwitchKpayModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mtSwitchKpayModalLabel">{{ __('message.money_transfer_switch_kpay_title') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-3 text-muted">{{ __('message.money_transfer_switch_kpay_hint') }}</p>
                    <div class="pds-mt-switch-kpay-meta">
                        <div class="pds-mt-switch-kpay-meta__item">
                            <span>{{ __('message.kpay_name') }}</span>
                            <strong id="mtSwitchKpayName">—</strong>
                        </div>
                        <div class="pds-mt-switch-kpay-meta__item">
                            <span>{{ __('message.kpay_phone_number') }}</span>
                            <strong id="mtSwitchKpayNo">—</strong>
                        </div>
                    </div>
                    <label class="d-block border rounded p-4 text-center"
                           style="cursor:pointer;border-style:dashed !important;background:#f8fafc;"
                           for="mtSwitchKpayFile">
                        <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-primary" aria-hidden="true"></i>
                        <div class="font-weight-bold">{{ __('message.upload_kpay_slip') }}</div>
                        <div class="small text-muted mt-1">JPG / PNG · max 10MB</div>
                        <input type="file" id="mtSwitchKpayFile" accept="image/*" class="d-none">
                    </label>
                    <div id="mtSwitchKpayPreviewWrap" class="mt-3 text-center" style="display:none;">
                        <img id="mtSwitchKpayPreview" alt="KBZ Pay Slip" style="max-width:100%;max-height:220px;border-radius:8px;border:1px solid #e2e8f0;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('message.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="mtSwitchKpayConfirm">
                        <i class="fas fa-check" aria-hidden="true"></i>
                        {{ __('message.switch_to_kpay') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade pds-mt-switch-modal" id="mtSwitchCashModal" tabindex="-1" role="dialog" aria-labelledby="mtSwitchCashModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content pds-mt-switch-modal__content pds-mt-switch-modal__content--cash">
                <div class="modal-header pds-mt-switch-modal__header pds-mt-switch-modal__header--cash">
                    <h5 class="modal-title" id="mtSwitchCashModalLabel">
                        <i class="fas fa-money-bill-wave" aria-hidden="true"></i>
                        {{ __('message.money_transfer_switch_cash_title') }}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-3 text-muted">{{ __('message.money_transfer_switch_cash_hint') }}</p>
                    <div class="pds-mt-switch-kpay-meta">
                        <div class="pds-mt-switch-kpay-meta__item pds-mt-switch-kpay-meta__item--cash">
                            <span>{{ __('message.os_name') }}</span>
                            <strong id="mtSwitchCashOsName">—</strong>
                        </div>
                        <div class="pds-mt-switch-kpay-meta__item pds-mt-switch-kpay-meta__item--cash">
                            <span>{{ __('message.os_to_pay') }}</span>
                            <strong id="mtSwitchCashAmount">—</strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('message.cancel') }}</button>
                    <button type="button" class="pds-mt-switch-btn pds-mt-switch-btn--cash pds-mt-switch-btn--lg" id="mtSwitchCashConfirm">
                        <span class="pds-mt-switch-btn__icon" aria-hidden="true"><i class="fas fa-money-bill-wave"></i></span>
                        <span class="pds-mt-switch-btn__label">{{ __('message.switch_to_cash') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Scoped styles so Switch buttons / OS chips look correct even if theme/custom CSS cache lags. --}}
    <style id="pds-mt-switch-btn-styles">
        body.pds-admin .pds-money-transfer-page .pds-money-transfer-shell {
            border: 1px solid #f1e7dc;
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 8px 24px rgba(120, 53, 15, 0.05);
        }
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table {
            border-collapse: separate;
            border-spacing: 0;
        }
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table thead th {
            white-space: nowrap;
            font-size: 0.7rem;
            letter-spacing: 0.04em;
            padding: 0.75rem 0.7rem;
        }
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table tbody td {
            vertical-align: middle;
            padding: 0.75rem 0.7rem;
            border-right: 1px solid #f3eee8;
            border-bottom: 1px solid #f3eee8;
            background: #fffefb;
        }
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table tbody tr:hover td {
            background: #fffaf3;
        }
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table tbody td:last-child {
            border-right: none;
        }
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table col.mt-col-no { width: 48px; }
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table col.mt-col-os { width: 160px; }
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table.has-kpay-detail-cols col.mt-col-os-kpay,
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table.has-kpay-detail-cols col.mt-col-admin-kpay {
            width: 188px;
        }
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table col.mt-col-method { width: 92px; }
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table col.mt-col-due { width: 100px; }
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table.is-cash-cols col.mt-col-rider { width: 110px; }
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table.is-cash-cols col.mt-col-status { width: 96px; }
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table.is-cash-cols col.mt-col-remark { width: 120px; }
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table.is-cash-cols col.mt-col-photo { width: 118px; }
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table.is-cash-cols col.mt-col-action { width: 148px; }
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table td.pds-mt-photo-cell,
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table th.pds-mt-photo-col {
            overflow: visible !important;
            text-overflow: clip;
            white-space: nowrap;
            text-align: center;
            min-width: 118px;
        }
        body.pds-admin .pds-money-transfer-os-cell,
        body.pds-admin .pds-mt-kpay-col {
            overflow: hidden !important;
        }
        body.pds-admin .pds-money-transfer-os {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            min-width: 0;
        }
        body.pds-admin .pds-money-transfer-os__identity {
            display: flex;
            flex-direction: column;
            gap: 0.12rem;
            min-width: 0;
        }
        body.pds-admin .pds-money-transfer-os__name {
            color: #1c1917;
            font-size: 0.9rem;
            font-weight: 800;
            line-height: 1.3;
            letter-spacing: -0.01em;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        body.pds-admin .pds-money-transfer-os__phone {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            color: #78716c;
            font-size: 0.74rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }
        body.pds-admin .pds-money-transfer-os__phone i {
            font-size: 0.62rem;
            color: #a8a29e;
        }
        body.pds-admin .pds-mt-pay-chip {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.42rem 0.5rem;
            border-radius: 12px;
            border: 1px solid transparent;
            box-sizing: border-box;
            min-width: 0;
        }
        body.pds-admin .pds-mt-pay-chip__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 10px;
            flex: 0 0 auto;
            font-size: 0.72rem;
        }
        body.pds-admin .pds-mt-pay-chip__body {
            display: flex;
            flex-direction: column;
            gap: 0.06rem;
            min-width: 0;
            flex: 1 1 auto;
        }
        body.pds-admin .pds-mt-pay-chip__body strong {
            font-size: 0.8rem;
            font-weight: 800;
            line-height: 1.25;
            color: #1c1917;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        body.pds-admin .pds-mt-pay-chip__body span {
            font-size: 0.72rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            color: #78716c;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        body.pds-admin .pds-mt-pay-chip--shop {
            background: #fffbeb;
            border-color: #fde68a;
        }
        body.pds-admin .pds-mt-pay-chip--shop .pds-mt-pay-chip__icon {
            background: #f59e0b;
            color: #fff;
        }
        body.pds-admin .pds-mt-pay-chip--admin {
            background: #fff7ed;
            border-color: #fdba74;
        }
        body.pds-admin .pds-mt-pay-chip--admin .pds-mt-pay-chip__icon {
            background: #ea580c;
            color: #fff;
        }
        body.pds-admin .pds-mt-pay-chip--admin .pds-mt-pay-chip__body strong {
            color: #9a3412;
        }
        body.pds-admin .pds-mt-method-cell {
            text-align: center;
        }
        body.pds-admin .pds-mt-method-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.28rem 0.58rem;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        body.pds-admin .pds-mt-method-badge i { font-size: 0.64rem; }
        body.pds-admin .pds-mt-method-badge.is-kpay {
            background: #fff7ed;
            border-color: #fdba74;
            color: #c2410c;
        }
        body.pds-admin .pds-mt-method-badge.is-cash {
            background: #ecfdf5;
            border-color: #6ee7b7;
            color: #047857;
        }
        body.pds-admin .pds-money-transfer-due {
            font-size: 0.98rem;
            font-weight: 800;
            color: #0f766e;
            letter-spacing: -0.01em;
            font-variant-numeric: tabular-nums;
        }
        body.pds-admin .pds-mt-empty {
            color: #d6d3d1;
            font-weight: 700;
            font-size: 0.85rem;
        }
        body.pds-admin .pds-mt-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.24rem 0.52rem;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: capitalize;
        }
        body.pds-admin .pds-mt-remark-cell { min-width: 0; }
        body.pds-admin .pds-mt-remark.is-stacked { margin-top: 0.45rem; }
        body.pds-admin .pds-mt-remark__tag {
            display: inline-flex;
            align-items: center;
            padding: 0.14rem 0.42rem;
            border-radius: 999px;
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }
        body.pds-admin .pds-mt-remark__tag.is-pending {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        body.pds-admin .pds-mt-remark__tag.is-done {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        body.pds-admin .pds-mt-remark__text {
            margin-top: 0.22rem;
            white-space: pre-wrap;
            font-size: 0.74rem;
            color: #57534e;
            line-height: 1.35;
        }
        body.pds-admin .pds-money-transfer-page .pds-mt-photo-gallery {
            display: inline-flex;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: center;
            gap: 6px;
            max-width: none;
            overflow: visible;
            vertical-align: middle;
        }
        body.pds-admin .pds-money-transfer-page .pds-mt-photo {
            width: 48px;
            height: 48px;
            border-radius: 11px;
            border: 1px solid #e7e5e4;
            box-shadow: 0 1px 3px rgba(28, 25, 23, 0.06);
            cursor: zoom-in;
            transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
        }
        body.pds-admin .pds-money-transfer-page .pds-mt-photo:hover {
            transform: translateY(-1px) scale(1.04);
            border-color: #fdba74;
            box-shadow: 0 6px 14px rgba(234, 88, 12, 0.16);
        }
        body.pds-admin .pds-money-transfer-page .pds-mt-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        body.pds-admin .pds-money-transfer-page .pds-mt-photo-more {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 11px;
            border: 1px solid #fdba74;
            background: #fff7ed;
            color: #c2410c;
            font-size: 0.78rem;
            font-weight: 800;
            cursor: zoom-in;
            padding: 0;
            line-height: 1;
        }
        body.pds-admin .pds-money-transfer-page .pds-mt-photo-more:hover {
            background: #ffedd5;
            border-color: #fb923c;
        }
        body.pds-admin .pds-money-transfer-page .pds-mt-photo-empty {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 11px;
            background: #fafaf9;
            border: 1px dashed #e7e5e4;
            color: #d6d3d1;
            font-size: 0.9rem;
        }
        body.pds-admin .pds-money-transfer-actions { white-space: nowrap; vertical-align: middle; }
        body.pds-admin .pds-mt-switch-empty {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 2rem; color: #d6d3d1; font-weight: 700;
        }
        body.pds-admin .pds-mt-switch-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem;
            height: 32px; padding: 0 0.75rem 0 0.35rem; border-radius: 999px;
            border: 1px solid transparent; font-size: 0.7rem; font-weight: 800;
            letter-spacing: 0.01em; line-height: 1; cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease, border-color .15s ease;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05); white-space: nowrap;
        }
        body.pds-admin .pds-mt-switch-btn:hover {
            transform: translateY(-1px); box-shadow: 0 6px 14px rgba(15, 23, 42, .1);
        }
        body.pds-admin .pds-mt-switch-btn:active { transform: translateY(0); }
        body.pds-admin .pds-mt-switch-btn:focus {
            outline: none; box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .22);
        }
        body.pds-admin .pds-mt-switch-btn__icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 22px; height: 22px; border-radius: 999px; font-size: .66rem; flex: 0 0 auto;
        }
        body.pds-admin .pds-mt-switch-btn__label { position: relative; top: .5px; }
        body.pds-admin .pds-mt-switch-btn--cash {
            background: #ecfdf5;
            border-color: #6ee7b7; color: #047857;
        }
        body.pds-admin .pds-mt-switch-btn--cash .pds-mt-switch-btn__icon { background: #059669; color: #fff; }
        body.pds-admin .pds-mt-switch-btn--cash:hover {
            background: #d1fae5;
            border-color: #34d399; color: #065f46;
        }
        body.pds-admin .pds-mt-switch-btn--kpay {
            background: #fff7ed;
            border-color: rgba(var(--brand-rgb), .45); color: var(--site-color);
        }
        body.pds-admin .pds-mt-switch-btn--kpay .pds-mt-switch-btn__icon { background: var(--site-color); color: #fff; }
        body.pds-admin .pds-mt-switch-btn--kpay:hover {
            background: #ffedd5;
            border-color: var(--site-color);
        }
        body.pds-admin .pds-mt-switch-btn--lg {
            height: 40px; padding: 0 1.1rem 0 .45rem; font-size: .82rem;
        }
        body.pds-admin .pds-mt-switch-btn--lg .pds-mt-switch-btn__icon { width: 28px; height: 28px; font-size: .78rem; }
        body.pds-admin .pds-mt-switch-modal__header--cash {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #fff; border-bottom: none;
        }
        body.pds-admin .pds-mt-switch-modal__header--cash .modal-title {
            display: inline-flex; align-items: center; gap: .5rem; font-weight: 800; color: #fff;
        }
        body.pds-admin .pds-mt-switch-modal__header--cash .close { color: #fff !important; opacity: 1; text-shadow: none; }
        body.pds-admin .pds-mt-switch-kpay-meta__item--cash { background: #ecfdf5; border-color: #a7f3d0; }
        body.pds-admin .pds-mt-switch-kpay-meta__item--cash span { color: #047857; }
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table.is-kpay-compact col.mt-col-action {
            width: 160px;
        }
        body.pds-admin .pds-money-transfer-page table.pds-money-transfer-table.is-kpay-compact td.pds-money-transfer-actions {
            overflow: visible !important; text-overflow: clip; text-align: center; white-space: nowrap;
        }
    </style>
    {{-- Inline boot: @section/@push is skipped by x-master-layout (same pattern as Daily Check). --}}
    <script src="{{ asset('js/dispatch-os-fields.js') }}?v=14"></script>
    <script>
        (function bootMoneyTransfer(attempt) {
            attempt = attempt || 0;
            if (!window.jQuery || typeof window.flatpickr === 'undefined') {
                if (attempt < 100) return setTimeout(function () { bootMoneyTransfer(attempt + 1); }, 50);
                return;
            }
            var $ = window.jQuery;
            var fp = window.flatpickr;

            function bindMoneyTransferCalendars() {
                var ids = ['mt_from_date', 'mt_to_date'];
                if (typeof window.pdsBindDispatchDatepickers === 'function') {
                    window.pdsBindDispatchDatepickers('#mt_from_date, #mt_to_date');
                }
                if (typeof fp === 'undefined' && typeof window.flatpickr !== 'undefined') {
                    fp = window.flatpickr;
                }
                if (typeof fp === 'undefined') return;
                ids.forEach(function (id) {
                    var el = document.getElementById(id);
                    if (!el || el._flatpickr) return;
                    fp(el, {
                        dateFormat: 'd-m-Y',
                        allowInput: true,
                        disableMobile: true,
                        clickOpens: true,
                        appendTo: document.body,
                        maxDate: null
                    });
                });
            }

            function bindMoneyTransferOsSelect() {
                if (!document.getElementById('mt_os')) return;
                if (typeof window.initOnlineShopFilterSelect !== 'function') return;
                if (!$.fn || !$.fn.select2) return;
                window.initOnlineShopFilterSelect('#mt_os', {
                    placeholder: @json(__('message.select_name', ['select' => __('message.os_name')])),
                    allowClear: false
                });
            }

            function bindMoneyTransferFrozenScroll() {
                var root = document.getElementById('moneyTransferFrozen');
                if (!root || root.getAttribute('data-mt-scroll-bound') === '1') return;
                root.setAttribute('data-mt-scroll-bound', '1');
                var headWrap = root.querySelector('.pds-frozen-table__head');
                var bodyWrap = root.querySelector('.pds-frozen-table__body');
                var footWrap = root.querySelector('.pds-frozen-table__foot');
                var headTable = headWrap && headWrap.querySelector('table');
                var bodyTable = bodyWrap && bodyWrap.querySelector('table');
                var footTable = footWrap && footWrap.querySelector('table');
                if (!headWrap || !bodyWrap || !bodyTable) return;

                function syncScroll() {
                    headWrap.scrollLeft = bodyWrap.scrollLeft;
                    if (footWrap) footWrap.scrollLeft = bodyWrap.scrollLeft;
                }

                function syncColWidths() {
                    var bodyRow = bodyTable.querySelector('tbody tr');
                    if (!bodyRow) return;
                    var bodyCells = bodyRow.children;
                    var headCols = headTable ? headTable.querySelectorAll('colgroup col') : [];
                    var bodyCols = bodyTable.querySelectorAll('colgroup col');
                    var footCols = footTable ? footTable.querySelectorAll('colgroup col') : [];
                    var n = bodyCells.length;
                    for (var i = 0; i < n; i++) {
                        var w = Math.ceil(bodyCells[i].getBoundingClientRect().width) + 'px';
                        if (headCols[i]) headCols[i].style.width = w;
                        if (bodyCols[i]) bodyCols[i].style.width = w;
                        if (footCols[i]) footCols[i].style.width = w;
                    }
                    var tableWidth = Math.ceil(bodyTable.getBoundingClientRect().width) + 'px';
                    if (headTable) headTable.style.width = tableWidth;
                    if (footTable) footTable.style.width = tableWidth;
                    bodyTable.style.width = tableWidth;
                    syncScroll();
                }

                bodyWrap.addEventListener('scroll', syncScroll);
                window.addEventListener('resize', syncColWidths);
                requestAnimationFrame(function () {
                    syncColWidths();
                    setTimeout(syncColWidths, 50);
                    setTimeout(syncColWidths, 250);
                });
            }

            function initMoneyTransferFilters() {
                if (!document.getElementById('mt_os') && !document.getElementById('mt_from_date')) {
                    return;
                }
                bindMoneyTransferCalendars();
                bindMoneyTransferOsSelect();
                bindMoneyTransferFrozenScroll();
            }

            window.pdsInitMoneyTransferFilters = initMoneyTransferFilters;
            initMoneyTransferFilters();
            setTimeout(initMoneyTransferFilters, 100);
            setTimeout(initMoneyTransferFilters, 400);
            setTimeout(initMoneyTransferFilters, 900);

            if (window.__pdsMoneyTransferBound) return;
            window.__pdsMoneyTransferBound = true;

            var mtLightboxUrls = [];
            var mtLightboxIndex = 0;
            var mtLightboxTitle = '';

            function ensureMtPhotoLightbox() {
                var $box = $('#pdsMtPhotoLightbox');
                if ($box.length) return $box;
                $('body').append(
                    '<div id="pdsMtPhotoLightbox" class="pds-os-proof-lightbox pds-mt-photo-lightbox" role="dialog" aria-modal="true" aria-label="Image preview">'
                    + '<div class="pds-os-proof-lightbox__shell">'
                    + '<header class="pds-os-proof-lightbox__top">'
                    + '<div class="pds-os-proof-lightbox__title"><i class="fas fa-image" aria-hidden="true"></i><span class="js-mt-lb-title">Image</span></div>'
                    + '<button type="button" class="pds-os-proof-lightbox__close" aria-label="Close"><i class="fas fa-times"></i></button>'
                    + '</header>'
                    + '<div class="pds-os-proof-lightbox__body">'
                    + '<button type="button" class="pds-os-proof-lightbox__nav pds-os-proof-lightbox__nav--prev" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>'
                    + '<div class="pds-os-proof-lightbox__stage"><img class="pds-os-proof-lightbox__img" alt="Preview"></div>'
                    + '<button type="button" class="pds-os-proof-lightbox__nav pds-os-proof-lightbox__nav--next" aria-label="Next"><i class="fas fa-chevron-right"></i></button>'
                    + '</div>'
                    + '<footer class="pds-os-proof-lightbox__foot">'
                    + '<div class="pds-os-proof-lightbox__meta"></div>'
                    + '<div class="pds-os-proof-lightbox__thumbs"></div>'
                    + '</footer>'
                    + '</div></div>'
                );
                return $('#pdsMtPhotoLightbox');
            }

            function renderMtPhotoLightbox() {
                var $box = ensureMtPhotoLightbox();
                var url = mtLightboxUrls[mtLightboxIndex] || '';
                var $img = $box.find('.pds-os-proof-lightbox__img');
                $box.find('.js-mt-lb-title').text(mtLightboxTitle || 'Image');
                $img.removeClass('is-ready').attr('src', url);
                $img.off('load.pdsMtLb error.pdsMtLb').on('load.pdsMtLb error.pdsMtLb', function () {
                    $(this).addClass('is-ready');
                });
                if ($img[0] && $img[0].complete && $img[0].naturalWidth) {
                    $img.addClass('is-ready');
                }
                $box.find('.pds-os-proof-lightbox__meta').html(
                    mtLightboxUrls.length
                        ? '<span class="pds-os-proof-lightbox__count">' + (mtLightboxIndex + 1)
                            + '</span><span class="pds-os-proof-lightbox__sep">/</span><span>'
                            + mtLightboxUrls.length + '</span>'
                        : ''
                );
                var multi = mtLightboxUrls.length > 1;
                $box.find('.pds-os-proof-lightbox__nav--prev').prop('hidden', !multi);
                $box.find('.pds-os-proof-lightbox__nav--next').prop('hidden', !multi);
                $box.toggleClass('has-multi', multi);
                var thumbsHtml = '';
                if (multi) {
                    mtLightboxUrls.forEach(function (thumbUrl, i) {
                        thumbsHtml += '<button type="button" class="pds-os-proof-lightbox__thumb'
                            + (i === mtLightboxIndex ? ' is-active' : '')
                            + '" data-index="' + i + '" aria-label="Image ' + (i + 1) + '">'
                            + '<img src="' + thumbUrl + '" alt=""></button>';
                    });
                }
                $box.find('.pds-os-proof-lightbox__thumbs').html(thumbsHtml);
            }

            function openMtPhotoLightbox(urls, index, title) {
                mtLightboxUrls = (urls || []).filter(Boolean);
                if (!mtLightboxUrls.length) return;
                mtLightboxTitle = title || 'Image';
                mtLightboxIndex = Math.max(0, Math.min(index || 0, mtLightboxUrls.length - 1));
                ensureMtPhotoLightbox().addClass('is-open');
                $('body').addClass('pds-os-proof-lightbox-open');
                renderMtPhotoLightbox();
            }

            function closeMtPhotoLightbox() {
                $('#pdsMtPhotoLightbox').removeClass('is-open');
                $('body').removeClass('pds-os-proof-lightbox-open');
            }

            function stepMtPhotoLightbox(delta) {
                if (mtLightboxUrls.length < 2) return;
                mtLightboxIndex = (mtLightboxIndex + delta + mtLightboxUrls.length) % mtLightboxUrls.length;
                renderMtPhotoLightbox();
            }

            function collectMtGalleryUrls($gallery) {
                var urls = $gallery.find('.pds-mt-photo-urls [data-url]').map(function () {
                    return $(this).attr('data-url');
                }).get().filter(Boolean);
                if (!urls.length) {
                    urls = $gallery.find('a.js-mt-photo').map(function () {
                        return $(this).attr('href');
                    }).get().filter(Boolean);
                }
                return urls;
            }

            $(document)
                .off('click.pdsMtPhoto', '.js-mt-photo, .js-mt-photo-more')
                .on('click.pdsMtPhoto', '.js-mt-photo, .js-mt-photo-more', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var $el = $(this);
                    var $gallery = $el.closest('.pds-mt-photo-gallery');
                    if (!$gallery.length) return;
                    var urls = collectMtGalleryUrls($gallery);
                    var index = parseInt($el.attr('data-index'), 10);
                    if (Number.isNaN(index)) index = 0;
                    openMtPhotoLightbox(urls, index, $gallery.attr('data-title') || '');
                });

            $(document)
                .off('click.pdsMtPhotoLb')
                .on('click.pdsMtPhotoLb', '#pdsMtPhotoLightbox', function (e) {
                    if (e.target === this) closeMtPhotoLightbox();
                })
                .on('click.pdsMtPhotoLb', '#pdsMtPhotoLightbox .pds-os-proof-lightbox__close', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeMtPhotoLightbox();
                })
                .on('click.pdsMtPhotoLb', '#pdsMtPhotoLightbox .pds-os-proof-lightbox__nav--prev', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    stepMtPhotoLightbox(-1);
                })
                .on('click.pdsMtPhotoLb', '#pdsMtPhotoLightbox .pds-os-proof-lightbox__nav--next', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    stepMtPhotoLightbox(1);
                })
                .on('click.pdsMtPhotoLb', '#pdsMtPhotoLightbox .pds-os-proof-lightbox__thumb', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var i = parseInt($(this).attr('data-index'), 10);
                    if (Number.isNaN(i)) return;
                    mtLightboxIndex = i;
                    renderMtPhotoLightbox();
                });

            $(document)
                .off('keydown.pdsMtPhotoLb')
                .on('keydown.pdsMtPhotoLb', function (e) {
                    if (!$('#pdsMtPhotoLightbox').hasClass('is-open')) return;
                    if (e.key === 'Escape') closeMtPhotoLightbox();
                    if (e.key === 'ArrowLeft') stepMtPhotoLightbox(-1);
                    if (e.key === 'ArrowRight') stepMtPhotoLightbox(1);
                });

            $(document)
                .off('admin-live:replaced.pdsMtFilters admin-spa:navigated.pdsMtFilters')
                .on('admin-live:replaced.pdsMtFilters admin-spa:navigated.pdsMtFilters', function () {
                    [0, 50, 150, 400].forEach(function (ms) {
                        setTimeout(function () {
                            if (typeof window.pdsInitMoneyTransferFilters === 'function') {
                                window.pdsInitMoneyTransferFilters();
                            }
                        }, ms);
                    });
                });

            var saveUrl = @json(route('order.money-transfer.upsert'));
            var fillUrl = @json(route('order.money-transfer.fill-kpay'));
            var switchUrl = @json(route('order.money-transfer.switch-method'));
            var csrf = $('meta[name="csrf-token"]').attr('content');
            var fromDate = @json($filterFromDate);
            var toDate = @json($filterToDate);
            var branchId = @json($branchFilter);
            var paymentMethod = @json($paymentMethod ?? 'all');
            var pendingSwitchTransferId = null;
            var pendingSwitchCashBatchId = null;
            var pendingSwitchCashTransferId = null;
            var previewObjectUrl = null;

            function num(v) {
                var n = parseFloat(String(v || '').replace(/,/g, ''));
                return isNaN(n) ? 0 : n;
            }

            function fmt(n) {
                return Math.round(n).toLocaleString();
            }

            function updateSummary(summary) {
                if (!summary) return;
                var $box = $('#mtSummary');
                $box.find('[data-summary="amount_due"]').text(fmt(summary.amount_due));
                $box.find('[data-summary="cash_total"]').text(fmt(summary.cash_total));
                $box.find('[data-summary="kpay_total"]').text(fmt(summary.kpay_total));
                $box.find('[data-summary="os_count"]').text(summary.os_count);
            }

            function saveRow($row) {
                var cash = paymentMethod === 'cash' ? num($row.find('.js-mt-cash').val()) : 0;
                var kpay = paymentMethod === 'kpay' ? num($row.find('.js-mt-kpay').val()) : 0;
                var payload = {
                    from_date: fromDate,
                    to_date: toDate,
                    branch_id: branchId,
                    os_user_id: $row.data('os-user-id'),
                    cash_amount: cash,
                    kpay_amount: kpay,
                    freight_amount: num($row.find('.js-mt-freight').val()),
                    payment_method: paymentMethod,
                    _token: csrf
                };
                $row.addClass('is-saving');
                return $.ajax({
                    url: saveUrl,
                    method: 'POST',
                    data: payload,
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                }).done(function (res) {
                    if (res.summary) updateSummary(res.summary);
                    $row.addClass('is-saved');
                    setTimeout(function () { $row.removeClass('is-saved'); }, 900);
                    if (window.toastr && res.message) toastr.success(res.message);
                }).fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Save failed';
                    if (window.toastr) toastr.error(msg);
                    else alert(msg);
                }).always(function () {
                    $row.removeClass('is-saving');
                });
            }

            function resetSwitchKpayModal() {
                pendingSwitchTransferId = null;
                $('#mtSwitchKpayFile').val('');
                $('#mtSwitchKpayPreviewWrap').hide();
                $('#mtSwitchKpayPreview').attr('src', '');
                $('#mtSwitchKpayName').text('—');
                $('#mtSwitchKpayNo').text('—');
                if (previewObjectUrl) {
                    URL.revokeObjectURL(previewObjectUrl);
                    previewObjectUrl = null;
                }
                $('#mtSwitchKpayConfirm').prop('disabled', false);
            }

            $(document).on('click', '.js-mt-save', function () {
                saveRow($(this).closest('tr'));
            });

            $(document).on('click', '.js-mt-switch', function () {
                var transferId = $(this).data('transfer-id');
                if (!transferId) return;
                resetSwitchKpayModal();
                pendingSwitchTransferId = transferId;
                var kpayName = String($(this).attr('data-kpay-name') || '').trim();
                var kpayNo = String($(this).attr('data-kpay-no') || '').trim();
                $('#mtSwitchKpayName').text(kpayName !== '' ? kpayName : '—');
                $('#mtSwitchKpayNo').text(kpayNo !== '' ? kpayNo : '—');
                $('#mtSwitchKpayModal').modal('show');
            });

            $('#mtSwitchKpayModal').on('hidden.bs.modal', function () {
                resetSwitchKpayModal();
            });

            $('#mtSwitchKpayFile').on('change', function () {
                var file = this.files && this.files[0];
                if (!file) {
                    $('#mtSwitchKpayPreviewWrap').hide();
                    return;
                }
                if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
                previewObjectUrl = URL.createObjectURL(file);
                $('#mtSwitchKpayPreview').attr('src', previewObjectUrl);
                $('#mtSwitchKpayPreviewWrap').show();
            });

            $('#mtSwitchKpayConfirm').on('click', function () {
                var $btn = $(this);
                var fileInput = document.getElementById('mtSwitchKpayFile');
                var file = fileInput && fileInput.files && fileInput.files[0];
                if (!pendingSwitchTransferId) return;
                if (!file) {
                    var needMsg = @json(__('message.money_transfer_kpay_slip_required'));
                    if (window.toastr) toastr.error(needMsg);
                    else alert(needMsg);
                    return;
                }

                var fd = new FormData();
                fd.append('_token', csrf);
                fd.append('transfer_id', pendingSwitchTransferId);
                fd.append('payment_method', 'kpay');
                fd.append('kpay_slip', file);

                $btn.prop('disabled', true);
                $.ajax({
                    url: switchUrl,
                    method: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                }).done(function (res) {
                    if (window.toastr && res.message) toastr.success(res.message);
                    $('#mtSwitchKpayModal').modal('hide');
                    var mtUrl = @json(route('order.money-transfer')) + '?method=kpay'
                        + '&from_date=' + encodeURIComponent(fromDate)
                        + '&to_date=' + encodeURIComponent(toDate)
                        + '&branch_id=' + encodeURIComponent(branchId);
                    if (typeof window.pdsAdminGo === 'function') window.pdsAdminGo(mtUrl);
                    else window.location.href = mtUrl;
                }).fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        || (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.kpay_slip && xhr.responseJSON.errors.kpay_slip[0])
                        || 'Switch failed';
                    if (window.toastr) toastr.error(msg);
                    else alert(msg);
                    $btn.prop('disabled', false);
                });
            });

            function resetSwitchCashModal() {
                pendingSwitchCashBatchId = null;
                pendingSwitchCashTransferId = null;
                $('#mtSwitchCashOsName').text('—');
                $('#mtSwitchCashAmount').text('—');
                $('#mtSwitchCashConfirm').prop('disabled', false);
            }

            $(document).on('click', '.js-mt-switch-cash', function () {
                var batchId = $(this).data('settlement-batch-id');
                if (!batchId) return;
                resetSwitchCashModal();
                pendingSwitchCashBatchId = batchId;
                pendingSwitchCashTransferId = $(this).data('transfer-id') || null;
                $('#mtSwitchCashOsName').text(String($(this).attr('data-os-name') || '').trim() || '—');
                $('#mtSwitchCashAmount').text(String($(this).attr('data-amount') || '').trim() || '—');
                $('#mtSwitchCashModal').modal('show');
            });

            $('#mtSwitchCashModal').on('hidden.bs.modal', function () {
                resetSwitchCashModal();
            });

            $('#mtSwitchCashConfirm').on('click', function () {
                var $btn = $(this);
                if (!pendingSwitchCashBatchId && !pendingSwitchCashTransferId) return;

                var fd = new FormData();
                fd.append('_token', csrf);
                fd.append('payment_method', 'cash');
                if (pendingSwitchCashBatchId) {
                    fd.append('settlement_batch_id', pendingSwitchCashBatchId);
                }
                if (pendingSwitchCashTransferId) {
                    fd.append('transfer_id', pendingSwitchCashTransferId);
                }

                $btn.prop('disabled', true);
                $.ajax({
                    url: switchUrl,
                    method: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                }).done(function (res) {
                    if (window.toastr && res.message) toastr.success(res.message);
                    $('#mtSwitchCashModal').modal('hide');
                    var mtUrl = @json(route('order.money-transfer')) + '?method=cash'
                        + '&from_date=' + encodeURIComponent(fromDate)
                        + '&to_date=' + encodeURIComponent(toDate)
                        + '&branch_id=' + encodeURIComponent(branchId);
                    if (typeof window.pdsAdminGo === 'function') window.pdsAdminGo(mtUrl);
                    else window.location.href = mtUrl;
                }).fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Switch failed';
                    if (window.toastr) toastr.error(msg);
                    else alert(msg);
                    $btn.prop('disabled', false);
                });
            });

            $(document).on('keydown', '.pds-money-transfer-input', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveRow($(this).closest('tr'));
                }
            });

            $('#mtFillKpayBtn').on('click', function () {
                var $btn = $(this);
                if (!confirm(@json(__('message.money_transfer_fill_kpay_confirm')))) return;
                $btn.prop('disabled', true);
                $.ajax({
                    url: fillUrl,
                    method: 'POST',
                    data: {
                        from_date: fromDate,
                        to_date: toDate,
                        branch_id: branchId,
                        method: 'kpay',
                        _token: csrf
                    },
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                }).done(function (res) {
                    if (window.toastr && res.message) toastr.success(res.message);
                    typeof window.adminLiveReloadPage === 'function' ? window.adminLiveReloadPage() : window.location.reload();
                }).fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed';
                    if (window.toastr) toastr.error(msg);
                    else alert(msg);
                    $btn.prop('disabled', false);
                });
            });
        })();
    </script>
</div>
</x-master-layout>
