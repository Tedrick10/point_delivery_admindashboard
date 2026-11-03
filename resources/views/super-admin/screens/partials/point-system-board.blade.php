@php
    $tab = $pointSystem['tab'] ?? 'give';
    $directory = $pointSystem['directory'] ?? collect();
    $directoryCounts = $pointSystem['directoryCounts'] ?? ['os' => 0, 'staff' => 0, 'rider' => 0];
    $recentCredits = $pointSystem['recentCredits'] ?? collect();
    $followUps = $pointSystem['followUps'] ?? collect();
    $salaryRequests = $pointSystem['salaryRequests'] ?? collect();
    $followPointTotals = $pointSystem['followPointTotals'] ?? ['pending' => 0, 'confirmed' => 0];
    $salaryPointTotals = $pointSystem['salaryPointTotals'] ?? ['pending' => 0, 'confirmed' => 0];
    $salaryKindCounts = $pointSystem['salaryKindCounts'] ?? ['os' => 0, 'staff' => 0, 'rider' => 0];
    $q = $pointSystem['q'] ?? '';
    $recipientType = $pointSystem['recipientType'] ?? 'os';
    $followStatus = $pointSystem['followStatus'] ?? 'all';
    $salaryStatus = $pointSystem['salaryStatus'] ?? 'all';
    $salaryKind = $pointSystem['salaryKind'] ?? 'os';
    $pendingFollowUps = (int) ($pointSystem['pendingFollowUps'] ?? 0);
    $pendingSalary = (int) ($pointSystem['pendingSalary'] ?? 0);
    $searchUsersUrl = $pointSystem['searchUsersUrl'] ?? route('point-system.search-users');
    $creditUrl = $pointSystem['creditUrl'] ?? route('point-system.credit');
    $tabUrl = fn (string $t) => route('super-admin.screens.show', ['screen' => 'point-system', 'tab' => $t]);
    $listUrl = fn (string $type) => route('super-admin.screens.show', array_filter([
        'screen' => 'point-system',
        'tab' => 'give',
        'recipient_type' => $type,
        'q' => $q !== '' ? $q : null,
    ]));
    $salaryKindUrl = fn (string $kind) => route('super-admin.screens.show', array_filter([
        'screen' => 'point-system',
        'tab' => 'salary',
        'salary_kind' => $kind,
        'salary_status' => $salaryStatus !== 'all' ? $salaryStatus : null,
        'q' => $q !== '' ? $q : null,
    ]));
    $listTitle = match ($recipientType) {
        'staff' => __('message.point_list_staff'),
        'rider' => __('message.point_list_rider'),
        default => __('message.point_list_os'),
    };
    $salaryKindTitle = match ($salaryKind) {
        'staff' => __('message.point_list_staff'),
        'rider' => __('message.point_list_rider'),
        default => __('message.point_list_os'),
    };
    $initialsFn = $initials ?? function (string $name): string {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $chars = [];
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $chars[] = mb_strtoupper(mb_substr($part, 0, 1));
            if (count($chars) >= 2) {
                break;
            }
        }

        return implode('', $chars) ?: '?';
    };
@endphp

