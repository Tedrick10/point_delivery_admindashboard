<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-to-assign-page pds-rider-remit-page">
        <div class="pds-dispatch-to-assign-screen pds-rider-screen">
            <div class="pds-rider-hero">
                <div class="pds-rider-hero__copy">
                    <div class="pds-rider-hero__eyebrow">
                        <i class="fas fa-wallet" aria-hidden="true"></i>
                        <span>{{ __('message.order') }}</span>
                    </div>
                    <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                    <p class="pds-rider-hero__subtitle">{{ __('message.rider_remit_subtitle') }}</p>
                </div>
                <div class="pds-rider-hero__stat">
                    <span class="pds-rider-hero__stat-value" data-rr-summary="rider_count">{{ $summary->rider_count }}</span>
                    <span class="pds-rider-hero__stat-label">{{ __('message.delivery_man') }}</span>
                </div>
            </div>

            @include('order.partials._settlement-tabs', ['activeTab' => 'rider-remit'])

            <form method="GET" action="{{ route('order.rider-remit') }}" class="pds-daily-check-toolbar" id="riderRemitFilterForm">
                <div class="pds-daily-check-toolbar__grid pds-rider-remit-toolbar">
                    <div class="pds-daily-check-field pds-daily-check-field--date">
                        <label for="rr_date">{{ __('message.date') }}</label>
                        <input type="text" name="date" id="rr_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $filterDate }}" autocomplete="off">
                    </div>
                    <div class="pds-daily-check-field pds-daily-check-field--branch">
                        <label for="rr_branch">{{ __('message.branch') }}</label>
                        <select name="branch_id" id="rr_branch" class="pds-dispatch-input pds-dispatch-select">
                            @if($branches->count() !== 1)
                                <option value="all" @selected($branchFilter === 'all')>{{ __('message.all') }}</option>
                            @endif
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) $branchFilter === (string) $branch->id)>{{ $branch->name }}</option>
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

            <div class="pds-money-transfer-summary pds-rider-remit-summary">
                <div class="pds-money-transfer-summary__card">
                    <span class="pds-money-transfer-summary__label">{{ __('message.rider_remit_due') }}</span>
                    <strong class="pds-money-transfer-summary__value" data-rr-summary="due_total">{{ number_format($summary->due_total) }}</strong>
                </div>
                <div class="pds-money-transfer-summary__card is-cash">
                    <span class="pds-money-transfer-summary__label">{{ __('message.rider_remit_cash') }}</span>
                    <strong class="pds-money-transfer-summary__value" data-rr-summary="cash_total">{{ number_format($summary->cash_total) }}</strong>
                </div>
                <div class="pds-money-transfer-summary__card is-kpay">
                    <span class="pds-money-transfer-summary__label">Kpay</span>
                    <strong class="pds-money-transfer-summary__value" data-rr-summary="kpay_total">{{ number_format($summary->kpay_total) }}</strong>
                </div>
                <div class="pds-money-transfer-summary__card {{ $summary->balanced_count === $summary->rider_count && $summary->rider_count > 0 ? 'is-ok' : 'is-warn' }}" id="rrBalanceCard">
                    <span class="pds-money-transfer-summary__label">{{ __('message.rider_remit_balanced') }}</span>
                    <strong class="pds-money-transfer-summary__value">
                        <span data-rr-summary="balanced_count">{{ $summary->balanced_count }}</span>/<span data-rr-summary="rider_count">{{ $summary->rider_count }}</span>
                    </strong>
                </div>
            </div>

            <div class="pds-rider-body">
                @if($riders->isEmpty())
                    <div class="pds-money-transfer-empty">
                        <div class="pds-money-transfer-empty__icon"><i class="fas fa-wallet"></i></div>
                        <h5>{{ __('message.rider_remit_empty_title') }}</h5>
                        <p>{{ __('message.rider_remit_empty') }}</p>
                    </div>
                @else
                    <div class="pds-rider-remit-shell">
                        <table class="pds-rider-remit-grid" id="riderRemitGrid"
                               data-save-url="{{ route('order.rider-remit.save') }}"
                               data-date="{{ $day }}"
                               data-branch="{{ $storeBranchId }}"
                               data-can-edit="{{ $canEdit ? '1' : '0' }}">
                            <thead>
                                <tr>
                                    <th class="pds-rider-remit-stub">{{ __('message.rider_remit_title') }}</th>
                                    @foreach($riders as $index => $rider)
                                        <th class="pds-rider-remit-col {{ $rider->balanced ? 'is-balanced' : '' }}" data-rider="{{ $rider->delivery_man_id }}">
                                            <span class="pds-rider-remit-col__no">{{ $index + 1 }}</span>
                                            <strong>{{ $rider->name }}</strong>
                                            @if($rider->item_count > 0)
                                                <small>{{ $rider->item_count }} {{ __('message.items') }}</small>
                                            @endif
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="pds-rider-remit-row is-input">
                                    <th>{{ __('message.rider_remit_prepaid') }}</th>
                                    @foreach($riders as $rider)
                                        <td data-rider="{{ $rider->delivery_man_id }}">
                                            <input class="js-rr-field" data-rider="{{ $rider->delivery_man_id }}" data-field="prepaid_amount" type="number" min="0" step="1" value="{{ $rider->prepaid_amount ?: '' }}" placeholder="0" @disabled(! $canEdit)>
                                        </td>
                                    @endforeach
                                </tr>
                                <tr class="pds-rider-remit-row is-due">
                                    <th>{{ __('message.rider_remit_due') }}</th>
                                    @foreach($riders as $rider)
                                        <td data-rider="{{ $rider->delivery_man_id }}" data-rr-due="{{ $rider->due_amount }}">
                                            <span class="pds-rider-remit-read">{{ number_format($rider->due_amount) }}</span>
                                        </td>
                                    @endforeach
                                </tr>
                                <tr class="pds-rider-remit-row is-input">
                                    <th>{{ __('message.rider_remit_fuel') }}</th>
                                    @foreach($riders as $rider)
                                        <td data-rider="{{ $rider->delivery_man_id }}">
                                            <input class="js-rr-field" data-rider="{{ $rider->delivery_man_id }}" data-field="fuel_amount" type="number" min="0" step="1" value="{{ $rider->fuel_amount ?: '' }}" placeholder="0" @disabled(! $canEdit)>
                                        </td>
                                    @endforeach
                                </tr>
                                <tr class="pds-rider-remit-row is-input">
                                    <th>{{ __('message.rider_remit_fee') }}</th>
                                    @foreach($riders as $rider)
                                        <td data-rider="{{ $rider->delivery_man_id }}">
                                            <input class="js-rr-field" data-rider="{{ $rider->delivery_man_id }}" data-field="fee_amount" type="number" min="0" step="1" value="{{ $rider->fee_amount ?: '' }}" placeholder="0" @disabled(! $canEdit)>
                                        </td>
                                    @endforeach
                                </tr>
                                <tr class="pds-rider-remit-row is-remain">
                                    <th>{{ __('message.rider_remit_remaining') }}</th>
                                    @foreach($riders as $rider)
                                        <td data-rider="{{ $rider->delivery_man_id }}">
                                            <span class="pds-rider-remit-read js-rr-remaining">{{ number_format($rider->remaining) }}</span>
                                        </td>
                                    @endforeach
                                </tr>

                                <tr class="pds-rider-remit-section">
                                    <th colspan="{{ $riders->count() + 1 }}">{{ __('message.rider_remit_notes') }}</th>
                                </tr>
                                @foreach($denoms as $note)
                                    <tr class="pds-rider-remit-row is-note">
                                        <th>{{ number_format($note) }}</th>
                                        @foreach($riders as $rider)
                                            <td data-rider="{{ $rider->delivery_man_id }}">
                                                <input class="js-rr-denom" data-rider="{{ $rider->delivery_man_id }}" data-note="{{ $note }}" type="number" min="0" step="1" value="{{ ($rider->denoms[(string) $note] ?? 0) ?: '' }}" placeholder="0" @disabled(! $canEdit)>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach

                                <tr class="pds-rider-remit-row is-money">
                                    <th>{{ __('message.rider_remit_cash') }}</th>
                                    @foreach($riders as $rider)
                                        <td data-rider="{{ $rider->delivery_man_id }}">
                                            <span class="pds-rider-remit-read js-rr-cash">{{ number_format($rider->cash_total) }}</span>
                                        </td>
                                    @endforeach
                                </tr>
                                <tr class="pds-rider-remit-row is-input is-kpay">
                                    <th>Kpay</th>
                                    @foreach($riders as $rider)
                                        <td data-rider="{{ $rider->delivery_man_id }}">
                                            <input class="js-rr-field" data-rider="{{ $rider->delivery_man_id }}" data-field="kpay_amount" type="number" min="0" step="1" value="{{ $rider->kpay_amount ?: '' }}" placeholder="0" @disabled(! $canEdit)>
                                        </td>
                                    @endforeach
                                </tr>
                                <tr class="pds-rider-remit-row is-total">
                                    <th>{{ __('message.rider_remit_combined') }}</th>
                                    @foreach($riders as $rider)
                                        <td data-rider="{{ $rider->delivery_man_id }}" class="{{ $rider->match_class ?? ($rider->balanced ? 'is-ok' : 'is-off') }}">
                                            <span class="pds-rider-remit-read js-rr-match">{{ $rider->match_label ?? '0' }}</span>
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="pds-rider-remit-hint">{{ __('message.rider_remit_hint') }}</p>
                @endif
            </div>
        </div>
    </div>

    <script>
        (function bootRiderRemit() {
            if (!window.jQuery) {
                return setTimeout(bootRiderRemit, 40);
            }
            var $ = window.jQuery;
            if (window.__pdsRiderRemitBound) return;
            window.__pdsRiderRemitBound = true;

                if (typeof flatpickr !== 'undefined') {
                    flatpickr('.dispatch-datepicker', { dateFormat: 'd-m-Y', allowInput: true });
                }
                var $grid = $('#riderRemitGrid');
                if (! $grid.length) return;
                var csrf = $('meta[name="csrf-token"]').attr('content');
                var saveUrl = $grid.data('save-url');
                var remitDate = $grid.data('date');
                var branchId = $grid.data('branch');
                var canEdit = String($grid.data('can-edit')) === '1';
                var denoms = @json($denoms);
                var timers = {};

                function num(v) {
                    var n = parseFloat(String(v == null ? '' : v).replace(/,/g, ''));
                    return isNaN(n) ? 0 : n;
                }
                function fmt(n) {
                    return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                }
                function col(riderId) {
                    return $grid.find('[data-rider="' + riderId + '"]');
                }
                function dueOf(riderId) {
                    return num(col(riderId).filter('[data-rr-due]').attr('data-rr-due'));
                }
                function compute(riderId) {
                    var prepaid = num($grid.find('.js-rr-field[data-rider="' + riderId + '"][data-field="prepaid_amount"]').val());
                    var fuel = num($grid.find('.js-rr-field[data-rider="' + riderId + '"][data-field="fuel_amount"]').val());
                    var fee = num($grid.find('.js-rr-field[data-rider="' + riderId + '"][data-field="fee_amount"]').val());
                    var kpay = num($grid.find('.js-rr-field[data-rider="' + riderId + '"][data-field="kpay_amount"]').val());
                    var cash = 0;
                    denoms.forEach(function (note) {
                        cash += note * num($grid.find('.js-rr-denom[data-rider="' + riderId + '"][data-note="' + note + '"]').val());
                    });
                    var remaining = dueOf(riderId) - prepaid - fuel - fee;
                    var combined = cash + kpay;
                    var diff = combined - remaining;
                    var ok = Math.abs(diff) < 0.51;
                    var matchLabel = ok ? '0' : ((diff > 0 ? '+' : '-') + fmt(Math.abs(diff)));
                    col(riderId).find('.js-rr-remaining').text(fmt(remaining));
                    col(riderId).find('.js-rr-cash').text(fmt(cash));
                    $grid.find('thead .pds-rider-remit-col[data-rider="' + riderId + '"]').toggleClass('is-balanced', ok);
                    $grid.find('tr.is-total td[data-rider="' + riderId + '"]')
                        .removeClass('is-ok is-off is-short is-over')
                        .addClass(ok ? 'is-ok' : (diff > 0 ? 'is-over' : 'is-off'))
                        .find('.js-rr-match').text(matchLabel);
                    return { prepaid: prepaid, fuel: fuel, fee: fee, kpay: kpay, remaining: remaining, cash: cash, combined: combined, ok: ok };
                }
                function payload(riderId) {
                    var dens = {};
                    denoms.forEach(function (note) {
                        dens[note] = num($grid.find('.js-rr-denom[data-rider="' + riderId + '"][data-note="' + note + '"]').val());
                    });
                    return {
                        _token: csrf,
                        remit_date: remitDate,
                        branch_id: branchId,
                        delivery_man_id: riderId,
                        prepaid_amount: num($grid.find('.js-rr-field[data-rider="' + riderId + '"][data-field="prepaid_amount"]').val()),
                        fuel_amount: num($grid.find('.js-rr-field[data-rider="' + riderId + '"][data-field="fuel_amount"]').val()),
                        fee_amount: num($grid.find('.js-rr-field[data-rider="' + riderId + '"][data-field="fee_amount"]').val()),
                        kpay_amount: num($grid.find('.js-rr-field[data-rider="' + riderId + '"][data-field="kpay_amount"]').val()),
                        denominations: dens
                    };
                }
                function refreshSummary() {
                    var due = 0, cash = 0, kpay = 0, ok = 0, count = 0;
                    $grid.find('thead .pds-rider-remit-col').each(function () {
                        var id = $(this).data('rider');
                        var c = compute(id);
                        due += dueOf(id);
                        cash += c.cash;
                        kpay += c.kpay;
                        if (c.ok) ok += 1;
                        count += 1;
                    });
                    $('[data-rr-summary="due_total"]').text(fmt(due));
                    $('[data-rr-summary="cash_total"]').text(fmt(cash));
                    $('[data-rr-summary="kpay_total"]').text(fmt(kpay));
                    $('[data-rr-summary="balanced_count"]').text(ok);
                    $('[data-rr-summary="rider_count"]').text(count);
                    $('#rrBalanceCard').toggleClass('is-ok', ok === count && count > 0).toggleClass('is-warn', !(ok === count && count > 0));
                }
                function saveRider(riderId) {
                    if (! canEdit) return;
                    compute(riderId);
                    refreshSummary();
                    $.ajax({
                        url: saveUrl,
                        type: 'POST',
                        data: payload(riderId),
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        error: function (xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : @json(__('message.something_went_wrong'));
                            if (typeof errorMessage === 'function') errorMessage(msg);
                        }
                    });
                }
                function schedule(riderId) {
                    compute(riderId);
                    refreshSummary();
                    clearTimeout(timers[riderId]);
                    timers[riderId] = setTimeout(function () { saveRider(riderId); }, 450);
                }

                $grid.on('input change', '.js-rr-field, .js-rr-denom', function () {
                    schedule($(this).data('rider'));
                });
                refreshSummary();
        })();
        </script>
</x-master-layout>
