<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-kyo-shin-page">
        <div class="pds-dispatch-to-assign-screen pds-rider-screen">
            <div class="pds-rider-hero pds-rider-hero--details">
                <div class="pds-rider-hero__copy">
                    <div class="pds-rider-hero__eyebrow">
                        <i class="fas fa-box-open" aria-hidden="true"></i>
                        <span>{{ __('message.kyo_shin_title') }}</span>
                    </div>
                    <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                    <div class="pds-rider-meta-row">
                        <span class="pds-rider-meta-pill">
                            <i class="fas fa-store" aria-hidden="true"></i>
                            {{ $osName }}
                        </span>
                        <span class="pds-rider-meta-pill pds-rider-meta-pill--count">
                            {{ __('message.item_count') }}
                            <strong>{{ $items->count() }}</strong>
                        </span>
                    </div>
                </div>
                <a
                    href="{{ route('order.kyo-shin', [
                        'scope' => $scopeKey,
                        'tab' => $tab,
                        'from_date' => $fromRaw,
                        'to_date' => $toRaw,
                    ]) }}"
                    class="pds-rider-close-btn"
                    title="{{ __('message.close') }}"
                >
                    <i class="fas fa-times"></i>
                </a>
            </div>

            @if($canMark || $canFinish || $tab === \App\Services\KyoShinService::TAB_FINISHED)
                <div class="pds-rider-toolbar">
                    <div class="pds-rider-toolbar__fields">
                        <div class="pds-dispatch-rider-items-total-wrap">
                            <span class="pds-dispatch-rider-items-total-label">{{ __('message.total') }}</span>
                            <div class="pds-dispatch-rider-items-total">
                                {{ number_format($items->sum(fn ($item) => (float) ($item->kyo_shin_amount ?? 0))) }}
                            </div>
                        </div>
                    </div>
                    <div class="pds-rider-toolbar__aside">
                        @if($canMark)
                            <form method="POST" action="{{ route('order.kyo-shin.mark-paid', $osId) }}">
                                @csrf
                                <input type="hidden" name="scope" value="{{ $scopeKey }}">
                                <input type="hidden" name="from_date" value="{{ $fromRaw }}">
                                <input type="hidden" name="to_date" value="{{ $toRaw }}">
                                <button type="submit" class="pds-rider-check-btn">
                                    <i class="fas fa-coins" aria-hidden="true"></i>
                                    <span>{{ __('message.kyo_shin_mark_paid') }}</span>
                                </button>
                            </form>
                        @endif
                        @if($canFinish)
                            <form method="POST" action="{{ route('order.kyo-shin.finish', $osId) }}">
                                @csrf
                                <input type="hidden" name="scope" value="{{ $scopeKey }}">
                                <input type="hidden" name="from_date" value="{{ $fromRaw }}">
                                <input type="hidden" name="to_date" value="{{ $toRaw }}">
                                <button type="submit" class="pds-rider-check-btn">
                                    <i class="fas fa-check" aria-hidden="true"></i>
                                    <span>{{ __('message.kyo_shin_mark_finished') }}</span>
                                </button>
                            </form>
                        @endif
                        @if($tab === \App\Services\KyoShinService::TAB_FINISHED && $items->isNotEmpty())
                            <form method="POST" action="{{ route('order.kyo-shin.send-to-os', $osId) }}" id="kyoShinSendSelectedForm">
                                @csrf
                                <input type="hidden" name="scope" value="{{ $scopeKey }}">
                                <input type="hidden" name="tab" value="{{ $tab }}">
                                <input type="hidden" name="from" value="items">
                                <input type="hidden" name="from_date" value="{{ $fromRaw }}">
                                <input type="hidden" name="to_date" value="{{ $toRaw }}">
                                <button type="submit" class="pds-rider-check-btn" id="kyoShinSendSelectedBtn" disabled>
                                    <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                    <span>{{ __('message.kyo_shin_send_to_os') }}</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            <div class="pds-rider-table-shell pds-rider-table-shell--scroll pds-no-freeze">
                @if($items->isEmpty())
                    <div class="pds-os-settlement-section__empty">
                        <p>{{ __('message.kyo_shin_empty') }}</p>
                    </div>
                @else
                    <table class="table pds-rider-list-table">
                        <thead>
                            <tr>
                                @if($tab === \App\Services\KyoShinService::TAB_FINISHED)
                                    <th class="pds-rider-sticky-col pds-rider-sticky-col--check">
                                        <input type="checkbox" id="kyoShinItemsSelectAll" title="{{ __('message.select_all') }}">
                                    </th>
                                @endif
                                <th class="pds-rider-col-no">{{ __('message.no') }}</th>
                                <th>{{ __('message.code') }}</th>
                                <th>{{ __('message.customer_name') }}</th>
                                <th>{{ __('message.phone') }}</th>
                                <th>{{ __('message.kyo_shin_paid_date') }}</th>
                                <th>{{ __('message.kyo_shin_due_date') }}</th>
                                <th>{{ __('message.status') }}</th>
                                <th>{{ __('message.kyo_shin_proof_image') }}</th>
                                @if($tab === \App\Services\KyoShinService::TAB_ADVANCED_PAID)
                                    <th>{{ __('message.kyo_shin_finished_date') }}</th>
                                @elseif($tab === \App\Services\KyoShinService::TAB_FINISHED)
                                    <th>{{ __('message.kyo_shin_return_date') }}</th>
                                @endif
                                <th class="text-right">{{ __('message.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $index => $item)
                                @php
                                    $isInReturnedMoney = ! empty($item->kyoShinItem?->received_at)
                                        || ! empty($item->kyoShinItem?->checked_at);
                                @endphp
                                <tr class="{{ $isInReturnedMoney ? 'is-kyo-shin-received' : '' }}">
                                    @if($tab === \App\Services\KyoShinService::TAB_FINISHED)
                                        <td class="pds-rider-sticky-col pds-rider-sticky-col--check">
                                            <input type="checkbox" class="js-kyo-shin-item-check" value="{{ $item->id }}">
                                        </td>
                                    @endif
                                    <td class="pds-rider-col-no">{{ $index + 1 }}</td>
                                    <td>{{ $item->code ?: '-' }}</td>
                                    <td>{{ $item->customer_name ?: '-' }}</td>
                                    <td>{{ $item->customer_phone ?: '-' }}</td>
                                    <td>
                                        {{ $item->kyoShinItem?->advanced_paid_at
                                            ? $item->kyoShinItem->advanced_paid_at->timezone('Asia/Yangon')->format('d-m-Y')
                                            : '-' }}
                                    </td>
                                    <td>
                                        @include('order.partials._kyo-shin-due-editor', [
                                            'dueValue' => $item->kyoShinItem?->due_finished_at
                                                ? \Carbon\Carbon::parse($item->kyoShinItem->due_finished_at)->format('d-m-Y')
                                                : '',
                                            'overdueDays' => 0,
                                            'canEditDue' => $canEditDue ?? false,
                                            'saveUrl' => route('order.kyo-shin.item-due-date', $item->id),
                                            'itemId' => $item->id,
                                        ])
                                    </td>
                                    <td>
                                        <div class="pds-kyo-shin-status-stack">
                                            @if((string) ($item->status ?? '') === 'return')
                                                <span class="pds-kyo-shin-row-badge">{{ __('message.follow_up_status_return') }}</span>
                                            @elseif((string) ($item->status ?? '') === 'os_returned')
                                                <span class="pds-kyo-shin-row-badge">{{ __('message.follow_up_status_os_returned') }}</span>
                                            @elseif((string) ($item->status ?? '') === 'completed')
                                                <span class="pds-kyo-shin-row-badge">{{ __('message.follow_up_status_completed') }}</span>
                                            @else
                                                {{ $item->status ?: '-' }}
                                            @endif
                                            @if($tab === \App\Services\KyoShinService::TAB_FINISHED && $isInReturnedMoney)
                                                <span class="pds-kyo-shin-received-badge">
                                                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                                                    {{ __('message.kyo_shin_returned_today') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $kyoShinImages = $item->kyoShinItem?->batch?->slipPhotoUrls() ?? [];
                                        @endphp
                                        @if($kyoShinImages !== [])
                                            <div class="pds-kyo-shin-proofs">
                                                @foreach($kyoShinImages as $imageUrl)
                                                    <a href="{{ $imageUrl }}" target="_blank" rel="noopener" class="pds-kyo-shin-proof">
                                                        <img src="{{ $imageUrl }}" alt="{{ __('message.kyo_shin_proof_image') }}">
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    @if($tab === \App\Services\KyoShinService::TAB_ADVANCED_PAID)
                                        <td>
                                            {{ $item->kyoShinItem?->finished_at
                                                ? $item->kyoShinItem->finished_at->timezone('Asia/Yangon')->format('d-m-Y')
                                                : ($item->admin_finished_at ? \Carbon\Carbon::parse($item->admin_finished_at)->timezone('Asia/Yangon')->format('d-m-Y') : '-') }}
                                        </td>
                                    @elseif($tab === \App\Services\KyoShinService::TAB_FINISHED)
                                        <td>
                                            {{ $item->admin_updated_at
                                                ? \Carbon\Carbon::parse($item->admin_updated_at)->timezone('Asia/Yangon')->format('d-m-Y')
                                                : ($item->kyoShinItem?->finished_at
                                                    ? $item->kyoShinItem->finished_at->timezone('Asia/Yangon')->format('d-m-Y')
                                                    : '-') }}
                                        </td>
                                    @endif
                                    <td class="text-right">{{ number_format((float) ($item->kyo_shin_amount ?? 0)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        @php
                            $itemsAmountTotal = (float) $items->sum(fn ($item) => (float) ($item->kyo_shin_amount ?? 0));
                            $itemsAmountColspan = 8
                                + ($tab === \App\Services\KyoShinService::TAB_ADVANCED_PAID ? 1 : 0)
                                + ($tab === \App\Services\KyoShinService::TAB_FINISHED ? 2 : 0);
                        @endphp
                        <tfoot>
                            <tr class="pds-daily-check-total-row">
                                <td colspan="{{ $itemsAmountColspan }}" class="pds-daily-check-total-label">{{ __('message.total') }}</td>
                                <td class="text-right">{{ number_format($itemsAmountTotal) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </div>
        </div>
    </div>
    <style>
        .pds-kyo-shin-row-badge {
            display: inline-flex; align-items: center; padding: 2px 7px;
            border-radius: 999px; font-size: 10px; font-weight: 800;
            background: #ffedd5; color: #c2410c;
        }
        .pds-kyo-shin-proofs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            max-width: 220px;
        }
        .pds-kyo-shin-proof {
            display: block;
            width: 72px;
            height: 72px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            background: #fff;
        }
        .pds-kyo-shin-proof img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .pds-kyo-shin-proof:hover { border-color: #fdba74; }
        .pds-kyo-shin-due-cell { display: inline-flex; flex-direction: column; gap: 4px; min-width: 148px; }
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
        .pds-kyo-shin-status-stack { display: inline-flex; flex-direction: column; align-items: flex-start; gap: 6px; }
        .pds-kyo-shin-received-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 8px; border-radius: 999px;
            background: #dcfce7; color: #15803d;
            font-size: 10px; font-weight: 800; letter-spacing: .02em;
        }
        .pds-kyo-shin-received-mark {
            display: inline-flex; align-items: center; justify-content: center;
            width: 22px; height: 22px; border-radius: 999px;
            background: #16a34a; color: #fff;
        }
        .pds-kyo-shin-received-mark i { font-size: 11px; line-height: 1; }
        .pds-rider-check-btn--received { background: #16a34a; }
        .pds-rider-check-btn--received:hover { background: #15803d; }
        .pds-rider-check-btn--received:disabled { opacity: .45; cursor: not-allowed; }
        tr.is-kyo-shin-received td { background: #f0fdf4; }
    </style>
    @push('bottom_script')
        @include('order.partials._kyo-shin-due-edit-scripts')
        @if($tab === \App\Services\KyoShinService::TAB_FINISHED)
        <script>
            (function () {
                var selectAll = document.getElementById('kyoShinItemsSelectAll');
                var boxes = Array.prototype.slice.call(document.querySelectorAll('.js-kyo-shin-item-check'));
                var sendBtn = document.getElementById('kyoShinSendSelectedBtn');
                var sendForm = document.getElementById('kyoShinSendSelectedForm');

                function selectedBoxes() {
                    return boxes.filter(function (box) { return box.checked; });
                }

                function sync() {
                    var checked = selectedBoxes().length;
                    if (sendBtn) sendBtn.disabled = checked === 0;
                    if (selectAll) {
                        selectAll.checked = boxes.length > 0 && checked === boxes.length;
                        selectAll.indeterminate = checked > 0 && checked < boxes.length;
                    }
                }

                function fillIds(form) {
                    form.querySelectorAll('input[name="item_ids[]"]').forEach(function (el) { el.remove(); });
                    selectedBoxes().forEach(function (box) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'item_ids[]';
                        input.value = box.value;
                        form.appendChild(input);
                    });
                }

                selectAll?.addEventListener('change', function () {
                    boxes.forEach(function (box) { box.checked = selectAll.checked; });
                    sync();
                });
                boxes.forEach(function (box) { box.addEventListener('change', sync); });
                sendForm?.addEventListener('submit', function (e) {
                    if (selectedBoxes().length === 0) {
                        e.preventDefault();
                        window.alert(@json(__('message.kyo_shin_send_to_os_none')));
                        return;
                    }
                    fillIds(sendForm);
                });
                sync();
            })();
        </script>
        @endif
    @endpush
</x-master-layout>
