<x-master-layout>
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <?php $id = $id ?? null; ?>
        @if(isset($id))
            {{ html()->modelForm($data, 'PATCH', route('shop-banner.update', $id))->attribute('enctype', 'multipart/form-data')->open() }}
        @else
            {{ html()->form('POST', route('shop-banner.store'))->attribute('enctype', 'multipart/form-data')->open() }}
        @endif
        <div class="row">
            <div class="col-lg-12">
                <div class="card pds-page-card">
                    <div class="card-header pds-page-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title pds-page-title mb-0">{{ $pageTitle }}</h4>
                        <a href="{{ route('shop-banner.index') }}" class="btn btn-sm btn-primary">{{ __('message.back') }}</a>
                    </div>
                    <div class="card-body pds-page-body">
                        <div class="row">
                            <div class="col-md-3 col-lg-2 mb-4 mb-md-0">
                                @include('partials._form_image_upload', [
                                    'fieldName' => 'banner_image',
                                    'previewId' => 'banner_image_preview',
                                    'inputId' => 'banner_image_input',
                                    'frameClass' => 'pds-vehicle-upload__frame--banner',
                                    'title' => __('message.image'),
                                    'imageUrl' => (isset($data) && getMediaFileExit($data, 'banner_image'))
                                        ? getSingleMedia($data, 'banner_image')
                                        : asset('images/default.png'),
                                ])
                            </div>
                            <div class="col-md-9 col-lg-10">
                                <div class="pds-form-section pds-form-section--last">
                                    <h6 class="pds-form-section__title">{{ __('message.information') }}</h6>
                                    <div class="row">
                                        <div class="form-group col-md-6 col-lg-4">
                                            {!! html()->label(__('message.title'))->class('form-control-label') !!}
                                            {!! html()->text('title', old('title', optional($data ?? null)->title))->placeholder(__('message.title'))->class('form-control') !!}
                                        </div>
                                        <div class="form-group col-md-6 col-lg-4">
                                            {!! html()->label(__('message.type').' <span class="text-danger">*</span>')->class('form-control-label') !!}
                                            {!! html()->select('banner_type', ['carousel' => 'Carousel (Top)', 'middle' => 'Middle Banner'], old('banner_type', optional($data ?? null)->banner_type ?? 'carousel'))->class('form-control select2js')->required() !!}
                                        </div>
                                        <div class="form-group col-md-6 col-lg-4">
                                            {!! html()->label(__('message.status'))->class('form-control-label') !!}
                                            {!! html()->select('status', ['1' => __('message.enable'), '0' => __('message.disable')], old('status', optional($data ?? null)->status ?? 1))->class('form-control select2js') !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="pds-form-footer">
                            {!! html()->submit(__('message.save'))->class('btn btn-md btn-primary') !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{ html()->form()->close() }}
    </div>

    @section('bottom_script')
        @include('shop.partials._form_scripts')
    @endsection
</x-master-layout>
