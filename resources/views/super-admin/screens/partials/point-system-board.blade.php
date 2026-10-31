@php
    $tab = $pointSystem['tab'] ?? 'give';
    $directory = $pointSystem['directory'] ?? collect();
    $directoryCounts = $pointSystem['directoryCounts'] ?? ['os' => 0, 'staff' => 0, 'rider' => 0];
    $recentCredits = $pointSystem['recentCredits'] ?? collect();
    $followUps = $pointSystem['followUps'] ?? collect();
    $salaryRequests = $pointSystem['salaryRequests'] ?? collect();
    $followPointTotals = $pointSystem['followPointTotals'] ?? ['pending' => 0, 'confirmed' => 0];
    $salaryPointTotals = $pointSystem['salaryPointTotals'] ?? ['pending' => 0, 'confirmed' => 0];
    $q = $pointSystem['q'] ?? '';
    $recipientType = $pointSystem['recipientType'] ?? 'os';
    $followStatus = $pointSystem['followStatus'] ?? 'all';
    $salaryStatus = $pointSystem['salaryStatus'] ?? 'all';
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
    $listTitle = match ($recipientType) {
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
                    <span><strong>{{ $directory->filter(fn ($r) => (float) $r->balance > 0)->count() }}</strong> with points</span>
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
                            <th style="width:7rem;">Balance</th>
                            <th style="width:6.5rem;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($directory as $i => $row)
                            <tr class="js-sa-point-row"
                                data-id="{{ $row->id }}"
                                data-name="{{ $row->name }}"
                                data-label="#{{ $row->id }} {{ $row->name }} · {{ number_format((float) $row->balance, 2) }} pts"
                                data-balance="{{ number_format((float) $row->balance, 2) }}">
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
                                        {{ number_format((float) $row->balance, 0) }}
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
                                <span>Points</span>
                                <input type="number" name="amount" id="saPointAmount" min="1" step="1" required value="100" inputmode="numeric">
                            </label>
                        </div>
                        <label class="sa-point-composer__note">
                            <span>Note</span>
                            <input type="text" name="description" value="{{ __('message.point_admin_credit_default') }}">
                        </label>
                        <button type="submit" class="sa-point-composer__go" id="saPointSubmit" disabled>
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            Credit Points
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
            <div class="sa-point-totals" aria-label="Point totals">
                <div class="sa-point-total sa-point-total--wait">
                    <span>Pending</span>
                    <b>{{ number_format((float) ($followPointTotals['pending'] ?? 0), 0) }}</b>
                    <em>Points</em>
                </div>
                <div class="sa-point-total sa-point-total--ok">
                    <span>Confirmed</span>
                    <b>{{ number_format((float) ($followPointTotals['confirmed'] ?? 0), 0) }}</b>
                    <em>Points</em>
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
                            <span>Points</span>
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
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ];
        @endphp
        <section class="sa-point-shell">
            <header class="sa-point-shell__head">
                <div>
                    <h3>{{ __('message.point_salary_requests') }}</h3>
                </div>
                <div class="sa-point-shell__stats">
                    <span><strong>{{ $salaryRequests->count() }}</strong> shown</span>
                </div>
            </header>
            <form method="GET" action="{{ route('super-admin.screens.show', 'point-system') }}" class="sa-point-toolbar">
                <input type="hidden" name="tab" value="salary">
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
            <div class="sa-point-totals" aria-label="Point totals">
                <div class="sa-point-total sa-point-total--wait">
                    <span>Pending</span>
                    <b>{{ number_format((float) ($salaryPointTotals['pending'] ?? 0), 0) }}</b>
                    <em>Points</em>
                </div>
                <div class="sa-point-total sa-point-total--ok">
                    <span>Confirmed</span>
                    <b>{{ number_format((float) ($salaryPointTotals['confirmed'] ?? 0), 0) }}</b>
                    <em>Points</em>
                </div>
            </div>
            <div class="sa-point-cards">
                @forelse($salaryRequests as $i => $row)
                    @php
                        $st = (string) $row->status;
                        $tone = $st === 'approved' ? 'ok' : ($st === 'rejected' ? 'no' : 'wait');
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
                                        <span class="sa-point-card__status sa-point-card__status--{{ $tone }}">{{ ucfirst($st) }}</span>
                                    </div>
                                    <div class="sa-point-card__meta">
                                        <span>#{{ $row->id }}</span>
                                        <span>{{ $row->salary_period }}</span>
                                        <span>{{ $i + 1 }} of {{ $salaryRequests->count() }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="sa-point-card__metrics">
                                <div class="sa-point-card__metric">
                                    <span>Points</span>
                                    <b>{{ number_format((float) $row->points, 0) }}</b>
                                </div>
                                <div class="sa-point-card__arrow" aria-hidden="true"><i class="fas fa-arrow-right"></i></div>
                                <div class="sa-point-card__metric sa-point-card__metric--mmk">
                                    <span>Salary</span>
                                    <b>{{ number_format((float) $row->amount, 0) }} <small>MMK</small></b>
                                </div>
                            </div>
                            <div class="sa-point-card__acts">
                                @if($st === 'pending')
                                    <form method="POST" action="{{ route('point-system.salary.approve', $row->id) }}">
                                        @csrf
                                        <input type="hidden" name="embed_return" value="super-admin">
                                        <input type="hidden" name="salary_status" value="{{ $salaryStatus }}">
                                        <input type="hidden" name="q" value="{{ $q }}">
                                        <button class="sa-point-card__btn sa-point-card__btn--ok" type="submit">
                                            <i class="fas fa-check" aria-hidden="true"></i>
                                            {{ __('message.point_action_approve') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('point-system.salary.reject', $row->id) }}" onsubmit="return confirm(@json(__('message.are_you_sure')));">
                                        @csrf
                                        <input type="hidden" name="embed_return" value="super-admin">
                                        <input type="hidden" name="salary_status" value="{{ $salaryStatus }}">
                                        <input type="hidden" name="q" value="{{ $q }}">
                                        <button class="sa-point-card__btn" type="submit">
                                            <i class="fas fa-times" aria-hidden="true"></i>
                                            {{ __('message.point_action_reject') }}
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
</script>
@endpush
@endonce
