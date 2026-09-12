@php
    $orderId = (int) $order->id;
    $riderName = optional($order->delivery_man)->name ?: '-';
    $itemCount = $items->count();
@endphp
<div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable pds-admin-rider-done-wrap" role="document">
    <div class="modal-content pds-admin-rider-done-modal">
        <form id="admin_mark_rider_done_form"
              method="POST"
              enctype="multipart/form-data"
              action="{{ route('order.dispatch.mark-rider-done', $orderId) }}"
              data-admin-rider-done-form="1">
            @csrf

            <div class="pds-ard-header">
                <div class="pds-ard-header__main">
                    <div class="pds-ard-header__chips">
                        <span class="pds-ard-chip pds-ard-chip--order">#{{ $orderId }}</span>
                        @if($itemCount > 0)
                            <span class="pds-ard-chip pds-ard-chip--muted">{{ $itemCount }} item{{ $itemCount > 1 ? 's' : '' }}</span>
                        @endif
                    </div>
                    <h5 class="pds-ard-header__title">{{ __('message.admin_mark_rider_done_title') }}</h5>
                    <div class="pds-ard-header__rider">
                        <span class="pds-ard-header__rider-icon" aria-hidden="true"><i class="fas fa-motorcycle"></i></span>
                        <span class="pds-ard-header__rider-label">Pickup Rider</span>
                        <strong>{{ $riderName }}</strong>
                    </div>
                </div>
                <button type="button" class="pds-ard-close" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="pds-ard-body">
                @if(!$isDispatchPickup || $items->isEmpty())
                    <p class="pds-ard-empty">{{ __('message.admin_mark_rider_done_confirm') }}</p>
                @else
                    @foreach($items as $index => $item)
                        @php
                            $itemId = (int) $item->id;
                            $weight = normalizeDispatchItemSize($item->weight ?? 0);
                            $deliAmount = (float) ($item->deli_amount ?? 0);
                            $itemValue = (float) ($item->item_value ?? 0);
                            $payMode = $pickupService->inferPickupPayMode($item);
                            $needsPhoto = $pickupService->itemRequiresPickupPhoto($order, $item);
                            $photoMedia = $item->photoMedia;
                            $photoUrl = ($photoMedia && getFileExistsCheck($photoMedia)) ? mediaAbsoluteUrl($photoMedia) : '';
                            $customerName = trim((string) ($item->customer_name ?? ''));
                            $customerPhone = trim((string) ($item->customer_phone ?? ''));
                            $customerAddress = trim((string) ($item->customer_address ?? ''));
                            $itemName = trim((string) ($item->item_name ?? ''));
                            $isExtra = ($item->remark ?? '') === 'rider_added';
                            $hasMeta = $customerName !== '' || $customerPhone !== '' || $customerAddress !== '' || ($itemName !== '' && !$photoUrl);
                        @endphp
                        <section class="pds-ard-card"
                                 data-item-id="{{ $itemId }}"
                                 data-prev-size="{{ $weight }}"
                                 data-needs-photo="{{ $needsPhoto ? '1' : '0' }}">
                            <header class="pds-ard-card__head">
                                <div class="pds-ard-card__title-wrap">
                                    <span class="pds-ard-card__index">{{ $index + 1 }}</span>
                                    <div>
                                        <h6 class="pds-ard-card__title">
                                            Item {{ $index + 1 }}
                                            @if($isExtra)
                                                <em class="pds-ard-card__extra">{{ __('message.admin_rider_done_extra') }}</em>
                                            @endif
                                        </h6>
                                        @if($itemName !== '')
                                            <p class="pds-ard-card__subtitle">{{ $itemName }}</p>
                                        @endif
                                    </div>
                                </div>
                                <span class="pds-ard-card__size @if($weight <= 0) is-empty @endif" data-size-tag @if($weight <= 0) hidden @endif>
                                    {{ $weight > 0 ? 'Size('.$weight.')' : '' }}
                                </span>
                            </header>

                            @if($hasMeta || $photoUrl)
                                <div class="pds-ard-info @if($photoUrl) has-photo @endif">
                                    @if($photoUrl)
                                        <div class="pds-ard-info__photo">
                                            <img src="{{ $photoUrl }}" alt="{{ $itemName ?: ('Item '.$itemId) }}" loading="lazy">
                                        </div>
                                    @endif
                                    @if($hasMeta)
                                        <dl class="pds-ard-info__list">
                                            @if($customerName !== '')
                                                <div>
                                                    <dt>{{ __('message.customer_name') }}</dt>
                                                    <dd>{{ $customerName }}</dd>
                                                </div>
                                            @endif
                                            @if($customerPhone !== '')
                                                <div>
                                                    <dt>{{ __('message.phone') }}</dt>
                                                    <dd>{{ $customerPhone }}</dd>
                                                </div>
                                            @endif
                                            @if($customerAddress !== '')
                                                <div>
                                                    <dt>{{ __('message.address') }}</dt>
                                                    <dd>{{ $customerAddress }}</dd>
                                                </div>
                                            @endif
                                        </dl>
                                    @endif
                                </div>
                            @endif

                            <div class="pds-ard-section">
                                <div class="pds-ard-section__label">ငွေကောက်</div>
                                <div class="pds-ard-amounts">
                                    <label class="pds-ard-field">
                                        <span>{{ __('message.item_value') }}</span>
                                        <input type="number"
                                               name="items[{{ $itemId }}][item_value]"
                                               class="js-ard-item-value"
                                               min="0"
                                               step="1"
                                               value="{{ (int) $itemValue }}"
                                               placeholder="0">
                                    </label>
                                    <label class="pds-ard-field">
                                        <span>{{ __('message.deli_amount') }} <i>*</i></span>
                                        <input type="number"
                                               name="items[{{ $itemId }}][deli_amount]"
                                               class="js-ard-deli-amount"
                                               min="1"
                                               step="1"
                                               required
                                               value="{{ $deliAmount > 0 ? (int) $deliAmount : '' }}"
                                               placeholder="0">
                                    </label>
                                </div>
                                <div class="pds-ard-pay" role="radiogroup" aria-label="{{ __('message.credit_holder') }}">
                                    @foreach([
                                        'os_pay' => 'Os Pay',
                                        'customer_pay' => 'Cust Pay',
                                        'pay_done' => 'Os Paid',
                                    ] as $mode => $label)
                                        <label class="pds-ard-pay__opt @if($payMode === $mode) is-selected @endif">
                                            <input type="radio"
                                                   name="items[{{ $itemId }}][pay_mode]"
                                                   value="{{ $mode }}"
                                                   @checked($payMode === $mode)>
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="pds-ard-section">
                                <div class="pds-ard-section__label">
                                    {{ __('message.size') }} <i>*</i>
                                </div>
                                <input type="hidden"
                                       name="items[{{ $itemId }}][weight]"
                                       class="js-ard-weight"
                                       value="{{ $weight > 0 ? $weight : '' }}"
                                       required>
                                <div class="pds-ard-sizes js-ard-size-grid" role="radiogroup">
                                    @for($size = 1; $size <= 10; $size++)
                                        <button type="button"
                                                class="pds-ard-size js-ard-size-pill @if($weight === $size) is-selected @endif"
                                                data-size-value="{{ $size }}"
                                                aria-pressed="{{ $weight === $size ? 'true' : 'false' }}">
                                            {{ $size }}
                                        </button>
                                    @endfor
                                </div>
                                <p class="pds-ard-hint js-ard-size-hint" @if($deliAmount > 0) hidden @endif>
                                    {{ __('message.admin_rider_done_deli_first') }}
                                </p>
                            </div>

                            @if($needsPhoto)
                                <div class="pds-ard-section">
                                    <div class="pds-ard-section__label">
                                        {{ __('message.admin_rider_done_photo') }} <i>*</i>
                                    </div>
                                    <label class="pds-ard-upload">
                                        <input type="file"
                                               name="items[{{ $itemId }}][photo]"
                                               class="js-ard-photo"
                                               accept="image/*"
                                               required>
                                        <span class="pds-ard-upload__preview" hidden>
                                            <img src="" alt="" data-ard-preview-img>
                                        </span>
                                        <span class="pds-ard-upload__icon" aria-hidden="true"><i class="fas fa-camera"></i></span>
                                        <span class="pds-ard-upload__text">
                                            <strong data-ard-file-label>{{ __('message.admin_rider_done_photo_pick') }}</strong>
                                            <small>{{ __('message.admin_rider_done_photo_required') }}</small>
                                        </span>
                                    </label>
                                </div>
                            @endif
                        </section>
                    @endforeach
                @endif
            </div>

            <div class="pds-ard-footer">
                <button type="button" class="pds-ard-btn pds-ard-btn--ghost" data-dismiss="modal">
                    {{ __('message.cancel') }}
                </button>
                <button type="submit" class="pds-ard-btn pds-ard-btn--primary" data-ard-submit>
                    <i class="fas fa-check" aria-hidden="true"></i>
                    {{ __('message.admin_mark_rider_done') }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function ($) {
    var $form = $('#admin_mark_rider_done_form');
    if (!$form.length) return;

    var SIZE_STEP = 500;

    function parseAmount(raw) {
        var n = parseFloat(String(raw || '').replace(/,/g, '').trim());
        return isNaN(n) ? 0 : n;
    }

    function adjustDeli(currentAmount, previousSize, newSize) {
        if (newSize <= 1) return null;
        if (currentAmount <= 0 && previousSize <= 0) return null;
        var effectivePrev = previousSize > 0 ? previousSize : 1;
        var adjusted = currentAmount + (newSize - effectivePrev) * SIZE_STEP;
        return adjusted < 0 ? 0 : adjusted;
    }

    $form.on('click', '.js-ard-size-pill', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $card = $btn.closest('.pds-ard-card');
        var $deli = $card.find('.js-ard-deli-amount');
        var amount = parseAmount($deli.val());
        if (amount <= 0) {
            $card.find('.js-ard-size-hint').prop('hidden', false);
            if (window.SnackBar) {
                SnackBar({ message: @json(__('message.admin_rider_done_deli_first')), status: 'error' });
            } else {
                alert(@json(__('message.admin_rider_done_deli_first')));
            }
            return;
        }

        var newSize = parseInt($btn.data('size-value'), 10) || 0;
        var prevSize = parseInt($card.attr('data-prev-size'), 10) || 0;
        var nextAmount = adjustDeli(amount, prevSize, newSize);
        if (nextAmount !== null) {
            $deli.val(Math.round(nextAmount));
        }

        $card.find('.js-ard-size-pill').removeClass('is-selected').attr('aria-pressed', 'false');
        $btn.addClass('is-selected').attr('aria-pressed', 'true');
        $card.find('.js-ard-weight').val(newSize);
        $card.attr('data-prev-size', newSize);

        var $tag = $card.find('[data-size-tag]');
        $tag.text('Size(' + newSize + ')').removeClass('is-empty').prop('hidden', false);
        $card.find('.js-ard-size-hint').prop('hidden', true);
    });

    $form.on('input', '.js-ard-deli-amount', function () {
        var $card = $(this).closest('.pds-ard-card');
        var amount = parseAmount($(this).val());
        $card.find('.js-ard-size-hint').prop('hidden', amount > 0);
    });

    $form.on('change', 'input[type=radio][name*="[pay_mode]"]', function () {
        var $opt = $(this).closest('.pds-ard-pay__opt');
        $opt.closest('.pds-ard-pay').find('.pds-ard-pay__opt').removeClass('is-selected');
        $opt.addClass('is-selected');
    });

    $form.on('change', '.js-ard-photo', function () {
        var input = this;
        var file = input.files && input.files[0];
        var $upload = $(input).closest('.pds-ard-upload');
        var $label = $upload.find('[data-ard-file-label]');
        var $preview = $upload.find('.pds-ard-upload__preview');
        var $img = $preview.find('[data-ard-preview-img]');
        var prevUrl = $upload.data('preview-url');

        if (prevUrl) {
            URL.revokeObjectURL(prevUrl);
            $upload.removeData('preview-url');
        }

        if (file && file.type && file.type.indexOf('image/') === 0) {
            var url = URL.createObjectURL(file);
            $upload.data('preview-url', url);
            $img.attr('src', url).attr('alt', file.name);
            $preview.prop('hidden', false);
            $upload.addClass('has-file');
            $label.text(file.name);
        } else {
            $img.attr('src', '').attr('alt', '');
            $preview.prop('hidden', true);
            $upload.removeClass('has-file');
            $label.text(@json(__('message.admin_rider_done_photo_pick')));
        }
    });

    $form.off('submit.ard').on('submit.ard', function (e) {
        e.preventDefault();
        if ($form.data('busy')) return;

        var invalid = false;
        $form.find('.pds-ard-card').each(function (idx) {
            var $card = $(this);
            var deli = parseAmount($card.find('.js-ard-deli-amount').val());
            var weight = parseInt($card.find('.js-ard-weight').val(), 10) || 0;
            if (deli <= 0) {
                invalid = true;
                if (window.SnackBar) SnackBar({ message: @json(__('message.admin_rider_done_deli_first')), status: 'error' });
                else alert(@json(__('message.admin_rider_done_deli_first')));
                return false;
            }
            if (weight <= 0) {
                invalid = true;
                var msg = @json(__('message.admin_rider_done_item_size_required')).replace(':item', String(idx + 1));
                if (window.SnackBar) SnackBar({ message: msg, status: 'error' });
                else alert(msg);
                return false;
            }
            if ($card.data('needs-photo') == 1) {
                var fileInput = $card.find('.js-ard-photo')[0];
                if (!fileInput || !fileInput.files || !fileInput.files.length) {
                    invalid = true;
                    if (window.SnackBar) SnackBar({ message: @json(__('message.admin_rider_done_photo_required')), status: 'error' });
                    else alert(@json(__('message.admin_rider_done_photo_required')));
                    return false;
                }
            }
        });
        if (invalid) return;

        var $btn = $form.find('[data-ard-submit]');
        $form.data('busy', true);
        $btn.prop('disabled', true);

        var formData = new FormData(this);
        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                $('#remoteModelData').modal('hide');
                if (window.SnackBar) {
                    SnackBar({ message: (res && res.message) ? res.message : @json(__('message.admin_rider_done_success')), status: 'success' });
                } else if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: @json(__('message.success')),
                        text: (res && res.message) ? res.message : @json(__('message.admin_rider_done_success')),
                        confirmButtonColor: '#FE6F07'
                    });
                }
                var dt = window.LaravelDataTables && window.LaravelDataTables['dataTableBuilder'];
                if (dt) dt.ajax.reload(null, false);
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : @json(__('message.something_went_wrong'));
                if (window.SnackBar) SnackBar({ message: msg, status: 'error' });
                else if (window.Swal) {
                    Swal.fire({ icon: 'error', title: @json(__('message.error')), text: msg, confirmButtonColor: '#FE6F07' });
                } else {
                    alert(msg);
                }
            },
            complete: function () {
                $form.data('busy', false);
                $btn.prop('disabled', false);
            }
        });
    });
})(jQuery);
</script>
