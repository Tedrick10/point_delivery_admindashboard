<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-to-assign-page pds-daily-check-page pds-money-transfer-page">
        <div class="pds-dispatch-to-assign-screen pds-rider-screen">
            <div class="pds-rider-hero">
                <div class="pds-rider-hero__copy">
                    <div class="pds-rider-hero__eyebrow">
                        <i class="fas fa-exchange-alt" aria-hidden="true"></i>
                        <span>{{ __('message.order') }}</span>
                    </div>
                    <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                    <p class="pds-rider-hero__subtitle">{{ __('message.money_transfer_subtitle') }}</p>
                </div>
                <div class="pds-rider-hero__stat">
                    <span class="pds-rider-hero__stat-value">{{ $summary->os_count }}</span>
                    <span class="pds-rider-hero__stat-label">OS</span>
                </div>
            </div>

            @include('order.partials._settlement-tabs', ['activeTab' => 'money-transfer'])

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
                <div class="pds-daily-check-toolbar__grid pds-money-transfer-toolbar__grid">
                    <div class="pds-daily-check-field pds-daily-check-field--date">
                        <label for="mt_from_date">{{ __('message.from') }}</label>
                        <input type="text" name="from_date" id="mt_from_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $filterFromDate }}" autocomplete="off">
                    </div>
                    <div class="pds-daily-check-field pds-daily-check-field--date">
                        <label for="mt_to_date">{{ __('message.to') }}</label>
                        <input type="text" name="to_date" id="mt_to_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $filterToDate }}" autocomplete="off">
                    </div>
                    <div class="pds-daily-check-field pds-daily-check-field--branch">
                        <label for="mt_branch">{{ __('message.branch') }}</label>
                        <select name="branch_id" id="mt_branch" class="pds-dispatch-input pds-dispatch-select">
                            @if($branches->count() !== 1)
                                <option value="all" @selected($branchFilter === 'all')>{{ __('message.all') }}</option>
                            @endif
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) $branchFilter === (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pds-daily-check-field pds-daily-check-field--party">
                        <label for="mt_os">{{ __('message.online_shopping') }}</label>
                        <select name="os_id" id="mt_os" class="pds-dispatch-input pds-dispatch-select">
                            <option value="all" @selected($osFilter === 'all')>{{ __('message.all') }}</option>
                            <option value="0" @selected((string) $osFilter === '0')>{{ __('message.no_os') }}</option>
                            @foreach($osOptions as $option)
                                <option value="{{ $option->id }}" @selected((string) $osFilter === (string) $option->id)>{{ $option->name }}</option>
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
                <div class="pds-money-transfer-summary__card">
                    <span class="pds-money-transfer-summary__label">{{ __('message.total_amount') }}</span>
                    <strong class="pds-money-transfer-summary__value" data-summary="amount_due">{{ number_format($summary->amount_due) }}</strong>
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
                    <div class="pds-rider-table-shell pds-rider-table-shell--scroll pds-daily-check-shell">
                        <table class="table pds-rider-list-table pds-daily-check-table pds-money-transfer-table {{ $showCashCols ? 'is-cash-cols' : 'is-kpay-compact' }}" id="moneyTransferTable">
                            <thead>
                                <tr>
                                    <th style="width:48px">{{ __('message.no') }}</th>
                                    <th>{{ __('message.os_name') }}</th>
                                    @if($showMethodCol)
                                        <th>{{ __('message.payment_method') }}</th>
                                    @endif
                                    <th class="text-right">{{ __('message.amount') }}</th>
                                    @if($showCashCols)
                                        <th>{{ __('message.delivery_man') }}</th>
                                        <th>{{ __('message.status') }}</th>
                                        <th>{{ __('message.remark_label') }}</th>
                                        <th class="pds-mt-photo-col">{{ __('message.finish_image') }}</th>
                                        <th class="pds-mt-photo-col">{{ __('message.pending_image') }}</th>
                                        <th class="text-center">{{ __('message.action') }}</th>
                                    @else
                                        <th>{{ __('message.kpay_slip') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $index => $row)
                                    @php $rowMethod = (string) ($row->payment_method ?? $paymentMethod); @endphp
                                    <tr class="pds-money-transfer-row"
                                        data-os-user-id="{{ $row->os_user_id }}"
                                        data-amount-due="{{ $row->amount_due }}"
                                        data-transfer-id="{{ $row->transfer_id ?? '' }}"
                                        data-payment-method="{{ $row->payment_method ?? $paymentMethod }}">
                                        <td class="pds-money-transfer-no">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="pds-money-transfer-os">
                                                <strong class="pds-money-transfer-os__name">{{ $row->name }}</strong>
                                                @if(!empty($row->os_phone))
                                                    <div class="text-muted small">{{ $row->os_phone }}</div>
                                                @endif
                                                @if($rowMethod === 'kpay' && ($row->kpay_name || $row->kpay_no))
                                                    <span class="pds-money-transfer-os__kpay">
                                                        KBZ Pay
                                                        @if($row->kpay_name) — {{ $row->kpay_name }} @endif
                                                        @if($row->kpay_no) · {{ $row->kpay_no }} @endif
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        @if($showMethodCol)
                                            <td>
                                                <span class="pds-mt-method-badge {{ $rowMethod === 'cash' ? 'is-cash' : 'is-kpay' }}">
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
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">{{ $row->cash_payout_status ?? 'unassigned' }}</span>
                                            </td>
                                            <td style="min-width:140px;max-width:220px;">
                                                @php
                                                    $hasPendingRemark = filled($row->pending_note ?? null);
                                                    $hasDoneRemark = filled($row->done_note ?? null);
                                                @endphp
                                                @if($hasPendingRemark)
                                                    <div class="mb-1">
                                                        <span class="badge badge-warning">Pending</span>
                                                        <div class="mt-1" style="white-space:pre-wrap;">{{ $row->pending_note }}</div>
                                                    </div>
                                                @endif
                                                @if($hasDoneRemark)
                                                    <div class="{{ $hasPendingRemark ? 'mt-2' : '' }}">
                                                        <span class="badge badge-success">Done</span>
                                                        <div class="mt-1" style="white-space:pre-wrap;">{{ $row->done_note }}</div>
                                                    </div>
                                                @endif
                                                @if(! $hasPendingRemark && ! $hasDoneRemark)
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="pds-mt-photo-cell">
                                                @if(!empty($row->kpay_slip_url))
                                                    <a href="{{ $row->kpay_slip_url }}" target="_blank" rel="noopener" class="pds-mt-photo" title="{{ __('message.finish_image') }}">
                                                        <img src="{{ $row->kpay_slip_url }}" alt="{{ __('message.finish_image') }}">
                                                    </a>
                                                @else
                                                    <span class="pds-mt-photo-empty" aria-hidden="true"><i class="far fa-image"></i></span>
                                                @endif
                                            </td>
                                            <td class="pds-mt-photo-cell">
                                                @if(!empty($row->pending_photo_url))
                                                    <a href="{{ $row->pending_photo_url }}" target="_blank" rel="noopener" class="pds-mt-photo" title="{{ __('message.pending_image') }}">
                                                        <img src="{{ $row->pending_photo_url }}" alt="{{ __('message.pending_image') }}">
                                                    </a>
                                                @else
                                                    <span class="pds-mt-photo-empty" aria-hidden="true"><i class="far fa-image"></i></span>
                                                @endif
                                            </td>
                                            <td class="text-center pds-money-transfer-actions">
                                                @if($canEdit && !empty($row->transfer_id) && !empty($row->can_switch_to_kpay))
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-primary js-mt-switch"
                                                            data-transfer-id="{{ $row->transfer_id }}"
                                                            data-kpay-name="{{ $row->kpay_name ?? '' }}"
                                                            data-kpay-no="{{ $row->kpay_no ?? '' }}"
                                                            data-method="kpay"
                                                            title="{{ __('message.switch_to_kpay') }}">
                                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                                        {{ __('message.switch_to_kpay') }}
                                                    </button>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            @else
                                            <td><span class="text-muted">—</span></td>
                                            <td><span class="text-muted">—</span></td>
                                            <td><span class="text-muted">—</span></td>
                                            <td class="pds-mt-photo-cell">
                                                @if(!empty($row->kpay_slip_url))
                                                    <a href="{{ $row->kpay_slip_url }}" target="_blank" rel="noopener" class="pds-mt-photo" title="{{ __('message.kpay_slip') }}">
                                                        <img src="{{ $row->kpay_slip_url }}" alt="{{ __('message.kpay_slip') }}">
                                                    </a>
                                                @else
                                                    <span class="pds-mt-photo-empty" aria-hidden="true"><i class="far fa-image"></i></span>
                                                @endif
                                            </td>
                                            <td class="pds-mt-photo-cell">
                                                <span class="pds-mt-photo-empty" aria-hidden="true"><i class="far fa-image"></i></span>
                                            </td>
                                            <td class="text-center pds-money-transfer-actions">
                                                <span class="text-muted">—</span>
                                            </td>
                                            @endif
                                        @else
                                            <td style="min-width:120px;">
                                                @if(!empty($row->kpay_slip_url))
                                                    <a href="{{ $row->kpay_slip_url }}" target="_blank" rel="noopener" title="{{ __('message.kpay_slip') }}">
                                                        <img src="{{ $row->kpay_slip_url }}"
                                                             alt="{{ __('message.kpay_slip') }}"
                                                             style="width:72px;height:72px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;">
                                                    </a>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
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

    <script>
        (function bootMoneyTransfer() {
            if (!window.jQuery) {
                return setTimeout(bootMoneyTransfer, 40);
            }
            var $ = window.jQuery;
            if (window.__pdsMoneyTransferBound) return;
            window.__pdsMoneyTransferBound = true;

            var saveUrl = @json(route('order.money-transfer.upsert'));
            var fillUrl = @json(route('order.money-transfer.fill-kpay'));
            var switchUrl = @json(route('order.money-transfer.switch-method'));
            var csrf = $('meta[name="csrf-token"]').attr('content');
            var fromDate = @json($filterFromDate);
            var toDate = @json($filterToDate);
            var branchId = @json($branchFilter);
            var paymentMethod = @json($paymentMethod ?? 'all');
            var pendingSwitchTransferId = null;
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
                    window.location.href = @json(route('order.money-transfer')) + '?method=kpay'
                        + '&from_date=' + encodeURIComponent(fromDate)
                        + '&to_date=' + encodeURIComponent(toDate)
                        + '&branch_id=' + encodeURIComponent(branchId);
                }).fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        || (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.kpay_slip && xhr.responseJSON.errors.kpay_slip[0])
                        || 'Switch failed';
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
                    window.location.reload();
                }).fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed';
                    if (window.toastr) toastr.error(msg);
                    else alert(msg);
                    $btn.prop('disabled', false);
                });
            });

            if (typeof flatpickr !== 'undefined') {
                document.querySelectorAll('.dispatch-datepicker').forEach(function (el) {
                    if (el._flatpickr) return;
                    flatpickr(el, { dateFormat: 'd-m-Y', allowInput: true });
                });
            }
        })();
    </script>
</x-master-layout>
