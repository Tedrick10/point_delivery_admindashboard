<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter">
    <div>
        <?php $id = $id ?? null;?>
        @if(isset($id))
            {{ html()->modelForm($data, 'PATCH', route('deliveryman.update', $id))->id('deliveryman_form')->attribute('enctype', 'multipart/form-data')->open() }}
        @else
            {{ html()->form('POST', route('deliveryman.store'))->id('deliveryman_form')->attribute('enctype', 'multipart/form-data')->open() }}
        @endif
        <div class="row">
            <div class="col-lg-12">
                <div class="card pds-page-card">
                    <div class="card-header pds-page-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title pds-page-title mb-0">{{ $pageTitle }}</h4>
                        <a href="{{ route('deliveryman.index') }}" class="btn btn-sm btn-primary">{{ __('message.back') }}</a>
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
                            </div>
                            <div class="col-md-9 col-lg-10">
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        {{ html()->label(__('message.name').' <span class="text-danger">*</span>', 'name')->class('form-control-label') }}
                                        {{ html()->text('name', old('name'))
                                            ->placeholder(__('message.name'))
                                            ->class('form-control')
                                            ->required() }}
                                        @error('name')
                                            <span class="help-block error">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ html()->label(__('message.email').' <span class="text-danger">*</span>', 'email')->class('form-control-label') }}
                                        {{ html()->text('email', isset($id) ? optional($data)->email : old('email'))
                                            ->placeholder(__('message.email'))
                                            ->class('form-control')
                                            ->required()
                                            ->when(isset($id), fn($field) => $field->attribute('readonly', 'readonly')) }}
                                        @error('email')
                                            <span class="help-block error">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ html()->label(__('message.username').' <span class="text-danger">*</span>', 'username')->class('form-control-label') }}
                                        {{ html()->text('username', isset($id) ? optional($data)->username : old('username'))
                                            ->placeholder(__('message.username'))
                                            ->class('form-control')
                                            ->required()
                                            ->when(isset($id), fn($field) => $field->attribute('readonly', 'readonly')) }}
                                        @error('username')
                                            <span class="help-block error">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    @if(!isset($id))
                                    <div class="form-group col-md-6">
                                        {{ html()->label(__('message.password').' <span class="text-danger">*</span>', 'password')->class('form-control-label') }}
                                        <div class="input-group">
                                            {{ html()->password('password')
                                                ->class('form-control')
                                                ->placeholder(__('message.password'))
                                                ->id('password') }}
                                            <div class="input-group-append">
                                                <span class="input-group-text hide-show-password" style="cursor: pointer;">
                                                    <i class="fas fa-eye-slash"></i>
                                                </span>
                                            </div>
                                        </div>
                                        @error('password')
                                            <span class="help-block error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    @endif

                                    <div class="form-group col-md-6">
                                        {{ html()->label(__('message.contact_number').' <span class="text-danger">*</span>', 'contact_number')->class('form-control-label') }}
                                        {{ html()->text('contact_number', isset($id) ? optional($data)->contact_number : old('contact_number'))
                                            ->placeholder(__('message.contact_number'))
                                            ->class('form-control')
                                            ->required()
                                            ->attribute('id', 'phone') }}
                                        @error('contact_number')
                                            <span class="help-block error">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ html()->label(__('message.branch').' <span class="text-danger">*</span>', 'branch_id')->class('form-control-label') }}
                                        {{ html()->select(
                                                'branch_id',
                                                ['' => __('message.select_name', ['select' => __('message.branch')])] + ($branches ?? []),
                                                old('branch_id', isset($data) ? optional($data)->branch_id : null)
                                            )
                                            ->class('form-control select2js')
                                            ->required()
                                            ->attribute('data-placeholder', __('message.branch')) }}
                                        @error('branch_id')
                                            <span class="help-block error">{{ $message }}</span>
                                        @enderror
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
    </div>
    @section('bottom_script')
        <script>
            $(document).ready(function() {
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

                formValidation("#deliveryman_form", {
                    name: { required: true },
                    email: { required: true, email: true },
                    username: { required: true },
                    password: { required: {{ isset($id) ? 'false' : 'true' }}, minlength: 6 },
                }, {
                    name: { required: "{{__('message.please_enter_name')}}"},
                    email: { required: "{{__('message.please_enter_email')}}" },
                    username: { required: "{{__('message.please_enter_username')}}" },
                    password: { required: "{{__('message.please_enter_password')}}", minlength: "{{__('message.please_enter_new_password')}}" },
                });

                $('#deliveryman_form [type="submit"]').prop('disabled', false).removeClass('disabled');

                var phoneInput = document.querySelector('#deliveryman_form #phone');
                if (phoneInput && !phoneInput.readOnly) {
                    ['input', 'keyup', 'blur', 'countrychange', 'change'].forEach(function (eventName) {
                        phoneInput.addEventListener(eventName, function () {
                            $('#deliveryman_form [type="submit"]').prop('disabled', false).removeClass('disabled');
                        });
                    });
                }
            });
        </script>
    @endsection
</x-master-layout>