<div class="sa-point">
    <nav class="sa-point__nav">
        <a href="{{ $tabUrl('give') }}" class="{{ $tab === 'give' ? 'is-on' : '' }}">{{ __('message.point_give_title') }}</a>
        <a href="{{ $tabUrl('follow-up') }}" class="{{ $tab === 'follow-up' ? 'is-on' : '' }}">
            {{ __('message.point_system_follow_up') }}
            @if($pendingFollowUps > 0)<b>{{ $pendingFollowUps }}</b>@endif
        </a>
        <a href="{{ $tabUrl('salary') }}" class="{{ $tab === 'salary' ? 'is-on' : '' }}">
            {{ __('message.point_salary_requests') }}
            @if($pendingSalary > 0)<b>{{ $pendingSalary }}</b>@endif
        </a>
    </nav>

    @if($tab === 'give')
        <section class="sa-point-shell">
            <header class="sa-point-shell__head">
                <div>
                    <h3>{{ $listTitle }}</h3>
                </div>
                <div class="sa-point-shell__stats">
                    <span><strong>{{ number_format((int) ($directoryCounts[$recipientType] ?? 0)) }}</strong> people</span>
                    <span><strong>{{ $directory->filter(fn ($r) => (float) $r->balance > 0)->count() }}</strong> with bonus</span>
                </div>
            </header>

            <div class="sa-point-toolbar">
                <div class="sa-point-seg">
                    <a href="{{ $listUrl('os') }}" class="{{ $recipientType === 'os' ? 'is-on' : '' }}">
                        OS <i>{{ (int) ($directoryCounts['os'] ?? 0) }}</i>
                    </a>
                    <a href="{{ $listUrl('staff') }}" class="{{ $recipientType === 'staff' ? 'is-on' : '' }}">
                        Staff <i>{{ (int) ($directoryCounts['staff'] ?? 0) }}</i>
                    </a>
                    <a href="{{ $listUrl('rider') }}" class="{{ $recipientType === 'rider' ? 'is-on' : '' }}">
                        Rider <i>{{ (int) ($directoryCounts['rider'] ?? 0) }}</i>
                    </a>
                </div>
                <form method="GET" action="{{ route('super-admin.screens.show', 'point-system') }}" class="sa-point-find">
                    <input type="hidden" name="tab" value="give">
                    <input type="hidden" name="recipient_type" value="{{ $recipientType }}">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input type="search" name="q" value="{{ $q }}" placeholder="Search name, phone, id…">
                    <button type="submit">Go</button>
                </form>
            </div>

            <div class="sa-point-list-wrap">
                <table class="sa-point-list">
                    <thead>
                        <tr>
                            <th style="width:3rem;">#</th>
                            <th>Person</th>
                            <th style="width:9.5rem;">Balance</th>
                            <th style="width:6.5rem;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($directory as $i => $row)
                            <tr class="js-sa-point-row"
                                data-id="{{ $row->id }}"
                                data-name="{{ $row->name }}"
                                data-label="#{{ $row->id }} {{ $row->name }} · {{ number_format((float) $row->balance, 0) }} MMK"
                                data-balance="{{ number_format((float) $row->balance, 0) }}">
                                <td class="sa-point-list__idx">{{ $i + 1 }}</td>
                                <td>
                                    <div class="sa-point-who">
                                        <span class="sa-point-who__av">{{ $initialsFn((string) $row->name) }}</span>
                                        <span class="sa-point-who__txt">
                                            <strong>{{ $row->name }}</strong>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="sa-point-chip {{ ((float) $row->balance) > 0 ? 'is-hot' : '' }}">
                                        {{ number_format((float) $row->balance, 0) }}<span class="sa-point-chip__unit">MMK</span>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="sa-point-pick js-sa-point-pick">
                                        <i class="fas fa-coins" aria-hidden="true"></i> Give
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="sa-point-empty">{{ __('message.no_record_found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="sa-point-modal" id="saPointModal" hidden>
            <div class="sa-point-modal__backdrop js-sa-point-modal-close" tabindex="-1"></div>
            <div class="sa-point-modal__panel" role="dialog" aria-modal="true" aria-labelledby="saPointModalTitle">
                <button type="button" class="sa-point-modal__x js-sa-point-modal-close" aria-label="Close">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>

                <div class="sa-point-composer" id="saPointComposer">
                    <div class="sa-point-composer__hero">
                        <div class="sa-point-composer__badge" aria-hidden="true">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="sa-point-composer__title">
                            <span id="saPointModalTitle">{{ __('message.point_give_title') }}</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ $creditUrl }}" class="sa-point-composer__form" id="saPointGiveForm">
                        @csrf
                        <input type="hidden" name="embed_return" value="super-admin">
                        <input type="hidden" name="recipient_type" value="{{ $recipientType }}">
                        <input type="hidden" name="q" value="{{ $q }}">
                        <input type="hidden" name="user_id" id="saPointUserId" required>

                        <div class="sa-point-composer__picked" id="saPointSelected">
                            <em>Select someone from the list</em>
                        </div>

                        <div class="sa-point-composer__row">
                            <label>
                                <span>{{ __('message.point_amount') }}</span>
                                <input type="number" name="amount" id="saPointAmount" min="1" step="1" required value="100" inputmode="numeric">
                            </label>
                        </div>
                        <label class="sa-point-composer__note">
                            <span>Note</span>
                            <input type="text" name="description" value="{{ __('message.point_admin_credit_default') }}">
                        </label>
                        <button type="submit" class="sa-point-composer__go" id="saPointSubmit" disabled>
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            {{ __('message.point_give_submit') }}
                        </button>
                    </form>

                </div>
            </div>
        </div>
    @endif

    @if($tab === 'follow-up')
        @php
            $followStatuses = [
                'all' => __('message.all'),
                'pending_admin' => __('message.point_status_pending_admin'),
                'pending_super_admin' => __('message.point_status_pending_super_admin'),
                'confirmed' => __('message.point_status_confirmed'),
            ];
        @endphp
        <section class="sa-point-shell">
            <header class="sa-point-shell__head">
                <div>
                    <h3>{{ __('message.point_system_follow_up') }}</h3>
                </div>
                <div class="sa-point-shell__stats">
                    <span><strong>{{ $followUps->count() }}</strong> shown</span>
                </div>
            </header>
            <form method="GET" action="{{ route('super-admin.screens.show', 'point-system') }}" class="sa-point-toolbar">
                <input type="hidden" name="tab" value="follow-up">
                <label class="sa-point-dd">
                    <span class="sa-point-dd__icon" aria-hidden="true"><i class="fas fa-filter"></i></span>
                    <select name="status" class="sa-point-dd__select" onchange="this.form.submit()">
                        @foreach($followStatuses as $key => $label)
                            <option value="{{ $key }}" @selected($followStatus === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <span class="sa-point-dd__chev" aria-hidden="true"><i class="fas fa-chevron-down"></i></span>
                </label>
                <div class="sa-point-find">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input type="search" name="q" value="{{ $q }}" placeholder="Search name, order, id…">
                    <button type="submit">Go</button>
                </div>
            </form>
            <div class="sa-point-totals" aria-label="Bonus totals">
                <div class="sa-point-total sa-point-total--wait">
                    <span>Pending</span>
                    <b>{{ number_format((float) ($followPointTotals['pending'] ?? 0), 0) }}</b>
                    <em>MMK</em>
                </div>
                <div class="sa-point-total sa-point-total--ok">
                    <span>Confirmed</span>
                    <b>{{ number_format((float) ($followPointTotals['confirmed'] ?? 0), 0) }}</b>
                    <em>MMK</em>
                </div>
            </div>
            <div class="sa-follow-cards">
                @forelse($followUps as $row)
                    @php
                        $isOsReceive = method_exists($row, 'isOsReceive') ? $row->isOsReceive() : ((int) ($row->os_receive_settlement_id ?? 0) > 0);
                        $st = (string) $row->status;
                        $tone = $st === 'confirmed' ? 'ok' : 'wait';
                    @endphp
                    <article class="sa-follow-card sa-follow-card--{{ $tone }} {{ $isOsReceive ? 'is-os' : '' }}">
                        <div class="sa-follow-card__who">
                            <span class="sa-follow-card__av" aria-hidden="true">{{ $initialsFn((string) optional($row->user)->name) }}</span>
                            <div class="sa-follow-card__copy">
                                <div class="sa-follow-card__title-row">
                                    <strong>{{ optional($row->user)->name }}</strong>
                                    <span class="sa-follow-card__status sa-follow-card__status--{{ $tone }}">
                                        {{ __('message.point_status_'.$st) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="sa-follow-card__pts">
                            <span>MMK</span>
                            <b>{{ number_format((float) $row->points, 0) }}</b>
                        </div>
                        <div class="sa-follow-card__acts">
                            @if($st === 'pending_super_admin')
                                <form method="POST" action="{{ route('point-system.payment.confirm', $row->id) }}">
                                    @csrf
                                    <input type="hidden" name="embed_return" value="super-admin">
                                    <input type="hidden" name="status" value="{{ $followStatus }}">
                                    <input type="hidden" name="q" value="{{ $q }}">
                                    <button class="sa-follow-card__btn sa-follow-card__btn--ok" type="submit">
                                        <i class="fas fa-check" aria-hidden="true"></i>
                                        {{ __('message.point_action_confirm') }}
                                    </button>
                                </form>
                            @elseif($st === 'pending_admin' && ! $isOsReceive)
                                <form method="POST" action="{{ route('point-system.payment.approve', $row->id) }}">
                                    @csrf
                                    <input type="hidden" name="embed_return" value="super-admin">
                                    <input type="hidden" name="status" value="{{ $followStatus }}">
                                    <input type="hidden" name="q" value="{{ $q }}">
                                    <button class="sa-follow-card__btn sa-follow-card__btn--ok" type="submit">
                                        <i class="fas fa-check" aria-hidden="true"></i>
                                        {{ __('message.point_action_approve') }}
                                    </button>
                                </form>
                            @elseif($st === 'pending_admin' && $isOsReceive)
                                <span class="sa-follow-card__wait">Waiting Admin</span>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="sa-point-empty">{{ __('message.no_record_found') }}</div>
                @endforelse
            </div>
        </section>
    @endif

    @if($tab === 'salary')
        @php
            $salaryStatuses = [
                'all' => __('message.all'),
                'pending' => __('message.point_status_pending'),
                'approved' => __('message.point_status_confirmed'),
                'rejected' => __('message.point_status_rejected'),
            ];
        @endphp
        <section class="sa-point-shell">
            <header class="sa-point-shell__head">
                <div>
                    <h3>{{ __('message.point_salary_requests') }} · {{ $salaryKindTitle }}</h3>
                    <p class="text-muted mb-0 small">{{ __('message.point_salary_requests_hint') }}</p>
                </div>
                <div class="sa-point-shell__stats">
                    <span><strong>{{ $salaryRequests->count() }}</strong> shown</span>
                </div>
            </header>
            <div class="sa-point-toolbar">
                <div class="sa-point-seg" role="tablist" aria-label="Bonus request audience">
                    <a href="{{ $salaryKindUrl('os') }}" class="{{ $salaryKind === 'os' ? 'is-on' : '' }}">
                        OS <i>{{ (int) ($salaryKindCounts['os'] ?? 0) }}</i>
                    </a>
                    <a href="{{ $salaryKindUrl('staff') }}" class="{{ $salaryKind === 'staff' ? 'is-on' : '' }}">
                        Staff <i>{{ (int) ($salaryKindCounts['staff'] ?? 0) }}</i>
                    </a>
                    <a href="{{ $salaryKindUrl('rider') }}" class="{{ $salaryKind === 'rider' ? 'is-on' : '' }}">
                        Rider <i>{{ (int) ($salaryKindCounts['rider'] ?? 0) }}</i>
                    </a>
                </div>
                <form method="GET" action="{{ route('super-admin.screens.show', 'point-system') }}" class="sa-point-toolbar__filters">
                    <input type="hidden" name="tab" value="salary">
                    <input type="hidden" name="salary_kind" value="{{ $salaryKind }}">
                    <label class="sa-point-dd">
                        <span class="sa-point-dd__icon" aria-hidden="true"><i class="fas fa-filter"></i></span>
                        <select name="salary_status" class="sa-point-dd__select" onchange="this.form.submit()">
                            @foreach($salaryStatuses as $key => $label)
                                <option value="{{ $key }}" @selected($salaryStatus === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="sa-point-dd__chev" aria-hidden="true"><i class="fas fa-chevron-down"></i></span>
                    </label>
                    <div class="sa-point-find">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <input type="search" name="q" value="{{ $q }}" placeholder="Search name, period, id…">
                        <button type="submit">Go</button>
                    </div>
                </form>
            </div>
            <div class="sa-point-totals" aria-label="Bonus totals">
                <div class="sa-point-total sa-point-total--wait">
                    <span>{{ __('message.point_status_pending') }}</span>
                    <b>{{ number_format((float) ($salaryPointTotals['pending'] ?? 0), 0) }}</b>
                    <em>MMK</em>
                </div>
                <div class="sa-point-total sa-point-total--ok">
                    <span>{{ __('message.point_status_confirmed') }}</span>
                    <b>{{ number_format((float) ($salaryPointTotals['confirmed'] ?? 0), 0) }}</b>
                    <em>MMK</em>
                </div>
            </div>
            <div class="sa-point-cards">
                @forelse($salaryRequests as $i => $row)
                    @php
                        $st = (string) $row->status;
                        $tone = $st === 'approved' ? 'ok' : ($st === 'rejected' ? 'no' : 'wait');
                        $statusLabel = match ($st) {
                            'approved' => __('message.point_status_confirmed'),
                            'rejected' => __('message.point_status_rejected'),
                            default => __('message.point_status_pending'),
                        };
                    @endphp
                    <article class="sa-point-card sa-point-card--{{ $tone }}">
                        <div class="sa-point-card__top">
                            <div class="sa-point-card__main">
                                <div class="sa-point-card__avatar" aria-hidden="true">
                                    <i class="fas fa-coins"></i>
                                </div>
                                <div class="sa-point-card__info">
                                    <div class="sa-point-card__title-row">
                                        <strong>{{ optional($row->user)->name }}</strong>
                                        <span class="sa-point-card__status sa-point-card__status--{{ $tone }}">{{ $statusLabel }}</span>
                                    </div>
                                    <div class="sa-point-card__meta">
                                        <span>#{{ $row->id }}</span>
                                        <span>{{ $row->salary_period }}</span>
                                        <span>{{ match ($salaryKind) {
                                            'staff' => 'Staff',
                                            'rider' => 'Rider',
                                            default => 'OS',
                                        } }}</span>
                                        <span>{{ $i + 1 }} of {{ $salaryRequests->count() }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="sa-point-card__metrics">
                                <div class="sa-point-card__metric sa-point-card__metric--mmk">
                                    <span>{{ __('message.point_bonus_requested') }}</span>
                                    <b>{{ number_format((float) $row->amount, 0) }} <small>MMK</small></b>
                                </div>
                                @if(($row->request_kind ?? '') === 'client')
                                    <div class="sa-point-card__metric">
                                        <span>{{ __('message.point_bonus_os_pay_settled') }}</span>
                                        <b>{{ number_format((float) ($row->os_pay_settled_amount ?? 0), 0) }}</b>
                                    </div>
                                    <div class="sa-point-card__metric sa-point-card__metric--remain">
                                        <span>{{ __('message.point_bonus_remaining') }}</span>
                                        <b>{{ number_format((float) ($row->points_remaining ?? max(0, (float) $row->points - (float) ($row->os_pay_settled_amount ?? 0))), 0) }}</b>
                                    </div>
                                @endif
                            </div>
                            <div class="sa-point-card__acts">
                                @if(($row->request_kind ?? '') === 'client' && is_array($row->os_pay_settled_details) && count($row->os_pay_settled_details) > 0)
                                    @php
                                        $parcelPayload = collect($row->os_pay_settled_details)->map(static function ($detail) {
                                            return [
                                                'item_id' => (int) ($detail['item_id'] ?? 0),
                                                'customer' => (string) ($detail['customer'] ?? '-'),
                                                'settled' => (float) ($detail['settled'] ?? 0),
                                                'deli_amount' => (float) ($detail['deli_amount'] ?? 0),
                                            ];
                                        })->values()->all();
                                    @endphp
                                    <button
                                        type="button"
                                        class="sa-point-card__info-btn js-sa-settled-parcels"
                                        data-name="{{ optional($row->user)->name ?: ('#'.$row->id) }}"
                                        data-request-id="{{ (int) $row->id }}"
                                        data-parcels='@json($parcelPayload)'
                                        title="{{ __('message.point_bonus_settled_parcels') }}"
                                        aria-label="{{ __('message.point_bonus_settled_parcels') }}"
                                    >
                                        <i class="fas fa-info" aria-hidden="true"></i>
                                    </button>
                                @endif
                                @if($st === 'pending')
                                    <form method="POST" action="{{ route('point-system.salary.approve', $row->id) }}">
                                        @csrf
                                        <input type="hidden" name="embed_return" value="super-admin">
                                        <input type="hidden" name="salary_status" value="{{ $salaryStatus }}">
                                        <input type="hidden" name="salary_kind" value="{{ $salaryKind }}">
                                        <input type="hidden" name="q" value="{{ $q }}">
                                        <button class="sa-point-card__btn sa-point-card__btn--ok" type="submit">
                                            <i class="fas fa-check" aria-hidden="true"></i>
                                            {{ __('message.point_salary_action_confirm') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        @if(filled($row->remark))
                            <p class="sa-point-card__note">
                                <i class="fas fa-quote-left" aria-hidden="true"></i>
                                <span class="sa-point-card__note-txt">{{ $row->remark }}</span>
                            </p>
                        @endif
                    </article>
                @empty
                    <div class="sa-point-empty">{{ __('message.no_record_found') }}</div>
                @endforelse
            </div>
        </section>
    @endif

    <div class="sa-point-modal sa-settled-parcels-modal" id="saSettledParcelsModal" hidden>
        <div class="sa-point-modal__backdrop js-sa-settled-parcels-close" tabindex="-1"></div>
        <div class="sa-point-modal__panel sa-settled-parcels-modal__panel" role="dialog" aria-modal="true" aria-labelledby="saSettledParcelsTitle">
            <button type="button" class="sa-point-modal__x js-sa-settled-parcels-close" aria-label="Close">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
            <div class="sa-settled-parcels-modal__hero">
                <span class="sa-settled-parcels-modal__icon" aria-hidden="true"><i class="fas fa-box-open"></i></span>
                <div>
                    <h4 id="saSettledParcelsTitle">{{ __('message.point_bonus_settled_parcels') }}</h4>
                    <p id="saSettledParcelsSubtitle">—</p>
                </div>
            </div>
            <ul class="sa-settled-parcels-modal__list" id="saSettledParcelsList"></ul>
            <div class="sa-settled-parcels-modal__foot">
                <button type="button" class="sa-settled-parcels-modal__done js-sa-settled-parcels-close">
                    {{ __('message.close') }}
                </button>
            </div>
        </div>
    </div>
</div>

@once
@push('bottom_script')
<script>
(function () {
    var modal = document.getElementById('saPointModal');
    var selected = document.getElementById('saPointSelected');
    var userId = document.getElementById('saPointUserId');
    var submitBtn = document.getElementById('saPointSubmit');
    var amount = document.getElementById('saPointAmount');
    if (!modal || !userId || !selected) return;

    function openModal() {
        modal.hidden = false;
        document.body.classList.add('sa-point-modal-open');
        requestAnimationFrame(function () {
            modal.classList.add('is-open');
        });
        if (amount) {
            setTimeout(function () { amount.focus(); amount.select(); }, 80);
        }
    }

    if (amount) {
        amount.addEventListener('wheel', function (e) { e.preventDefault(); }, { passive: false });
    }

    function closeModal() {
        modal.classList.remove('is-open');
        document.body.classList.remove('sa-point-modal-open');
        setTimeout(function () {
            if (!modal.classList.contains('is-open')) modal.hidden = true;
        }, 180);
    }

    function setPicked(row) {
        userId.value = row.id;
        selected.innerHTML = '<strong>' + (row.name || ('#' + row.id)) + '</strong>';
        selected.classList.add('is-filled');
        if (submitBtn) submitBtn.disabled = false;
        document.querySelectorAll('.js-sa-point-row').forEach(function (tr) {
            tr.classList.toggle('is-active', String(tr.getAttribute('data-id')) === String(row.id));
        });
        openModal();
    }

    function pickFromRow(tr) {
        setPicked({
            id: tr.getAttribute('data-id'),
            name: tr.getAttribute('data-name'),
            label: tr.getAttribute('data-label')
        });
    }

    document.querySelectorAll('.js-sa-point-row').forEach(function (tr) {
        tr.addEventListener('click', function (e) {
            if (e.target.closest('button')) return;
            pickFromRow(tr);
        });
        var btn = tr.querySelector('.js-sa-point-pick');
        if (btn) btn.addEventListener('click', function (e) {
            e.stopPropagation();
            pickFromRow(tr);
        });
    });

    modal.querySelectorAll('.js-sa-point-modal-close').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
    });
})();

(function () {
    var modal = document.getElementById('saSettledParcelsModal');
    var list = document.getElementById('saSettledParcelsList');
    var subtitle = document.getElementById('saSettledParcelsSubtitle');
    if (!modal || !list) return;

    function fmt(n) {
        var v = Number(n || 0);
        if (!isFinite(v)) v = 0;
        return v.toLocaleString('en-US', { maximumFractionDigits: 0 });
    }

    function openParcelsModal(btn) {
        var name = btn.getAttribute('data-name') || '—';
        var requestId = btn.getAttribute('data-request-id') || '';
        var parcels = [];
        try {
            parcels = JSON.parse(btn.getAttribute('data-parcels') || '[]') || [];
        } catch (e) {
            parcels = [];
        }

        if (subtitle) {
            subtitle.textContent = (requestId ? '#' + requestId + ' · ' : '') + name + ' · ' + parcels.length + ' parcels';
        }

        list.innerHTML = '';
        if (!parcels.length) {
            list.innerHTML = '<li class="is-empty">{{ __('message.no_record_found') }}</li>';
        } else {
            parcels.forEach(function (row) {
                var li = document.createElement('li');
                li.innerHTML =
                    '<div class="sa-settled-parcels-modal__main">' +
                        '<strong>#' + (row.item_id || 0) + '</strong>' +
                        '<span>' + (row.customer || '-') + '</span>' +
                    '</div>' +
                    '<div class="sa-settled-parcels-modal__amts">' +
                        '<b>' + fmt(row.settled) + '</b>' +
                        '<em>Deli ' + fmt(row.deli_amount) + '</em>' +
                    '</div>';
                list.appendChild(li);
            });
        }

        modal.hidden = false;
        document.body.classList.add('sa-point-modal-open');
        requestAnimationFrame(function () {
            modal.classList.add('is-open');
        });
    }

    function closeParcelsModal() {
        modal.classList.remove('is-open');
        document.body.classList.remove('sa-point-modal-open');
        setTimeout(function () {
            if (!modal.classList.contains('is-open')) modal.hidden = true;
        }, 180);
    }

    document.querySelectorAll('.js-sa-settled-parcels').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openParcelsModal(btn);
        });
    });

    modal.querySelectorAll('.js-sa-settled-parcels-close').forEach(function (el) {
        el.addEventListener('click', closeParcelsModal);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) closeParcelsModal();
    });
})();
</script>
@endpush
@endonce
