    <x-master-layout :assets="$assets ?? []">
        @php
            $isCreatePage = request()->routeIs('order.create');
            $isEdit = isset($id) && isset($data) && request()->routeIs('order.dispatch.edit');
            $hasActiveOrder = isset($id) && isset($data);
            $pickup = $hasActiveOrder ? (is_array($data->pickup_point) ? $data->pickup_point : json_decode($data->pickup_point, true) ?? []) : [];
            $delivery = $hasActiveOrder ? (is_array($data->delivery_point) ? $data->delivery_point : json_decode($data->delivery_point, true) ?? []) : [];
            $orderType = $hasActiveOrder ? resolveDispatchOrderType($data) : ['key' => 'standard', 'label' => '', 'class' => ''];
            $isShopOrder = ($orderType['key'] ?? '') === 'shop';
            $isGateOrder = ($orderType['key'] ?? '') === 'gate';
            $gatePassImages = $gatePassImages ?? [];
            $receivedDate = $hasActiveOrder
                ? \Carbon\Carbon::parse($data->pickup_datetime ?? $data->date ?? $data->created_at)->format('d-m-Y')
                : now()->format('d-m-Y');
            $receivedDate = old('received_date', $receivedDate);
            $osName = old('os_name', $hasActiveOrder ? resolveDispatchOsName($data) : ($pickup['name'] ?? ''));
            $osPhone = old('os_phone', $hasActiveOrder ? resolveDispatchOsPhone($data) : normalizeContactNumber($pickup['contact_number'] ?? ''));
            $osAddress = old('os_address', $hasActiveOrder ? resolveDispatchOsAddress($data) : ($pickup['address'] ?? ''));
            if ($osName === '-') {
                $osName = '';
            }
            if ($osPhone === '-') {
                $osPhone = '';
            }
            if ($osAddress === '-') {
                $osAddress = '';
            }
            $orderCount = old('order_count', 1);
            $remark = old('remark', $hasActiveOrder ? ($data->description ?? '') : '');
            $clientId = old('client_id', $hasActiveOrder ? ($data->client_id ?? '') : '');
            $riderId = old('delivery_man_id', $hasActiveOrder ? ($data->delivery_man_id ?? '') : '');
            $riderName = $hasActiveOrder
                ? (optional($data->delivery_man)->name ?? optional(($pickupRiders ?? collect())->firstWhere('id', (int) $riderId))->name ?? '')
                : '';
        @endphp

        @if($isEdit)
            {!! html()->modelForm($data, 'PATCH', route('order.dispatch.update', $id))->attribute('id', 'dispatch_order_form')->attribute('data-dispatch-mode', 'update')->open() !!}
        @else
            {!! html()->form('POST', route('order.dispatch-store'))->attribute('id', 'dispatch_order_form')->attribute('data-dispatch-mode', $hasActiveOrder ? 'update' : 'create')->open() !!}
        @endif

        <div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-page">
            <div class="pds-dispatch-layout">
                <div class="pds-dispatch-form-card">
                    <input type="hidden" name="order_mode" id="order_mode" value="now">

                    <div class="pds-dispatch-form-header">
                        <div>
                            <h1 class="pds-dispatch-form-title">{{ $pageTitle }}</h1>
                            @if($isCreatePage)
                                <p class="pds-dispatch-form-subtitle">{{ __('message.or_create_a_new_order') }}</p>
                            @endif
                        </div>
                    </div>

                    <div id="dispatch_save_alert" class="pds-dispatch-save-alert" role="alert" hidden></div>

                    @if($hasActiveOrder && ($data->status ?? '') === 'pickup_error')
                        <div class="pds-dispatch-pickup-error-banner mb-3" role="alert">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-exclamation-circle mr-2 mt-1" aria-hidden="true"></i>
                                <div>
                                    <strong>{{ __('message.pickup_error') }}</strong>
                                    <div class="mt-1">{{ $data->reason ?: '-' }}</div>
                                    @if(!empty($data->pickup_error_at))
                                        <div class="small text-muted mt-1">{{ dateAgoFormate($data->pickup_error_at) }}</div>
                                    @endif
                                    @if(!empty($data->pickup_error_choice))
                                        <div class="mt-2 pds-dispatch-pickup-choice-banner">
                                            {{ __('message.user_pickup_error_choice') }} · {{ pickupErrorChoiceLabel($data->pickup_error_choice) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="pds-dispatch-form-body">
                        <section class="pds-dispatch-section">
                            <h2 class="pds-dispatch-section-title">
                                <i class="fas fa-calendar-day" aria-hidden="true"></i>
                                {{ __('message.received_date') }}
                            </h2>
                            <div class="pds-dispatch-grid pds-dispatch-grid-3">
                                <div class="pds-dispatch-field">
                                    <label for="received_date">{{ __('message.received_date') }}</label>
                                    <input type="text" name="received_date" id="received_date" class="pds-dispatch-input dispatch-datepicker"
                                        value="{{ $receivedDate }}" required>
                                </div>
                            </div>
                        </section>

                        @if($isShopOrder || $isGateOrder)
                            @include('order.partials._dispatch_user_details', [
                                'pickup' => $pickup,
                                'delivery' => $delivery,
                                'orderType' => $orderType,
                                'isShopOrder' => $isShopOrder,
                                'isGateOrder' => $isGateOrder,
                                'gatePassImages' => $gatePassImages,
                                'clientId' => $clientId,
                                'order' => $hasActiveOrder ? $data : null,
                                'deliveryRecipients' => $hasActiveOrder ? orderDeliveryRecipients($data) : [],
                                'isSelfOrder' => $hasActiveOrder ? (int) ($data->is_self_order ?? 1) : 1,
                            ])
                        @else
                        <section class="pds-dispatch-section">
                            <div class="pds-dispatch-section-head">
                                <h2 class="pds-dispatch-section-title">
                                    <i class="fas fa-user-circle" aria-hidden="true"></i>
                                    {{ __('message.os_name') }}
                                </h2>
                                <div class="pds-dispatch-panel-actions">
                                    <button type="button" class="pds-dispatch-link-btn" id="openOsSearch">
                                        <i class="fas fa-search"></i> {{ __('message.search') }}
                                    </button>
                                    <a href="{{ route('users.os-account.create') }}" class="pds-dispatch-link-btn pds-dispatch-link-btn-accent loadRemoteModel">
                                        <i class="fas fa-plus"></i> {{ __('message.add') }}
                                    </a>
                                </div>
                            </div>

                            <div class="pds-dispatch-grid pds-dispatch-grid-2">
                                <div class="pds-dispatch-field">
                                    <label for="client_id">{{ __('message.os_name') }}</label>
                                    <select name="client_id" id="client_id" class="pds-dispatch-input pds-dispatch-select" required>
                                        @if($clientId)
                                            <option value="{{ $clientId }}" selected>{{ $osName }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="pds-dispatch-field">
                                    <label for="os_name">{{ __('message.os_name') }}</label>
                                    <input type="text" name="os_name" id="os_name" class="pds-dispatch-input" value="{{ $osName }}" required>
                                </div>
                                <div class="pds-dispatch-field">
                                    <label for="os_phone">{{ __('message.os_phone') }}</label>
                                    <input type="text" name="os_phone" id="os_phone" class="pds-dispatch-input" value="{{ $osPhone }}" required>
                                </div>
                                <div class="pds-dispatch-field">
                                    <label for="os_address">{{ __('message.os_address') }}</label>
                                    <input type="text" name="os_address" id="os_address" class="pds-dispatch-input" value="{{ $osAddress }}" required>
                                </div>
                            </div>
                        </section>
                        @endif

                        <section class="pds-dispatch-section">
                            <h2 class="pds-dispatch-section-title">
                                <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                                {{ __('message.order') }}
                            </h2>
                            <div class="pds-dispatch-grid pds-dispatch-grid-2">
                                <div class="pds-dispatch-field">
                                    <label for="order_count">{{ __('message.order_count') }}</label>
                                    <input type="number" name="order_count" id="order_count" class="pds-dispatch-input" min="1" value="{{ $orderCount }}" required>
                                </div>
                                <div class="pds-dispatch-field">
                                    <label for="delivery_man_id">{{ __('message.pickup_rider') }} <span class="pds-required-mark">*</span></label>
                                    <select name="delivery_man_id" id="delivery_man_id" class="pds-dispatch-input pds-dispatch-select"
                                            data-order-id="{{ $hasActiveOrder ? $id : '' }}"
                                            data-current="{{ $riderId }}" required>
                                        <option value=""></option>
                                        @foreach(($pickupRiders ?? collect()) as $rider)
                                            @php
                                                $riderPhone = method_exists($rider, 'riderAssignedPhone')
                                                    ? ($rider->riderAssignedPhone() ?: '')
                                                    : '';
                                                $riderLabel = trim($rider->name . ($riderPhone !== '' ? ' · ' . $riderPhone : ''));
                                            @endphp
                                            <option value="{{ $rider->id }}" @selected((string) $riderId === (string) $rider->id)>{{ $riderLabel }}</option>
                                        @endforeach
                                        @if($riderId && !($pickupRiders ?? collect())->contains('id', (int) $riderId))
                                            <option value="{{ $riderId }}" selected>{{ $riderName ?: $riderId }}</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="pds-dispatch-field pds-dispatch-field--full">
                                <label for="remark">{{ __('message.remark_label') }}</label>
                                <textarea name="remark" id="remark" class="pds-dispatch-input pds-dispatch-textarea" rows="3" placeholder="{{ __('message.remark_label') }}">{{ $remark }}</textarea>
                            </div>
                        </section>
                    </div>

                    <div class="pds-dispatch-form-actions">
                        <div class="pds-dispatch-form-actions-group">
                            @if($isCreatePage)
                                <button type="button" class="pds-dispatch-btn pds-dispatch-btn-ghost" id="newOrderBtn">{{ __('message.new_order') }}</button>
                            @endif

                            <div id="dispatchPostSaveActions" class="pds-dispatch-post-save-actions" @if($hasActiveOrder) style="display:flex" @else hidden @endif>
                                <a href="{{ $hasActiveOrder ? route('order.dispatch.items', ['id' => $id, 'from' => 'dispatch']) : '#' }}"
                                id="dispatchOrderItemsBtn"
                                class="pds-dispatch-post-save-icon"
                                title="{{ __('message.order_detail_list') }}">
                                    <i class="fas fa-clipboard-list"></i>
                                </a>
                                <a href="{{ $hasActiveOrder ? route('order.dispatch.items', ['id' => $id, 'from' => 'dispatch']) : '#' }}"
                                id="dispatchOrderItemsBtnAlt"
                                class="pds-dispatch-post-save-icon"
                                title="{{ __('message.order_detail_list') }}">
                                    <i class="fas fa-comment-medical"></i>
                                </a>
                            </div>

                            <button type="submit" class="pds-dispatch-btn pds-dispatch-btn-primary" id="dispatchSaveBtn">
                                <span class="pds-dispatch-btn-label">{{ $hasActiveOrder ? __('message.update') : __('message.save') }}</span>
                                <span class="pds-dispatch-btn-spinner" hidden>
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <span id="dispatchSaveBtnSpinnerText">{{ $hasActiveOrder ? __('message.updating') : __('message.saving') }}</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {!! html()->form()->close() !!}

        @include('order.partials._os_search_modal')

        @section('bottom_script')
        <script src="{{ asset('js/dispatch-os-fields.js') }}?v=4"></script>
        <script>
            $(document).ready(function () {
                if ($.fn.magnificPopup && $('.pds-gate-pass-showcase').length) {
                    $('.pds-gate-pass-showcase').magnificPopup({
                        delegate: 'a.pds-dispatch-gate-photo-lightbox',
                        type: 'image',
                        mainClass: 'mfp-with-zoom pds-dispatch-item-photo-lightbox',
                        closeOnContentClick: true,
                        closeOnBgClick: true,
                        showCloseBtn: false,
                        gallery: {
                            enabled: true,
                            navigateByImgClick: false,
                            preload: [0, 1],
                        },
                        zoom: {
                            enabled: true,
                            duration: 300,
                        },
                    });
                }

                var osSearchRoute = "{{ route('ajax-list', ['type' => 'os_dispatch_search']) }}";
                var dispatchConfig = {
                    storeUrl: @json(route('order.dispatch-store')),
                    createUrl: @json(route('order.create')),
                    updateUrlTemplate: @json(route('order.dispatch.update', ['id' => 'ORDER_ID'])),
                    assignPickupRiderUrlTemplate: @json(route('order.dispatch.assign-pickup-rider', ['id' => 'ORDER_ID'])),
                    itemsUrlTemplate: @json(route('order.dispatch.items', ['id' => 'ORDER_ID'])),
                    mode: @json($hasActiveOrder ? 'update' : 'create'),
                    orderId: @json($hasActiveOrder ? $id : null),
                    labels: {
                        save: @json(__('message.save')),
                        update: @json(__('message.update')),
                        saving: @json(__('message.saving')),
                        updating: @json(__('message.updating')),
                        success: @json(__('message.success')),
                        close: @json(__('message.close'))
                    }
                };

                function buildDispatchUrl(template, orderId) {
                    return String(template).replace('ORDER_ID', orderId);
                }

                function setDispatchSaveButtonLabel(label) {
                    $('#dispatchSaveBtn .pds-dispatch-btn-label').text(label);
                }

                function setDispatchSaveSpinnerText(text) {
                    $('#dispatchSaveBtnSpinnerText').text(text);
                }

                function showDispatchSuccessPopup(message, orderId) {
                    var detail = message + (orderId ? ' #' + orderId : '');
                    if (typeof window.Swal !== 'undefined') {
                        window.Swal.fire({
                            icon: 'success',
                            title: dispatchConfig.labels.success,
                            text: detail,
                            confirmButtonText: dispatchConfig.labels.close,
                            confirmButtonColor: '#FE6F07'
                        });
                        return;
                    }
                    dispatchToast(detail, false);
                }

                function showDispatchPostSaveActions(orderId) {
                    var itemsUrl = buildDispatchUrl(dispatchConfig.itemsUrlTemplate, orderId) + '?from=dispatch';
                    $('#dispatchPostSaveActions').removeAttr('hidden').css('display', 'flex');
                    $('#dispatchOrderItemsBtn, #dispatchOrderItemsBtnAlt').attr('href', itemsUrl);
                }

                function hideDispatchPostSaveActions() {
                    $('#dispatchPostSaveActions').attr('hidden', true).css('display', '');
                    $('#dispatchOrderItemsBtn, #dispatchOrderItemsBtnAlt').attr('href', '#');
                }

                function activateDispatchUpdateMode(orderId) {
                    var $form = $('#dispatch_order_form');
                    $form.attr('action', buildDispatchUrl(dispatchConfig.updateUrlTemplate, orderId));
                    $form.attr('data-dispatch-mode', 'update');

                    if (!$form.find('input[name="_method"]').length) {
                        $form.append('<input type="hidden" name="_method" value="PATCH">');
                    }

                    dispatchConfig.mode = 'update';
                    dispatchConfig.orderId = orderId;

                    $('#delivery_man_id').attr('data-order-id', String(orderId));
                    setSavedPickupRiderId(getSavedPickupRiderId());

                    setDispatchSaveButtonLabel(dispatchConfig.labels.update);
                    setDispatchSaveSpinnerText(dispatchConfig.labels.updating);
                    showDispatchPostSaveActions(orderId);

                    if (window.history.replaceState && dispatchConfig.createUrl && window.location.pathname.indexOf('/order/create') !== -1) {
                        var resumeUrl = new URL(dispatchConfig.createUrl, window.location.origin);
                        resumeUrl.searchParams.set('order_id', orderId);
                        window.history.replaceState({}, '', resumeUrl.toString());
                    }
                }

                function enterDispatchUpdateMode(orderId, message) {
                    activateDispatchUpdateMode(orderId);
                    if (message) {
                        showDispatchSuccessPopup(message, orderId);
                    }
                }

                function exitDispatchCreateMode() {
                    var $form = $('#dispatch_order_form');
                    $form.attr('action', dispatchConfig.storeUrl);
                    $form.attr('data-dispatch-mode', 'create');
                    $form.find('input[name="_method"]').remove();

                    dispatchConfig.mode = 'create';
                    dispatchConfig.orderId = null;

                    setDispatchSaveButtonLabel(dispatchConfig.labels.save);
                    setDispatchSaveSpinnerText(dispatchConfig.labels.saving);
                    hideDispatchPostSaveActions();
                }

                function dispatchToast(message, isError) {
                    if (typeof window.iziToast !== 'undefined') {
                        if (isError) {
                            window.iziToast.error({
                                title: '{{ __('message.error') }}',
                                message: message,
                                position: 'topRight',
                                timeout: 5000,
                                progressBar: true,
                                close: true
                            });
                        } else {
                            window.iziToast.success({
                                title: '{{ __('message.success') }}',
                                message: message,
                                position: 'topRight',
                                timeout: 5000,
                                progressBar: true,
                                close: true
                            });
                        }
                        return;
                    }
                    if (typeof window.Snackbar !== 'undefined') {
                        window.Snackbar.show({
                            text: message,
                            pos: 'top-center',
                            backgroundColor: isError ? '#dc3545' : '#16a34a',
                            textColor: '#ffffff',
                            showAction: false,
                            duration: 4000
                        });
                        return;
                    }
                    if (isError && typeof window.errorMessage === 'function') {
                        window.errorMessage(message);
                        return;
                    }
                    if (typeof window.showMessage === 'function') {
                        window.showMessage(message);
                    }
                }

                var saveAlertTimer = null;

                function hideDispatchSaveAlert() {
                    var $alert = $('#dispatch_save_alert');
                    $alert.attr('hidden', true).removeClass('is-success is-error is-visible').empty();
                    $('.pds-dispatch-form-card').removeClass('is-save-success');
                }

                function showDispatchSaveAlert(message, isError, orderId) {
                    var $alert = $('#dispatch_save_alert');
                    var $card = $('.pds-dispatch-form-card');
                    var icon = isError ? 'fa-circle-exclamation' : 'fa-circle-check';
                    var title = isError ? '{{ __('message.error') }}' : '{{ __('message.order_saved_title') }}';
                    var detail = message;
                    if (!isError && orderId) {
                        detail = message + ' (#' + orderId + ')';
                    }

                    $alert
                        .removeClass('is-success is-error')
                        .addClass(isError ? 'is-error' : 'is-success')
                        .html(
                            '<div class="pds-dispatch-save-alert-icon"><i class="fas ' + icon + '"></i></div>' +
                            '<div class="pds-dispatch-save-alert-body">' +
                                '<strong class="pds-dispatch-save-alert-title">' + title + '</strong>' +
                                '<span class="pds-dispatch-save-alert-text">' + detail + '</span>' +
                            '</div>' +
                            '<button type="button" class="pds-dispatch-save-alert-close" aria-label="Close"><i class="fas fa-times"></i></button>'
                        )
                        .removeAttr('hidden')
                        .addClass('is-visible');

                    if (!isError) {
                        $card.addClass('is-save-success');
                    } else {
                        $card.removeClass('is-save-success');
                    }

                    window.clearTimeout(saveAlertTimer);
                    saveAlertTimer = window.setTimeout(hideDispatchSaveAlert, isError ? 8000 : 6000);

                    var alertTop = $alert.offset().top - 24;
                    $('html, body').animate({ scrollTop: Math.max(0, alertTop) }, 280);
                }

                $(document).on('click', '.pds-dispatch-save-alert-close', hideDispatchSaveAlert);

                function setDispatchSaveLoading(isLoading) {
                    var $btn = $('#dispatchSaveBtn');
                    if (!$btn.length) {
                        return;
                    }
                    $btn.prop('disabled', isLoading).toggleClass('is-saving', isLoading);
                    $btn.find('.pds-dispatch-btn-label').prop('hidden', isLoading);
                    $btn.find('.pds-dispatch-btn-spinner').prop('hidden', !isLoading);
                }

                function getDispatchOrderId() {
                    var fromConfig = dispatchConfig.orderId ? String(dispatchConfig.orderId) : '';
                    if (fromConfig) {
                        return fromConfig;
                    }
                    return String($('#delivery_man_id').attr('data-order-id') || '');
                }

                function getSavedPickupRiderId() {
                    return String($('#delivery_man_id').attr('data-current') || $('#delivery_man_id').val() || '');
                }

                function setSavedPickupRiderId(riderId) {
                    var value = String(riderId || '');
                    $('#delivery_man_id').attr('data-current', value);
                    $('#delivery_man_id').data('savedRider', value);
                }

                function persistPickupRiderSelection(riderId) {
                    var orderId = getDispatchOrderId();
                    var next = String(riderId || '');
                    var previous = getSavedPickupRiderId();

                    if (!next || next === previous) {
                        return;
                    }

                    if (dispatchConfig.mode !== 'update' || !orderId) {
                        setSavedPickupRiderId(next);
                        return;
                    }

                    $.ajax({
                        url: buildDispatchUrl(dispatchConfig.assignPickupRiderUrlTemplate, orderId),
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            _token: @json(csrf_token()),
                            delivery_man_id: next
                        },
                        success: function (res) {
                            setSavedPickupRiderId(next);
                            if (res && res.message) {
                                dispatchToast(res.message, false);
                            }
                        },
                        error: function (xhr) {
                            $('#delivery_man_id').val(previous || null).trigger('change.select2');
                            setSavedPickupRiderId(previous || '');
                            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                ? xhr.responseJSON.message
                                : @json(__('message.something_went_wrong'));
                            dispatchToast(msg, true);
                        }
                    });
                }

                function initDispatchPickupRiderField() {
                    var $el = $('#delivery_man_id');
                    var placeholder = @json(__('message.select_name', ['select' => __('message.pickup_rider')]));
                    var currentId = getSavedPickupRiderId();

                    $el.select2({
                        width: '100%',
                        placeholder: placeholder,
                        allowClear: false,
                        dropdownParent: $(document.body),
                        minimumResultsForSearch: 0
                    });

                    $el.on('select2:open.dispatchPickupRider', function () {
                        $('.select2-dropdown').last().addClass('pds-dispatch-pickup-rider-dropdown');
                    });

                    $el.on('select2:close.dispatchPickupRider', function () {
                        $('.select2-dropdown.pds-dispatch-pickup-rider-dropdown').removeClass('pds-dispatch-pickup-rider-dropdown');
                    });

                    if (currentId) {
                        $el.val(currentId).trigger('change.select2');
                    }

                    setSavedPickupRiderId(currentId);

                    $el.off('select2:select.dispatchPickupRider').on('select2:select.dispatchPickupRider', function (event) {
                        var riderId = String((event.params && event.params.data && event.params.data.id) || $el.val() || '');
                        persistPickupRiderSelection(riderId);
                    });
                }

                window.initDispatchOsFields({
                    osSearchRoute: osSearchRoute,
                    placeholder: "{{ __('message.select_name', ['select' => __('message.os_name')]) }}"
                });

                initDispatchPickupRiderField();

                if ($.fn.datepicker) {
                    $('.dispatch-datepicker').each(function () {
                        var $input = $(this);
                        $input.datepicker({
                            dateFormat: 'dd-mm-yy',
                            changeMonth: true,
                            changeYear: true
                        });
                        if ($input.val()) {
                            $input.datepicker('setDate', $input.val());
                        }
                    });
                }

                function renderOsRows(rows) {
                    var $body = $('#osSearchTableBody').empty();
                    if (!rows.length) {
                        $body.append('<tr class="pds-dispatch-empty-row"><td colspan="3">{{ __('message.no_record_found') }}</td></tr>');
                        return;
                    }
                    rows.forEach(function (row, idx) {
                        $('<tr class="pds-dispatch-table-row"></tr>')
                            .append('<td>' + (idx + 1) + '</td><td>' + (row.name || row.text || '') + '</td><td>' + (row.phone || '') + '</td>')
                            .data('item', row).appendTo($body);
                    });
                }

                function openOsSearchModal() {
                    $('#os_modal_search').val('');
                    if (typeof window.refreshOsClientCache === 'function') {
                        window.refreshOsClientCache(function () {
                            renderOsRows(window.getOsClientList ? window.getOsClientList() : []);
                        });
                    } else {
                        $.get(osSearchRoute, { list_all: 1 }, function (res) {
                            renderOsRows(res.results || []);
                        });
                    }
                    $('#osSearchModal').modal('show');
                }

                $('#openOsSearch').on('click', openOsSearchModal);

                var osSearchTimer;
                $('#os_modal_search').on('input', function () {
                    clearTimeout(osSearchTimer);
                    var term = $(this).val();
                    osSearchTimer = setTimeout(function () {
                        if (typeof window.getOsClientList === 'function') {
                            renderOsRows(window.getOsClientList(term));
                            return;
                        }
                        $.get(osSearchRoute, { q: term }, function (res) { renderOsRows(res.results || []); });
                    }, 200);
                });

                $(document).on('click', '#osSearchTableBody .pds-dispatch-table-row', function () {
                    var item = $(this).data('item');
                    if (item) {
                        window.fillDispatchOsFields(item);
                        $('#osSearchModal').modal('hide');
                    }
                });

                function hasUnsavedDispatchData() {
                    return !!(
                        $('#client_id').val() ||
                        $.trim($('#os_name').val()) ||
                        $.trim($('#os_phone').val()) ||
                        $.trim($('#os_address').val()) ||
                        $.trim($('#remark').val())
                    );
                }

                function submitDispatchForm(options) {
                    options = options || {};
                    var $form = $('#dispatch_order_form');

                    if (!$('#delivery_man_id').val()) {
                        var riderMsg = @json(__('message.select_name', ['select' => __('message.pickup_rider')]));
                        showDispatchSaveAlert(riderMsg, true);
                        dispatchToast(riderMsg, true);
                        return;
                    }

                    hideDispatchSaveAlert();
                    setDispatchSaveLoading(true);

                    $.ajax({
                        url: $form.attr('action'),
                        method: 'POST',
                        data: $form.serialize(),
                        dataType: 'json',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        success: function (res) {
                            if (res && res.status) {
                                var msg = res.message || @json(__('message.save_form', ['form' => __('message.order')]));
                                var orderId = res.order_id || dispatchConfig.orderId || null;

                                if (typeof options.onSuccess === 'function') {
                                    options.onSuccess(res, orderId, msg);
                                    return;
                                }

                                showDispatchSaveAlert(msg, false, orderId);

                                if (dispatchConfig.mode === 'create') {
                                    enterDispatchUpdateMode(orderId, msg);
                                } else {
                                    showDispatchSuccessPopup(msg, orderId);
                                    dispatchToast(msg, false);
                                }
                            } else {
                                var failMsg = (res && res.message) ? res.message : 'Error';
                                showDispatchSaveAlert(failMsg, true);
                                dispatchToast(failMsg, true);
                            }
                        },
                        error: function (xhr) {
                            var msg = 'Error';
                            if (xhr.responseJSON) {
                                if (xhr.responseJSON.message) {
                                    msg = xhr.responseJSON.message;
                                } else if (xhr.responseJSON.errors) {
                                    msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                                }
                            }
                            showDispatchSaveAlert(msg, true);
                            dispatchToast(msg, true);
                        },
                        complete: function () {
                            setDispatchSaveLoading(false);
                        }
                    });
                }

                function resetDispatchForm() {
                    hideDispatchSaveAlert();
                    if (dispatchConfig.orderId || window.location.search.indexOf('order_id=') !== -1) {
                        window.location.href = dispatchConfig.createUrl;
                        return;
                    }
                    exitDispatchCreateMode();
                    document.getElementById('dispatch_order_form').reset();
                    $('#client_id').empty().val(null).trigger('change');
                    $('#delivery_man_id').val(null).trigger('change');
                    $('#os_name, #os_phone, #os_address, #remark').val('');
                    $('#received_date').val("{{ now()->format('d-m-Y') }}");
                    $('#order_count').val(1);
                    $('#order_mode').val('now');
                }

                $('#newOrderBtn').on('click', function () {
                    if (dispatchConfig.mode === 'create' && hasUnsavedDispatchData()) {
                        var form = document.getElementById('dispatch_order_form');
                        if (form && !form.reportValidity()) {
                            return;
                        }

                        submitDispatchForm({
                            onSuccess: function (res, orderId, msg) {
                                var detail = msg + (orderId ? ' #' + orderId : '');
                                dispatchToast(detail, false);
                                window.setTimeout(function () {
                                    window.location.href = dispatchConfig.createUrl;
                                }, 350);
                            }
                        });
                        return;
                    }

                    resetDispatchForm();
                });

                $('#dispatch_order_form').on('submit', function (e) {
                    e.preventDefault();
                    submitDispatchForm();
                });

                @if($isCreatePage && $hasActiveOrder)
                activateDispatchUpdateMode(@json($id));
                @endif
            });
        </script>
        @endsection
    </x-master-layout>
