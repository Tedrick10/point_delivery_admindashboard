@php
    $isEdit = isset($item);
    $orderId = $order->id;
    $branchCities = $cities;
    $deliveryCities = $deliveryCities ?? config('dispatch_item_cities.cities', []);
    if (!is_array($deliveryCities) || empty($deliveryCities)) {
        $deliveryCities = [
            ['name' => 'Mandalay', 'name_mm' => 'မန္တလေး', 'nrc_state' => 'Mandalay'],
            ['name' => 'Monywa', 'name_mm' => 'မုံရွာ', 'nrc_state' => 'Sagaing'],
            ['name' => 'Myitkyina', 'name_mm' => 'မြစ်ကြီးနား', 'nrc_state' => 'Kachin'],
            ['name' => 'Nay Pyi Taw', 'name_mm' => 'နေပြည်တော်', 'nrc_state' => 'Mandalay'],
            ['name' => 'Pyin Oo Lwin', 'name_mm' => 'ပြင်ဦးလွင်', 'nrc_state' => 'Mandalay'],
            ['name' => 'Sagaing', 'name_mm' => 'စစ်ကိုင်း', 'nrc_state' => 'Sagaing'],
            ['name' => 'Taung Gyi', 'name_mm' => 'တောင်ကြီး', 'nrc_state' => 'Shan'],
            ['name' => 'Yangon', 'name_mm' => 'ရန်ကုန်', 'nrc_state' => 'Yangon'],
        ];
    }
    $defaultFromBranchName = config('dispatch_item_cities.default_from_branch', 'မန္တလေး');
    $defaultToBranchName = config('dispatch_item_cities.default_to_branch', $defaultFromBranchName);
    $defaultBranchId = defaultDestinationBranchId() ?: resolveDefaultDispatchBranchId($defaultFromBranchName);
    $defaultToBranchId = $defaultBranchId ?: resolveDefaultDispatchBranchId($defaultToBranchName);
    $mdyBranch = $defaultBranchId ? $branchCities->firstWhere('id', $defaultBranchId) : null;
    $defaultToBranch = $defaultToBranchId ? $branchCities->firstWhere('id', $defaultToBranchId) : $mdyBranch;
    $panelRoute = defaultDeliveryRouteForBranch((int) ($defaultToBranchId ?: $defaultBranchId));
    $defaultDeliveryCity = $panelRoute['city'] ?: config('dispatch_item_cities.default_delivery_city', 'Mandalay');
    $defaultTownship = $panelRoute['township'] ?: config('dispatch_item_cities.default_township', 'ချမ်းမြသာစည်');
    $mdyPlaceholderCity = (string) config('dispatch_item_cities.default_delivery_city', 'Mandalay');
    $receivedDate = old('received_date', $isEdit && $item->received_date
        ? formatDispatchYangonDate($item->received_date)
        : formatDispatchYangonDate($order->pickup_datetime ?? $order->created_at ?? now('Asia/Yangon')));
    $fromBranchId = old('from_branch_id', $isEdit
        ? ($item->from_branch_id ?: $defaultBranchId)
        : $defaultBranchId);
    $toBranchId = old('to_branch_id', $isEdit
        ? ($item->to_branch_id ?: $defaultToBranchId)
        : $defaultToBranchId);
    $toRoute = defaultDeliveryRouteForBranch((int) $toBranchId ?: (int) $defaultToBranchId);
    $savedCity = $isEdit ? trim((string) ($item->delivery_city ?? '')) : '';
    $savedTownship = $isEdit ? trim((string) ($item->township ?? '')) : '';
    $savedCityIsPlaceholder = $savedCity === ''
        || strcasecmp($savedCity, $mdyPlaceholderCity) === 0
        || $savedCity === 'မန္တလေး';
    $toCityDiffers = $toRoute['city'] !== '' && strcasecmp($toRoute['city'], $mdyPlaceholderCity) !== 0;
    $deliveryCity = old('delivery_city', ($savedCityIsPlaceholder && $toCityDiffers)
        ? $toRoute['city']
        : ($savedCity !== '' ? $savedCity : $defaultDeliveryCity));
    $hasCustomDeliveryCity = $deliveryCity && !collect($deliveryCities)->contains(function ($city) use ($deliveryCity) {
        return strcasecmp((string) ($city['name'] ?? ''), (string) $deliveryCity) === 0
            || strcasecmp((string) ($city['name_mm'] ?? ''), (string) $deliveryCity) === 0;
    });
    $township = old('township', ($savedCityIsPlaceholder && $toCityDiffers)
        ? ($toRoute['township'] ?: $defaultTownship)
        : ($savedTownship !== '' ? $savedTownship : $defaultTownship));
    $itemName = old('item_name', $isEdit ? $item->item_name : '');
    $remark = old('remark', $isEdit ? $item->remark : ($order->description ?? ''));
    $weight = normalizeDispatchItemSize(old('weight', $isEdit ? ($item->weight ?? 0) : 0));
    $advancePaid = old('advance_paid', $isEdit ? $item->advance_paid : 0);
    $osPaid = old('os_paid', $isEdit ? $item->os_paid : 0);
    $itemValue = old('item_value', $isEdit ? $item->item_value : 0);
    $deliAmount = old('deli_amount', $isEdit ? $item->deli_amount : 0);
    $customerPrefill = resolveDispatchItemCustomerPrefill($order, $isEdit ? $item : null, $isEdit);
    $customerPhone = old('customer_phone', $customerPrefill['customer_phone'] ?? '');
    $customerPhone = normalizeContactNumber($customerPhone);
    $customerName = old('customer_name', $customerPrefill['customer_name'] ?? '');
    $customerAddress = old('customer_address', $customerPrefill['customer_address'] ?? '');
    $creditTo = old('credit_to', $isEdit ? $item->credit_to : 'customer');
    $disableOsCredit = false;
    $itemCount = $order->dispatchItems()->where('status', 'collected')->count();
    $photoMedia = ($isEdit && !empty($item->photo_id))
        ? ($item->relationLoaded('photoMedia') ? $item->photoMedia : $item->photoMedia()->first())
        : null;
    $isPhotoItem = $photoMedia !== null;
    $photoUrl = '';
    if ($isPhotoItem) {
        $basePhotoUrl = mediaPublicUrl($photoMedia);
        if ($basePhotoUrl) {
            $photoUrl = $basePhotoUrl.'?v='.(
                ($photoMedia->updated_at?->timestamp)
                ?: (@filemtime((string) $photoMedia->getPath()) ?: time())
            );
        }
    }
    $photoName = $isPhotoItem ? ($photoMedia->file_name ?? __('message.photo_order')) : '';
    $workflow = app(\App\Services\DispatchOrderWorkflowService::class);
    // Prefer live query/DB return_type over flashed old() so a prior Normal save
    // cannot force Delivery edit forms to show locked Deli 0.
    $requestReturnType = request('return_type');
    $returnType = old('return_type');
    if ($requestReturnType !== null && $requestReturnType !== '') {
        $returnType = $requestReturnType;
    } elseif ($returnType === null || $returnType === '') {
        $returnType = $isEdit ? ($item->returnType() ?: null) : null;
    }
    $returnType = in_array($returnType, ['normal', 'delivery'], true) ? $returnType : null;
    $isReturnContext = $isEdit && (
        (string) ($item->status ?? '') === 'return'
        || $returnType !== null
        || (string) request('return_type', '') !== ''
    );
    if ($isReturnContext && $returnType === null && $isEdit) {
        $returnType = $item->returnType();
    }
    $kyoShinAmountLocked = $isEdit && $workflow->isKyoShinAmountLocked($item);
    $kyoShinItemValueLocked = $isEdit && $workflow->isKyoShinItemValueLocked($item);
    $kyoShinDeliAmountLocked = $isEdit && $workflow->isKyoShinDeliAmountLocked($item);
    // Admin Return: keep / show Customer information (not OS contact overwrite).
    if ($isReturnContext && $returnType === 'normal') {
        $deliAmount = old('deli_amount', 0);
    }
