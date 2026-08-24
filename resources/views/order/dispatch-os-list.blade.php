<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-to-assign-page pds-dispatch-os-list-page">
        <div class="pds-dispatch-to-assign-screen pds-rider-screen">
            <div class="pds-rider-hero">
                <div class="pds-rider-hero__copy">
                    <div class="pds-rider-hero__eyebrow">
                        <i class="fas fa-store" aria-hidden="true"></i>
                        <span>{{ __('message.order') }}</span>
                    </div>
                    <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                    <p class="pds-rider-hero__subtitle">{{ __('message.os_list_settlement_subtitle') }}</p>
                </div>
                <div class="pds-rider-hero__stat">
                    <span class="pds-rider-hero__stat-value">{{ ($payToOsRows->count() ?? 0) + ($receiveFromOsRows->count() ?? 0) }}</span>
                    <span class="pds-rider-hero__stat-label">{{ __('message.online_shopping') }}</span>
                </div>
            </div>

            <form method="GET" action="{{ route('order.dispatch.os-list') }}" class="pds-rider-toolbar" id="osListFilterForm">
                <div class="pds-rider-toolbar__fields">
                    <div class="pds-dispatch-field pds-dispatch-field-sm pds-rider-toolbar__grow">
                        <label for="os_list_os">{{ __('message.online_shopping') }}</label>
                        <select name="os_id" id="os_list_os" class="pds-dispatch-input pds-dispatch-select">
                            <option value="all" @selected($osFilter === 'all')>{{ __('message.all') }}</option>
                            <option value="0" @selected((string) $osFilter === '0')>{{ __('message.no_os') }}</option>
                            @foreach($osOptions as $option)
                                <option value="{{ $option->id }}" @selected((string) $osFilter === (string) $option->id)>{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="os_list_from_date">{{ __('message.from') }}</label>
                        <input type="text" name="from_date" id="os_list_from_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $filterFromDate }}" autocomplete="off">
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="os_list_to_date">{{ __('message.to') }}</label>
                        <input type="text" name="to_date" id="os_list_to_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $filterToDate }}" autocomplete="off">
                    </div>
                </div>
                <div class="pds-rider-toolbar__aside">
                    <button type="button" class="pds-rider-day-btn" id="osListPrevDay" title="{{ __('message.previous') ?? 'Previous' }}">
                        <i class="fas fa-chevron-left" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="pds-rider-day-btn" id="osListNextDay" title="{{ __('message.next') ?? 'Next' }}">
                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </button>
                    <button type="submit" class="pds-rider-check-btn">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <span>{{ __('message.check') }}</span>
                    </button>
                </div>
            </form>

            @php
                $payToOsRows = $payToOsRows ?? collect();
                $receiveFromOsRows = $receiveFromOsRows ?? collect();
                $defaultTab = request('tab') === 'receive'
                    ? 'receive'
                    : (request('tab') === 'pay'
                        ? 'pay'
                        : ($payToOsRows->isNotEmpty() || $receiveFromOsRows->isEmpty() ? 'pay' : 'receive'));
            @endphp

            <div class="pds-rider-body">
                    @if(!empty($usingOsSettlementDemo))
                        <div class="pds-os-settlement-demo-banner" role="status">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                            <span>{{ __('message.os_settlement_demo_banner') }}</span>
                        </div>
                    @endif
                    <div class="pds-os-settlement-tabs" role="tablist" aria-label="{{ __('message.os_list') }}">
                        <button
                            type="button"
                            class="pds-os-settlement-tab {{ $defaultTab === 'pay' ? 'is-active' : '' }}"
                            data-tab="pay"
                            role="tab"
                            aria-selected="{{ $defaultTab === 'pay' ? 'true' : 'false' }}"
                        >
                            <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            <span>{{ __('message.os_settlement_pay_to_os') }}</span>
                            <em class="js-os-tab-count" data-tab-count="pay">{{ $payToOsRows->count() }}</em>
                        </button>
                        <button
                            type="button"
                            class="pds-os-settlement-tab {{ $defaultTab === 'receive' ? 'is-active' : '' }}"
                            data-tab="receive"
                            role="tab"
                            aria-selected="{{ $defaultTab === 'receive' ? 'true' : 'false' }}"
                        >
                            <i class="fas fa-hand-holding-usd" aria-hidden="true"></i>
                            <span>{{ __('message.os_settlement_receive_from_os') }}</span>
                            <em class="js-os-tab-count" data-tab-count="receive">{{ $receiveFromOsRows->count() }}</em>
                        </button>
                    </div>

                    <div
                        class="pds-os-settlement-panel pds-os-settlement-section {{ $defaultTab === 'pay' ? 'is-active' : '' }}"
                        data-panel="pay"
                        data-section="pay"
                        role="tabpanel"
                    >
                        <div class="pds-os-settlement-bulk-bar" data-section="pay" @if($payToOsRows->isEmpty()) style="display:none" @endif>
                            <div class="pds-os-settlement-bulk-bar__hint">
                                <i class="fas fa-info-circle" aria-hidden="true"></i>
                                <span>{{ __('message.os_settlement_bulk_hint') }}</span>
                            </div>
                            <div class="pds-os-settlement-bulk-bar__actions">
                                <button type="button" class="pds-os-finish-all-btn" id="osSettlementFinishAllPay" disabled>
                                    <i class="fas fa-check-double" aria-hidden="true"></i>
                                    <span>{{ __('message.finished_all') }}</span>
                                </button>
                            </div>
                        </div>
                        <div class="pds-rider-table-shell pds-rider-table-shell--scroll pds-os-settlement-shell">
                            <table class="table pds-rider-list-table pds-os-settlement-table" id="osSettlementPayTable">
                                <thead>
                                    <tr>
                                        <th class="pds-rider-col-no">{{ __('message.no') }}</th>
                                        <th class="pds-os-settlement-col-os">{{ __('message.os_name') }}</th>
                                        <th class="pds-os-settlement-col-amount text-right">{{ __('message.amount') }}</th>
                                        <th class="pds-os-settlement-col-kpay">{{ __('message.kpay_name') }}</th>
                                        <th class="pds-os-settlement-col-kpay">{{ __('message.kpay_no') }}</th>
                                        <th class="pds-os-settlement-col-slip">{{ __('message.kpay_slip') }}</th>
                                        <th class="pds-os-settlement-col-method">{{ __('message.payment_method') }}</th>
                                        <th class="pds-os-settlement-col-preview">{{ __('message.show_slip_completed') }}</th>
                                        <th class="pds-os-settlement-col-action">{{ __('message.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($payToOsRows as $index => $row)
                                        @include('order.partials._dispatch-os-settlement-row', ['row' => $row, 'index' => $index, 'section' => 'pay'])
                                    @empty
                                        <tr class="pds-os-settlement-empty-row">
                                            <td colspan="9" class="pds-os-settlement-empty-cell">
                                                {{ __('message.os_settlement_pay_empty') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div
                        class="pds-os-settlement-panel pds-os-settlement-section {{ $defaultTab === 'receive' ? 'is-active' : '' }}"
                        data-panel="receive"
                        data-section="receive"
                        role="tabpanel"
                    >
                        <div class="pds-os-receive-bulk-upload" id="osReceiveBulkUpload" @if($receiveFromOsRows->isEmpty()) style="display:none" @endif>
                            <div class="pds-os-receive-bulk-upload__preview" id="osReceiveBulkPreview" hidden>
                                <img src="" alt="" id="osReceiveBulkPreviewImg">
                            </div>
                            <div class="pds-os-receive-bulk-upload__copy">
                                <strong>{{ __('message.os_settlement_bulk_proof_title') }}</strong>
                                <span>{{ __('message.os_settlement_bulk_proof_hint') }}</span>
                            </div>
                            <label class="pds-os-receive-bulk-upload__btn" for="osReceiveBulkProofInput">
                                <i class="fas fa-qrcode" aria-hidden="true"></i>
                                <span>{{ __('message.upload_os_settlement_qr') }}</span>
                                <input
                                    type="file"
                                    id="osReceiveBulkProofInput"
                                    accept="image/*"
                                    hidden
                                >
                            </label>
                            <div class="pds-os-receive-bulk-upload__status" id="osReceiveBulkStatus" hidden></div>
                        </div>

                        <div class="pds-os-settlement-bulk-bar pds-os-settlement-bulk-bar--receive" data-section="receive" @if($receiveFromOsRows->isEmpty()) style="display:none" @endif>
                            <div class="pds-os-settlement-bulk-bar__hint">
                                <i class="fas fa-info-circle" aria-hidden="true"></i>
                                <span>{{ __('message.os_settlement_receive_bulk_hint') }}</span>
                            </div>
                            <div class="pds-os-settlement-bulk-bar__actions">
                                <button type="button" class="pds-os-finish-all-btn" id="osSettlementFinishAllReceive" disabled>
                                    <i class="fas fa-check-double" aria-hidden="true"></i>
                                    <span>{{ __('message.finished_all') }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="pds-rider-table-shell pds-rider-table-shell--scroll pds-os-settlement-shell">
                            <table class="table pds-rider-list-table pds-os-settlement-table" id="osSettlementReceiveTable">
                                <thead>
                                    <tr>
                                        <th class="pds-rider-col-no">{{ __('message.no') }}</th>
                                        <th class="pds-os-settlement-col-os">{{ __('message.os_name') }}</th>
                                        <th class="pds-os-settlement-col-amount text-right">{{ __('message.amount') }}</th>
                                        <th class="pds-os-settlement-col-slip">{{ __('message.os_settlement_qr') }}</th>
                                        <th class="pds-os-settlement-col-preview">{{ __('message.show_slip_completed') }}</th>
                                        <th class="pds-os-settlement-col-action">{{ __('message.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($receiveFromOsRows as $index => $row)
                                        @include('order.partials._dispatch-os-settlement-row', ['row' => $row, 'index' => $index, 'section' => 'receive'])
                                    @empty
                                        <tr class="pds-os-settlement-empty-row">
                                            <td colspan="6" class="pds-os-settlement-empty-cell">
                                                {{ __('message.os_settlement_receive_empty') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
            </div>
        </div>
    </div>

    <div id="osSettlementSlipModalHost"></div>

    @section('bottom_script')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script>
            $(document).ready(function () {
                var filterFrom = @json($filterFromDate);
                var filterTo = @json($filterToDate);
                var uploadUrlTemplate = @json(url('dispatch-os-list/__OS__/upload-kpay-slip'));
                var finishUrlTemplate = @json(url('dispatch-os-list/__OS__/finish'));
                var slipPreviewUrlTemplate = @json(url('dispatch-os-list/__OS__/slip-preview'));
                var finishAllUrl = @json(route('order.dispatch.os-finish-all'));
                var csrf = $('meta[name="csrf-token"]').attr('content');

                if (typeof flatpickr !== 'undefined') {
                    flatpickr('.dispatch-datepicker', {
                        dateFormat: 'd-m-Y',
                        allowInput: true,
                    });
                }

                function parseDmy(value) {
                    var parts = String(value || '').split('-');
                    if (parts.length !== 3) return null;
                    var d = parseInt(parts[0], 10);
                    var m = parseInt(parts[1], 10) - 1;
                    var y = parseInt(parts[2], 10);
                    if (!y || m < 0 || !d) return null;
                    return new Date(y, m, d);
                }

                function formatDmy(date) {
                    var d = String(date.getDate()).padStart(2, '0');
                    var m = String(date.getMonth() + 1).padStart(2, '0');
                    var y = date.getFullYear();
                    return d + '-' + m + '-' + y;
                }

                function shiftDay(delta) {
                    var fromEl = document.getElementById('os_list_from_date');
                    var toEl = document.getElementById('os_list_to_date');
                    if (!fromEl || !toEl) return;
                    var from = parseDmy(fromEl.value);
                    var to = parseDmy(toEl.value);
                    if (!from || !to) return;
                    from.setDate(from.getDate() + delta);
                    to.setDate(to.getDate() + delta);
                    fromEl.value = formatDmy(from);
                    toEl.value = formatDmy(to);
                    $('#osListFilterForm').trigger('submit');
                }

                $('#osListPrevDay').on('click', function () { shiftDay(-1); });
                $('#osListNextDay').on('click', function () { shiftDay(1); });

                function switchOsSettlementTab(tab) {
                    tab = tab === 'receive' ? 'receive' : 'pay';
                    $('.pds-os-settlement-tab').each(function () {
                        var isActive = $(this).attr('data-tab') === tab;
                        $(this).toggleClass('is-active', isActive).attr('aria-selected', isActive ? 'true' : 'false');
                    });
                    $('.pds-os-settlement-panel').each(function () {
                        $(this).toggleClass('is-active', $(this).attr('data-panel') === tab);
                    });
                }

                $(document).on('click', '.pds-os-settlement-tab', function () {
                    switchOsSettlementTab($(this).attr('data-tab'));
                });

                function notify(message, status) {
                    status = status || 'error';
                    if (status === 'success') {
                        return;
                    }
                    if (typeof SnackBar === 'function') {
                        SnackBar({ message: message, status: status });
                    }
                }

                function setPayMethodUi($wrap, method) {
                    method = method === 'cash' ? 'cash' : 'kpay';
                    $wrap.attr('data-method', method);
                    $wrap.find('.pds-os-pay-method__btn').each(function () {
                        $(this).toggleClass('is-active', $(this).data('method') === method);
                    });
                }

                function syncRowFinishState($row) {
                    if (String($row.attr('data-is-finished')) === '1') return;
                    if (String($row.attr('data-is-demo')) === '1') {
                        $row.find('.pds-os-finish-btn').addClass('is-disabled').prop('disabled', true);
                        return;
                    }
                    var method = $row.attr('data-payment-method') === 'cash' ? 'cash' : 'kpay';
                    var hasKpay = String($row.attr('data-has-kpay')) === '1';
                    var $btn = $row.find('.pds-os-finish-btn');
                    // Both Kpay and Cash require an uploaded proof image.
                    var canFinish = hasKpay;
                    $row.toggleClass('is-cash-method', method === 'cash');
                    $btn.toggleClass('is-disabled', !canFinish).prop('disabled', !canFinish);
                }

                function updateFinishAllState() {
                    function canFinishRows($rows) {
                        var real = $rows.filter(function () {
                            return String($(this).attr('data-is-demo')) !== '1';
                        });
                        if (! real.length) return false;
                        var ok = true;
                        real.each(function () {
                            if (String($(this).attr('data-has-kpay')) !== '1') {
                                ok = false;
                                return false;
                            }
                        });
                        return ok;
                    }
                    $('#osSettlementFinishAllPay').prop(
                        'disabled',
                        !canFinishRows($('#osSettlementPayTable tbody tr.pds-os-settlement-row[data-is-finished="0"]'))
                    );
                    $('#osSettlementFinishAllReceive').prop(
                        'disabled',
                        !canFinishRows($('#osSettlementReceiveTable tbody tr.pds-os-settlement-row[data-is-finished="0"]'))
                    );
                }

                function setRowKpay($row, url) {
                    $row.attr('data-has-kpay', '1');
                    var $card = $row.find('.pds-os-kpay-upload-card');
                    var alt = $row.attr('data-section') === 'receive' ? 'QR' : 'Proof Image';
                    var $preview = $card.find('.pds-os-kpay-preview');
                    if ($preview.length) {
                        $preview.attr('href', url).find('img').attr('src', url).attr('alt', alt);
                    } else {
                        $card.find('.pds-os-kpay-placeholder').replaceWith(
                            '<a href="' + url + '" target="_blank" rel="noopener" class="pds-os-kpay-preview"'
                            + ' onclick="event.preventDefault(); event.stopPropagation(); window.open(this.href, \'_blank\', \'noopener\');">'
                            + '<img src="' + url + '" alt="' + alt + '" class="pds-os-kpay-thumb"></a>'
                        );
                    }
                    syncRowFinishState($row);
                    updateFinishAllState();
                }

                function uploadProofForRow($row, file) {
                    return new Promise(function (resolve, reject) {
                        var osId = parseInt($row.data('os-id'), 10);
                        if (!osId && osId !== 0) {
                            reject(new Error('missing os'));
                            return;
                        }
                        var formData = new FormData();
                        formData.append('_token', csrf);
                        formData.append('from_date', filterFrom);
                        formData.append('to_date', filterTo);
                        formData.append('kpay_slip', file);
                        $.ajax({
                            url: uploadUrlTemplate.replace('__OS__', osId),
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                            success: function (res) {
                                if (res.kpay_slip_url) {
                                    setRowKpay($row, res.kpay_slip_url);
                                }
                                resolve(res);
                            },
                            error: function (xhr) {
                                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                    ? xhr.responseJSON.message
                                    : @json(__('message.something_went_wrong'));
                                reject(new Error(msg));
                            },
                        });
                    });
                }

                function syncTabCounts() {
                    var payCount = $('#osSettlementPayTable tbody tr.pds-os-settlement-row').length;
                    var receiveCount = $('#osSettlementReceiveTable tbody tr.pds-os-settlement-row').length;
                    $('.js-os-tab-count[data-tab-count="pay"]').text(payCount);
                    $('.js-os-tab-count[data-tab-count="receive"]').text(receiveCount);
                    $('.pds-rider-hero__stat-value').text(payCount + receiveCount);
                }

                function renumberOsSettlementRows($table) {
                    var $dataRows = $table.find('tbody tr.pds-os-settlement-row');
                    $dataRows.each(function (i) {
                        $(this).find('td.pds-rider-col-no').first().text(i + 1);
                    });
                    var $section = $table.closest('.pds-os-settlement-section');
                    var sectionKey = String($section.attr('data-section') || '');
                    var count = $dataRows.length;
                    if (count === 0) {
                        if (sectionKey === 'pay') {
                            $section.find('.pds-os-settlement-bulk-bar[data-section="pay"]').hide();
                        }
                        if (sectionKey === 'receive') {
                            $section.find('#osReceiveBulkUpload, .pds-os-settlement-bulk-bar[data-section="receive"]').hide();
                        }
                        var emptyMsg = sectionKey === 'receive'
                            ? @json(__('message.os_settlement_receive_empty'))
                            : @json(__('message.os_settlement_pay_empty'));
                        var colSpan = sectionKey === 'receive' ? 6 : 9;
                        $table.find('tbody').html(
                            '<tr class="pds-os-settlement-empty-row"><td colspan="' + colSpan + '" class="pds-os-settlement-empty-cell">' + emptyMsg + '</td></tr>'
                        );
                    }
                    syncTabCounts();
                    var total = $('#osSettlementPayTable tbody tr.pds-os-settlement-row').length
                        + $('#osSettlementReceiveTable tbody tr.pds-os-settlement-row').length;
                    if (total === 0) {
                        $('.pds-os-settlement-tabs').remove();
                        var emptyHtml = '<div class="pds-rider-empty">'
                            + '<div class="pds-rider-empty__icon"><i class="fas fa-store"></i></div>'
                            + '<p>' + @json(__('message.os_settlement_no_rows')) + '</p>'
                            + '</div>';
                        $('.pds-rider-body').html(emptyHtml);
                    }
                }

                function markRowFinished($row) {
                    var $table = $row.closest('table');
                    $row.fadeOut(180, function () {
                        $(this).remove();
                        renumberOsSettlementRows($table);
                        updateFinishAllState();
                    });
                }

                $(document).on('click', '.pds-os-pay-method__btn', function () {
                    var method = $(this).data('method') === 'cash' ? 'cash' : 'kpay';
                    var $wrap = $(this).closest('.pds-os-pay-method');
                    setPayMethodUi($wrap, method);
                    if ($wrap.hasClass('js-os-row-pay-method')) {
                        var $row = $wrap.closest('tr');
                        $row.attr('data-payment-method', method);
                        syncRowFinishState($row);
                    }
                    updateFinishAllState();
                });

                $(document).on('change', '.pds-os-kpay-file-input', function () {
                    var input = this;
                    var file = input.files && input.files[0];
                    if (!file) return;
                    var $row = $(input).closest('tr');
                    uploadProofForRow($row, file).then(function () {
                        input.value = '';
                    }).catch(function (err) {
                        notify(err.message || @json(__('message.something_went_wrong')), 'error');
                        input.value = '';
                    });
                });

                $('#osReceiveBulkProofInput').on('change', function () {
                    var input = this;
                    var file = input.files && input.files[0];
                    if (!file) return;

                    var $rows = $('#osSettlementReceiveTable tbody tr.pds-os-settlement-row[data-is-finished="0"]');
                    if (! $rows.length) {
                        input.value = '';
                        return;
                    }

                    var $zone = $('#osReceiveBulkUpload');
                    var $status = $('#osReceiveBulkStatus');
                    var $preview = $('#osReceiveBulkPreview');
                    var $previewImg = $('#osReceiveBulkPreviewImg');
                    var localUrl = URL.createObjectURL(file);
                    $previewImg.attr('src', localUrl);
                    $preview.prop('hidden', false);
                    $zone.addClass('is-uploading');
                    $status.prop('hidden', false).text(@json(__('message.os_settlement_bulk_proof_uploading')));

                    var index = 0;
                    var failed = 0;

                    function next() {
                        if (index >= $rows.length) {
                            $zone.removeClass('is-uploading');
                            input.value = '';
                            if (failed > 0) {
                                $status.text(@json(__('message.os_settlement_bulk_proof_partial')));
                                notify(@json(__('message.os_settlement_bulk_proof_partial')), 'error');
                            } else {
                                $status.text(@json(__('message.os_settlement_bulk_proof_done')));
                            }
                            updateFinishAllState();
                            return;
                        }
                        var $row = $($rows[index]);
                        index += 1;
                        $status.text(
                            @json(__('message.os_settlement_bulk_proof_progress'))
                                .replace(':current', String(index))
                                .replace(':total', String($rows.length))
                        );
                        uploadProofForRow($row, file).then(function () {
                            next();
                        }).catch(function () {
                            failed += 1;
                            next();
                        });
                    }

                    next();
                });

                function finishOneOs(osId, $row, $btn, paymentMethod) {
                    var settlementSide = $row.attr('data-section') === 'receive' ? 'receive' : 'pay';
                    $btn.prop('disabled', true);
                    $.ajax({
                        url: finishUrlTemplate.replace('__OS__', osId),
                        type: 'POST',
                        data: {
                            _token: csrf,
                            from_date: filterFrom,
                            to_date: filterTo,
                            delivery_format: 'table',
                            payment_method: paymentMethod || 'kpay',
                            settlement_side: settlementSide,
                        },
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function (res) {
                            markRowFinished($row);
                            if (res.message) notify(res.message, 'success');
                        },
                        error: function (xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : @json(__('message.something_went_wrong'));
                            notify(msg, 'error');
                            syncRowFinishState($row);
                        },
                    });
                }

                function finishAllOs(items, $btn, section) {
                    var label = $btn.find('span').first();
                    var originalText = label.text();
                    $btn.prop('disabled', true);
                    label.text(@json(__('message.processing')));
                    $.ajax({
                        url: finishAllUrl,
                        type: 'POST',
                        data: {
                            _token: csrf,
                            from_date: filterFrom,
                            to_date: filterTo,
                            items: items,
                            delivery_format: 'table',
                            settlement_side: section,
                        },
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function (res) {
                            var finishedIds = (res.finished_os_ids || []).map(function (id) { return String(id); });
                            var selector = section === 'receive'
                                ? '#osSettlementReceiveTable tbody tr.pds-os-settlement-row[data-is-finished="0"]'
                                : '#osSettlementPayTable tbody tr.pds-os-settlement-row[data-is-finished="0"]';
                            $(selector).each(function () {
                                var $row = $(this);
                                if (!finishedIds.length || finishedIds.indexOf(String($row.data('os-id'))) !== -1) {
                                    markRowFinished($row);
                                }
                            });
                            if (res.message) notify(res.message, 'success');
                            // Reload so badges / empty tabs stay accurate after Finish All.
                            setTimeout(function () { window.location.reload(); }, 400);
                        },
                        error: function (xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : @json(__('message.something_went_wrong'));
                            notify(msg, 'error');
                            label.text(originalText);
                            updateFinishAllState();
                        },
                    });
                }

                $(document).on('click', '.pds-os-finish-btn:not(:disabled)', function () {
                    var $btn = $(this);
                    var $row = $btn.closest('tr');
                    if (String($row.attr('data-is-demo')) === '1' || String($btn.attr('data-is-demo')) === '1') {
                        notify(@json(__('message.os_settlement_demo_action_blocked')), 'error');
                        return;
                    }
                    var osId = $btn.data('os-id');
                    var method = $row.attr('data-section') === 'receive'
                        ? 'cash'
                        : ($row.attr('data-payment-method') === 'cash' ? 'cash' : 'kpay');
                    finishOneOs(osId, $row, $btn, method);
                });

                $(document).on('click', '#osSettlementFinishAllPay', function () {
                    if ($(this).prop('disabled')) return;
                    var items = [];
                    $('#osSettlementPayTable tbody tr.pds-os-settlement-row[data-is-finished="0"]').each(function () {
                        var $row = $(this);
                        if (String($row.attr('data-is-demo')) === '1') return;
                        items.push({
                            os_id: parseInt($row.data('os-id'), 10),
                            payment_method: $row.attr('data-payment-method') === 'cash' ? 'cash' : 'kpay',
                            settlement_side: 'pay',
                        });
                    });
                    if (!items.length) return;
                    finishAllOs(items, $(this), 'pay');
                });

                $(document).on('click', '#osSettlementFinishAllReceive', function () {
                    if ($(this).prop('disabled')) return;
                    var items = [];
                    $('#osSettlementReceiveTable tbody tr.pds-os-settlement-row[data-is-finished="0"]').each(function () {
                        var $row = $(this);
                        if (String($row.attr('data-is-demo')) === '1') return;
                        items.push({
                            os_id: parseInt($row.data('os-id'), 10),
                            payment_method: 'cash',
                            settlement_side: 'receive',
                        });
                    });
                    if (!items.length) return;
                    finishAllOs(items, $(this), 'receive');
                });

                $(document).on('click', '.pds-os-slip-preview-btn', function () {
                    if (String($(this).attr('data-is-demo')) === '1' || $(this).prop('disabled')) {
                        notify(@json(__('message.os_settlement_demo_action_blocked')), 'error');
                        return;
                    }
                    var osId = $(this).data('os-id');
                    var settlementSide = $(this).closest('tr').attr('data-section') === 'receive' ? 'receive' : 'pay';
                    $.ajax({
                        url: slipPreviewUrlTemplate.replace('__OS__', osId),
                        type: 'GET',
                        data: { from_date: filterFrom, to_date: filterTo, settlement_side: settlementSide },
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function (res) {
                            $('#osSettlementSlipModalHost').html(res.html || '');
                            var $modal = $('#osSettlementSlipModalHost #osSlipCompletedModal');
                            if ($modal.length) {
                                $modal.modal('show');
                            }
                        },
                        error: function (xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : @json(__('message.something_went_wrong'));
                            notify(msg, 'error');
                        },
                    });
                });

                $(document).on('click', '#osSlipScreenshotBtn', function (e) {
                    e.preventDefault();
                    var target = document.getElementById('osSlipCompletedCapture');
                    if (!target || typeof html2canvas !== 'function') {
                        notify(@json(__('message.something_went_wrong')), 'error');
                        return;
                    }
                    var $btn = $(this);
                    $btn.prop('disabled', true);
                    html2canvas(target, {
                        backgroundColor: '#ffffff',
                        scale: 2,
                        useCORS: true,
                        allowTaint: true,
                    }).then(function (canvas) {
                        var link = document.createElement('a');
                        link.download = 'slip-completed-' + filterTo + '.png';
                        link.href = canvas.toDataURL('image/png');
                        link.click();
                    }).catch(function () {
                        notify(@json(__('message.something_went_wrong')), 'error');
                    }).finally(function () {
                        $btn.prop('disabled', false);
                    });
                });

                $('.pds-os-settlement-table tbody tr[data-is-finished="0"]').each(function () {
                    syncRowFinishState($(this));
                });
                updateFinishAllState();
            });
        </script>
    @endsection
</x-master-layout>
