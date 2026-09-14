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

            @include('partials._branch-tabs', [
                'branchTabs' => $branchTabs ?? $branches,
                'selectedBranchId' => $selectedBranchId ?? ($branchFilter === 'all' ? null : (int) $branchFilter),
                'branchTabCounts' => $branchTabCounts ?? [],
                'allCount' => $allBranchCount ?? null,
                'includeAll' => false,
                'routeName' => 'order.rider-remit',
                'routeQuery' => [
                    'date' => $filterDate,
                ],
            ])

            <form method="GET" action="{{ route('order.rider-remit') }}" class="pds-daily-check-toolbar" id="riderRemitFilterForm">
                <input type="hidden" name="branch_id" id="rr_branch" value="{{ $branchFilter }}">
                <div class="pds-daily-check-toolbar__grid pds-rider-remit-toolbar">
                    <div class="pds-daily-check-field pds-daily-check-field--date">
                        <label for="rr_date">{{ __('message.date') }}</label>
                        <input type="text" name="date" id="rr_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $filterDate }}" autocomplete="off">
                    </div>
                    <div class="pds-daily-check-toolbar__actions">
                        <button type="button" class="pds-rider-remit-audit-btn" id="rrAuditBtn"
                                data-logs-url="{{ route('order.rider-remit.logs') }}"
                                data-date="{{ $day }}"
                                data-branch="{{ $storeBranchId }}">
                            <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                            <span>{{ __('message.rider_remit_audit_log') }}</span>
                        </button>
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
                <div class="pds-money-transfer-summary__card is-combined">
                    <span class="pds-money-transfer-summary__label">{{ __('message.rider_remit_combined') }}</span>
                    <strong class="pds-money-transfer-summary__value" data-rr-summary="combined_total">{{ number_format(($summary->cash_total ?? 0) + ($summary->kpay_total ?? 0)) }}</strong>
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
                    @php
                        $riderCount = $riders->count();
                        $colCount = $riderCount + 2; // label + riders + total
                        $sheetTotals = [];
                        foreach ($denoms as $note) {
                            $sheetTotals[$note] = (int) $riders->sum(function ($rider) use ($note) {
                                return (int) ($rider->denoms[(string) $note] ?? 0);
                            });
                        }
                    @endphp
                    <div class="pds-rider-remit-shell">
                        <table class="pds-rider-remit-grid" id="riderRemitGrid"
                               data-save-url="{{ route('order.rider-remit.save') }}"
                               data-date="{{ $day }}"
                               data-branch="{{ $storeBranchId }}"
                               data-can-edit="{{ $canEdit ? '1' : '0' }}">
                            <thead>
                                <tr>
                                    <th class="pds-rider-remit-stub pds-rider-remit-label">{{ __('message.rider_remit_title') }}</th>
                                    @foreach($riders as $index => $rider)
                                        <th class="pds-rider-remit-col {{ $rider->balanced ? 'is-balanced' : '' }} {{ !($rider->has_ways ?? true) ? 'is-rider-no-ways' : '' }}" data-rider="{{ $rider->delivery_man_id }}" data-item-count="{{ (int) $rider->item_count }}" data-can-edit="{{ ($rider->can_edit ?? false) ? '1' : '0' }}">
                                            <div class="pds-rider-remit-col-inner">
                                                <strong>{{ $rider->name }}</strong>
                                                <small class="pds-rider-remit-col__ways" title="{{ __('message.rider_remit_delivered_way_hint') }}">
                                                    <b>{{ (int) $rider->item_count }}</b>
                                                </small>
                                            </div>
                                        </th>
                                    @endforeach
                                    <th class="pds-rider-remit-total-col">
                                        <strong>{{ __('message.rider_remit_sheet_total') }}</strong>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="pds-rider-remit-row is-input is-prepaid">
                                    <th class="pds-rider-remit-label">{{ __('message.rider_remit_prepaid') }}</th>
                                    @foreach($riders as $rider)
                                        <td data-rider="{{ $rider->delivery_man_id }}">
                                            <input class="js-rr-field" data-rider="{{ $rider->delivery_man_id }}" data-field="prepaid_amount" type="number" min="0" step="1" value="{{ $rider->prepaid_amount ?: '' }}" placeholder="0" @disabled(! $canEdit || ! ($rider->can_edit ?? true))>
                                        </td>
                                    @endforeach
                                    <td class="pds-rider-remit-total-cell">
                                        <span class="pds-rider-remit-read js-rr-prepaid-total">{{ number_format($summary->prepaid_total ?? $riders->sum('prepaid_amount')) }}</span>
                                    </td>
                                </tr>
                                <tr class="pds-rider-remit-row is-due">
                                    <th class="pds-rider-remit-label">{{ __('message.rider_remit_due') }}</th>
                                    @foreach($riders as $rider)
                                        <td data-rider="{{ $rider->delivery_man_id }}" data-rr-due="{{ $rider->due_amount }}">
                                            <span class="pds-rider-remit-read">{{ number_format($rider->due_amount) }}</span>
                                        </td>
                                    @endforeach
                                    <td class="pds-rider-remit-total-cell">
                                        <span class="pds-rider-remit-read js-rr-due-total">{{ number_format($summary->due_total) }}</span>
                                    </td>
                                </tr>
                                @if(empty($isOtherBranchRemit))
                                <tr class="pds-rider-remit-row is-fuel">
                                    <th class="pds-rider-remit-label">{{ __('message.rider_remit_fuel') }}</th>
                                    @foreach($riders as $rider)
                                        <td data-rider="{{ $rider->delivery_man_id }}" data-rr-fuel="{{ (float) ($rider->fuel_amount ?? 0) }}">
                                            <span class="pds-rider-remit-read js-rr-fuel">{{ number_format((float) ($rider->fuel_amount ?? 0)) }}</span>
                                        </td>
                                    @endforeach
                                    <td class="pds-rider-remit-total-cell">
                                        <span class="pds-rider-remit-read js-rr-fuel-total">{{ number_format($summary->fuel_total ?? 0) }}</span>
                                    </td>
                                </tr>
                                @endif
                                <tr class="pds-rider-remit-row {{ !empty($isOtherBranchRemit) ? 'is-half-deli' : 'is-fee' }}">
                                    <th class="pds-rider-remit-label">
                                        {{ !empty($isOtherBranchRemit) ? __('message.rider_remit_half_deli') : __('message.rider_remit_fee') }}
                                    </th>
                                    @foreach($riders as $rider)
                                        <td data-rider="{{ $rider->delivery_man_id }}" data-rr-fee="{{ (float) ($rider->fee_amount ?? 0) }}">
                                            <span class="pds-rider-remit-read js-rr-fee">{{ number_format((float) ($rider->fee_amount ?? 0)) }}</span>
                                        </td>
                                    @endforeach
                                    <td class="pds-rider-remit-total-cell">
                                        <span class="pds-rider-remit-read js-rr-fee-total">{{ number_format($summary->fee_total ?? 0) }}</span>
                                    </td>
                                </tr>
                                <tr class="pds-rider-remit-row is-remain">
                                    <th class="pds-rider-remit-label">{{ __('message.rider_remit_remaining') }}</th>
                                    @foreach($riders as $rider)
                                        <td data-rider="{{ $rider->delivery_man_id }}">
                                            <span class="pds-rider-remit-read js-rr-remaining">{{ number_format($rider->remaining) }}</span>
                                        </td>
                                    @endforeach
                                    <td class="pds-rider-remit-total-cell">
                                        <span class="pds-rider-remit-read js-rr-remaining-total">{{ number_format($summary->remaining_total) }}</span>
                                    </td>
                                </tr>

                                <tr class="pds-rider-remit-section">
                                    <th class="pds-rider-remit-label">{{ __('message.rider_remit_notes') }}</th>
                                    <td colspan="{{ $riderCount }}" class="pds-rider-remit-section-span"></td>
                                    <td class="pds-rider-remit-total-cell is-blank"></td>
                                </tr>
                                @foreach($denoms as $note)
                                    <tr class="pds-rider-remit-row is-note" data-note-row="{{ $note }}">
                                        <th class="pds-rider-remit-label">{{ number_format($note) }}</th>
                                        @foreach($riders as $rider)
                                            <td data-rider="{{ $rider->delivery_man_id }}">
                                                <input class="js-rr-denom" data-rider="{{ $rider->delivery_man_id }}" data-note="{{ $note }}" type="number" min="0" step="1" value="{{ ($rider->denoms[(string) $note] ?? 0) ?: '' }}" placeholder="0" @disabled(! $canEdit || ! ($rider->can_edit ?? true))>
                                            </td>
                                        @endforeach
                                        <td class="pds-rider-remit-total-cell">
                                            <span class="pds-rider-remit-read js-rr-note-total" data-note-total="{{ $note }}">{{ number_format($sheetTotals[$note] ?? 0) }}</span>
                                        </td>
                                    </tr>
                                @endforeach

                                <tr class="pds-rider-remit-row is-money">
                                    <th class="pds-rider-remit-label">{{ __('message.rider_remit_cash') }}</th>
                                    @foreach($riders as $rider)
                                        <td data-rider="{{ $rider->delivery_man_id }}">
                                            <span class="pds-rider-remit-read js-rr-cash">{{ number_format($rider->cash_total) }}</span>
                                        </td>
                                    @endforeach
                                    <td class="pds-rider-remit-total-cell">
                                        <span class="pds-rider-remit-read js-rr-money-total">{{ number_format($summary->cash_total) }}</span>
                                    </td>
                                </tr>
                                <tr class="pds-rider-remit-row is-input is-kpay">
                                    <th class="pds-rider-remit-label">Kpay</th>
                                    @foreach($riders as $rider)
                                        <td data-rider="{{ $rider->delivery_man_id }}">
                                            <input class="js-rr-field" data-rider="{{ $rider->delivery_man_id }}" data-field="kpay_amount" type="number" min="0" step="1" value="{{ $rider->kpay_amount ?: '' }}" placeholder="0" @disabled(! $canEdit || ! ($rider->can_edit ?? true))>
                                        </td>
                                    @endforeach
                                    <td class="pds-rider-remit-total-cell">
                                        <span class="pds-rider-remit-read js-rr-kpay-total">{{ number_format($summary->kpay_total) }}</span>
                                    </td>
                                </tr>
                                <tr class="pds-rider-remit-row is-total">
                                    <th class="pds-rider-remit-label">{{ __('message.rider_remit_combined') }}</th>
                                    @foreach($riders as $rider)
                                        <td data-rider="{{ $rider->delivery_man_id }}" class="{{ $rider->match_class ?? ($rider->balanced ? 'is-ok' : 'is-off') }}">
                                            <span class="pds-rider-remit-read js-rr-match">{{ $rider->match_label ?? '0' }}</span>
                                        </td>
                                    @endforeach
                                    <td class="pds-rider-remit-total-cell">
                                        <span class="pds-rider-remit-read js-rr-combined-total">{{ number_format(($summary->cash_total ?? 0) + ($summary->kpay_total ?? 0)) }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pds-rider-remit-footer">
                        <p class="pds-rider-remit-hint">{{ __('message.rider_remit_hint') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="pds-rider-remit-audit" id="rrAuditModal" hidden>
        <div class="pds-rider-remit-audit__backdrop" data-rr-audit-close></div>
        <div class="pds-rider-remit-audit__panel" role="dialog" aria-modal="true" aria-labelledby="rrAuditTitle">
            <div class="pds-rider-remit-audit__head">
                <div>
                    <h5 id="rrAuditTitle">{{ __('message.rider_remit_audit_log') }}</h5>
                    <p>{{ $filterDate }}</p>
                </div>
                <button type="button" class="pds-rider-remit-audit__close" data-rr-audit-close aria-label="Close">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="pds-rider-remit-audit__body" id="rrAuditBody">
                <p class="pds-rider-remit-audit__empty">{{ __('message.rider_remit_audit_empty') }}</p>
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

                (function bindAuditLog() {
                    var $btn = $('#rrAuditBtn');
                    var $modal = $('#rrAuditModal');
                    var $body = $('#rrAuditBody');
                    if (! $btn.length || ! $modal.length) return;
                    var emptyText = @json(__('message.rider_remit_audit_empty'));
                    var fromText = @json(__('message.rider_remit_audit_from'));
                    var toText = @json(__('message.rider_remit_audit_to'));

                    function closeAudit() {
                        $modal.attr('hidden', true);
                    }
                    function openAudit() {
                        $modal.removeAttr('hidden');
                        $body.html('<p class="pds-rider-remit-audit__loading"><i class="fas fa-spinner fa-spin"></i></p>');
                        $.ajax({
                            url: $btn.data('logs-url'),
                            type: 'GET',
                            data: {
                                date: $btn.data('date'),
                                branch_id: $btn.data('branch')
                            },
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                            success: function (res) {
                                var logs = (res && res.logs) ? res.logs : [];
                                if (! logs.length) {
                                    $body.html('<p class="pds-rider-remit-audit__empty">' + emptyText + '</p>');
                                    return;
                                }
                                var html = '<ol class="pds-rider-remit-audit__list">';
                                logs.forEach(function (row, index) {
                                    var isSubmit = row.action === 'submitted';
                                    html += '<li class="pds-rider-remit-audit__item' + (isSubmit ? ' is-submit' : '') + '">';
                                    html += '<span class="pds-rider-remit-audit__index">' + (index + 1) + '</span>';
                                    html += '<div class="pds-rider-remit-audit__card">';
                                    html += '<div class="pds-rider-remit-audit__meta">';
                                    html += '<strong>' + $('<div>').text(row.actor || '-').html() + '</strong>';
                                    html += '<time>' + $('<div>').text(row.time || '').html() + '</time>';
                                    html += '</div>';
                                    html += '<p>' + $('<div>').text(row.message || '').html() + '</p>';
                                    if (! isSubmit && row.old_value != null && row.new_value != null) {
                                        html += '<div class="pds-rider-remit-audit__change">';
                                        html += '<span>' + $('<div>').text(fromText).html() + ' <b>' + $('<div>').text(String(row.old_value)).html() + '</b></span>';
                                        html += '<i class="fas fa-arrow-right" aria-hidden="true"></i>';
                                        html += '<span>' + $('<div>').text(toText).html() + ' <b>' + $('<div>').text(String(row.new_value)).html() + '</b></span>';
                                        html += '</div>';
                                    }
                                    html += '</div></li>';
                                });
                                html += '</ol>';
                                $body.html(html);
                            },
                            error: function (xhr) {
                                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : @json(__('message.something_went_wrong'));
                                $body.html('<p class="pds-rider-remit-audit__empty">' + $('<div>').text(msg).html() + '</p>');
                            }
                        });
                    }

                    $btn.on('click', function (e) {
                        e.preventDefault();
                        openAudit();
                    });
                    $modal.on('click', '[data-rr-audit-close]', closeAudit);
                    $(document).on('keydown.rrAudit', function (e) {
                        if (e.key === 'Escape' && ! $modal.is('[hidden]')) {
                            closeAudit();
                        }
                    });
                })();

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
                function fuelOf(riderId) {
                    return num(col(riderId).filter('[data-rr-fuel]').attr('data-rr-fuel'));
                }
                function feeOf(riderId) {
                    return num(col(riderId).filter('[data-rr-fee]').attr('data-rr-fee'));
                }
                function col(riderId) {
                    return $grid.find('[data-rider="' + riderId + '"]');
                }
                function dueOf(riderId) {
                    return num(col(riderId).filter('[data-rr-due]').attr('data-rr-due'));
                }
                function compute(riderId) {
                    if (!canEditRider(riderId)) {
                        $grid.find('tr.is-total td[data-rider="' + riderId + '"]')
                            .removeClass('is-ok is-off is-short is-over is-off-rider')
                            .addClass('is-ok')
                            .find('.js-rr-match').text('0');
                        $grid.find('thead .pds-rider-remit-col[data-rider="' + riderId + '"]').removeClass('is-balanced');
                        return { prepaid: 0, fuel: 0, fee: 0, kpay: 0, remaining: dueOf(riderId), cash: 0, combined: 0, ok: true };
                    }
                    var prepaid = num($grid.find('.js-rr-field[data-rider="' + riderId + '"][data-field="prepaid_amount"]').val());
                    var fuel = fuelOf(riderId);
                    var fee = feeOf(riderId);
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
                        .removeClass('is-ok is-off is-short is-over is-off-rider')
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
                        kpay_amount: num($grid.find('.js-rr-field[data-rider="' + riderId + '"][data-field="kpay_amount"]').val()),
                        denominations: dens
                    };
                }
                function itemCountOf(riderId) {
                    return num($grid.find('thead .pds-rider-remit-col[data-rider="' + riderId + '"]').data('item-count'));
                }
                function canEditRider(riderId) {
                    return String($grid.find('thead .pds-rider-remit-col[data-rider="' + riderId + '"]').data('can-edit')) === '1';
                }
                function syncColumnStyles() {
                    $grid.find('thead .pds-rider-remit-col').each(function () {
                        var id = $(this).data('rider');
                        var noWays = itemCountOf(id) < 1;
                        $(this).removeClass('is-rider-off').toggleClass('is-rider-no-ways', noWays);
                        $grid.find('tbody td[data-rider="' + id + '"]').removeClass('is-rider-off-cell').toggleClass('is-rider-no-ways-cell', noWays);
                    });
                }
                function refreshNoteTotals() {
                    denoms.forEach(function (note) {
                        var sum = 0;
                        $grid.find('.js-rr-denom[data-note="' + note + '"]').each(function () {
                            sum += num($(this).val());
                        });
                        $grid.find('.js-rr-note-total[data-note-total="' + note + '"]').text(fmt(sum));
                    });
                }
                function refreshSummary() {
                    var due = 0, cash = 0, kpay = 0, combined = 0, prepaid = 0, fuel = 0, fee = 0, remaining = 0, ok = 0, count = 0;
                    $grid.find('thead .pds-rider-remit-col').each(function () {
                        var id = $(this).data('rider');
                        var c = compute(id);
                        due += dueOf(id);
                        cash += c.cash;
                        kpay += c.kpay;
                        combined += c.combined;
                        prepaid += c.prepaid;
                        fuel += c.fuel;
                        fee += c.fee;
                        remaining += c.remaining;
                        if (c.ok) ok += 1;
                        count += 1;
                    });
                    refreshNoteTotals();
                    $grid.find('.js-rr-due-total').text(fmt(due));
                    $grid.find('.js-rr-prepaid-total').text(fmt(prepaid));
                    $grid.find('.js-rr-fuel-total').text(fmt(fuel));
                    $grid.find('.js-rr-fee-total').text(fmt(fee));
                    $grid.find('.js-rr-remaining-total').text(fmt(remaining));
                    $grid.find('.js-rr-money-total').text(fmt(cash));
                    $grid.find('.js-rr-kpay-total').text(fmt(kpay));
                    $grid.find('.js-rr-combined-total').text(fmt(combined));
                    $('[data-rr-summary="due_total"]').text(fmt(due));
                    $('[data-rr-summary="cash_total"]').text(fmt(cash));
                    $('[data-rr-summary="kpay_total"]').text(fmt(kpay));
                    $('[data-rr-summary="combined_total"]').text(fmt(combined));
                    $('[data-rr-summary="balanced_count"]').text(ok);
                    $('[data-rr-summary="rider_count"]').text(count);
                    $('#rrBalanceCard').toggleClass('is-ok', ok === count && count > 0).toggleClass('is-warn', !(ok === count && count > 0));
                    syncColumnStyles();
                }
                function saveRider(riderId) {
                    if (! canEdit || ! canEditRider(riderId)) return;
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
                    if (! canEditRider(riderId)) return;
                    compute(riderId);
                    refreshSummary();
                    clearTimeout(timers[riderId]);
                    timers[riderId] = setTimeout(function () { saveRider(riderId); }, 450);
                }

                $grid.on('input change', '.js-rr-field, .js-rr-denom', function () {
                    schedule($(this).data('rider'));
                });

                $grid.on('keydown', '.js-rr-field, .js-rr-denom', function (e) {
                    if (e.key !== 'Enter') return;
                    e.preventDefault();
                    var $el = $(this);
                    if ($el.is(':disabled')) return;
                    var riderId = $el.data('rider');
                    if (!canEdit || !canEditRider(riderId)) return;
                    clearTimeout(timers[riderId]);
                    saveRider(riderId);
                    var el = $el.get(0);
                    if (el) {
                        if (typeof el.setSelectionRange === 'function') {
                            var len = String(el.value || '').length;
                            el.setSelectionRange(len, len);
                        }
                        el.blur();
                    }
                    if (window.getSelection) {
                        var sel = window.getSelection();
                        if (sel && sel.removeAllRanges) sel.removeAllRanges();
                    }
                });

                refreshSummary();
        })();
        </script>
</x-master-layout>
