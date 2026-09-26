@php
    $pickup = $data->pickup_point ?? [];
    $delivery = $data->delivery_point ?? [];
    $hasPhotos = isset($photoOrderImages) && $photoOrderImages->isNotEmpty();
@endphp

<div class="col-12 mb-3 pds-order-block">
    <div class="card pds-page-card pds-photo-order-card">
        <div class="card-body">
            <div class="pds-section-head mb-3">
                <div>
                    <p class="pds-order-eyebrow mb-1">{{ __('message.photo_order') }}</p>
                    <h4 class="pds-section-title mb-0">{{ __('message.photo_order_fill_details') }}</h4>
                </div>
                <span class="pds-badge pds-badge--photo-hint">{{ __('message.photo_order_fill_hint') }}</span>
            </div>

            <div class="pds-photo-order-split pds-photo-order-split--two-col">
                @if($hasPhotos)
                    <aside class="pds-photo-order-carousel-col">
                        <div class="pds-panel-label">
                            <i class="fas fa-images"></i>
                            <span>{{ __('message.photo_order_images') }}</span>
                        </div>
                        <div class="pds-photo-carousel" id="photoOrderCarousel">
                            <button type="button" class="pds-photo-carousel-nav pds-photo-carousel-nav--prev" aria-label="Previous photo">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <div class="pds-photo-carousel-viewport" id="photoOrderGallery">
                                <div class="pds-photo-carousel-track">
                                    @foreach($photoOrderImages as $index => $file)
                                        <div class="pds-photo-carousel-slide"
                                             data-caption="{{ $index + 1 }}. {{ $file->file_name }}">
                                            <div class="pds-photo-carousel-stage">
                                                <img src="{{ mediaPublicUrl($file) }}"
                                                     alt="{{ $file->file_name }}"
                                                     class="pds-photo-carousel-img"
                                                     loading="eager"
                                                     decoding="async"
                                                     draggable="false">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <button type="button" class="pds-photo-carousel-nav pds-photo-carousel-nav--next" aria-label="Next photo">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        <div class="pds-photo-carousel-zoom-bar">
                            <button type="button" class="pds-photo-carousel-zoom-btn" data-carousel-zoom="out" aria-label="Zoom out" title="Zoom out">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <span class="pds-photo-carousel-zoom-level" data-carousel-zoom-level>100%</span>
                            <button type="button" class="pds-photo-carousel-zoom-btn" data-carousel-zoom="in" aria-label="Zoom in" title="Zoom in">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button type="button" class="pds-photo-carousel-zoom-btn" data-carousel-zoom="rotate-left" aria-label="Rotate left" title="Rotate left">
                                <i class="fas fa-undo"></i>
                            </button>
                            <button type="button" class="pds-photo-carousel-zoom-btn" data-carousel-zoom="rotate-right" aria-label="Rotate right" title="Rotate right">
                                <i class="fas fa-redo"></i>
                            </button>
                            <button type="button" class="pds-photo-carousel-zoom-btn pds-photo-carousel-zoom-btn--reset" data-carousel-zoom="reset" aria-label="Reset zoom" title="Reset">
                                Reset
                            </button>
                        </div>
                        <p class="pds-photo-carousel-zoom-hint">Scroll / pinch ဖြင့် ချုံ/ချဲ့ · Rotate · ဆွဲပြီး ရွှေ့ · နှစ်ချက်နှိပ် zoom</p>
                        <div class="pds-photo-carousel-meta">
                            <span class="pds-photo-carousel-counter">1 / {{ $photoOrderImages->count() }}</span>
                            <small class="pds-photo-carousel-caption">{{ $photoOrderImages->first()->file_name ?? '' }}</small>
                        </div>
                    </aside>
                @else
                    <aside class="pds-photo-order-carousel-col">
                        <div class="pds-photo-gallery-empty">
                            <i class="far fa-image"></i>
                            <p>{{ __('message.no_photo_uploaded') }}</p>
                        </div>
                    </aside>
                @endif

                <div class="pds-photo-order-col pds-photo-order-col--fields">
                    {{ html()->form('PATCH', route('order.photo-order-update', $data->id))->attribute('id', 'photo_order_details_form')->open() }}

                    <div class="pds-photo-order-total-parcel pds-photo-order-total-parcel--readonly">
                        <label class="pds-photo-order-total-parcel-label">
                            {{ __('message.total_parcel') }}
                        </label>
                        <div class="pds-photo-order-total-parcel-value">1</div>
                    </div>

                    <div class="pds-photo-order-tabs-wrap">
                        <div class="pds-dispatch-segment pds-photo-order-segment" role="tablist">
                            <button type="button" class="pds-dispatch-segment-btn is-active" data-photo-tab="user" role="tab" aria-selected="true">
                                <i class="fas fa-user"></i>
                                <span>{{ __('message.user_information') }}</span>
                            </button>
                            <button type="button" class="pds-dispatch-segment-btn" data-photo-tab="pickup" role="tab" aria-selected="false">
                                <i class="fas fa-truck"></i>
                                <span>{{ __('message.photo_pickup_information') }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="pds-photo-order-tab-panel is-active" data-photo-tab-panel="user" role="tabpanel">
                        <section class="pds-form-section pds-form-section--user">
                            <div class="pds-panel-label">
                                <i class="fas fa-user"></i>
                                <span>{{ __('message.user_information') }}</span>
                            </div>
                            <div class="pds-form-fields">
                                <div class="form-group">
                                    {{ html()->label(__('message.user_person_name') . ' *')->class('form-control-label') }}
                                    {{ html()->text('pickup_point[name]', $pickup['name'] ?? '')->class('form-control')->required() }}
                                </div>
                                <div class="form-group">
                                    {{ html()->label(__('message.user_contact_number') . ' *')->class('form-control-label') }}
                                    {{ html()->text('pickup_point[contact_number]', $pickup['contact_number'] ?? '')->class('form-control')->required() }}
                                </div>
                                <div class="form-group">
                                    {{ html()->label(__('message.user_location') . ' *')->class('form-control-label') }}
                                    {{ html()->textarea('pickup_point[address]', $pickup['address'] ?? '')->class('form-control')->rows(2)->required() }}
                                </div>
                                <div class="form-group">
                                    {{ html()->label(__('message.user_description'))->class('form-control-label') }}
                                    {{ html()->textarea('pickup_point[description]', $pickup['description'] ?? '')->class('form-control')->rows(2) }}
                                </div>
                                <div class="form-group mb-0">
                                    {{ html()->label(__('message.photo_order_remark'))->class('form-control-label') }}
                                    {{ html()->textarea('pickup_point[instruction]', $pickup['instruction'] ?? '')->class('form-control')->rows(2) }}
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="pds-photo-order-tab-panel" data-photo-tab-panel="pickup" role="tabpanel" hidden>
                        <section class="pds-form-section pds-form-section--pickup-tab">
                            <div class="pds-panel-label">
                                <i class="fas fa-truck"></i>
                                <span>{{ __('message.photo_pickup_information') }}</span>
                            </div>
                            <div class="pds-form-fields">
                                <div class="form-group">
                                    {{ html()->label(__('message.photo_pickup_person_name') . ' *')->class('form-control-label') }}
                                    {{ html()->text('delivery_point[name]', $delivery['name'] ?? '')->class('form-control')->required() }}
                                </div>
                                <div class="form-group">
                                    {{ html()->label(__('message.photo_pickup_contact_number') . ' *')->class('form-control-label') }}
                                    {{ html()->text('delivery_point[contact_number]', $delivery['contact_number'] ?? '')->class('form-control')->required() }}
                                </div>
                                <div class="form-group">
                                    {{ html()->label(__('message.photo_pickup_location') . ' *')->class('form-control-label') }}
                                    {{ html()->textarea('delivery_point[address]', $delivery['address'] ?? '')->class('form-control')->rows(2)->required() }}
                                </div>
                                <div class="form-group">
                                    {{ html()->label(__('message.photo_pickup_description'))->class('form-control-label') }}
                                    {{ html()->textarea('delivery_point[description]', $delivery['description'] ?? '')->class('form-control')->rows(2) }}
                                </div>
                                <div class="form-group mb-0">
                                    {{ html()->label(__('message.photo_order_remark'))->class('form-control-label') }}
                                    {{ html()->textarea('delivery_point[instruction]', $delivery['instruction'] ?? '')->class('form-control')->rows(2) }}
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="pds-form-actions">
                        <button type="submit" class="btn btn-primary pds-photo-save-btn">
                            <i class="fas fa-save mr-1"></i>
                            {{ __('message.save') }} {{ __('message.order_detail') }}
                        </button>
                    </div>

                    {{ html()->form()->close() }}
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/pds-photo-zoom.js') }}?v=3"></script>
<script>
    (function () {
        var root = document.getElementById('photo_order_details_form');
        if (root) {
            var tabButtons = root.querySelectorAll('[data-photo-tab]');
            var tabPanels = root.querySelectorAll('[data-photo-tab-panel]');

            tabButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var target = button.getAttribute('data-photo-tab');

                    tabButtons.forEach(function (btn) {
                        btn.classList.remove('is-active');
                        btn.setAttribute('aria-selected', 'false');
                    });
                    button.classList.add('is-active');
                    button.setAttribute('aria-selected', 'true');

                    tabPanels.forEach(function (panel) {
                        var isActive = panel.getAttribute('data-photo-tab-panel') === target;
                        panel.classList.toggle('is-active', isActive);
                        panel.hidden = !isActive;
                    });

                    // User / Pickup tab switch — keep rotate/zoom bound and re-apply active photo.
                    if (window.PdsPhotoZoom) {
                        window.PdsPhotoZoom.mountCarousel();
                    }
                });
            });
        }

        function bootCarousel() {
            if (window.PdsPhotoZoom) {
                window.PdsPhotoZoom.mountCarousel();
            }
        }

        if (window.PdsPhotoZoom) {
            bootCarousel();
        } else {
            // Script tag above should have defined it; retry once if order raced.
            window.setTimeout(bootCarousel, 0);
        }
    })();
</script>
