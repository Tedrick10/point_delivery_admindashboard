<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <?php $id = $id ?? null; ?>
        @if(isset($id))
            {{ html()->modelForm($data, 'PATCH', route('sub-admin.update', $id))->id('subadmin_form')->attribute('enctype', 'multipart/form-data')->open() }}
        @else
            {{ html()->form('POST', route('sub-admin.store'))->id('subadmin_form')->attribute('enctype', 'multipart/form-data')->open() }}
        @endif

        <div class="row">
            <div class="col-lg-12">
                <div class="card pds-page-card">
                    <div class="card-header pds-page-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title pds-page-title mb-0">{{ $pageTitle }}</h4>
                        <a href="{{ route('sub-admin.index', array_filter(['employee_type' => optional($employeeType ?? null)->id, 'role' => $selectedRole ?? null])) }}" class="btn btn-sm btn-primary">{{ __('message.back') }}</a>
                    </div>

                    <div class="card-body pds-page-body">
                        <div class="row">
                            <div class="col-md-3 col-lg-2 mb-4 mb-md-0">
                                @include('partials._profile_upload', ['profileImage' => $profileImage ?? null])
                                <p class="pds-profile-upload__formats text-center">
                                    {{ __('message.only') }}
                                    @foreach(config('constant.IMAGE_EXTENTIONS') as $extention)
                                        <span>.{{ __('message.'.$extention) }}</span>
                                    @endforeach
                                    {{ __('message.allowed') }}
                                </p>
                            </div>

                            <div class="col-md-9 col-lg-10">
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        {{ html()->label(__('message.role') . ' <span class="text-danger">*</span>', 'user_type')->class('form-control-label') }}
                                        {{ html()->select('user_type', $roles, old('user_type', optional($data ?? null)->user_type ?? ($selectedRole ?? null)))
                                            ->class('select2js role')
                                            ->attribute('data-placeholder', __('message.select_name', ['select' => __('message.role')]))
                                            ->attribute('required', true) }}
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ html()->label(__('message.name') . ' <span class="text-danger">*</span>', 'name')->class('form-control-label') }}
                                        {{ html()->text('name', old('name'))
                                            ->placeholder(__('message.name'))
                                            ->class('form-control')
                                            ->attribute('required', true) }}
                                    </div>

                                    @php $readonly = isset($id) ? 'readonly' : ''; @endphp

                                    <div class="form-group col-md-6">
                                        {{ html()->label(__('message.email') . ' <span class="text-danger">*</span>', 'email')->class('form-control-label') }}
                                        {{ html()->email('email', isset($id) ? optional($data)->email : old('email'))
                                            ->placeholder(__('message.email'))
                                            ->class('form-control')
                                            ->attribute('required', true)
                                            ->attribute($readonly, '') }}
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ html()->label(__('message.username') . ' <span class="text-danger">*</span>', 'username')->class('form-control-label') }}
                                        {{ html()->text('username', isset($id) ? optional($data)->username : old('username'))
                                            ->placeholder(__('message.username'))
                                            ->class('form-control')
                                            ->attribute('required', true)
                                            ->attribute($readonly, '') }}
                                    </div>

                                    @if(!isset($id))
                                    <div class="form-group col-md-6">
                                        {{ html()->label(__('message.password') . ' <span class="text-danger">*</span>', 'password')->class('form-control-label') }}
                                        <div class="input-group">
                                            {{ html()->password('password')
                                                ->class('form-control')
                                                ->placeholder(__('message.password'))
                                                ->attribute('id', 'password') }}
                                            <div class="input-group-append">
                                                <span class="input-group-text hide-show-password" style="cursor: pointer;">
                                                    <i class="fas fa-eye-slash"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    <div class="form-group col-md-6">
                                        {{ html()->label(__('message.contact_number') . ' <span class="text-danger">*</span>', 'contact_number')->class('form-control-label') }}
                                        {{ html()->text('contact_number', isset($id) ? optional($data)->contact_number : old('contact_number'))
                                            ->placeholder(__('message.contact_number'))
                                            ->class('form-control')
                                            ->attribute('id', 'phone')
                                            ->attribute('required', true)
                                            ->attribute($readonly, '') }}
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ html()->label(__('message.country'))->class('form-control-label') }}
                                        {{ html()->select('country_id', isset($data) && $data->country ? [$data->country->id => $data->country->name] : [], old('country_id'))
                                            ->class('select2js country_id')
                                            ->attribute('data-placeholder', __('message.country'))
                                            ->attribute('data-ajax--url', route('ajax-list', ['type' => 'country-list'])) }}
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ html()->label(__('message.city'))->class('form-control-label') }}
                                        {{ html()->select('city_id', isset($data) && $data->city ? [$data->city->id => $data->city->name] : [], old('city_id'))
                                            ->class('select2js city_id')
                                            ->attribute('data-placeholder', __('message.city')) }}
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

                $('.hide-show-password').on('click', function() {
                    var passwordInput = $('#password');
                    var eyeIcon = $('.hide-show-password i');
                    var passwordFieldType = passwordInput.attr('type');
                    if (passwordFieldType === 'password') {
                        passwordInput.attr('type', 'text');
                        eyeIcon.removeClass('fa-eye-slash').addClass('fa-eye');
                    } else {
                        passwordInput.attr('type', 'password');
                        eyeIcon.removeClass('fa-eye').addClass('fa-eye-slash');
                    }
                });

                $(document).on('change', '#country_id', function() {
                    $('#city_id').empty();
                    cityList($(this).val());
                });

                @if(isset($data) && $data->country_id)
                    cityList({{ $data->country_id }});
                @endif
            });

            function cityList(country_id) {
                if (!country_id) return;
                var route = "{{ route('ajax-list', ['type' => 'extra_charge_city', 'country_id' => '']) }}" + country_id;
                $.ajax({
                    url: route.replace('amp;', ''),
                    success: function(result) {
                        $('#city_id').select2({
                            width: '100%',
                            placeholder: "{{ __('message.select_name', ['select' => __('message.city')]) }}",
                            data: result.results
                        });
                        @if(isset($data) && $data->city_id)
                            $('#city_id').val({{ $data->city_id }}).trigger('change');
                        @endif
                    }
                });
            }
        </script>
    @endsection
</x-master-layout>
