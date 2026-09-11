@php
    $pendingEntries = $item->displayPendingRemarks();
    $payload = $pendingEntries->map(static function ($entry) {
        return [
            'date' => $entry->pendingDateLabel(),
            'remark' => trim((string) ($entry->remark ?? '')),
            'photo_url' => $entry->photoUrl() ?: '',
        ];
    })->values()->all();
@endphp
@if(count($payload) < 1)
    <div>{{ stringLong($item->remark ?? '', 'title', 16) ?: '-' }}</div>
@else
    <button
        type="button"
        class="pds-pending-total-btn"
        data-pending-remarks="{{ base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE)) }}"
        title="{{ __('message.pending_remark_history') }}"
    >
        <span>{{ __('message.total') }}</span>
        <strong>{{ count($payload) }}</strong>
    </button>
@endif
