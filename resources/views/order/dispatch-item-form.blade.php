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
    $defaultBranchId = resolveDefaultDispatchBranchId($defaultFromBranchName);
    $defaultToBranchId = resolveDefaultDispatchBranchId($defaultToBranchName) ?: $defaultBranchId;
    $mdyBranch = $defaultBranchId ? $branchCities->firstWhere('id', $defaultBranchId) : null;
    $defaultToBranch = $defaultToBranchId ? $branchCities->firstWhere('id', $defaultToBranchId) : $mdyBranch;
    $defaultDeliveryCity = config('dispatch_item_cities.default_delivery_city', 'Mandalay');
    $defaultTownship = config('dispatch_item_cities.default_township', 'ချမ်းမြသာစည်');
    $receivedDate = old('received_date', $isEdit && $item->received_date
        ? formatDispatchYangonDate($item->received_date)
        : formatDispatchYangonDate($order->pickup_datetime ?? $order->created_at ?? now('Asia/Yangon')));
    $fromBranchId = old('from_branch_id', $isEdit
        ? ($item->from_branch_id ?: $defaultBranchId)
        : $defaultBranchId);
    $toBranchId = old('to_branch_id', $isEdit
        ? ($item->to_branch_id ?: $defaultToBranchId)
        : $defaultToBranchId);
    $deliveryCity = old('delivery_city', $isEdit
        ? ($item->delivery_city ?: $defaultDeliveryCity)
        : $defaultDeliveryCity);
    $hasCustomDeliveryCity = $deliveryCity && !collect($deliveryCities)->contains(function ($city) use ($deliveryCity) {
        return strcasecmp((string) ($city['name'] ?? ''), (string) $deliveryCity) === 0
            || strcasecmp((string) ($city['name_mm'] ?? ''), (string) $deliveryCity) === 0;
    });
    $township = old('township', $isEdit
        ? ($item->township ?: $defaultTownship)
        : $defaultTownship);
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
    $photoUrl = $isPhotoItem ? $photoMedia->getUrl() : '';
    $photoName = $isPhotoItem ? ($photoMedia->file_name ?? __('message.photo_order')) : '';
@endphp

