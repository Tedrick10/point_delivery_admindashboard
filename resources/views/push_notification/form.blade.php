<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <?php $id = $id ?? null; ?>
        @if(isset($id))
            {{ html()->modelForm($data, 'POST', route('pushnotification.store', ['notify_type' => 'resend']))->attribute('enctype', 'multipart/form-data')->id('pushnotificaton_form')->open() }}
        @else
            {{ html()->form('POST', route('pushnotification.store'))->attribute('enctype', 'multipart/form-data')->id('pushnotificaton_form')->open() }}
        @endif

        <div class="row">
            <div class="col-lg-12">
                <div class="card pds-page-card">
                    <div class="card-header pds-page-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title pds-page-title mb-0">{{ $pageTitle }}</h4>
                        <a href="{{ route('pushnotification.index') }}" class="btn btn-sm btn-primary">{{ __('message.back') }}</a>
                    </div>

                    <div class="card-body pds-page-body">
                        <div class="pds-form-section">
                            <h6 class="pds-form-section__title">{{ __('message.recipients') }}</h6>
                            <div class="row">
                                <div class="form-group col-md-6 col-lg-4">
                                    {{ html()->label(__('message.target_type'))->class('form-control-label') }}
                                    {{ html()->select('target_type', [
                                        'selected' => __('message.selected_users'),
                                        'all' => __('message.all_users'),
                                        'vip' => __('message.vip_users'),
                                    ], old('target_type', optional($data ?? null)->target_type ?? 'selected'))
                                        ->class('form-control select2js')
                                        ->id('target_type') }}
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group col-md-6 col-lg-5">
                                    {{ html()->label(__('message.user'))->class('form-control-label') }}
                                    {{ html()->select('client[]', $client, old('client'))
                                        ->id('client_list')
                                        ->class('select2js form-control')
                                        ->multiple()
                                        ->attribute('data-placeholder', __('message.select_name', ['select' => __('message.client')])) }}
                                </div>
                                <div class="form-group col-md-6 col-lg-1 d-flex align-items-end">
                                    <div class="pds-form-check">
                                        <input type="checkbox" class="custom-control-input selectAll" id="all_client" data-usertype="client">
                                        <label class="pds-form-check__label" for="all_client">{{ __('message.select_all') }}</label>
                                    </div>
                                </div>

                                <div class="form-group col-md-6 col-lg-5">
                                    {{ html()->label(__('message.delivery_man'))->class('form-control-label') }}
                                    {{ html()->select('delivery_man[]', $delivery_man, old('delivery_man'))
                                        ->id('delivery_man_list')
                                        ->class('select2js form-control')
                                        ->multiple()
                                        ->attribute('data-placeholder', __('message.select_name', ['select' => __('message.delivery_man')])) }}
                                </div>
                                <div class="form-group col-md-6 col-lg-1 d-flex align-items-end">
                                    <div class="pds-form-check">
                                        <input type="checkbox" class="custom-control-input selectAll" id="all_delivery_man" data-usertype="delivery_man">
                                        <label class="pds-form-check__label" for="all_delivery_man">{{ __('message.select_all') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pds-form-section">
                            <h6 class="pds-form-section__title">{{ __('message.notification_content') }}</h6>
                            <div class="row">
                                <div class="form-group col-md-12 col-lg-8">
                                    {{ html()->label(__('message.title').' <span class="text-danger">*</span>')->class('form-control-label') }}
                                    {{ html()->text('title', old('title'))->placeholder(__('message.title'))->class('form-control')->attribute('required', true) }}
                                </div>
                                <div class="form-group col-md-12">
                                    {{ html()->label(__('message.message').' <span class="text-danger">*</span>')->class('form-control-label') }}
                                    {{ html()->textarea('message', old('message'))->class('form-control')->rows(4)->attribute('required', true)->placeholder(__('message.message')) }}
                                </div>
                            </div>
                        </div>

                        <div class="pds-form-section pds-form-section--last">
                            <h6 class="pds-form-section__title">{{ __('message.image') }}</h6>
                            <div class="row align-items-start">
                                <div class="col-md-3 col-lg-2 mb-3 mb-md-0">
                                    @include('partials._form_image_upload', [
                                        'fieldName' => 'notification_image',
                                        'previewId' => 'notification_image_preview',
                                        'inputId' => 'notification_image_input',
                                        'title' => __('message.notification_image'),
                                        'imageUrl' => asset('images/default.png'),
                                    ])
                                </div>
                                <div class="col-md-9 col-lg-10">
                                    <p class="pds-form-hint mb-0">{{ __('message.notification_image_hint') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="pds-form-footer">
                            {{ html()->submit(__('message.send'))->class('btn btn-md btn-primary') }}
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

            function updateClientCounter() {
                var count = $('#client_list').val() ? $('#client_list').val().length : 0;
                var label = count + ' {{ __('message.selected') }}';
                var $container = $('#client_list').next('span.select2').find('ul');
                if (count > 0) {
                    $container.html('<li class="select2-selection__choice">' + label + '</li>');
                }
            }

            function updateDeliveryManCounter() {
                var count = $('#delivery_man_list').val() ? $('#delivery_man_list').val().length : 0;
                var label = count + ' {{ __('message.selected') }}';
                var $container = $('#delivery_man_list').next('span.select2').find('ul');
                if (count > 0) {
                    $container.html('<li class="select2-selection__choice">' + label + '</li>');
                }
            }

            $('#all_client').on('change', function() {
                $('#client_list').find('option').prop('selected', $(this).is(':checked'));
                $('#client_list').trigger('change');
                updateClientCounter();
            });

            $('#all_delivery_man').on('change', function() {
                $('#delivery_man_list').find('option').prop('selected', $(this).is(':checked'));
                $('#delivery_man_list').trigger('change');
                updateDeliveryManCounter();
            });

            $('#client_list').on('change', updateClientCounter);
            $('#delivery_man_list').on('change', updateDeliveryManCounter);

            updateClientCounter();
            updateDeliveryManCounter();

            formValidation('#pushnotificaton_form', {
                title: { required: true },
                message: { required: true },
            }, {
                title: { required: "{{ __('message.please_enter_name') }}" },
                message: { required: "{{ __('message.please_enter_message') }}" },
            });
        });
    </script>
    @endsection
</x-master-layout>
