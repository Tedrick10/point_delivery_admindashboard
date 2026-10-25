@php
    /** @var array $payload */
    $payload = $payload ?? [];
    $orderSummary = is_array($payload['order'] ?? null) ? $payload['order'] : [];
    $status = (string) ($payload['status'] ?? '');
    $statusLabel = (string) ($payload['status_label'] ?? '');
    if ($statusLabel === '') {
        $statusLabel = strtoupper(str_replace('_', ' ', $status ?: '-'));
    }
    $code = trim((string) ($payload['code'] ?? ''));
    $idLabel = $code !== '' ? $code : ('#'.(int) ($payload['id'] ?? 0));
    $receivedDate = trim((string) ($payload['received_date'] ?? ''));
    $sizeLabel = trim((string) ($payload['size'] ?? ''));
    $isReturnStatus = in_array($status, ['return', 'os_returned'], true);
    $inFlight = in_array($status, ['assigned', 'courier_assigned', 'courier_departed', 'pending'], true);
    $returnType = (string) ($payload['return_type'] ?? '');
    $assignedFromReturn = ! empty($payload['assigned_from_return']);
    $isDeliveryReturn = $returnType === 'delivery';
    $isNormalReturn = $returnType === 'normal';
    $usesReturnUi = $isReturnStatus
        || ($inFlight && ($isDeliveryReturn || $assignedFromReturn || $isNormalReturn));
    $isKyoShin = ! empty($payload['is_kyo_shin']);
    $kyoShinDue = trim((string) ($payload['kyo_shin_due_date'] ?? ''));
    $showItemValue = ! $usesReturnUi || $isKyoShin;
    $showDeliAmount = ! $usesReturnUi || $isDeliveryReturn;
    $photoUrl = trim((string) ($payload['photo_url'] ?? ''));
    $pendingRemarks = is_array($payload['pending_remarks'] ?? null) ? $payload['pending_remarks'] : [];
    $deliveredPhotoUrl = trim((string) ($payload['delivered_photo_url'] ?? ''));
    $deliveredType = trim((string) ($payload['delivered_type'] ?? ''));
    $gateAmount = isset($payload['gate_amount']) ? (float) $payload['gate_amount'] : 0;

    $fmtMoney = static function ($v): string {
        if ($v === null || $v === '' || ! is_numeric($v)) {
            return '';
        }

        return number_format((float) $v);
    };
    $val = static function (...$candidates): string {
        foreach ($candidates as $c) {
            $t = trim((string) ($c ?? ''));
            if ($t !== '' && $t !== '-') {
                return $t;
            }
        }

        return '';
    };

    $osName = $val($payload['os_name'] ?? null, $orderSummary['os_name'] ?? null);
    $osPhone = $val($payload['os_phone'] ?? null, $orderSummary['os_phone'] ?? null);
    $osAddress = $val($payload['os_address'] ?? null, $orderSummary['os_address'] ?? null);
    $orderCount = $val($orderSummary['order_count'] ?? null);
    $pickupRider = $val($payload['pickup_rider_name'] ?? null, $orderSummary['pickup_rider_name'] ?? null);
    $assignedName = $val($payload['assigned_name'] ?? null, $orderSummary['assigned_name'] ?? null);
    $assignedPhone = $val($payload['assigned_phone'] ?? null, $orderSummary['assigned_phone'] ?? null);
    $custName = $val($payload['customer_name'] ?? null);
    $custPhone = $val($payload['customer_phone'] ?? null);
    $custAddress = $val($payload['customer_address'] ?? null);
    $township = $val($payload['township'] ?? null);
    $remark = $val($payload['remark'] ?? null);
    $itemValue = $fmtMoney($payload['item_value'] ?? null);
    $deliAmount = $fmtMoney($payload['deli_amount'] ?? null);
    $custGet = $fmtMoney($payload['cust_get'] ?? null);

    $rows = [];
    $push = static function (string $label, string $value, string $tone = '') use (&$rows) {
        if ($value === '') {
            return;
        }
        $rows[] = compact('label', 'value', 'tone');
    };

    if ($usesReturnUi) {
        $push('Online Shop', $osName, 'accent');
        $push('Os Phone', $osPhone, 'phone');
        $push('Os Address', $osAddress);
    } else {
        $push('Os Name', $osName);
        $push('Os Phone', $osPhone, 'phone');
        $push('Os Address', $osAddress);
        $push('Order Count', $orderCount !== '' ? (string) $orderCount : '');
        $push('Pick Up Rider', $pickupRider, 'accent');
        $push('Cust Name', $custName);
        $push('Cust Phone', $custPhone, 'phone');
        $push('Cust Address', $custAddress);
        $push('Township', $township, 'blue');
    }

    if ($isKyoShin) {
        $kyoVal = $kyoShinDue !== '' ? ('ပေးထားသည် · '.$kyoShinDue) : 'ပေးထားသည်';
        $push('ကြိုရှင်း', $kyoVal, 'kyo');
    }
    if ($showItemValue) {
        $push('Item Value', $itemValue);
    }
    if ($showDeliAmount) {
        $push('Deli Amount', $deliAmount);
    }
    $push('Size', ($sizeLabel !== '' && $sizeLabel !== '-') ? $sizeLabel : '');
    if (empty($pendingRemarks)) {
        $push('Remark', $remark);
    }
    if (! $usesReturnUi) {
        $push('CustomerToGet', $custGet, 'danger');
    }
    if ($usesReturnUi) {
        $push('Pick Up Rider', $pickupRider, 'accent');
    }
    $push('Assigned', $assignedName, 'accent');
    $push('Assigned Phone', $assignedPhone, 'phone');

    $headerTone = $usesReturnUi ? 'return' : 'delivery';
    $statusTone = match (true) {
        in_array($status, ['completed', 'delivered', 'finished'], true) => 'done',
        in_array($status, ['pending'], true) => 'pending',
        in_array($status, ['return', 'os_returned'], true) => 'return',
        in_array($status, ['courier_departed'], true) => 'onway',
        default => 'assigned',
    };
