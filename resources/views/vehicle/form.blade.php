<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <?php $id = $id ?? null; ?>
        @if(isset($id))
            {{ html()->modelForm($data, 'PATCH', route('vehicle.update', $id))->attribute('enctype', 'multipart/form-data')->id('vehicle_form')->open() }}
        @else
            {{ html()->form('POST', route('vehicle.store'))->attribute('enctype', 'multipart/form-data')->id('vehicle_form')->open() }}
        @endif

        <div class="row">
            <div class="col-lg-12">
                <div class="card pds-page-card">
                    <div class="card-header pds-page-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title pds-page-title mb-0">{{ $pageTitle }}</h4>
                        <a href="{{ route('vehicle.index') }}" class="btn btn-sm btn-primary">{{ __('message.back') }}</a>
                    </div>

                    <div class="card-body pds-page-body">
                        <div class="row">
                            <div class="col-md-3 col-lg-2 mb-4 mb-md-0">
                                @include('partials._vehicle_image_upload')
                            </div>

                            <div class="col-md-9 col-lg-10">
                                <div class="pds-form-section">
                                    <h6 class="pds-form-section__title">{{ __('message.information') }}</h6>
                                    <div class="row">
                                        <div class="form-group col-md-6 col-lg-4">
                                            {{ html()->label(__('message.name').' <span class="text-danger">*</span>')->class('form-control-label') }}
                                            {{ html()->text('title', old('title'))->placeholder(__('message.name'))->class('form-control') }}
                                        </div>
                                        <div class="form-group col-md-6 col-lg-4">
                                            {{ html()->label(__('message.vehicle_capacity').' <span class="text-danger">*</span>')->class('form-control-label') }}
                                            {{ html()->text('capacity', old('capacity'))->placeholder(__('message.vehicle_capacity'))->class('form-control') }}
                                        </div>
                                        <div class="form-group col-md-6 col-lg-4">
                                            {{ html()->label(__('message.vehicle_size').' <span class="text-danger">*</span>')->class('form-control-label') }}
                                            {{ html()->text('size', old('size'))->placeholder(__('message.vehicle_size'))->class('form-control') }}
                                        </div>
                                        <div class="form-group col-md-12 col-lg-8">
                                            {{ html()->label(__('message.description').' <span class="text-danger">*</span>')->class('form-control-label') }}
                                            {{ html()->text('description', old('description'))->placeholder(__('message.description'))->class('form-control') }}
                                        </div>
                                    </div>
                                </div>

                                <div class="pds-form-section">
                                    <h6 class="pds-form-section__title">{{ __('message.type') }} & {{ __('message.city') }}</h6>
                                    <div class="row">
                                        <div class="form-group col-md-6 col-lg-4">
                                            {{ html()->label(__('message.type').' <span class="text-danger">*</span>')->class('form-control-label') }}
                                            {{ html()->select('type', ['city_wise' => __('message.city_wise'), 'all' => __('message.all')], old('type'))
                                                ->class('form-control select2js')->required() }}
                                        </div>
                                        <div class="form-group col-md-6 col-lg-8" id="cityField">
                                            {{ html()->label(__('message.city').' <span class="text-danger">*</span>')->class('form-control-label') }}
                                            {{ html()->select('city_ids[]', $selected_cities ?? [], old('city_ids', isset($data) ? ($data->city_ids ?? []) : []))
                                                ->class('select2js city_ids')
                                                ->attribute('data-placeholder', __('message.city'))
                                                ->multiple()
                                                ->attribute('data-ajax--url', route('ajax-list', ['type' => 'city-list'])) }}
                                        </div>
                                        <div class="form-group col-md-6 col-lg-4">
                                            {{ html()->label(__('message.status').' <span class="text-danger">*</span>')->class('form-control-label') }}
                                            {{ html()->select('status', ['1' => __('message.enable'), '0' => __('message.disable')], old('status'))
                                                ->class('form-control select2js') }}
                                        </div>
                                    </div>
                                </div>

                                <div class="pds-form-section pds-form-section--last">
                                    <h6 class="pds-form-section__title">{{ __('message.price') }}</h6>
                                    <div class="row">
                                        <div class="form-group col-md-6 col-lg-4">
                                            {{ html()->label(__('message.base_price').' <span class="text-danger">*</span>')->class('form-control-label') }}
                                            {{ html()->number('price', old('price'))->attribute('step', 'any')->attribute('min', 0)->placeholder(__('message.base_price'))->class('form-control') }}
                                        </div>
                                        <div class="form-group col-md-6 col-lg-4">
                                            {{ html()->label(__('message.min_km'))->class('form-control-label') }}
                                            {{ html()->number('min_km', old('min_km'))->attribute('step', 'any')->attribute('min', 0)->placeholder(__('message.min_km'))->class('form-control') }}
                                        </div>
                                        <div class="form-group col-md-6 col-lg-4">
                                            {{ html()->label(__('message.per_km_charge'))->class('form-control-label') }}
                                            {{ html()->number('per_km_charge', old('per_km_charge'))->attribute('step', 'any')->attribute('min', 0)->placeholder(__('message.per_km_charge'))->class('form-control') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="mt-2">
                        {{ html()->submit(isset($id) ? __('message.update') : __('message.save'))->class('btn btn-md btn-primary float-right') }}
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

                function toggleCityField() {
                    var typeValue = $('select[name="type"]').val();
                    if (typeValue === 'all') {
                        $('#cityField').hide();
                        $('select[name="city_ids[]"]').val(null).trigger('change');
                    } else {
                        $('#cityField').show();
                    }
                }

                toggleCityField();
                $('select[name="type"]').on('change', toggleCityField);

                var isImageUploaded = {{ isset($id) && getMediaFileExit($data, 'vehicle_image') ? 'true' : 'false' }};

                formValidation("#vehicle_form", {
                    title: { required: true },
                    capacity: { required: true },
                    size: { required: true },
                    description: { required: true },
                    type: { required: true },
                    'city_ids[]': { required: function() { return $('select[name="type"]').val() !== 'all'; } },
                    status: { required: true },
                    price: { required: true },
                    vehicle_image: { required: !isImageUploaded },
                }, {
                    title: { required: "{{ __('message.please_enter_title') }}" },
                    capacity: { required: "{{ __('message.please_enter_capacity') }}" },
                    size: { required: "{{ __('message.please_enter_size') }}" },
                    description: { required: "{{ __('message.please_enter_description') }}" },
                    type: { required: "{{ __('message.please_select_type') }}" },
                    'city_ids[]': { required: "{{ __('message.please_select_city') }}" },
                    status: { required: "{{ __('message.please_select_status') }}" },
                    price: { required: "{{ __('message.please_enter_price') }}" },
                    vehicle_image: { required: "{{ __('message.please_image_select') }}" },
                });
            });
        </script>
    @endsection
</x-master-layout>
