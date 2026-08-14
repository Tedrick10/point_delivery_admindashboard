<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <?php $id = $id ?? null; ?>
        @if(isset($id))
            {{ html()->modelForm($data, 'PATCH', route('advertisement.update', $id))->id('advertisement_form')->attribute('enctype', 'multipart/form-data')->open() }}
        @else
            {{ html()->form('POST', route('advertisement.store'))->id('advertisement_form')->attribute('enctype', 'multipart/form-data')->open() }}
        @endif

        <div class="row">
            <div class="col-lg-12">
                <div class="card pds-page-card">
                    <div class="card-header pds-page-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title pds-page-title mb-0">{{ $pageTitle }}</h4>
                        <a href="{{ route('advertisement.index') }}" class="btn btn-sm btn-primary">{{ __('message.back') }}</a>
                    </div>

                    <div class="card-body pds-page-body">
                        <div class="row">
                            <div class="col-md-3 col-lg-2 mb-4 mb-md-0">
                                @include('partials._form_image_upload', [
                                    'fieldName' => 'ad_image',
                                    'previewId' => 'ad_image_preview',
                                    'inputId' => 'ad_image_input',
                                    'title' => __('message.image'),
                                    'imageUrl' => (isset($data) && getMediaFileExit($data, 'ad_image'))
                                        ? getSingleMedia($data, 'ad_image')
                                        : asset('images/default.png'),
                                ])
                            </div>

                            <div class="col-md-9 col-lg-10">
                                <div class="pds-form-section">
                                    <h6 class="pds-form-section__title">{{ __('message.information') }}</h6>
                                    <div class="row">
                                        <div class="form-group col-md-6 col-lg-4">
                                            {!! html()->label(__('message.title').' <span class="text-danger">*</span>')->class('form-control-label') !!}
                                            {!! html()->text('title', old('title', optional($data ?? null)->title))->placeholder(__('message.title'))->class('form-control')->required() !!}
                                        </div>
                                        <div class="form-group col-md-6 col-lg-4">
                                            {!! html()->label(__('message.type'))->class('form-control-label') !!}
                                            {!! html()->select('ad_type', [
                                                'promotion' => __('message.promotion'),
                                                'discount' => __('message.discount'),
                                                'banner' => __('message.banner'),
                                            ], old('ad_type', optional($data ?? null)->ad_type ?? 'promotion'))->class('form-control select2js') !!}
                                        </div>
                                        <div class="form-group col-md-6 col-lg-4">
                                            {!! html()->label(__('message.status'))->class('form-control-label') !!}
                                            {!! html()->select('status', ['1' => __('message.enable'), '0' => __('message.disable')], old('status', optional($data ?? null)->status ?? 1))->class('form-control select2js') !!}
                                        </div>
                                        <div class="form-group col-md-12">
                                            {!! html()->label(__('message.description'))->class('form-control-label') !!}
                                            {!! html()->textarea('description', old('description', optional($data ?? null)->description))->placeholder(__('message.description'))->class('form-control')->rows(3) !!}
                                        </div>
                                    </div>
                                </div>

                                <div class="pds-form-section">
                                    <h6 class="pds-form-section__title">{{ __('message.placement') }} & {{ __('message.target_app') }}</h6>
                                    <div class="row">
                                        <div class="form-group col-md-6 col-lg-4">
                                            {!! html()->label(__('message.placement'))->class('form-control-label') !!}
                                            {!! html()->select('placement', [
                                                'home' => __('message.home_screen'),
                                                'order' => __('message.order_screen'),
                                                'delivery_home' => __('message.delivery_home_screen'),
                                            ], old('placement', optional($data ?? null)->placement ?? 'home'))->class('form-control select2js') !!}
                                        </div>
                                        <div class="form-group col-md-6 col-lg-4">
                                            {!! html()->label(__('message.target_app'))->class('form-control-label') !!}
                                            {!! html()->select('target_app', [
                                                'client' => __('message.user_app'),
                                                'delivery_man' => __('message.delivery_app'),
                                                'both' => __('message.both_apps'),
                                            ], old('target_app', optional($data ?? null)->target_app ?? 'client'))->class('form-control select2js') !!}
                                        </div>
                                        <div class="form-group col-md-6 col-lg-4">
                                            {!! html()->label(__('message.client').' ('.__('message.optional').')')->class('form-control-label') !!}
                                            {!! html()->select('client_id', ['' => __('message.select_name', ['select' => __('message.client')])] + $clients->toArray(), old('client_id', optional($data ?? null)->client_id))->class('form-control select2js') !!}
                                            <small class="pds-form-hint">{{ __('message.client_ad_requires_approval') }}</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="pds-form-section pds-form-section--last">
                                    <h6 class="pds-form-section__title">{{ __('message.schedule') }}</h6>
                                    <div class="row">
                                        <div class="form-group col-md-6 col-lg-4">
                                            {!! html()->label(__('message.start_date'))->class('form-control-label') !!}
                                            {!! html()->date('start_date', old('start_date', isset($data) ? $data->start_date?->format('Y-m-d') : ''))->class('form-control') !!}
                                        </div>
                                        <div class="form-group col-md-6 col-lg-4">
                                            {!! html()->label(__('message.end_date'))->class('form-control-label') !!}
                                            {!! html()->date('end_date', old('end_date', isset($data) ? $data->end_date?->format('Y-m-d') : ''))->class('form-control') !!}
                                        </div>
                                        <div class="form-group col-md-12 col-lg-4">
                                            {!! html()->label(__('message.link_url'))->class('form-control-label') !!}
                                            {!! html()->text('link_url', old('link_url', optional($data ?? null)->link_url))->placeholder('https://')->class('form-control') !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pds-form-footer">
                            {!! html()->submit(isset($id) ? __('message.update') : __('message.save'))->class('btn btn-md btn-primary') !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{ html()->form()->close() }}
    </div>

    @section('bottom_script')
        <script>
            $(document).ready(function() {
                $('.select2js').select2({ width: '100%' });
            });
        </script>
    @endsection
</x-master-layout>