@endphp
<div class="modal-dialog modal-dialog-centered modal-lg pds-app-view-wrap" role="document">
    <div class="modal-content pds-app-view">
        <div class="pds-app-view__topbar">
            <div class="pds-app-view__heading">
                <span class="pds-app-view__badge" aria-hidden="true">
                    <i class="fas fa-eye"></i>
                </span>
                <div class="pds-app-view__titles">
                    <h5 class="pds-app-view__title">{{ __('message.rider_app_view') }}</h5>
                    <p class="pds-app-view__sub">{{ __('message.parcel_details') }} · {{ $idLabel }}</p>
                </div>
            </div>
            <button type="button" class="pds-app-view__close" data-dismiss="modal" aria-label="{{ __('message.close') }}">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="pds-app-view__body">
            <article class="pds-app-view-card pds-app-view-card--{{ $headerTone }}">
                <header class="pds-app-view-card__header">
                    <div class="pds-app-view-card__header-top">
                        <div class="pds-app-view-card__id">{{ $idLabel }}</div>
                        <span class="pds-app-view-card__status pds-app-view-card__status--{{ $statusTone }}">{{ $statusLabel }}</span>
                    </div>
                    <div class="pds-app-view-card__meta">
                        @if($receivedDate !== '')
                            <span><i class="far fa-calendar-alt" aria-hidden="true"></i> {{ $receivedDate }}</span>
                        @endif
                        @if($sizeLabel !== '' && $sizeLabel !== '-')
                            <span><i class="fas fa-box" aria-hidden="true"></i> {{ $sizeLabel }}</span>
                        @endif
                    </div>
                </header>

                @if(count($rows) > 0)
                    <div class="pds-app-view-card__rows">
                        @foreach($rows as $i => $row)
                            <div class="pds-app-view-card__row {{ $i % 2 === 1 ? 'is-alt' : '' }}">
                                <span class="pds-app-view-card__label">{{ $row['label'] }}</span>
                                <span class="pds-app-view-card__value tone-{{ $row['tone'] ?: 'default' }}{{ $row['tone'] === 'danger' ? ' is-emphasize' : '' }}">
                                    {{ $row['value'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(!empty($pendingRemarks))
                    <section class="pds-app-view-card__section">
                        <div class="pds-app-view-card__section-title">Pending Remarks</div>
                        <div class="pds-app-view-card__pending-list">
                            @foreach($pendingRemarks as $pr)
                                @php
                                    $prRemark = trim((string) ($pr['remark'] ?? ''));
                                    $prDate = trim((string) ($pr['pending_date'] ?? ''));
                                    $prPhoto = trim((string) ($pr['photo_url'] ?? ''));
                                @endphp
                                @if($prRemark !== '' || $prPhoto !== '')
                                    <div class="pds-app-view-pending">
                                        <div class="pds-app-view-pending__head">
                                            <span class="pds-app-view-pending__tag">Pending</span>
                                            @if($prDate !== '')
                                                <span class="pds-app-view-pending__date">{{ $prDate }}</span>
                                            @endif
                                        </div>
                                        @if($prRemark !== '')
                                            <p class="pds-app-view-pending__text">{{ $prRemark }}</p>
                                        @endif
                                        @if($prPhoto !== '')
                                            <a class="pds-app-view-media" href="{{ $prPhoto }}" target="_blank" rel="noopener">
                                                <img src="{{ $prPhoto }}" alt="Pending photo">
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </section>
                @endif

                @if($deliveredPhotoUrl !== '' || $deliveredType !== '')
                    <section class="pds-app-view-card__section">
                        <div class="pds-app-view-card__section-title">Delivered Proof</div>
                        <div class="pds-app-view-proof">
                            @if($deliveredType !== '')
                                <div class="pds-app-view-proof__meta">
                                    <span class="pds-app-view-proof__type">{{ ucfirst($deliveredType) }}</span>
                                    @if($deliveredType === 'gate' && $gateAmount > 0)
                                        <span class="pds-app-view-proof__gate">Gate {{ number_format($gateAmount) }}</span>
                                    @endif
                                </div>
                            @endif
                            @if($deliveredPhotoUrl !== '')
                                <a class="pds-app-view-media" href="{{ $deliveredPhotoUrl }}" target="_blank" rel="noopener">
                                    <img src="{{ $deliveredPhotoUrl }}" alt="Delivered proof">
                                </a>
                            @endif
                        </div>
                    </section>
                @endif

                @if($photoUrl !== '')
                    <section class="pds-app-view-card__section">
                        <div class="pds-app-view-card__section-title">Photo</div>
                        <a class="pds-app-view-media" href="{{ $photoUrl }}" target="_blank" rel="noopener">
                            <img src="{{ $photoUrl }}" alt="Parcel photo">
                        </a>
                    </section>
                @endif
            </article>
        </div>
    </div>
</div>

<style>
    #remoteModelData.modal .pds-app-view-wrap {
        max-width: 560px;
    }
    body.pds-admin .pds-app-view {
        border: 0 !important;
        border-radius: 20px !important;
        overflow: hidden;
        box-shadow: 0 28px 64px rgba(28, 25, 23, 0.2);
        font-family: 'Outfit', 'Noto Sans Myanmar', system-ui, sans-serif;
        background: #fff;
    }
    body.pds-admin .pds-app-view__topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.85rem;
        padding: 1.05rem 1.25rem;
        background: linear-gradient(180deg, #FFF8F1 0%, #ffffff 100%);
        border-bottom: 1px solid rgba(var(--brand-rgb), 0.12);
    }
    body.pds-admin .pds-app-view__heading {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        min-width: 0;
    }
    body.pds-admin .pds-app-view__badge {
        width: 42px;
        height: 42px;
        border-radius: 13px;
        display: grid;
        place-items: center;
        background: var(--site-color);
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 8px 18px rgba(var(--brand-rgb), 0.3);
        font-size: 1rem;
    }
    body.pds-admin .pds-app-view__titles { min-width: 0; }
    body.pds-admin .pds-app-view__title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 780;
        color: #1c1917 !important;
        letter-spacing: -0.01em;
    }
    body.pds-admin .pds-app-view__sub {
        margin: 0.2rem 0 0;
        font-size: 0.78rem;
        color: #a8a29e;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    body.pds-admin .pds-app-view__close {
        width: 34px;
        height: 34px;
        border: 0;
        border-radius: 999px;
        background: rgba(28, 25, 23, 0.06);
        color: #78716c;
        display: grid;
        place-items: center;
        cursor: pointer;
        flex-shrink: 0;
    }
    body.pds-admin .pds-app-view__close:hover {
        background: rgba(var(--brand-rgb), 0.14);
        color: var(--site-color);
    }
    body.pds-admin .pds-app-view__body {
        padding: 1.15rem 1.25rem 1.4rem !important;
        background:
            radial-gradient(120% 80% at 50% 0%, rgba(var(--brand-rgb), 0.08) 0%, transparent 55%),
            #f4f4f5;
        max-height: min(78vh, 780px);
        overflow: auto;
        box-sizing: border-box;
    }
    body.pds-admin .pds-app-view-card {
        background: #fff;
        border-radius: 18px;
        border: 1px solid rgba(28, 25, 23, 0.08);
        overflow: hidden;
        box-shadow: 0 14px 34px rgba(28, 25, 23, 0.08);
    }
    body.pds-admin .pds-app-view-card__header {
        padding: 1.1rem 1.15rem 1rem;
        color: #fff !important;
        background: linear-gradient(135deg, var(--site-color) 0%, #ea580c 100%);
    }
    body.pds-admin .pds-app-view-card--return .pds-app-view-card__header {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
    }
    body.pds-admin .pds-app-view-card__header-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    body.pds-admin .pds-app-view-card__id {
        font-size: 1rem;
        font-weight: 820;
        letter-spacing: 0.02em;
        color: #fff !important;
    }
    body.pds-admin .pds-app-view-card__status {
        display: inline-flex;
        align-items: center;
        padding: 0.32rem 0.75rem;
        border-radius: 999px;
        background: rgba(255,255,255,0.22);
        border: 1px solid rgba(255,255,255,0.35);
        font-size: 0.76rem;
        font-weight: 750;
        line-height: 1.2;
        color: #fff !important;
    }
    body.pds-admin .pds-app-view-card__meta {
        margin-top: 0.7rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem 0.9rem;
        font-size: 0.76rem;
        font-weight: 650;
        color: rgba(255,255,255,0.95) !important;
    }
    body.pds-admin .pds-app-view-card__meta span,
    body.pds-admin .pds-app-view-card__meta i {
        color: rgba(255,255,255,0.95) !important;
    }
    body.pds-admin .pds-app-view-card__meta i {
        margin-right: 0.28rem;
        opacity: 0.95;
    }
    body.pds-admin .pds-app-view-card__row {
        display: grid;
        grid-template-columns: minmax(7.2rem, 36%) minmax(0, 1fr);
        gap: 0.75rem;
        padding: 0.78rem 1.15rem;
        border-top: 1px solid #f5f5f4;
        align-items: start;
    }
    body.pds-admin .pds-app-view-card__row.is-alt { background: #fafaf9; }
    body.pds-admin .pds-app-view-card__label {
        color: #78716c;
        font-size: 0.78rem;
        font-weight: 720;
        padding-top: 0.1rem;
    }
    body.pds-admin .pds-app-view-card__value {
        color: #1c1917;
        font-size: 0.92rem;
        font-weight: 650;
        word-break: break-word;
        text-align: right;
        line-height: 1.45;
        font-family: 'Noto Sans Myanmar', 'Myanmar Text', 'Padauk', 'Outfit', system-ui, sans-serif;
    }
    body.pds-admin .pds-app-view-card__value.tone-accent { color: var(--site-color); font-weight: 760; }
    body.pds-admin .pds-app-view-card__value.tone-phone { color: #15803d; font-weight: 760; }
    body.pds-admin .pds-app-view-card__value.tone-blue { color: #2563eb; font-weight: 760; }
    body.pds-admin .pds-app-view-card__value.tone-kyo { color: #c2410c; font-weight: 760; }
    body.pds-admin .pds-app-view-card__value.tone-danger { color: #dc2626; }
    body.pds-admin .pds-app-view-card__value.is-emphasize { font-size: 1.08rem; font-weight: 820; }

    body.pds-admin .pds-app-view-card__section {
        padding: 1rem 1.15rem 1.15rem;
        border-top: 1px solid #f0ebe6;
        background: linear-gradient(180deg, #fff 0%, #fffdfb 100%);
    }
    body.pds-admin .pds-app-view-card__section-title {
        margin: 0 0 0.7rem;
        color: #78716c;
        font-size: 0.76rem;
        font-weight: 780;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    body.pds-admin .pds-app-view-card__pending-list {
        display: flex;
        flex-direction: column;
        gap: 0.7rem;
    }
    body.pds-admin .pds-app-view-pending {
        padding: 0.85rem 0.95rem;
        border-radius: 14px;
        background: #fff7ed;
        border: 1px solid rgba(var(--brand-rgb), 0.22);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
    }
    body.pds-admin .pds-app-view-pending__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.6rem;
        margin-bottom: 0.4rem;
    }
    body.pds-admin .pds-app-view-pending__tag {
        display: inline-flex;
        align-items: center;
        padding: 0.22rem 0.55rem;
        border-radius: 999px;
        background: rgba(var(--brand-rgb), 0.14);
        color: #9a3412;
        font-size: 0.72rem;
        font-weight: 800;
    }
    body.pds-admin .pds-app-view-pending__date {
        color: #a8a29e;
        font-size: 0.74rem;
        font-weight: 650;
    }
    body.pds-admin .pds-app-view-pending__text {
        margin: 0;
        color: #7c2d12;
        font-size: 0.9rem;
        font-weight: 650;
        line-height: 1.45;
        font-family: 'Noto Sans Myanmar', 'Myanmar Text', 'Padauk', 'Outfit', system-ui, sans-serif;
    }
    body.pds-admin .pds-app-view-proof__meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.45rem;
        margin-bottom: 0.7rem;
    }
    body.pds-admin .pds-app-view-proof__type {
        display: inline-flex;
        padding: 0.28rem 0.65rem;
        border-radius: 999px;
        background: #f5f5f4;
        border: 1px solid #e7e5e4;
        color: #1c1917;
        font-size: 0.8rem;
        font-weight: 750;
    }
    body.pds-admin .pds-app-view-proof__gate {
        color: var(--site-color);
        font-size: 0.8rem;
        font-weight: 750;
    }
    body.pds-admin .pds-app-view-media {
        display: block;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid #e7e5e4;
        background: #fafaf9;
        box-shadow: 0 8px 18px rgba(28, 25, 23, 0.06);
    }
    body.pds-admin .pds-app-view-media img {
        display: block;
        width: 100%;
        max-height: 260px;
        object-fit: cover;
    }
    body.pds-admin .pds-app-view-pending .pds-app-view-media {
        margin-top: 0.65rem;
    }
</style>