@endphp

<div class="modal-dialog modal-xl pds-dispatch-item-modal-wrap @if($isPhotoItem) is-photo-item-modal @endif" role="document">
    <div class="modal-content pds-dispatch-item-modal @if($isPhotoItem) is-photo-item-modal @endif">
        <form id="dispatch_item_form" method="POST" novalidate data-dispatch-item-form="1"
              data-default-township="{{ $defaultTownship }}"
              data-saved-township="{{ $township }}"
              data-default-delivery-city="{{ $defaultDeliveryCity }}"
              data-apply-default-township="0"
              data-disable-os-credit="{{ $disableOsCredit ? '1' : '0' }}"
              action="{{ $isEdit ? route('order.dispatch.item.update', [$orderId, $item->id]) : route('order.dispatch.item.store', $orderId) }}">
            @csrf
            @if($isEdit)
                @method('PATCH')
            @endif

            <div class="pds-dispatch-item-modal-topbar">
                <div class="pds-dispatch-item-modal-heading">
                    <span class="pds-dispatch-items-badge">{{ __('message.order') }} #{{ $orderId }}</span>
                    <h5 class="pds-dispatch-items-heading">{{ __('message.add_or_update_item') }}</h5>
                </div>
                <button type="button" class="pds-dispatch-items-btn-close" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="pds-dispatch-item-modal-body @if($isPhotoItem) is-photo-item-body @endif">
                <div class="pds-dispatch-item-layout @if($isPhotoItem) is-photo-item-layout @endif">
                    @if($isPhotoItem)
                        <div class="pds-dispatch-item-col pds-dispatch-item-col--photo">
                            <section class="pds-dispatch-item-section pds-dispatch-item-section--photo">
                                <header class="pds-dispatch-item-section-head">
                                    <i class="fas fa-image"></i>
                                    <span>{{ __('message.photo_order_images') }}</span>
                                </header>
                                <div class="pds-dispatch-item-section-body pds-dispatch-item-photo-panel">
                                    <div class="pds-dispatch-item-photo-viewport" id="pdsItemInlinePhotoViewport">
                                        <img src="{{ $photoUrl }}"
                                             alt="{{ $photoName }}"
                                             class="pds-dispatch-item-photo-img"
                                             id="pdsItemInlinePhotoImg"
                                             loading="eager"
                                             decoding="async"
                                             draggable="false">
                                    </div>
                                    <div class="pds-photo-carousel-zoom-bar pds-dispatch-item-photo-zoom-bar">
                                        <button type="button" class="pds-photo-carousel-zoom-btn" data-inline-photo-zoom="out" aria-label="Zoom out" title="Zoom out">
                                            <i class="fas fa-search-minus"></i>
                                        </button>
                                        <span class="pds-photo-carousel-zoom-level" data-inline-photo-zoom-level>100%</span>
                                        <button type="button" class="pds-photo-carousel-zoom-btn" data-inline-photo-zoom="in" aria-label="Zoom in" title="Zoom in">
                                            <i class="fas fa-search-plus"></i>
                                        </button>
                                        <button type="button" class="pds-photo-carousel-zoom-btn" data-inline-photo-zoom="rotate-left" aria-label="Rotate left" title="Rotate left">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                        <button type="button" class="pds-photo-carousel-zoom-btn" data-inline-photo-zoom="rotate-right" aria-label="Rotate right" title="Rotate right">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                        <button type="button" class="pds-photo-carousel-zoom-btn pds-photo-carousel-zoom-btn--reset" data-inline-photo-zoom="reset" aria-label="Reset zoom" title="Reset">
                                            Reset
                                        </button>
                                    </div>
                                    <input type="hidden" name="item_name" id="item_name" value="{{ $itemName ?: $photoName }}">
                                    <input type="hidden" name="photo_rotation" id="photo_rotation" value="0">
                                </div>
                            </section>
                        </div>

                        <div class="pds-dispatch-item-col pds-dispatch-item-col--details">
                            @include('order.partials._dispatch-item-delivery-route')

                            @include('order.partials._dispatch-item-payment', [
                                'showRemark' => true,
                                'disableOsCredit' => $disableOsCredit,
                                'isReturnContext' => $isReturnContext,
                                'returnType' => $returnType,
                                'kyoShinAmountLocked' => $kyoShinAmountLocked,
                                'kyoShinItemValueLocked' => $kyoShinItemValueLocked,
                                'kyoShinDeliAmountLocked' => $kyoShinDeliAmountLocked,
                            ])
                        </div>
                    @else
                        <div class="pds-dispatch-item-col">
                            @include('order.partials._dispatch-item-delivery-route')

                            <section class="pds-dispatch-item-section">
                                <header class="pds-dispatch-item-section-head">
                                    <i class="fas fa-box"></i>
                                    <span>{{ __('message.item_details') }}</span>
                                </header>
                                <div class="pds-dispatch-item-section-body">
                                    <div class="pds-dispatch-grid pds-dispatch-grid-2 pds-dispatch-item-grid">
                                        <div class="pds-dispatch-field">
                                            <label for="item_name">{{ __('message.item_name') }}</label>
                                            <textarea name="item_name" id="item_name" class="pds-dispatch-input pds-dispatch-textarea pds-dispatch-item-textarea" rows="4">{{ $itemName }}</textarea>
                                        </div>
                                        <div class="pds-dispatch-field">
                                            <label for="item_remark">{{ __('message.remark_label') }}</label>
                                            <textarea name="remark" id="item_remark" class="pds-dispatch-input pds-dispatch-textarea pds-dispatch-item-textarea" rows="4">{{ $remark }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="pds-dispatch-item-col">
                            @include('order.partials._dispatch-item-payment', [
                                'showRemark' => false,
                                'disableOsCredit' => $disableOsCredit,
                                'isReturnContext' => $isReturnContext,
                                'returnType' => $returnType,
                                'kyoShinAmountLocked' => $kyoShinAmountLocked,
                                'kyoShinItemValueLocked' => $kyoShinItemValueLocked,
                                'kyoShinDeliAmountLocked' => $kyoShinDeliAmountLocked,
                            ])
                        </div>
                    @endif
                </div>
            </div>

            <div class="pds-dispatch-item-modal-footer">
                <div class="pds-dispatch-item-footer-summary">
                    <p class="pds-dispatch-item-summary-line is-os" id="summary_os_to_pay">
                        {{ __('message.os_credit_value') }} = <span>0</span>
                    </p>
                    <p class="pds-dispatch-item-summary-line is-cust" id="summary_cust_get">
                        {{ __('message.customer_receive_value') }} = <span>0</span>
                    </p>
                    <p class="pds-dispatch-item-summary-line is-count" id="summary_item_count">
                        {{ __('message.item_count') }} = <span>{{ number_format($itemCount) }}</span>
                    </p>
                </div>
                <div class="pds-dispatch-item-footer-actions">
                    <button type="button" class="pds-dispatch-btn pds-dispatch-btn-ghost" data-dismiss="modal">{{ __('message.close') }}</button>
                    <button type="submit"
                            id="dispatch_item_save_btn"
                            class="pds-dispatch-btn pds-dispatch-btn-primary"
                            data-dispatch-item-save="1"
                            onclick="return window.__pdsSaveDispatchItem ? window.__pdsSaveDispatchItem(event) : true;">
                        <i class="fas fa-check"></i>
                        {{ $isEdit ? __('message.update') : __('message.save') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var opts = {
            nrcDataUrl: "{{ asset('data/myanmar-nrc.json') }}",
            townshipsUrl: "{{ route('delivery-route-locations.townships') }}",
            citiesStoreUrl: "{{ route('delivery-route-locations.cities.store') }}",
            townshipsStoreUrl: "{{ route('delivery-route-locations.townships.store') }}",
            branchesStoreUrl: "{{ route('delivery-route-locations.branches.store') }}",
            modalParent: '#remoteModelData',
            savedTownship: @json($township)
        };
        var formSrc = "{{ asset('js/dispatch-item-form.js') }}?v=36";
        var zoomSrc = "{{ asset('js/pds-photo-zoom.js') }}?v=3";

        function mountPhotoZoom() {
            if (window.PdsPhotoZoom && typeof window.PdsPhotoZoom.mountInlinePhoto === 'function') {
                window.__pdsInlinePhotoRotation = 0;
                // Fresh modal open — start upright; rotate clicks update hidden field + data attr.
                window.PdsPhotoZoom.mountInlinePhoto(null, { reset: true });
            }
            // Force-reload photo when opened inside a previously-hidden Bootstrap modal.
            // loading=lazy / display:none often leaves a broken image icon.
            var img = document.getElementById('pdsItemInlinePhotoImg');
            if (img) {
                var src = img.getAttribute('src') || '';
                if (src && src.charAt(0) !== '?') {
                    img.setAttribute('loading', 'eager');
                    img.src = src;
                }
            }
        }

        function bootForm() {
            if (typeof window.initDispatchItemForm === 'function') {
                window.initDispatchItemForm(opts);
            }
            mountPhotoZoom();
            $('#remoteModelData').addClass('has-photo-item-modal');
        }

        function loadForm() {
            if (typeof window.initDispatchItemForm === 'function') {
                bootForm();
            } else {
                $.getScript(formSrc, bootForm);
            }
        }

        if (window.PdsPhotoZoom) {
            loadForm();
        } else {
            $.getScript(zoomSrc, loadForm);
        }

        $(document).off('hidden.bs.modal.pdsPhotoItem').on('hidden.bs.modal.pdsPhotoItem', '#remoteModelData', function () {
            $(this).removeClass('has-photo-item-modal');
            window.__pdsInlinePhotoRotation = 0;
        });
    })();
</script>
