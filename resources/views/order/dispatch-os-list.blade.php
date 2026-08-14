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
                    <span class="pds-rider-hero__stat-value">{{ $rows->count() }}</span>
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

            @if($rows->isNotEmpty())
                <div class="pds-os-settlement-bulk-bar">
                    <div class="pds-os-settlement-bulk-bar__hint">
                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                        <span>{{ __('message.os_settlement_bulk_hint') }}</span>
                    </div>
                    <div class="pds-os-settlement-bulk-bar__actions">
                        <div class="pds-os-pay-method pds-os-pay-method--bulk" id="osBulkPayMethod" data-method="kpay" title="{{ __('message.payment_method') }}">
                            <button type="button" class="pds-os-pay-method__btn is-active" data-method="kpay">Kpay</button>
                            <button type="button" class="pds-os-pay-method__btn" data-method="cash">{{ __('message.cash') }}</button>
                        </div>
                        <button type="button" class="pds-os-finish-all-btn" id="osSettlementFinishAll" disabled>
                            <i class="fas fa-check-double" aria-hidden="true"></i>
                            <span>{{ __('message.finished_all') }}</span>
                        </button>
                    </div>
                </div>
            @endif

            <div class="pds-rider-body">
                @if($rows->isEmpty())
                    <div class="pds-rider-empty">
                        <div class="pds-rider-empty__icon"><i class="fas fa-store"></i></div>
                        <p>{{ __('message.os_settlement_no_rows') }}</p>
                    </div>
                @else
                    <div class="pds-rider-table-shell pds-rider-table-shell--scroll pds-os-settlement-shell">
                        <table class="table pds-rider-list-table pds-os-settlement-table" id="osSettlementTable">
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
                                @foreach($rows as $index => $row)
                                    @php
                                        $initial = mb_strtoupper(mb_substr(trim($row->name) ?: 'O', 0, 1));
                                    @endphp
                                    <tr
                                        class="pds-os-settlement-row"
                                        data-os-id="{{ $row->id }}"
                                        data-has-kpay="{{ $row->has_kpay_slip ? '1' : '0' }}"
                                        data-is-finished="0"
                                        data-payment-method="kpay"
                                    >
                                        <td class="pds-rider-col-no">{{ $index + 1 }}</td>
                                        <td class="pds-os-settlement-col-os">
                                            <div class="pds-rider-person">
                                                <span class="pds-rider-avatar pds-os-settlement-avatar" aria-hidden="true">{{ $initial }}</span>
                                                <div class="pds-rider-person__meta">
                                                    <div class="pds-dispatch-rider-list-name">{{ $row->name }}</div>
                                                    @if($row->phone && $row->phone !== '-')
                                                        <div class="pds-dispatch-rider-list-phone">{{ $row->phone }}</div>
                                                    @endif
                                                    <span class="pds-os-settlement-badge">{{ $row->item_count }} {{ __('message.items') }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="pds-os-settlement-col-amount text-right">
                                            <span class="pds-os-amount-pill {{ $row->amount < 0 ? 'is-negative' : '' }}">
                                                {{ number_format($row->amount) }}
                                            </span>
                                        </td>
                                        <td class="pds-os-settlement-col-kpay">
                                            @if($row->kpay_name)
                                                <span class="pds-os-kpay-value">{{ $row->kpay_name }}</span>
                                            @else
                                                <span class="pds-os-kpay-empty">—</span>
                                            @endif
                                        </td>
                                        <td class="pds-os-settlement-col-kpay">
                                            @if($row->kpay_no)
                                                <span class="pds-os-kpay-value pds-os-kpay-value--mono">{{ $row->kpay_no }}</span>
                                            @else
                                                <span class="pds-os-kpay-empty">—</span>
                                            @endif
                                        </td>
                                        <td class="pds-os-settlement-col-slip pds-os-kpay-upload-cell">
                                            <div class="pds-os-kpay-upload-card">
                                                @if($row->kpay_slip_url)
                                                    <a href="{{ $row->kpay_slip_url }}" target="_blank" rel="noopener" class="pds-os-kpay-preview">
                                                        <img src="{{ $row->kpay_slip_url }}" alt="KBZ Pay Slip" class="pds-os-kpay-thumb">
                                                    </a>
                                                @else
                                                    <div class="pds-os-kpay-placeholder">
                                                        <i class="fas fa-image" aria-hidden="true"></i>
                                                        <span>{{ __('message.upload_kpay_slip') }}</span>
                                                    </div>
                                                @endif
                                                <label class="pds-os-kpay-upload-btn" title="{{ __('message.upload_kpay_slip') }}">
                                                    <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                                                    <input
                                                        type="file"
                                                        class="pds-os-kpay-file-input"
                                                        accept="image/*"
                                                        data-os-id="{{ $row->id }}"
                                                    >
                                                </label>
                                            </div>
                                        </td>
                                        <td class="pds-os-settlement-col-method">
                                            <div class="pds-os-pay-method js-os-row-pay-method" data-method="kpay">
                                                <button type="button" class="pds-os-pay-method__btn is-active" data-method="kpay">Kpay</button>
                                                <button type="button" class="pds-os-pay-method__btn" data-method="cash">{{ __('message.cash') }}</button>
                                            </div>
                                        </td>
                                        <td class="pds-os-settlement-col-preview">
                                            <button
                                                type="button"
                                                class="pds-os-slip-preview-btn"
                                                data-os-id="{{ $row->id }}"
                                                title="{{ __('message.show_slip_completed') }}"
                                            >
                                                <i class="fas fa-eye" aria-hidden="true"></i>
                                                <span>{{ __('message.show_slip_completed') }}</span>
                                            </button>
                                        </td>
                                        <td class="pds-os-settlement-col-action">
                                            <button
                                                type="button"
                                                class="pds-os-finish-btn {{ $row->has_kpay_slip ? '' : 'is-disabled' }}"
                                                data-os-id="{{ $row->id }}"
                                                data-has-kpay="{{ $row->has_kpay_slip ? '1' : '0' }}"
                                                @disabled(!$row->has_kpay_slip)
                                            >
                                                <i class="fas fa-check" aria-hidden="true"></i>
                                                <span>{{ __('message.finished') }}</span>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
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
                    var method = $row.attr('data-payment-method') === 'cash' ? 'cash' : 'kpay';
                    var hasKpay = String($row.attr('data-has-kpay')) === '1';
                    var $btn = $row.find('.pds-os-finish-btn');
                    // Both Kpay and Cash require an uploaded proof image.
                    var canFinish = hasKpay;
                    $row.toggleClass('is-cash-method', method === 'cash');
                    $btn.toggleClass('is-disabled', !canFinish).prop('disabled', !canFinish);
                }

                function updateFinishAllState() {
                    var $rows = $('#osSettlementTable tbody tr[data-is-finished="0"]');
                    var canFinish = $rows.length > 0;
                    if (canFinish) {
                        $rows.each(function () {
                            if (String($(this).attr('data-has-kpay')) !== '1') {
                                canFinish = false;
                                return false;
                            }
                        });
                    }
                    $('#osSettlementFinishAll').prop('disabled', !canFinish);
                }

                function setRowKpay($row, url) {
                    $row.attr('data-has-kpay', '1');
                    var $card = $row.find('.pds-os-kpay-upload-card');
                    var $preview = $card.find('.pds-os-kpay-preview');
                    if ($preview.length) {
                        $preview.attr('href', url).find('img').attr('src', url);
                    } else {
                        $card.find('.pds-os-kpay-placeholder').replaceWith(
                            '<a href="' + url + '" target="_blank" rel="noopener" class="pds-os-kpay-preview"><img src="' + url + '" alt="KBZ Pay Slip" class="pds-os-kpay-thumb"></a>'
                        );
                    }
                    syncRowFinishState($row);
                    updateFinishAllState();
                }

                function renumberOsSettlementRows() {
                    $('#osSettlementTable tbody tr').each(function (i) {
                        $(this).find('td.pds-rider-col-no').first().text(i + 1);
                    });
                    var count = $('#osSettlementTable tbody tr').length;
                    $('.pds-rider-hero__stat-value').text(count);
                    if (count === 0) {
                        $('.pds-os-settlement-bulk-bar').addClass('d-none');
                        var emptyHtml = '<div class="pds-rider-empty">'
                            + '<div class="pds-rider-empty__icon"><i class="fas fa-store"></i></div>'
                            + '<p>' + @json(__('message.os_settlement_no_rows')) + '</p>'
                            + '</div>';
                        $('.pds-rider-body').html(emptyHtml);
                    }
                }

                function markRowFinished($row) {
                    $row.fadeOut(180, function () {
                        $(this).remove();
                        renumberOsSettlementRows();
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
                    var osId = $(input).data('os-id');
                    var $row = $(input).closest('tr');
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
                            input.value = '';
                        },
                        error: function (xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : @json(__('message.something_went_wrong'));
                            notify(msg, 'error');
                            input.value = '';
                        },
                    });
                });

                function finishOneOs(osId, $row, $btn, paymentMethod) {
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

                function finishAllOs(osIds, $btn, paymentMethod) {
                    $btn.prop('disabled', true);
                    $.ajax({
                        url: finishAllUrl,
                        type: 'POST',
                        data: {
                            _token: csrf,
                            from_date: filterFrom,
                            to_date: filterTo,
                            os_ids: osIds,
                            delivery_format: 'table',
                            payment_method: paymentMethod || 'kpay',
                        },
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function (res) {
                            $('#osSettlementTable tbody tr[data-is-finished="0"]').each(function () {
                                markRowFinished($(this));
                            });
                            if (res.message) notify(res.message, 'success');
                        },
                        error: function (xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : @json(__('message.something_went_wrong'));
                            notify(msg, 'error');
                            updateFinishAllState();
                        },
                    });
                }

                $(document).on('click', '.pds-os-finish-btn:not(:disabled)', function () {
                    var $btn = $(this);
                    var $row = $btn.closest('tr');
                    var osId = $btn.data('os-id');
                    var method = $row.attr('data-payment-method') === 'cash' ? 'cash' : 'kpay';
                    finishOneOs(osId, $row, $btn, method);
                });

                $('#osSettlementFinishAll').on('click', function () {
                    if ($(this).prop('disabled')) return;
                    var method = $('#osBulkPayMethod').attr('data-method') === 'cash' ? 'cash' : 'kpay';
                    var osIds = [];
                    $('#osSettlementTable tbody tr[data-is-finished="0"]').each(function () {
                        osIds.push(parseInt($(this).data('os-id'), 10));
                    });
                    if (!osIds.length) return;
                    finishAllOs(osIds, $(this), method);
                });

                $(document).on('click', '.pds-os-slip-preview-btn', function () {
                    var osId = $(this).data('os-id');
                    $.ajax({
                        url: slipPreviewUrlTemplate.replace('__OS__', osId),
                        type: 'GET',
                        data: { from_date: filterFrom, to_date: filterTo },
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

                $('#osSettlementTable tbody tr[data-is-finished="0"]').each(function () {
                    syncRowFinishState($(this));
                });
                updateFinishAllState();
            });
        </script>
    @endsection
</x-master-layout>
