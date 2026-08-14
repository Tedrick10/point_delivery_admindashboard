<x-master-layout>
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-shop-product-page">
        <?php $id = $id ?? null; ?>
        @if(isset($id))
            {{ html()->modelForm($data, 'PATCH', route('shop-product.update', $id))->id('shop_product_form')->attribute('enctype', 'multipart/form-data')->open() }}
        @else
            {{ html()->form('POST', route('shop-product.store'))->id('shop_product_form')->attribute('enctype', 'multipart/form-data')->open() }}
        @endif
        <div class="row">
            <div class="col-12">
                <div class="card pds-page-card pds-shop-product-card">
                    <div class="card-header pds-page-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title pds-page-title mb-0">{{ $pageTitle }}</h4>
                            <p class="pds-shop-product-card__sub mb-0">Manage product shown in Point Shop mobile app</p>
                        </div>
                        <a href="{{ route('shop-product.index') }}" class="btn btn-sm btn-primary">{{ __('message.back') }}</a>
                    </div>

                    <div class="card-body pds-page-body">
                        <div class="pds-shop-product-layout">
                            <div class="pds-shop-product-cover">
                                <span class="pds-shop-product-cover__badge">Cover</span>
                                @include('partials._form_image_upload', [
                                    'fieldName' => 'product_image',
                                    'previewId' => 'product_image_preview',
                                    'inputId' => 'product_image_input',
                                    'showLabels' => false,
                                    'imageUrl' => (isset($data) && getMediaFileExit($data, 'product_image'))
                                        ? getSingleMedia($data, 'product_image')
                                        : asset('images/default.png'),
                                ])
                                <p class="pds-shop-product-cover__hint">Main image on product cards</p>
                            </div>

                            <div class="pds-shop-product-panel">
                                <div class="pds-shop-product-panel__head">
                                    <span class="pds-shop-product-panel__icon"><i class="fas fa-box"></i></span>
                                    <div>
                                        <h6 class="pds-shop-product-panel__title">{{ __('message.information') }}</h6>
                                        <p class="pds-shop-product-panel__desc mb-0">Basic product details</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-12">
                                        {!! html()->label(__('message.name').' <span class="text-danger">*</span>')->class('form-control-label') !!}
                                        {!! html()->text('name', old('name', optional($data ?? null)->name))->placeholder(__('message.name'))->class('form-control pds-shop-product-input')->required() !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! html()->label(__('message.category'))->class('form-control-label') !!}
                                        {!! html()->select('category_id', ['' => __('message.select_name', ['select' => __('message.category')])] + $categories->toArray(), old('category_id', optional($data ?? null)->category_id))->class('form-control select2js pds-shop-product-input') !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! html()->label(__('message.price').' <span class="text-danger">*</span>')->class('form-control-label') !!}
                                        {!! html()->text('price', old('price', optional($data ?? null)->price))->class('form-control pds-shop-product-input pds-price-input')->attribute('inputmode', 'decimal')->placeholder('0.00')->required() !!}
                                    </div>
                                    <div class="form-group col-md-6 mb-md-0">
                                        {!! html()->label('Home Section')->class('form-control-label') !!}
                                        {!! html()->select('home_section', $homeSections, old('home_section', optional($data ?? null)->home_section ?? 'none'))->class('form-control select2js-home-section pds-shop-product-input')->id('home_section') !!}
                                        <small class="pds-form-hint">Type custom name or select existing section.</small>
                                    </div>
                                    <div class="form-group col-md-6 mb-0">
                                        {!! html()->label(__('message.status'))->class('form-control-label') !!}
                                        {!! html()->select('status', ['1' => __('message.enable'), '0' => __('message.disable')], old('status', optional($data ?? null)->status ?? 1))->class('form-control select2js pds-shop-product-input') !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pds-shop-product-panel pds-shop-product-panel--wide">
                            <div class="pds-shop-product-panel__head">
                                <span class="pds-shop-product-panel__icon"><i class="fas fa-images"></i></span>
                                <div>
                                    <h6 class="pds-shop-product-panel__title">{{ __('message.description') }} & Gallery</h6>
                                    <p class="pds-shop-product-panel__desc mb-0">Detail page content for mobile app</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-12">
                                    {!! html()->label(__('message.description'))->class('form-control-label') !!}
                                    {!! html()->textarea('description', old('description', optional($data ?? null)->description))->class('form-control pds-shop-product-input')->rows(4)->placeholder(__('message.description')) !!}
                                </div>
                                <div class="form-group col-12 mb-0">
                                    <label class="form-control-label">Gallery Images</label>
                                    <label class="pds-shop-gallery-upload">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <span class="pds-shop-gallery-upload__text">Tap to choose multiple images</span>
                                        <span class="pds-shop-gallery-upload__sub">JPG, PNG — product detail carousel</span>
                                        <input type="file" name="product_gallery[]" multiple accept="image/*">
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="pds-shop-product-footer">
                            {!! html()->submit(__('message.save'))->class('btn btn-primary pds-shop-product-save') !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{ html()->form()->close() }}
    </div>

    @section('bottom_script')
        @include('shop.partials._form_scripts', ['enableHomeSectionTags' => true])
        <script>
            $(document).on('change', '.pds-shop-gallery-upload input[type="file"]', function () {
                var count = this.files ? this.files.length : 0;
                var $text = $(this).closest('.pds-shop-gallery-upload').find('.pds-shop-gallery-upload__text');
                $text.text(count > 0 ? count + ' file(s) selected' : 'Tap to choose multiple images');
            });
        </script>
    @endsection
</x-master-layout>
