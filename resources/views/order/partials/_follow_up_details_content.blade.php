@php
    $order = $item->order;
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
    $auditTimeline = $order
        ? app(\App\Services\DispatchOrderAuditService::class)->timelineForOrder($order)
        : collect();
@endphp

<div class="pds-follow-up-details-grid">
    <div class="pds-follow-up-details-item">
        <span class="pds-follow-up-details-label">{{ __('message.order') }}</span>
        <span class="pds-follow-up-details-value">{{ $orderDate }}</span>
    </div>
    <div class="pds-follow-up-details-item">
        <span class="pds-follow-up-details-label">{{ __('message.received_date') }}</span>
        <span class="pds-follow-up-details-value">{{ $receivedDate }}</span>
    </div>
    <div class="pds-follow-up-details-item">
        <span class="pds-follow-up-details-label">{{ __('message.voucher_code') }}</span>
        <span class="pds-follow-up-details-value">{{ $item->code ?? '-' }}</span>
    </div>
    <div class="pds-follow-up-details-item">
        <span class="pds-follow-up-details-label">{{ __('message.from_to') }}</span>
        <span class="pds-follow-up-details-value">{{ $fromTo }}</span>
    </div>
    <div class="pds-follow-up-details-item">
        <span class="pds-follow-up-details-label">{{ __('message.township') }}</span>
        <span class="pds-follow-up-details-value">{{ $township }}</span>
    </div>
    <div class="pds-follow-up-details-item">
        <span class="pds-follow-up-details-label">{{ __('message.cust_paid') }}</span>
        <span class="pds-follow-up-details-value">{{ (float) $item->advance_paid == 0.0 ? '-' : number_format((float) $item->advance_paid) }}</span>
    </div>
    <div class="pds-follow-up-details-item pds-follow-up-details-item-wide">
        <span class="pds-follow-up-details-label">{{ __('message.remark_label') }}</span>
        <span class="pds-follow-up-details-value">
            @include('order.partials._pending-remark-history', ['item' => $item, 'photoSize' => 120])
        </span>
    </div>
    @php
        $deliveredPhotoUrl = function_exists('dispatchItemProofPhotoUrl')
            ? dispatchItemProofPhotoUrl($item, 'delivered')
            : null;
        $deliveredType = trim((string) ($item->delivered_type ?? ''));
    @endphp
    @if($deliveredPhotoUrl || $deliveredType !== '')
        <div class="pds-follow-up-details-item pds-follow-up-details-item-wide">
            <span class="pds-follow-up-details-label">{{ __('message.delivered_image') }}</span>
            <span class="pds-follow-up-details-value">
                @if($deliveredType === 'gate')
                    {{ __('message.delivered_type_gate') }}
                    · {{ __('message.transport_fee') }} {{ number_format((float) ($item->gate_amount ?? 0)) }}
                @elseif($deliveredType === 'other')
                    {{ __('message.delivered_type_other') }}
                @endif
                @if($deliveredPhotoUrl)
                    <a href="{{ $deliveredPhotoUrl }}" target="_blank" rel="noopener" class="pds-follow-up-pending-photo d-block mt-2">
                        <img src="{{ $deliveredPhotoUrl }}" alt="{{ __('message.delivered_image') }}" style="max-width: 160px; max-height: 120px; border-radius: 10px; object-fit: cover; border: 1px solid #e5e7eb;">
                    </a>
                @endif
            </span>
        </div>
    @endif
</div>

