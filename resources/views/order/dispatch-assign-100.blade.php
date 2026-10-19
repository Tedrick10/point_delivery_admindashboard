<x-master-layout :assets="$assets ?? []">
    
<div id="adminLiveRoot" data-live-page="auto">
<style>
        .pds-assign-action-btn--kyo-shin { background: rgba(var(--brand-rgb), 0.08); border-color: rgba(var(--brand-rgb), 0.45); color: var(--site-color); }
        .pds-assign-action-btn--kyo-shin:disabled { opacity: .45; }
        .pds-kyo-shin-row-badge {
            display: inline-flex; align-items: center; margin-left: 6px;
            padding: 2px 7px; border-radius: 999px; font-size: 10px; font-weight: 800;
            background: rgba(var(--brand-rgb), 0.16); color: var(--site-color); letter-spacing: .02em;
        }
        .pds-kyo-shin-modal {
            border: 0;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(28, 25, 23, .18);
        }
        .pds-kyo-shin-modal__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 22px 22px 16px;
            background: linear-gradient(180deg, rgba(var(--brand-rgb), 0.08) 0%, #ffffff 100%);
            border-bottom: 1px solid rgba(var(--brand-rgb), 0.16);
        }
        .pds-kyo-shin-modal__heading {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            min-width: 0;
        }
        .pds-kyo-shin-modal__icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--site-color), var(--site-color));
            color: #fff;
            box-shadow: 0 10px 20px rgba(var(--brand-rgb), .28);
            flex-shrink: 0;
        }
        .pds-kyo-shin-modal__title {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: #1c1917;
            letter-spacing: 0;
            line-height: 1.35;
        }
        .pds-kyo-shin-modal__count {
            display: inline-flex;
            margin-top: 6px;
            padding: 3px 10px;
            border-radius: 999px;
            background: rgba(var(--brand-rgb), 0.16);
            color: var(--site-color);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0;
        }
        .pds-kyo-shin-modal__close {
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 10px;
            background: #fff;
            color: #78716c;
            box-shadow: inset 0 0 0 1px #e7e5e4;
        }
        .pds-kyo-shin-modal__close:hover { color: #1c1917; background: #f5f5f4; }
        .pds-kyo-shin-modal__body {
            padding: 18px 22px 8px;
            word-spacing: normal;
            letter-spacing: 0;
        }
        .pds-kyo-shin-modal__steps {
            display: grid;
            gap: 8px;
            margin: 0 0 18px;
        }
        .pds-kyo-shin-modal__step {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 14px;
            background: #fafaf9;
            border: 1px solid #f5f5f4;
            color: #44403c;
            font-size: 14px;
            line-height: 1.55;
            letter-spacing: 0;
            word-spacing: normal;
        }
        .pds-kyo-shin-modal__step i {
            width: 22px;
            height: 22px;
            margin-top: 1px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: rgba(var(--brand-rgb), 0.08);
            color: #ea580c;
            font-size: 11px;
            flex-shrink: 0;
        }
        .pds-kyo-shin-modal__field label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
            color: #57534e;
            letter-spacing: 0;
        }
        .pds-kyo-shin-modal__date {
            position: relative;
        }
        .pds-kyo-shin-modal__date i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #ea580c;
            pointer-events: none;
        }
        .pds-kyo-shin-modal__date input {
            width: 100%;
            height: 48px;
            padding: 0 16px 0 42px;
            border: 1px solid #e7e5e4;
            border-radius: 14px;
            background: #fff;
            font-size: 15px;
            font-weight: 600;
            color: #1c1917;
            letter-spacing: 0;
        }
        .pds-kyo-shin-modal__date input:focus {
            outline: none;
            border-color: var(--site-color);
            box-shadow: 0 0 0 4px rgba(var(--brand-rgb), .12);
        }
        .pds-kyo-shin-modal__footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 16px 22px 22px;
        }
        .pds-kyo-shin-modal__btn {
            min-height: 44px;
            padding: 0 18px;
            border-radius: 999px;
            border: 0;
            font-weight: 700;
            letter-spacing: 0;
        }
        .pds-kyo-shin-modal__btn--ghost {
            background: #f5f5f4;
            color: #44403c;
        }
        .pds-kyo-shin-modal__btn--ghost:hover { background: #e7e5e4; }
        .pds-kyo-shin-modal__btn--primary {
            background: linear-gradient(135deg, var(--site-color), var(--site-color));
            color: #fff;
            box-shadow: 0 10px 18px rgba(var(--brand-rgb), .24);
        }
        .pds-kyo-shin-modal__btn--primary:hover { filter: brightness(1.03); }
        .pds-kyo-shin-modal__btn--primary:disabled { opacity: .5; box-shadow: none; }
        #assign100Table .js-assign100-toggle-check {
            cursor: pointer;
            user-select: none;
        }
        #assign100Table .js-assign100-toggle-check:hover {
            color: var(--site-color);
            text-decoration: underline;
            text-underline-offset: 2px;
        }
    </style>
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-to-assign-page pds-dispatch-assign-100-page">
        <div class="pds-dispatch-to-assign-screen">
            <div class="pds-dispatch-to-assign-topbar">
                <div class="pds-dispatch-to-assign-topbar-copy">
                    <h4 class="pds-dispatch-to-assign-heading">{{ $pageTitle ?? __('message.assign_100') }}</h4>
                    <p class="pds-dispatch-to-assign-subtitle">
                        @if(!empty($pageSubtitle))
                            {{ $pageSubtitle }}
                            <br>
                        @endif
                        {{ __('message.item_count') }} = <strong id="assign100ItemCount">{{ $items->count() }}</strong>
                    </p>
                </div>
                @php
                    $showAssignAction = $showAssignAction ?? true;
                    $assignButtonLabel = $assignButtonLabel ?? __('message.assign_rider');
                    $needsRider = $needsRider ?? true;
                    $assignActionUrl = $assignActionUrl ?? route('order.dispatch.assign-rider');
                    $hideBranchTabs = $hideBranchTabs ?? false;
                    $showSendToMdy = $showSendToMdy ?? false;
                    $sendToMdyUrl = $sendToMdyUrl ?? route('order.dispatch.send-to-mdy');
                    $showKyoShinAction = $showKyoShinAction ?? false;
                    $kyoShinActionUrl = $kyoShinActionUrl ?? route('order.dispatch.give-kyo-shin');
                @endphp
                <div class="pds-dispatch-to-assign-topbar-actions">
                    @if($showKyoShinAction)
                    <button type="button" class="pds-assign-action-btn pds-assign-action-btn--kyo-shin" id="giveKyoShinBtn" disabled>
                        <span class="pds-assign-action-btn__icon" aria-hidden="true">
                            <i class="fas fa-hand-holding-usd"></i>
                        </span>
                        <span class="pds-assign-action-btn__label">{{ __('message.kyo_shin_give') }}</span>
                    </button>
                    @endif
                    @if($showSendToMdy)
                    <button type="button" class="pds-assign-action-btn pds-assign-action-btn--rider" id="sendToMdyBtn" disabled>
                        <span class="pds-assign-action-btn__icon" aria-hidden="true">
                            <i class="fas fa-share"></i>
                        </span>
                        <span class="pds-assign-action-btn__label">{{ __('message.send_to_mdy') }}</span>
                    </button>
                    @endif
                    @if($showAssignAction)
                    <button type="button" class="pds-assign-action-btn pds-assign-action-btn--rider" id="assignRiderBtn" disabled>
                        <span class="pds-assign-action-btn__icon" aria-hidden="true">
                            <i class="fas {{ $needsRider ? 'fa-motorcycle' : 'fa-check' }}"></i>
                        </span>
                        <span class="pds-assign-action-btn__label">{{ $assignButtonLabel }}</span>
                    </button>
                    @endif
                    <button type="button" class="pds-dispatch-items-icon-btn" id="printAssign100LabelsBtn" title="{{ __('message.assign_100_print_labels') }}" disabled>
                        <i class="fas fa-print"></i>
                        <span id="assign100PrintCount" hidden>0</span>
                    </button>
                </div>
            </div>

            @php
                $destinationBranches = $destinationBranches ?? collect();
                $activeToBranchId = (int) ($activeToBranchId ?? 0);
                $tabCounts = $tabCounts ?? collect();
            @endphp
            @if(!$hideBranchTabs && $destinationBranches->isNotEmpty())
                <div class="pds-assign-100-tabs">
                    @foreach($destinationBranches as $branchTab)
                        @php $count = (int) ($tabCounts[$branchTab->id] ?? 0); @endphp
                        <a href="{{ route('order.dispatch.assign-100', ['to_branch_id' => $branchTab->id]) }}"
                           class="pds-assign-100-tab{{ $activeToBranchId === (int) $branchTab->id ? ' is-active' : '' }}">
                            <span>{{ $branchTab->name }}</span>
                            <em>{{ $count }}</em>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="pds-dispatch-to-assign-filter pds-assign-100-filter">
                <div class="pds-dispatch-to-assign-filter-grid pds-dispatch-assign-100-filter-grid">
                    <div class="pds-dispatch-to-assign-os-filter">
                        <div class="pds-dispatch-field pds-dispatch-field-sm">
                            <label for="assign_100_os_name">{{ __('message.os_name') }}</label>
                            <div class="pds-follow-up-os-input-wrap">
                                <input type="text" id="assign_100_os_name" class="pds-dispatch-input" placeholder="{{ __('message.os_name') }} / {{ __('message.phone') }}" autocomplete="off">
                                <button type="button" class="pds-dispatch-to-assign-os-search-btn" id="openAssign100OsSearch" title="{{ __('message.to_find_os_name') }}">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="assign_100_customer_search">{{ __('message.customer_name') }} / {{ __('message.phone') }}</label>
                        <div class="pds-assign-100-input-icon">
                            <i class="fas fa-user" aria-hidden="true"></i>
                            <input type="text" id="assign_100_customer_search" class="pds-dispatch-input" placeholder="{{ __('message.customer_name') }} / {{ __('message.phone') }}" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <div class="pds-dispatch-to-assign-body">
                @if($items->isEmpty())
                    <div class="pds-dispatch-to-assign-empty" id="assign100EmptyState">
                        <i class="fas fa-inbox"></i>
                        <p>{{ __('message.no_record_found') }}</p>
                    </div>
                @else
                    <div class="pds-dispatch-to-assign-empty d-none" id="assign100FilterEmptyState">
                        <i class="fas fa-search"></i>
                        <p>{{ __('message.no_record_found') }}</p>
                    </div>
                    <div class="pds-dispatch-to-assign-table-shell pds-table-shell" id="assign100TableShell">
                        <table class="table w-100 pds-dispatch-to-assign-table" id="assign100Table">
                            <thead>
                                <tr>
                                    <th>{{ __('message.no') }}</th>
                                    <th>
                                        <input type="checkbox" id="assign100SelectAll" title="{{ __('message.select_all') }}">
                                    </th>
                                    <th>{{ __('message.order') }}</th>
                                    <th>{{ __('message.received_date') }}</th>
                                    <th>{{ __('message.voucher_code') }}</th>
                                    <th>{{ __('message.status') }}</th>
                                    <th>{{ __('message.from_to') }}</th>
                                    <th>{{ __('message.os_name') }}</th>
                                    <th>{{ __('message.os_address') }}</th>
                                    <th>{{ __('message.pickup') }}</th>
                                    <th>{{ __('message.customer_name') }}</th>
                                    <th>{{ __('message.phone') }}</th>
                                    <th>{{ __('message.address') }}</th>
                                    <th>{{ __('message.township') }}</th>
                                    <th>{{ __('message.delivery_man') }}</th>
                                    <th>{{ __('message.cust_paid') }}</th>
                                    <th>{{ __('message.item_value') }}</th>
                                    <th>{{ __('message.deli_amount') }}</th>
                                    <th>{{ __('message.remark_label') }}</th>
                                    @if(($assignMode ?? '') !== 'mdy_inbound')
                                        <th>{{ __('message.action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $index => $item)
                                    @php
                                        $workflow = app(\App\Services\DispatchOrderWorkflowService::class);
                                        $order = $item->order;
                                        $pickup = resolveDispatchPickupPoint($order);
                                        $osName = resolveDispatchOsName($order);
                                        $osPhone = resolveDispatchOsPhone($order);
                                        $osAddress = resolveDispatchOsAddress($order);
                                        $pickupRider = optional($order?->delivery_man)->name ?? '-';
                                        $deliveryRider = optional($item->deliveryMan)->name ?? '-';
                                        $fromTo = trim((optional($item->fromBranch)->name ?? '-') . ' - ' . (optional($item->toBranch)->name ?? '-'));
                                        $township = \App\Models\DispatchOrderItem::deliveryCityLabel($item->delivery_city);
                                        if ($township === '-' && $item->township) {
                                            $township = $item->township;
                                        }
                                        $orderDate = $order?->created_at
                                            ? \Carbon\Carbon::parse($order->created_at)->format('d-m-Y')
                                            : '-';
                                        $receivedDate = $item->received_date
                                            ? \Carbon\Carbon::parse($item->received_date)->format('d-m-Y')
                                            : '-';
                                    @endphp
                                    <tr
                                        data-os-name="{{ $osName !== '-' ? $osName : '' }}"
                                        data-os-phone="{{ $osPhone !== '-' ? $osPhone : '' }}"
                                        data-customer-name="{{ $item->customer_name ?: '' }}"
                                        data-customer-phone="{{ $item->customer_phone ?: '' }}"
                                        data-item-id="{{ $item->id }}"
                                        data-to-branch-id="{{ (int) ($item->to_branch_id ?? 0) }}"
                                        data-kyo-shin="{{ $item->kyoShinItem ? '1' : '0' }}"
                                    >
                                        <td class="assign-100-row-no">{{ $index + 1 }}</td>
                                        <td>
                                            <input type="checkbox" class="pds-dispatch-item-check" value="{{ $item->id }}" data-item-value="{{ (float) ($item->item_value ?? 0) }}" data-item-code="{{ $item->code }}">
                                        </td>
                                        <td>{{ $orderDate }}</td>
                                        <td>{{ $receivedDate }}</td>
                                        <td>
                                            {{ $item->code ?? '-' }}
                                            @if($item->kyoShinItem)
                                                <span class="pds-kyo-shin-row-badge">{{ __('message.kyo_shin_title') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="pds-dispatch-status {{ $workflow->assign100ItemStatusClass($item) }}">{{ $workflow->assign100ItemStatusLabel($item) }}</span>
                                        </td>
                                        <td>{{ $fromTo }}</td>
                                        <td class="js-assign100-toggle-check" title="{{ __('message.select') }}">{{ $osName }}</td>
                                        <td title="{{ $osAddress }}">{{ stringLong($osAddress, 'title', 18) ?: '-' }}</td>
                                        <td>{{ $pickupRider }}</td>
                                        <td class="js-assign100-toggle-check" title="{{ __('message.select') }}">{{ $item->customer_name ?: '-' }}</td>
                                        <td>{{ $item->customer_phone ?: '-' }}</td>
                                        <td title="{{ $item->customer_address }}">{{ stringLong($item->customer_address ?? '', 'title', 18) ?: '-' }}</td>
                                        <td>{{ $township }}</td>
                                        <td>{{ $deliveryRider !== '-' ? $deliveryRider : '-' }}</td>
                                        <td>{{ (float) $item->advance_paid == 0.0 ? '' : number_format((float) $item->advance_paid) }}</td>
                                        <td>{{ number_format((float) $item->item_value) }}</td>
                                        <td class="text-right pds-follow-up-num-cell">{!! formatDispatchDeliAmountHtml($item) !!}</td>
                                        <td title="{{ $item->remark }}">{{ stringLong($item->remark ?? '', 'title', 16) ?: '-' }}</td>
                                        @if(($assignMode ?? '') !== 'mdy_inbound')
                                            <td>
                                                @include('order.dispatch-item-action', ['item' => $item, 'hideGate' => true, 'showAssign100Print' => true])
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('order.partials._os_search_modal')
    @include('order.partials._rider_assign_modal')
    @include('order.partials._dispatch-item-message-modal')

    <div class="pds-label-popup" id="assign100LabelPopup" hidden>
        <div class="pds-label-popup__backdrop" data-label-popup-close></div>
        <div class="pds-label-popup__panel" role="dialog" aria-modal="true" aria-label="{{ __('message.assign_100_print_labels') }}">
            <div class="pds-label-popup__head">
                <strong id="assign100LabelPopupTitle">{{ __('message.assign_100_print_labels') }}</strong>
                <div class="pds-label-popup__actions">
                    <button type="button" class="pds-label-popup__btn" id="assign100LabelPrintBtn">{{ __('message.print') }}</button>
                    <button type="button" class="pds-label-popup__btn pds-label-popup__btn--ghost" data-label-popup-close>{{ __('message.close') }}</button>
                </div>
            </div>
            <div class="pds-label-popup__body">
                <div class="pds-label-popup__loading" id="assign100LabelLoading" aria-live="polite">
                    <span class="pds-label-popup__spinner" aria-hidden="true"></span>
                    Loading…
                </div>
                <iframe class="pds-label-popup__frame" id="assign100LabelFrame" title="{{ __('message.assign_100_print_labels') }}"></iframe>
            </div>
        </div>
    </div>

    @if($showKyoShinAction)
    <div class="modal fade pds-dispatch-modal" id="kyoShinGiveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
            <div class="modal-content pds-kyo-shin-modal">
                <div class="pds-kyo-shin-modal__header">
                    <div class="pds-kyo-shin-modal__heading">
                        <span class="pds-kyo-shin-modal__icon" aria-hidden="true">
                            <i class="fas fa-hand-holding-usd"></i>
                        </span>
                        <div>
                            <h5 class="pds-kyo-shin-modal__title">{{ __('message.kyo_shin_give') }}</h5>
                            <span class="pds-kyo-shin-modal__count" id="kyoShinSelectedCountLabel">
                                {{ __('message.kyo_shin_selected_count', ['count' => 0]) }}
                            </span>
                        </div>
                    </div>
                    <button type="button" class="pds-kyo-shin-modal__close" data-dismiss="modal" aria-label="{{ __('message.close') }}">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="pds-kyo-shin-modal__body">
                    <div class="pds-kyo-shin-modal__steps">
                        <div class="pds-kyo-shin-modal__step">
                            <i class="fas fa-calendar-plus" aria-hidden="true"></i>
                            <span>{{ __('message.kyo_shin_give_step_date') }}</span>
                        </div>
                        <div class="pds-kyo-shin-modal__step">
                            <i class="fas fa-flag-checkered" aria-hidden="true"></i>
                            <span>{{ __('message.kyo_shin_give_step_due') }}</span>
                        </div>
                    </div>
                    <div class="pds-kyo-shin-modal__field">
                        <label for="kyo_shin_due_finished_at">{{ __('message.kyo_shin_due_date') }}</label>
                        <div class="pds-kyo-shin-modal__date">
                            <i class="far fa-calendar-alt" aria-hidden="true"></i>
                            <input type="text" id="kyo_shin_due_finished_at" class="dispatch-datepicker" value="{{ now('Asia/Yangon')->addDays(7)->format('d-m-Y') }}" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="pds-kyo-shin-modal__footer">
                    <button type="button" class="pds-kyo-shin-modal__btn pds-kyo-shin-modal__btn--ghost" data-dismiss="modal">{{ __('message.cancel') }}</button>
                    <button type="button" class="pds-kyo-shin-modal__btn pds-kyo-shin-modal__btn--primary" id="confirmKyoShinGiveBtn">{{ __('message.kyo_shin_give') }}</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>


    @section('bottom_script')
        <script src="{{ asset('js/dispatch-os-fields.js') }}?v=10"></script>
        @include('order.partials._dispatch-item-message-scripts')
        <script>
            $(document).ready(function () {
                var osSearchRoute = "{{ route('ajax-list', ['type' => 'os_dispatch_search']) }}";
                var riderRoute = "{{ route('ajax-list', ['type' => 'dispatch_deliveryman_search']) }}";
                var activeToBranchId = {{ (int) ($activeToBranchId ?? 0) }};
                var osInputSelector = '#assign_100_os_name';
                var riderCache = [];
                var needsRider = @json((bool) ($needsRider ?? true));
                var assignActionUrl = @json($assignActionUrl ?? route('order.dispatch.assign-rider'));
                var showAssignAction = @json((bool) ($showAssignAction ?? true));
                var showSendToMdy = @json((bool) ($showSendToMdy ?? false));
                var sendToMdyUrl = @json($sendToMdyUrl ?? route('order.dispatch.send-to-mdy'));
                var showKyoShinAction = @json((bool) ($showKyoShinAction ?? false));
                var kyoShinActionUrl = @json($kyoShinActionUrl ?? route('order.dispatch.give-kyo-shin'));

                // Warm logo in browser cache so label iframe paints faster.
                try {
                    var warmLogo = new Image();
                    warmLogo.decoding = 'async';
                    warmLogo.src = @json(asset('images/airway-bill-logo.png'));
                } catch (e) {}

                window.reloadDispatchItemsTable = function () {
                    typeof window.adminLiveReloadPage === 'function' ? window.adminLiveReloadPage() : window.location.reload();
                };

                function renderOsRows(rows) {
                    var $body = $('#osSearchTableBody').empty();
                    if (!rows.length) {
                        $body.append('<tr class="pds-dispatch-empty-row"><td colspan="3">{{ __('message.no_record_found') }}</td></tr>');
                        return;
                    }
                    rows.forEach(function (row, idx) {
                        $('<tr class="pds-dispatch-table-row"></tr>')
                            .append('<td>' + (idx + 1) + '</td><td>' + (row.name || row.text || '') + '</td><td>' + (row.phone || '') + '</td>')
                            .data('item', row)
                            .appendTo($body);
                    });
                }

                function renderRiderRows(rows, activeIndex) {
                    var $body = $('#riderAssignTableBody').empty();
                    if (!rows.length) {
                        $body.append('<tr class="pds-dispatch-empty-row"><td colspan="4">{{ __('message.no_record_found') }}</td></tr>');
                        return;
                    }
                    var focusIdx = typeof activeIndex === 'number' ? activeIndex : 0;
                    if (focusIdx < 0) focusIdx = 0;
                    if (focusIdx >= rows.length) focusIdx = rows.length - 1;
                    rows.forEach(function (row, idx) {
                        var $tr = $('<tr class="pds-dispatch-table-row" tabindex="-1"></tr>');
                        if (idx === focusIdx) {
                            $tr.addClass('is-keyboard-active');
                        }
                        $tr.append('<td>' + (idx + 1) + '</td>')
                            .append('<td>' + (row.name || row.text || '') + '</td>')
                            .append('<td>' + (row.branch || row.city || '-') + '</td>')
                            .append('<td>' + (row.phone || '-') + '</td>')
                            .data('rider', row)
                            .appendTo($body);
                    });
                }

                function getVisibleRiderRows() {
                    return $('#riderAssignTableBody .pds-dispatch-table-row');
                }

                function getActiveRiderIndex() {
                    var $rows = getVisibleRiderRows();
                    var idx = $rows.index($rows.filter('.is-keyboard-active').first());
                    return idx < 0 ? 0 : idx;
                }

                function getRiderTableWrap() {
                    var wrap = document.getElementById('riderAssignTableWrap');
                    if (wrap) {
                        return wrap;
                    }
                    var $wrap = $('#riderAssignModal .pds-dispatch-table-wrap');
                    return $wrap.length ? $wrap.get(0) : null;
                }

                function resetRiderTableScroll() {
                    var wrap = getRiderTableWrap();
                    if (wrap) {
                        wrap.scrollTop = 0;
                    }
                    var modal = document.getElementById('riderAssignModal');
                    if (modal) {
                        modal.scrollTop = 0;
                    }
                }

                function scrollActiveRiderIntoView($row, index) {
                    var wrap = getRiderTableWrap();
                    if (!wrap || !$row.length) {
                        return;
                    }
                    // Keep early rows fully visible — never tuck #1/#2 under the header.
                    if (index <= 1) {
                        wrap.scrollTop = 0;
                        return;
                    }
                    var $wrap = $(wrap);
                    var wrapTop = wrap.scrollTop;
                    var rowTop = $row.offset().top - $wrap.offset().top + wrapTop;
                    var rowBottom = rowTop + $row.outerHeight();
                    var viewBottom = wrapTop + wrap.clientHeight;
                    var pad = 6;
                    if (rowTop < wrapTop + pad) {
                        wrap.scrollTop = Math.max(0, rowTop - pad);
                    } else if (rowBottom > viewBottom - pad) {
                        wrap.scrollTop = Math.max(0, rowBottom - wrap.clientHeight + pad);
                    }
                }

                function setActiveRiderIndex(index) {
                    var $rows = getVisibleRiderRows();
                    if (!$rows.length) {
                        return;
                    }
                    var next = index;
                    if (next < 0) next = 0;
                    if (next >= $rows.length) next = $rows.length - 1;
                    $rows.removeClass('is-keyboard-active');
                    var $active = $rows.eq(next).addClass('is-keyboard-active');
                    scrollActiveRiderIntoView($active, next);
                }

                function assignActiveRider() {
                    var $active = getVisibleRiderRows().filter('.is-keyboard-active').first();
                    if (!$active.length) {
                        $active = getVisibleRiderRows().first();
                    }
                    var rider = $active.data('rider');
                    var itemIds = getVisibleCheckedItemIds();
                    if (!rider || !rider.id || !itemIds.length) {
                        return;
                    }
                    submitAssignAction(itemIds, rider.id);
                }

                function isTypingTarget(el) {
                    if (!el) return false;
                    var tag = (el.tagName || '').toLowerCase();
                    if (tag === 'input' || tag === 'textarea' || tag === 'select') return true;
                    if (el.isContentEditable) return true;
                    return false;
                }

                function isRiderAssignModalOpen() {
                    var $modal = $('#riderAssignModal');
                    return $modal.length && ($modal.hasClass('show') || $modal.is(':visible'));
                }

                function openAssignRiderFromShortcut() {
                    if (!showAssignAction) {
                        return;
                    }
                    var itemIds = getVisibleCheckedItemIds();
                    if (!itemIds.length) {
                        if (typeof errorMessage === 'function') {
                            errorMessage('{{ __('message.select_items_to_assign') }}');
                        }
                        return;
                    }
                    if (needsRider) {
                        openRiderAssignModal();
                        return;
                    }
                    submitAssignAction(itemIds, null);
                }

                function filterRiders(term) {
                    var query = String(term || '').trim();
                    if (!query) {
                        return riderCache;
                    }
                    var matchFn = typeof window.pdsMatchNameOrPhone === 'function'
                        ? window.pdsMatchNameOrPhone
                        : null;
                    return riderCache.filter(function (row) {
                        var name = row.name || row.text || '';
                        var phone = row.phone || '';
                        if (matchFn) {
                            return matchFn(query, name, phone)
                                || String(row.city || '').toLowerCase().indexOf(query.toLowerCase()) !== -1
                                || String(row.branch || '').toLowerCase().indexOf(query.toLowerCase()) !== -1;
                        }
                        var q = query.toLowerCase();
                        return String(name).toLowerCase().indexOf(q) !== -1
                            || String(phone).toLowerCase().indexOf(q) !== -1
                            || String(row.city || '').toLowerCase().indexOf(q) !== -1
                            || String(row.branch || '').toLowerCase().indexOf(q) !== -1;
                    });
                }

                function loadRiders(callback) {
                    var params = {};
                    if (activeToBranchId > 0) {
                        params.branch_id = activeToBranchId;
                    }
                    $.get(riderRoute, params, function (res) {
                        riderCache = res.results || [];
                        if (typeof callback === 'function') {
                            callback(riderCache);
                        }
                    });
                }

                function openOsSearchModal() {
                    $('#os_modal_search').val('');
                    if (typeof window.refreshOsClientCache === 'function') {
                        window.refreshOsClientCache(function () {
                            renderOsRows(window.getOsClientList ? window.getOsClientList() : []);
                        });
                    } else {
                        $.get(osSearchRoute, { list_all: 1 }, function (res) {
                            renderOsRows(res.results || []);
                        });
                    }
                    $('#osSearchModal').modal('show');
                }

                function openRiderAssignModal() {
                    $('#rider_modal_search').val('');
                    resetRiderTableScroll();
                    loadRiders(function (rows) {
                        renderRiderRows(rows, 0);
                        resetRiderTableScroll();
                        setActiveRiderIndex(0);
                    });
                    $('#riderAssignModal')
                        .off('shown.bs.modal.assign100Rider')
                        .on('shown.bs.modal.assign100Rider', function () {
                            resetRiderTableScroll();
                            setActiveRiderIndex(0);
                            var search = document.getElementById('rider_modal_search');
                            if (search && typeof search.focus === 'function') {
                                try {
                                    search.focus({ preventScroll: true });
                                } catch (err) {
                                    search.focus();
                                }
                            }
                            // Focus can still nudge scroll in some browsers — snap back.
                            requestAnimationFrame(function () {
                                resetRiderTableScroll();
                                setActiveRiderIndex(0);
                            });
                        })
                        .modal('show');
                }

                function normalizeFilterValue(value) {
                    return String(value || '').toLowerCase().trim();
                }

                function applyAssign100Filters() {
                    var osTerm = String($(osInputSelector).val() || '').trim();
                    var customerTerm = normalizeFilterValue($('#assign_100_customer_search').val());
                    var visibleCount = 0;
                    var matchOs = typeof window.pdsMatchOsTerm === 'function'
                        ? window.pdsMatchOsTerm
                        : function (term, name, phone) {
                            var q = String(term || '').toLowerCase();
                            return !q
                                || String(name || '').toLowerCase().indexOf(q) !== -1
                                || String(phone || '').toLowerCase().indexOf(q) !== -1;
                        };

                    $('#assign100Table tbody tr').each(function () {
                        var $row = $(this);
                        var rowOsName = String($row.data('os-name') || '');
                        var rowOsPhone = String($row.data('os-phone') || '');
                        var rowCustomerName = normalizeFilterValue($row.data('customer-name'));
                        var rowCustomerPhone = normalizeFilterValue($row.data('customer-phone'));
                        var isMatch = (!osTerm || matchOs(osTerm, rowOsName, rowOsPhone))
                            && (!customerTerm || rowCustomerName.indexOf(customerTerm) !== -1 || rowCustomerPhone.indexOf(customerTerm) !== -1);

                        $row.toggleClass('d-none', !isMatch);
                        if (isMatch) {
                            visibleCount += 1;
                            $row.find('.assign-100-row-no').text(visibleCount);
                        }
                    });

                    $('#assign100ItemCount').text(visibleCount);
                    $('#assign100FilterEmptyState').toggleClass('d-none', visibleCount > 0);
                    $('#assign100TableShell').toggleClass('d-none', visibleCount === 0);
                    updateAssignRiderButtonState();
                }

                function getVisibleCheckedItemIds() {
                    var ids = [];
                    $('#assign100Table tbody tr:not(.d-none) .pds-dispatch-item-check:checked').each(function () {
                        ids.push(parseInt($(this).val(), 10));
                    });
                    return ids;
                }

                function getVisibleItemIds() {
                    var ids = [];
                    $('#assign100Table tbody tr:not(.d-none)').each(function () {
                        var id = parseInt($(this).data('item-id'), 10);
                        if (id) {
                            ids.push(id);
                        }
                    });
                    return ids;
                }

                var assign100LabelFrameReady = false;

                function openAssign100LabelPopup(url, count) {
                    var $popup = $('#assign100LabelPopup');
                    var $frame = $('#assign100LabelFrame');
                    if (!$popup.length || !$frame.length || !url) {
                        return;
                    }
                    var title = @json(__('message.assign_100_print_labels'));
                    if (count && count > 1) {
                        title += ' · ' + count;
                    }
                    assign100LabelFrameReady = false;
                    $('#assign100LabelPopupTitle').text(title);
                    $popup.removeAttr('hidden').addClass('is-open is-loading');
                    $('body').addClass('pds-label-popup-open');
                    $frame.off('load.assign100Labels').one('load.assign100Labels', function () {
                        // Keep spinner until iframe signals images/QR are paint-ready
                        // (or fall back shortly after load).
                        setTimeout(function () {
                            if (!assign100LabelFrameReady) {
                                assign100LabelFrameReady = true;
                                $popup.removeClass('is-loading');
                            }
                        }, 900);
                    });
                    $frame.attr('src', url);
                }

                window.addEventListener('message', function (ev) {
                    if (ev.origin !== window.location.origin) return;
                    if (!ev.data || ev.data.type !== 'pds-assign100-labels-ready') return;
                    assign100LabelFrameReady = true;
                    $('#assign100LabelPopup').removeClass('is-loading');
                });

                function openAssign100LabelsForIds(ids) {
                    ids = (ids || []).filter(function (id) { return id > 0; });
                    if (!ids.length) {
                        if (typeof errorMessage === 'function') {
                            errorMessage('{{ __('message.assign_100_select_to_print') }}');
                        }
                        return;
                    }
                    if (ids.length > 100) {
                        if (typeof errorMessage === 'function') {
                            errorMessage('{{ __('message.max_assign_100_items') }}');
                        }
                        return;
                    }
                    var params = ids.map(function (id) { return 'ids[]=' + encodeURIComponent(id); });
                    params.push('embed=1');
                    openAssign100LabelPopup(@json(route('order.dispatch.assign-100-labels')) + '?' + params.join('&'), ids.length);
                }

                function closeAssign100LabelPopup() {
                    var $popup = $('#assign100LabelPopup');
                    var $frame = $('#assign100LabelFrame');
                    $popup.attr('hidden', true).removeClass('is-open is-loading');
                    $frame.off('load.assign100Labels').attr('src', 'about:blank');
                    $('body').removeClass('pds-label-popup-open');
                }

                $(document).on('click', '.js-assign100-label-popup', function (e) {
                    e.preventDefault();
                    var rowId = parseInt($(this).closest('tr').find('.pds-dispatch-item-check').val(), 10);
                    var selected = getVisibleCheckedItemIds();
                    if (selected.length > 1 && selected.indexOf(rowId) !== -1) {
                        openAssign100LabelsForIds(selected);
                        return;
                    }
                    openAssign100LabelPopup($(this).data('url'), 1);
                });

                $(document).on('click', '[data-label-popup-close]', function (e) {
                    e.preventDefault();
                    closeAssign100LabelPopup();
                });

                $('#assign100LabelPrintBtn').on('click', function () {
                    var frame = document.getElementById('assign100LabelFrame');
                    if (!frame || !frame.contentWindow) {
                        return;
                    }
                    try {
                        if (typeof frame.contentWindow.pdsPrintLabels === 'function') {
                            frame.contentWindow.pdsPrintLabels();
                            return;
                        }
                        frame.contentWindow.postMessage({ type: 'pds-assign100-labels-print' }, window.location.origin);
                    } catch (e) {
                        try {
                            frame.contentWindow.focus();
                            frame.contentWindow.print();
                        } catch (e2) {}
                    }
                });

                $(document).on('keydown.assign100LabelPopup', function (e) {
                    if (e.key === 'Escape' && $('#assign100LabelPopup').hasClass('is-open')) {
                        closeAssign100LabelPopup();
                    }
                });

                $(document).on('click', '#printAssign100LabelsBtn', function () {
                    openAssign100LabelsForIds(getVisibleCheckedItemIds());
                });

                function updateAssignRiderButtonState() {
                    var count = getVisibleCheckedItemIds().length;
                    $('#assignRiderBtn').prop('disabled', count === 0);
                    $('#sendToMdyBtn').prop('disabled', count === 0);
                    $('#giveKyoShinBtn').prop('disabled', count === 0);
                    var $print = $('#printAssign100LabelsBtn');
                    var $printCount = $('#assign100PrintCount');
                    $print.prop('disabled', count === 0);
                    if (count > 0) {
                        $printCount.text(String(count)).removeAttr('hidden');
                        $print.attr('title', @json(__('message.assign_100_print_labels')) + ' · ' + count);
                    } else {
                        $printCount.attr('hidden', true).text('0');
                        $print.attr('title', @json(__('message.assign_100_print_labels')));
                    }
                }

                function submitAssignAction(itemIds, riderId) {
                    if (!itemIds.length) {
                        return;
                    }
                    if (needsRider && !riderId) {
                        return;
                    }

                    if (itemIds.length > 100) {
                        if (typeof errorMessage === 'function') {
                            errorMessage('{{ __('message.max_assign_100_items') }}');
                        }
                        return;
                    }

                    $('#assignRiderBtn').prop('disabled', true);

                    var payload = {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        item_ids: itemIds
                    };
                    if (needsRider && riderId) {
                        payload.delivery_man_id = riderId;
                    }

                    $.ajax({
                        url: assignActionUrl,
                        type: 'POST',
                        data: payload,
                        success: function (res) {
                            $('#riderAssignModal').modal('hide');
                            if (res && res.message && typeof showMessage === 'function') {
                                showMessage(res.message);
                            }
                            if (res && res.redirect) {
                                if (typeof window.pdsAdminGo === 'function') window.pdsAdminGo(res.redirect);
                                else window.location.href = res.redirect;
                                return;
                            }
                            typeof window.adminLiveReloadPage === 'function' ? window.adminLiveReloadPage() : window.location.reload();
                        },
                        error: function (xhr) {
                            updateAssignRiderButtonState();
                            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                ? xhr.responseJSON.message
                                : '{{ __('message.something_went_wrong') }}';
                            if (typeof errorMessage === 'function') {
                                errorMessage(msg);
                            }
                        }
                    });
                }

                if (typeof window.initDispatchOsFields === 'function') {
                    window.initDispatchOsFields({ osSearchRoute: osSearchRoute });
                }

                $('#openAssign100OsSearch').on('click', openOsSearchModal);

                var osSearchTimer;
                $('#os_modal_search').on('input', function () {
                    clearTimeout(osSearchTimer);
                    var term = $(this).val();
                    osSearchTimer = setTimeout(function () {
                        if (typeof window.getOsClientList === 'function') {
                            renderOsRows(window.getOsClientList(term));
                            return;
                        }
                        $.get(osSearchRoute, { q: term }, function (res) {
                            renderOsRows(res.results || []);
                        });
                    }, 200);
                });

                var riderSearchTimer;
                $('#rider_modal_search').on('input', function () {
                    clearTimeout(riderSearchTimer);
                    var term = $(this).val();
                    riderSearchTimer = setTimeout(function () {
                        renderRiderRows(filterRiders(term), 0);
                    }, 200);
                });

                $(document).on('click', '#osSearchTableBody .pds-dispatch-table-row', function () {
                    var item = $(this).data('item');
                    if (!item) {
                        return;
                    }
                    var osName = item.name || String(item.text || '').replace(/\s*\([^)]*\)\s*$/, '').trim();
                    $(osInputSelector).val(osName);
                    $('#osSearchModal').modal('hide');
                    applyAssign100Filters();
                });

                $(document).on('click', '#riderAssignTableBody .pds-dispatch-table-row', function () {
                    var $row = $(this);
                    getVisibleRiderRows().removeClass('is-keyboard-active');
                    $row.addClass('is-keyboard-active');
                    var rider = $row.data('rider');
                    var itemIds = getVisibleCheckedItemIds();
                    if (!rider || !rider.id || !itemIds.length) {
                        return;
                    }
                    submitAssignAction(itemIds, rider.id);
                });

                // Click OS Name / Cust Name → toggle row checkbox.
                $(document).on('click', '#assign100Table .js-assign100-toggle-check', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var $row = $(this).closest('tr');
                    if ($row.hasClass('d-none')) {
                        return;
                    }
                    var $check = $row.find('.pds-dispatch-item-check');
                    if (!$check.length) {
                        return;
                    }
                    $check.prop('checked', !$check.prop('checked')).trigger('change');
                });

                // Shortcuts: "." opens Assign Rider; arrows + Enter inside rider modal.
                $(document).on('keydown.assign100Shortcuts', function (e) {
                    if (e.defaultPrevented || e.altKey || e.ctrlKey || e.metaKey) {
                        return;
                    }

                    var key = e.key;
                    var riderModalOpen = isRiderAssignModalOpen();
                    var typing = isTypingTarget(e.target);
                    var inRiderSearch = $(e.target).is('#rider_modal_search');

                    if (riderModalOpen) {
                        if (key === 'ArrowDown' || key === 'ArrowUp') {
                            if (typing && !inRiderSearch) {
                                return;
                            }
                            e.preventDefault();
                            var current = getActiveRiderIndex();
                            setActiveRiderIndex(key === 'ArrowDown' ? current + 1 : current - 1);
                            return;
                        }
                        if (key === 'Enter') {
                            if (typing && !inRiderSearch) {
                                return;
                            }
                            e.preventDefault();
                            assignActiveRider();
                            return;
                        }
                        return;
                    }

                    // P → open Print airway bill popup for the selected rows.
                    if ((key === 'p' || key === 'P') && !typing) {
                        if ($('#assign100LabelPopup').hasClass('is-open')) {
                            return;
                        }
                        if ($('.modal.show').length) {
                            return;
                        }
                        e.preventDefault();
                        openAssign100LabelsForIds(getVisibleCheckedItemIds());
                        return;
                    }

                    // Dot (.) → open Assign Rider popup (ignore while typing in filters).
                    if ((key === '.' || key === 'Period') && !typing) {
                        if ($('#assign100LabelPopup').hasClass('is-open')) {
                            return;
                        }
                        if ($('.modal.show').length) {
                            return;
                        }
                        e.preventDefault();
                        openAssignRiderFromShortcut();
                    }
                });

                var assign100FilterTimer;
                $(osInputSelector + ', #assign_100_customer_search').on('input', function () {
                    clearTimeout(assign100FilterTimer);
                    assign100FilterTimer = setTimeout(applyAssign100Filters, 150);
                });

                $(document).on('change', '#assign100SelectAll', function () {
                    var isChecked = $(this).is(':checked');
                    $('#assign100Table tbody tr:not(.d-none) .pds-dispatch-item-check').prop('checked', isChecked);
                    updateAssignRiderButtonState();
                });

                $(document).on('change', '.pds-dispatch-item-check', function () {
                    var $visibleChecks = $('#assign100Table tbody tr:not(.d-none) .pds-dispatch-item-check');
                    var allChecked = $visibleChecks.length > 0 && $visibleChecks.filter(':checked').length === $visibleChecks.length;
                    $('#assign100SelectAll').prop('checked', allChecked);
                    updateAssignRiderButtonState();
                });

                $('#giveKyoShinBtn').on('click', function () {
                    var itemIds = getVisibleCheckedItemIds();
                    if (!itemIds.length) {
                        if (typeof errorMessage === 'function') {
                            errorMessage('{{ __('message.select_items_to_assign') }}');
                        }
                        return;
                    }
                    $('#kyoShinSelectedCountLabel').text(
                        {!! json_encode(__('message.kyo_shin_selected_count', ['count' => '__COUNT__'])) !!}.replace('__COUNT__', String(itemIds.length))
                    );
                    $('#kyoShinGiveModal').modal('show');
                });

                $('#confirmKyoShinGiveBtn').on('click', function () {
                    var itemIds = getVisibleCheckedItemIds();
                    var dueDate = String($('#kyo_shin_due_finished_at').val() || '').trim();
                    if (!itemIds.length) {
                        return;
                    }
                    if (!dueDate) {
                        if (typeof errorMessage === 'function') {
                            errorMessage('{{ __('message.kyo_shin_due_required') }}');
                        }
                        return;
                    }
                    if (itemIds.length > 100) {
                        if (typeof errorMessage === 'function') {
                            errorMessage('{{ __('message.max_assign_100_items') }}');
                        }
                        return;
                    }
                    $('#confirmKyoShinGiveBtn').prop('disabled', true);
                    $('#giveKyoShinBtn').prop('disabled', true);
                    $.ajax({
                        url: kyoShinActionUrl,
                        type: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            item_ids: itemIds,
                            due_finished_at: dueDate
                        },
                        success: function (res) {
                            $('#kyoShinGiveModal').modal('hide');
                            if (res && res.message && typeof showMessage === 'function') {
                                showMessage(res.message);
                            }
                            typeof window.adminLiveReloadPage === 'function' ? window.adminLiveReloadPage() : window.location.reload();
                        },
                        error: function (xhr) {
                            $('#confirmKyoShinGiveBtn').prop('disabled', false);
                            updateAssignRiderButtonState();
                            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                ? xhr.responseJSON.message
                                : '{{ __('message.something_went_wrong') }}';
                            if (typeof errorMessage === 'function') {
                                errorMessage(msg);
                            }
                        }
                    });
                });

                $('#assignRiderBtn').on('click', function () {
                    var itemIds = getVisibleCheckedItemIds();
                    if (!itemIds.length) {
                        if (typeof errorMessage === 'function') {
                            errorMessage('{{ __('message.select_items_to_assign') }}');
                        }
                        return;
                    }
                    if (needsRider) {
                        openRiderAssignModal();
                        return;
                    }
                    submitAssignAction(itemIds, null);
                });

                $('#sendToMdyBtn').on('click', function () {
                    var itemIds = getVisibleCheckedItemIds();
                    if (!itemIds.length) {
                        if (typeof errorMessage === 'function') {
                            errorMessage('{{ __('message.select_items_to_assign') }}');
                        }
                        return;
                    }
                    if (itemIds.length > 100) {
                        if (typeof errorMessage === 'function') {
                            errorMessage('{{ __('message.max_assign_100_items') }}');
                        }
                        return;
                    }
                    $('#sendToMdyBtn').prop('disabled', true);
                    $.ajax({
                        url: sendToMdyUrl,
                        type: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            item_ids: itemIds
                        },
                        success: function (res) {
                            if (res && res.message && typeof showMessage === 'function') {
                                showMessage(res.message);
                            }
                            if (res && res.redirect) {
                                if (typeof window.pdsAdminGo === 'function') window.pdsAdminGo(res.redirect);
                                else window.location.href = res.redirect;
                                return;
                            }
                            typeof window.adminLiveReloadPage === 'function' ? window.adminLiveReloadPage() : window.location.reload();
                        },
                        error: function (xhr) {
                            updateAssignRiderButtonState();
                            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                ? xhr.responseJSON.message
                                : '{{ __('message.something_went_wrong') }}';
                            if (typeof errorMessage === 'function') {
                                errorMessage(msg);
                            }
                        }
                    });
                });

                updateAssignRiderButtonState();

                $(document).on('click', '.pds-dispatch-action-edit.loadRemoteModel', function (e) {
                    e.stopPropagation();
                });

                $(document).on('click', '[data-dispatch-item-delete]', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var $btn = $(this);
                    var url = $btn.data('delete-url');
                    var title = $btn.data('title') || '{{ __('message.confirmation') }}';
                    var message = $btn.data('message') || '{{ __('message.delete_msg') }}';
                    var storageDark = localStorage.getItem('dark');
                    var theme = (storageDark == 'false') ? 'material' : 'dark';

                    if (!url || typeof $.confirm !== 'function') {
                        return;
                    }

                    $.confirm({
                        title: title,
                        content: message,
                        type: '',
                        theme: theme,
                        buttons: {
                            yes: {
                                action: function () {
                                    $.ajax({
                                        url: url,
                                        type: 'DELETE',
                                        headers: {
                                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                        },
                                        success: function (res) {
                                            if (res && res.message && typeof showMessage === 'function') {
                                                showMessage(res.message);
                                            }
                                            typeof window.adminLiveReloadPage === 'function' ? window.adminLiveReloadPage() : window.location.reload();
                                        },
                                        error: function (xhr) {
                                            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                                ? xhr.responseJSON.message
                                                : '{{ __('message.something_went_wrong') }}';
                                            if (typeof errorMessage === 'function') {
                                                errorMessage(msg);
                                            }
                                        }
                                    });
                                }
                            },
                            no: {
                                action: function () {}
                            }
                        }
                    });
                });
            });
        </script>
    @endsection
</x-master-layout>