<div class="modal-dialog modal-xl pds-dispatch-item-modal-wrap @if($isPhotoItem) is-photo-item-modal @endif" role="document">
    <div class="modal-content pds-dispatch-item-modal @if($isPhotoItem) is-photo-item-modal @endif">
        <form id="dispatch_item_form" method="POST" novalidate data-dispatch-item-form="1"
              data-default-township="{{ $defaultTownship }}"
              data-default-delivery-city="{{ $defaultDeliveryCity }}"
              data-apply-default-township="1"
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
                                             loading="lazy"
                                             draggable="false">
                                    </div>
                                    <div class="pds-photo-carousel-zoom-bar pds-dispatch-item-photo-zoom-bar">
                                        <button type="button" class="pds-photo-carousel-zoom-btn" data-inline-photo-zoom="out" aria-label="Zoom out">
                                            <i class="fas fa-search-minus"></i>
                                        </button>
                                        <span class="pds-photo-carousel-zoom-level" data-inline-photo-zoom-level>100%</span>
                                        <button type="button" class="pds-photo-carousel-zoom-btn" data-inline-photo-zoom="in" aria-label="Zoom in">
                                            <i class="fas fa-search-plus"></i>
                                        </button>
                                        <button type="button" class="pds-photo-carousel-zoom-btn pds-photo-carousel-zoom-btn--reset" data-inline-photo-zoom="reset" aria-label="Reset zoom">
                                            Reset
                                        </button>
                                    </div>
                                    <input type="hidden" name="item_name" id="item_name" value="{{ $itemName ?: $photoName }}">
                                </div>
                            </section>
                        </div>

                        <div class="pds-dispatch-item-col pds-dispatch-item-col--details">
                            @include('order.partials._dispatch-item-delivery-route')

                            @include('order.partials._dispatch-item-payment', ['showRemark' => true, 'disableOsCredit' => $disableOsCredit])
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
                            @include('order.partials._dispatch-item-payment', ['showRemark' => false, 'disableOsCredit' => $disableOsCredit])
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
                    <button type="submit" class="pds-dispatch-btn pds-dispatch-btn-primary">
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
            modalParent: '#remoteModelData'
        };
        var src = "{{ asset('js/dispatch-item-form.js') }}?v=26";

        function bootForm() {
            if (typeof window.initDispatchItemForm === 'function') {
                window.initDispatchItemForm(opts);
            }
            initDispatchItemInlinePhotoZoom();
            $('#remoteModelData').addClass('has-photo-item-modal');
        }

        if (typeof window.initDispatchItemForm === 'function') {
            bootForm();
        } else {
            $.getScript(src, bootForm);
        }

        function initDispatchItemInlinePhotoZoom() {
            var viewport = document.getElementById('pdsItemInlinePhotoViewport');
            var img = document.getElementById('pdsItemInlinePhotoImg');
            if (!viewport || !img) {
                return;
            }

            var zoomLevel = viewport.parentElement.querySelector('[data-inline-photo-zoom-level]');
            var scale = 1;
            var translateX = 0;
            var translateY = 0;
            var minScale = 1;
            var maxScale = 5;
            var isDragging = false;
            var dragStartX = 0;
            var dragStartY = 0;
            var dragOriginX = 0;
            var dragOriginY = 0;
            var activePointers = new Map();

            function clamp(value, min, max) {
                return Math.min(max, Math.max(min, value));
            }

            function updateZoomLabel() {
                if (zoomLevel) {
                    zoomLevel.textContent = Math.round(scale * 100) + '%';
                }
            }

            function applyTransform() {
                img.style.transform = 'translate(calc(-50% + ' + translateX + 'px), calc(-50% + ' + translateY + 'px)) scale(' + scale + ')';
                viewport.classList.toggle('is-zoomed', scale > 1.02);
                updateZoomLabel();
            }

            function resetTransform() {
                scale = 1;
                translateX = 0;
                translateY = 0;
                img.style.transform = 'translate(-50%, -50%) scale(1)';
                viewport.classList.remove('is-zoomed');
                updateZoomLabel();
            }

            function zoomAt(clientX, clientY, nextScale) {
                var rect = viewport.getBoundingClientRect();
                var centerX = rect.left + rect.width / 2;
                var centerY = rect.top + rect.height / 2;
                var offsetX = clientX - centerX;
                var offsetY = clientY - centerY;
                var ratio = nextScale / scale;
                translateX = (translateX - offsetX) * ratio + offsetX;
                translateY = (translateY - offsetY) * ratio + offsetY;
                scale = clamp(nextScale, minScale, maxScale);
                applyTransform();
            }

            viewport.parentElement.querySelectorAll('[data-inline-photo-zoom]').forEach(function (button) {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var action = button.getAttribute('data-inline-photo-zoom');
                    if (action === 'reset') {
                        resetTransform();
                        return;
                    }
                    var rect = viewport.getBoundingClientRect();
                    var delta = action === 'in' ? 0.35 : -0.35;
                    zoomAt(rect.left + rect.width / 2, rect.top + rect.height / 2, scale + delta);
                });
            });

            viewport.addEventListener('wheel', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var delta = event.deltaY < 0 ? 0.15 : -0.15;
                zoomAt(event.clientX, event.clientY, scale + delta);
            }, { passive: false });

            viewport.addEventListener('dblclick', function (event) {
                event.preventDefault();
                if (scale > 1.02) {
                    resetTransform();
                } else {
                    zoomAt(event.clientX, event.clientY, 2.2);
                }
            });

            viewport.addEventListener('pointerdown', function (event) {
                if (scale <= 1.02) return;
                activePointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
                if (activePointers.size === 1) {
                    isDragging = true;
                    dragStartX = event.clientX;
                    dragStartY = event.clientY;
                    dragOriginX = translateX;
                    dragOriginY = translateY;
                    viewport.setPointerCapture(event.pointerId);
                }
            });

            viewport.addEventListener('pointermove', function (event) {
                if (!activePointers.has(event.pointerId)) return;
                activePointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
                if (isDragging && scale > 1.02) {
                    translateX = dragOriginX + (event.clientX - dragStartX);
                    translateY = dragOriginY + (event.clientY - dragStartY);
                    applyTransform();
                }
            });

            function endPointer(event) {
                activePointers.delete(event.pointerId);
                if (activePointers.size === 0) {
                    isDragging = false;
                }
            }

            viewport.addEventListener('pointerup', endPointer);
            viewport.addEventListener('pointercancel', endPointer);
        }

        $(document).off('hidden.bs.modal.pdsPhotoItem').on('hidden.bs.modal.pdsPhotoItem', '#remoteModelData', function () {
            $(this).removeClass('has-photo-item-modal');
        });
    })();
</script>