<div class="pds-follow-up-audit">
    <div class="pds-follow-up-audit-header">
        <span class="pds-follow-up-audit-header-icon" aria-hidden="true">
            <i class="fa-solid fa-timeline"></i>
        </span>
        <div class="pds-follow-up-audit-header-text">
            <h6 class="pds-follow-up-audit-title">{{ __('message.dispatch_audit_log') }}</h6>
            @if($auditTimeline->isNotEmpty())
                <span class="pds-follow-up-audit-count">{{ $auditTimeline->count() }} events</span>
            @endif
        </div>
    </div>
    @if($auditTimeline->isEmpty())
        <p class="pds-follow-up-audit-empty">{{ __('message.dispatch_audit_empty') }}</p>
    @else
        <ol class="pds-follow-up-audit-list">
            @foreach($auditTimeline as $entry)
                @php
                    $tone = $entry['tone'] ?? 'neutral';
                    $summary = $entry['summary'] ?? $entry['message'] ?? '';
                    $action = $entry['action'] ?? null;
                    $actionParts = $entry['action_parts'] ?? [];
                    if ($actionParts === [] && $action) {
                        $actionParts = preg_split('/\s*[·+]\s*/u', (string) $action, -1, PREG_SPLIT_NO_EMPTY) ?: [(string) $action];
                    }
                    $reason = $entry['reason'] ?? null;
                    $items = $entry['items'] ?? [];
                    $photos = $entry['photos'] ?? [];
                    $hasMeta = $action || $reason || !empty($actionParts) || !empty($items) || !empty($photos);
                @endphp
                <li class="pds-follow-up-audit-item is-{{ $tone }}">
                    <div class="pds-follow-up-audit-dot" aria-hidden="true">
                        <i class="{{ $entry['icon'] }}"></i>
                    </div>
                    <div class="pds-follow-up-audit-card">
                        <div class="pds-follow-up-audit-head">
                            <span class="pds-follow-up-audit-label">{{ $entry['title'] }}</span>
                            <time class="pds-follow-up-audit-time">{{ $entry['time'] }}</time>
                        </div>
                        <p class="pds-follow-up-audit-message">{{ $summary }}</p>

                        @if($hasMeta)
                            <div class="pds-follow-up-audit-meta">
                                @if(!empty($actionParts))
                                    <div class="pds-follow-up-audit-action">
                                        <span class="pds-follow-up-audit-meta-label">{{ __('message.dispatch_audit_action_label') }}</span>
                                        <ul class="pds-follow-up-audit-details">
                                            @foreach($actionParts as $part)
                                                @php
                                                    $detailLabel = null;
                                                    $detailValue = (string) $part;
                                                    if (str_contains($detailValue, ' — ')) {
                                                        [$detailLabel, $detailValue] = array_map('trim', explode(' — ', $detailValue, 2));
                                                    } elseif (str_contains($detailValue, ': ')) {
                                                        [$detailLabel, $detailValue] = array_map('trim', explode(': ', $detailValue, 2));
                                                    }
                                                @endphp
                                                <li>
                                                    @if($detailLabel)
                                                        <span class="is-label">{{ $detailLabel }}</span>
                                                    @endif
                                                    <span class="is-value">{{ $detailValue }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if(!empty($photos))
                                    <div class="pds-follow-up-audit-photos">
                                        <span class="pds-follow-up-audit-meta-label">{{ __('message.dispatch_audit_photos_label') }}</span>
                                        <div class="pds-follow-up-audit-photo-grid">
                                            @foreach($photos as $photo)
                                                <a href="{{ $photo['url'] }}" target="_blank" rel="noopener" class="pds-follow-up-audit-photo" title="{{ $photo['label'] ?? __('message.dispatch_audit_photos_label') }}">
                                                    <span class="pds-follow-up-audit-photo-frame">
                                                        <img src="{{ $photo['url'] }}" alt="{{ $photo['label'] ?? 'Parcel photo' }}" loading="lazy">
                                                        <span class="pds-follow-up-audit-photo-zoom" aria-hidden="true">
                                                            <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                                                        </span>
                                                    </span>
                                                    @if(!empty($photo['label']))
                                                        <span class="pds-follow-up-audit-photo-caption">{{ $photo['label'] }}</span>
                                                    @endif
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if($reason)
                                    <div class="pds-follow-up-audit-reason">
                                        <span class="pds-follow-up-audit-meta-label">
                                            {{ ($entry['type'] ?? '') === \App\Services\DispatchOrderAuditService::TYPE_DELIVERY_ITEM_STATUS
                                                ? __('message.remark_label')
                                                : __('message.dispatch_audit_reason_label') }}
                                        </span>
                                        <p class="pds-follow-up-audit-reason-text">{{ $reason }}</p>
                                    </div>
                                @endif

                                @if(!empty($items))
                                    <div class="pds-follow-up-audit-items">
                                        <span class="pds-follow-up-audit-meta-label">{{ __('message.dispatch_audit_items_label') }}</span>
                                        <div class="pds-follow-up-audit-item-cards">
                                            @foreach($items as $itemCard)
                                                <div class="pds-follow-up-audit-item-card">
                                                    <div class="pds-follow-up-audit-item-code">{{ $itemCard['code'] ?? '-' }}</div>
                                                    @if(!empty($itemCard['chips']))
                                                        <div class="pds-follow-up-audit-chips">
                                                            @foreach($itemCard['chips'] as $chip)
                                                                <span class="pds-follow-up-audit-chip">
                                                                    <em>{{ $chip['label'] }}</em>
                                                                    <strong>{{ $chip['value'] }}</strong>
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</div>

<div class="pds-follow-up-details-actions">
    @include('order.dispatch-item-action', ['item' => $item])
</div>
