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
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 pl-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

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
                                        {{ html()->select('user_type', ['' => __('message.select_name', ['select' => __('message.role')])] + $roles->toArray(), old('user_type', optional($data ?? null)->user_type ?? ($selectedRole ?? null)))
                                            ->class('select2js role')
                                            ->attribute('data-placeholder', __('message.select_name', ['select' => __('message.role')]))
                                            ->attribute('required', true) }}
                                        @error('user_type')
                                            <span class="help-block error text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ html()->label(__('message.name') . ' <span class="text-danger">*</span>', 'name')->class('form-control-label') }}
                                        {{ html()->text('name', old('name'))
                                            ->placeholder(__('message.name'))
                                            ->class('form-control')
                                            ->attribute('required', true) }}
                                        @error('name')
                                            <span class="help-block error text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    @php $readonly = isset($id) ? 'readonly' : ''; @endphp

                                    <div class="form-group col-md-6">
                                        {{ html()->label(__('message.email') . ' <span class="text-danger">*</span>', 'email')->class('form-control-label') }}
                                        {{ html()->email('email', isset($id) ? optional($data)->email : old('email'))
                                            ->placeholder(__('message.email'))
                                            ->class('form-control')
                                            ->attribute('required', true)
                                            ->attribute($readonly, '') }}
                                        @error('email')
                                            <span class="help-block error text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ html()->label(__('message.username') . ' <span class="text-danger">*</span>', 'username')->class('form-control-label') }}
                                        {{ html()->text('username', isset($id) ? optional($data)->username : old('username'))
                                            ->placeholder(__('message.username'))
                                            ->class('form-control')
                                            ->attribute('required', true)
                                            ->attribute($readonly, '') }}
                                        @error('username')
                                            <span class="help-block error text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ html()->label(__('message.contact_number') . ' <span class="text-danger">*</span>', 'contact_number')->class('form-control-label') }}
                                        {{ html()->text('contact_number', isset($id) ? optional($data)->contact_number : old('contact_number'))
                                            ->placeholder(__('message.contact_number'))
                                            ->class('form-control')
                                            ->attribute('id', 'phone')
                                            ->attribute('required', true)
                                            ->attribute($readonly, '') }}
                                        <span id="valid-msg" class="text-success d-none">✓</span>
                                        <span id="error-msg" class="text-danger d-none"></span>
                                        @error('contact_number')
                                            <span class="help-block error text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group col-md-6">
                                        @if(isset($id))
                                            {{ html()->label(__('message.new_password'), 'password')->class('form-control-label') }}
                                            <div class="input-group">
                                                {{ html()->password('password')
                                                    ->class('form-control')
                                                    ->placeholder(__('message.new_password'))
                                                    ->attribute('id', 'password')
                                                    ->attribute('autocomplete', 'new-password') }}
                                                <div class="input-group-append">
                                                    <span class="input-group-text hide-show-password" style="cursor: pointer;">
                                                        <i class="fas fa-eye-slash"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">{{ __('message.leave_blank_to_keep_current_password') }}</small>
                                        @else
                                            {{ html()->label(__('message.password') . ' <span class="text-danger">*</span>', 'password')->class('form-control-label') }}
                                            <div class="input-group">
                                                {{ html()->password('password')
                                                    ->class('form-control')
                                                    ->placeholder(__('message.password'))
                                                    ->attribute('id', 'password')
                                                    ->attribute('required', true)
                                                    ->attribute('autocomplete', 'new-password') }}
                                                <div class="input-group-append">
                                                    <span class="input-group-text hide-show-password" style="cursor: pointer;">
                                                        <i class="fas fa-eye-slash"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            @error('password')
                                                <span class="help-block error text-danger">{{ $message }}</span>
                                            @enderror
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="mt-2">
                        {{ html()->submit(isset($id) ? __('message.update') : __('message.save'))->class('btn btn-md btn-primary float-right')->attribute('id', 'subadmin_submit') }}
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

                // Always allow Save; phone script may have disabled it earlier.
                $('#subadmin_form [type="submit"]').prop('disabled', false).removeClass('disabled');

                var phoneInput = document.querySelector('#subadmin_form #phone');
                if (phoneInput && !phoneInput.readOnly) {
                    ['input', 'keyup', 'blur', 'countrychange', 'change'].forEach(function (eventName) {
                        phoneInput.addEventListener(eventName, function () {
                            $('#subadmin_form [type="submit"]').prop('disabled', false).removeClass('disabled');
                        });
                    });
                }

                $('#subadmin_form').on('submit', function () {
                    if (phoneInput && window.intlTelInputGlobals) {
                        var iti = window.intlTelInputGlobals.getInstance(phoneInput);
                        if (iti) {
                            var full = iti.getNumber();
                            if (full) {
                                phoneInput.value = full;
                                var hidden = document.querySelector('#subadmin_form input[type="hidden"][name="contact_number"]');
                                if (hidden) {
                                    hidden.value = full;
                                }
                            }
                        }
                    }
                    $('#subadmin_form [type="submit"]').prop('disabled', false).removeClass('disabled');
                });

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
            });
        </script>
    @endsection
</x-master-layout>
